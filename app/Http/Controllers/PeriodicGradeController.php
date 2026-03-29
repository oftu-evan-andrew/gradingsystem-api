<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePeriodicGradeRequest;
use App\Http\Requests\UpdatePeriodicGradeRequest;
use App\Http\Resources\PeriodicGradeCollection;
use App\Http\Resources\PeriodicGradeResource;
use App\Models\PeriodicGrade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Symfony\Component\HttpFoundation\JsonResponse;

class PeriodicGradeController extends Controller implements HasMiddleware
{
    // Authorization middleware - only professor and admins can access
    public static function middleware() {
        return [
            new Middleware(function ($request, $next) {
                if (!in_array($request->user()->role, ['professor', 'admin'])) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
                return $next($request);
            }),
        ];
    }

    private function getProfessorId(): ?string
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return null;
        }
        
        return $user->professor->professor_id ?? null;
    }

    // Get all periodic grades with pagination
    public function index()
    {
        $professorId = $this->getProfessorId();
        
        $periodicGrades = PeriodicGrade::with(['student.user', 'classStanding'])
            ->when($professorId, fn($q) => $q->whereHas('classStanding.sectionSubject', fn($sq) => 
                $sq->where('professor_id', $professorId)
            ))
            ->paginate(15);
        
        return new PeriodicGradeCollection($periodicGrades);
    }

    // Store a new periodic grade or multiple records (bulk)
    // Supports two formats:
    // - Single: student_id, class_standing_id, grading_period with periodic_grade
    // - Bulk: grades array with multiple student records
    public function store(StorePeriodicGradeRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $grades = $validated['grades'];
            unset($validated['grades']);

            try { 
                $records = DB::transaction(function () use ($validated, $grades) {
                    $createdRecords = [];
                    foreach ($grades as $grade) { 

                        $createdRecords[] = PeriodicGrade::create([
                            'student_id' => $grade['student_id'],
                            'class_standing_id' => $validated['class_standing_id'],
                            'grading_period' => $validated['grading_period'],
                            'periodic_grade' => $grade['periodic_grade'] ?? null,
                            'status' => $grade['status']
                        ]);

                    }
                    return $createdRecords;
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }

            $records = PeriodicGrade::with(['student.user', 'classStanding'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();

            return response()->json([
                'message' => 'Periodic grades created successfully',
                'data' => PeriodicGradeResource::collection($records),
            ], 201);
        } else { 
            $record = PeriodicGrade::create($validated);
            $record->load(['student.user', 'classStanding']);

            return response()->json([
                'message' => 'Periodic grade created successfully',
                'data' => new PeriodicGradeResource($record)
            ], 201);
        }
    }

    // Get a single periodic grade by ID
    public function show($id): JsonResponse
    {
        $periodicGrade = PeriodicGrade::with(['student.user', 'classStanding'])
            ->find($id);
        
        if (!$periodicGrade) {
            return response()->json(['message' => 'Periodic grade not found'], 404);
        }

        $this->authorize('view', $periodicGrade);

        return (new PeriodicGradeResource($periodicGrade))->response();
    }

    // Update periodic grade(s) - single or bulk
    // Supports two formats:
    // - Single: id field with periodic_grade, status
    // - Bulk: grades array with periodic_grade_id and scores for each
    public function update(UpdatePeriodicGradeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $recordIds = array_column($validated['grades'], 'periodic_grade_id');
            $records = PeriodicGrade::whereIn('id', $recordIds)->get()->keyBy('id');


            try {
                DB::transaction(function () use ($validated, $records) {
                    foreach ($validated['grades'] as $gradeData) {
                        if ($record = $records->get($gradeData['periodic_grade_id'])) {
                            $this->authorize('finalize', $record);
                            $record->update([
                                'periodic_grade' => $gradeData['periodic_grade'] ?? $record->periodic_grade, 
                                'status' => $gradeData['status'] ?? $record->status,
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to update records: ' . $e->getMessage()], 500);
            }

            $records = PeriodicGrade::with(['student.user', 'classStanding'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();

            return response()->json([
                'message' => 'Records updated successfully',
                'data' => PeriodicGradeResource::collection($records)
            ], 201);
        } else { 
            $record = PeriodicGrade::find($validated['id']);

            if(!$record) {
                return response()->json(['message' => 'Periodic grade not found'], 404);
            }

            $this->authorize('finalize', $record);

            $record->update([
                'periodic_grade' => $validated['periodic_grade'] ?? $record->periodic_grade, 
                'status' => $validated['status'] ?? $record->status,
                'submitted_at' => $validated['submitted_at'] ?? $record->submitted_at,
                'last_modified_by' => $validated['last_modified_by'] ?? $record->last_modified_by,
            ]);

            $record->load(['student.user', 'classStanding']);

            return response()->json([
                'message' => 'Periodic grade updated successfully',
                'data' => new PeriodicGradeResource($record)
            ]);
        }
    }

    // Delete a periodic grade by ID
    public function destroy($id): JsonResponse
    {
        $periodicGrade = PeriodicGrade::find($id);

        if (!$periodicGrade) {
            return response()->json(['message' => 'Periodic grade not found'], 404);
        }

        $this->authorize('finalize', $periodicGrade);

        $periodicGrade->delete();

        return response()->json(null, 204);
    }

    public function finalize($id): JsonResponse {
        $periodicGrade = PeriodicGrade::find($id);

        if (!$periodicGrade) {
            return response()->json(['mesasge' => 'Not found'], 404);
        }

        $this->authorize('finalize', $periodicGrade);

        $periodicGrade->status = 'finalized';
        $periodicGrade->save();

        return response()->json([
            'message' => 'Grade finalized successfully',
            'data' => new PeriodicGradeResource($periodicGrade)
        ]);
    }
}
