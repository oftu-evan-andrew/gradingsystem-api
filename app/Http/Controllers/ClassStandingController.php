<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClassStandingRequest;
use App\Http\Requests\UpdateClassStandingRequest;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\JsonResponse;
use App\Models\ClassStanding;
use App\Http\Resources\ClassStandingCollection;
use App\Http\Resources\ClassStandingResource;

class ClassStandingController extends Controller implements HasMiddleware
{
    // Authorization middleware - only professor and admins can access
    // these endpoints.
    public static function middleware(): array {
        return [
            new Middleware(function ($request, $next) {
                if (!in_array($request->user()->role, ['professor', 'admin'])) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
                return $next($request);
            }),
        ];
    }
    
    // Get the professor id based on the authenticated user. (Unused)
    private function getProfessorId(): ?string
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return null;
        }
        
        return $user->professor->professor_id ?? null;
    }

    

    // Get all class standing records with pagination.
    public function index()
    {
        $professorId = $this->getProfessorId();

        $classStandings = ClassStanding::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->whereHas('sectionSubject', fn($sq) => 
                $sq->where('professor_id', $professorId)
            ))
            ->paginate(15);

        return new ClassStandingCollection($classStandings);
    }

    // Store a new class standing record or multiple records.
    // Supports two formats: 
        // Single: student_id, section_subject_id, grading_period with scores.
        // Bulk: grades array with multiple student records. 
    public function store(StoreClassStandingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $grades = $validated['grades'];
            unset($validated['grades']);

            try { 
            $records = DB::transaction(function () use ($validated, $grades) {
                $createdRecords = [];
                foreach ($grades as $grade) { 
                    $createdRecords[] = ClassStanding::create([
                        'student_id' => $grade['student_id'],
                        'section_subject_id' => $validated['section_subject_id'],
                        'grading_period' => $validated['grading_period'],
                        'attendance_score' => $grade['attendance_score'] ?? null, 
                        'recitation_score' => $grade['recitation_score'] ?? null,
                        'quiz_score' => $grade['quiz_score'] ?? null,
                        'project_score' => $grade['project_score'] ?? null,
                        'major_exam_score' => $this->calculateRating($grade['major_exam_pts'] ?? null, $grade['major_exam_items'] ?? null),
                    ]);
                }
                return $createdRecords;
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }

            $records = ClassStanding::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
                ->get();

            return response()->json([
                'message' => 'Class standing records created successfully',
                'data' => ClassStandingResource::collection($records),
            ], 201);
        } else { 
            $record = ClassStanding::create([
                'student_id' => $validated['student_id'],
                'section_subject_id' => $validated['section_subject_id'],
                'grading_period' => $validated['grading_period'],
                'attendance_score' => $validated['attendance_score'] ?? null,
                'recitation_score' => $validated['recitation_score'] ?? null,
                'quiz_score' => $validated['quiz_score'] ?? null,
                'project_score' => $validated['project_score'] ?? null,
                'major_exam_score' => $this->calculateRating($validated['major_exam_pts'] ?? null, $validated['major_exam_items'] ?? null),
            ]);
            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Class standing record created successfully',
                'data' => new ClassStandingResource($record),
            ], 201);
        }
    }

    // Get a signle class standing record by ID
    // Professors can only see their own records; admins can see all. 
    public function show($id): JsonResponse
    {
        $classStanding = ClassStanding::with(['student.user', 'sectionSubject.subject'])
            ->find($id);   

        if (!$classStanding) { 
            return response()->json(['message' => 'Class standing not found'], 404);
        }

        $this->authorize('view', $classStanding);

        return (new ClassStandingResource($classStanding))->response();
    }

    // Update class standing records
    // Supports two formats:
        // Single: id field with score fields
        // Bulk: grades array with class_standing_id and scores for each
    public function update(UpdateClassStandingRequest $request): JsonResponse
    {
        $validated = $request->validated();
        
        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $recordIds = array_column($validated['grades'], 'class_standing_id');
            $records = ClassStanding::whereIn('id', $recordIds)->get()
                ->keyBy('id');

            try {
                DB::transaction(function() use ($validated, $records) {
                    foreach ($validated['grades'] as $gradeData) {
                        
                        if ($record = $records->get($gradeData['class_standing_id'])) {
                            $this->authorize('finalize', $record);
                            $record->update([
                                'attendance_score' => $gradeData['attendance_score'] ?? $record->attendance_score,
                                'recitation_score' => $gradeData['recitation_score'] ?? $record->recitation_score,
                                'quiz_score' => $gradeData['quiz_score'] ?? $record->quiz_score,
                                'project_score' => $gradeData['project_score'] ?? $record->project_score,
                                'major_exam_score' => isset($gradeData['major_exam_pts']) 
                                    ? $this->calculateRating($gradeData['major_exam_pts'], $gradeData['major_exam_items']) 
                                    : $record->major_exam_score,
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to update records: ' . $e->getMessage()], 500);
            }

            $records = ClassStanding::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
                ->get();

            return response()->json([
                'message' => "Records updated successfully",
                'data' => ClassStandingResource::collection($records)
            ], 201);
        } else {
            $record = ClassStanding::find($validated['id']);

            if (!$record) { 
                return response()->json(['message' => 'Class standing not found'], 404);
            }

            $this->authorize('finalize', $record);

            $record->update([
                'attendance_score' => $validated['attendance_score'] ?? $record->attendance_score,
                'recitation_score' => $validated['recitation_score'] ?? $record->recitation_score, 
                'quiz_score' => $validated['quiz_score'] ?? $record->quiz_score,
                'project_score' => $validated['project_score'] ?? $record->project_score,
                'major_exam_score' => isset($validated['major_exam_pts']) 
                    ? $this->calculateRating($validated['major_exam_pts'], $validated['major_exam_items']) 
                    : $record->major_exam_score, 
            ]);

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Class standing updated successfully',
                'data' => new ClassStandingResource($record)
            ]);
        }
    }
    
    // Delete a class standing record by ID
    // Professors can only delete their own records; admin can delete all.
    public function destroy($id): JsonResponse
    {
        $classStanding = ClassStanding::find($id);

        if (!$classStanding) {
            return response()->json(['message' => 'Class standing not found'], 404);
        }

        $this->authorize('finalize', $classStanding);

        $classStanding->delete();

        return response()->json(null, 204);
    }
    
    public function finalize(Request $request, $id): JsonResponse {
        $classStanding = ClassStanding::find($id);

        if (!$classStanding) {
            return response()->json(['mesasge' => 'Not found'], 404);
        }

        $this->authorize('finalize', $classStanding);

        $classStanding->status = 'finalized';
        $classStanding->save();

        return response()->json([
            'message' => 'Grade finalized successfully',
            'data' => new ClassStandingResource($classStanding)
        ]);
    }
    
    private function calculateRating(?float $pts, ?float $items): ?float
    {
        if ($pts === null || $items === null || $items === 0) {
            return null;
        }
        return round(($pts / $items) * 50 + 50, 2);
    }
    
}
