<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRecordRequest;
use App\Http\Requests\UpdateProjectRecordRequest;
use App\Http\Resources\ProjectRecordResource;
use App\Http\Resources\ProjectRecordCollection;
use App\Models\ProjectRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\Middleware;


class ProjectRecordController extends Controller implements HasMiddleware
{
    /**
     * Authorization middleware - Apply middleware to restrict access.
     * Only professors and admins can access these endpoints.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!in_array($request->user()->role, ['professor', 'admin'])) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
                return $next($request);
            }),
        ];
    }

    /**
     * Get the professor ID based on the authenticated user.
     * Returns null for admins (to allow access to all records).
     * Returns the professor's ID for professors.
     */
    private function getProfessorId(): ?string
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return null;
        }
        
        return $user->professor->professor_id ?? null;
    }

    private function calculateRating(?float $pts, ?float $items): ?float {
        if ($pts === null || $items === null || $items === 0) {
            return null;
        }
        return round(($pts / $items) * 50 + 50, 2);
    }

    /**
     * Get all project records with pagination.
     * Students see only their own finalized records.
     * Professors see only their own records; admins see all.
     */
    public function index(): ProjectRecordCollection
    {
        $user = Auth::user();
        $request = request();

        // Students: view only their finalized project records
        if ($user->role === 'student') {
            $records = ProjectRecord::with(['sectionSubject.subject'])
                ->where('student_id', $user->student->student_id)
                ->when($request->section_subject_id, fn($q) => $q->where('section_subject_id', $request->section_subject_id))
                ->when($request->grading_period, fn($q) => $q->where('grading_period', $request->grading_period))
                ->wherehas('classStanding', fn($cs) => $cs->where('status', 'finalized'))
                ->paginate(15);

            return new ProjectRecordCollection($records);
        }

        // Professors/Admins: existing scoping logic
        $professorId = $this->getProfessorId();
        
        $projectRecords = ProjectRecord::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
            ->when($request->section_subject_id, fn($q) => $q->where('section_subject_id', $request->section_subject_id))
            ->when($request->grading_period, fn($q) => $q->where('grading_period', $request->grading_period))
            ->paginate(15);
        
        return new ProjectRecordCollection($projectRecords);
    }

    /**
     * Store a new project record or multiple records (bulk).
     * Supports two formats:
     * - Single: student_id, project_number, project_title, rating (direct fields)
     * - Bulk: grades array with multiple student records
     */
    public function store(StoreProjectRecordRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== 'professor' && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // For admins, use provided professor_id; for professors, use their own ID
        $professorId = $user->role === 'admin' 
            ? $request->input('professor_id') 
            : $user->professor->professor_id;
        
        $validated = $request->validated();
        
        // Check if bulk format (grades array) or single record
        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $grades = $validated['grades'];
            unset($validated['grades']);
            
            try { 
                $records = DB::transaction(function () use ($validated, $grades, $professorId) {
                $createdRecords = [];
                
                foreach ($grades as $grade) {
                    $record = ProjectRecord::firstOrNew([
                        'student_id' => $grade['student_id'],
                        'section_subject_id' => $validated['section_subject_id'],
                        'grading_period' => $validated['grading_period'],
                        'project_number' => $validated['project_number'] ?? 1,
                    ]);
                    $record->professor_id = $professorId;
                    $record->project_title = $validated['project_title'] ?? null;
                    $record->rating = isset($grade['pts'], $grade['items']) 
                        ? $this->calculateRating($grade['pts'], $grade['items']) 
                        : ($grade['rating'] ?? null);
                    $record->save();
                    $createdRecords[] = $record;
                }
                
                return $createdRecords;
                });
            
            } catch (\Exception $e) { 
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }
            
            $records = ProjectRecord::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();
            
            return response()->json([
                'message' => 'Project records created successfully',
                'data' => ProjectRecordResource::collection($records),
            ], 201);

        } else {
            // Single record creation
            if (!isset($validated['student_id'])) {
                return response()->json(['message' => 'student_id is required'], 422);
            }
            
            $rating = isset($validated['pts'], $validated['items']) 
                ? $this->calculateRating($validated['pts'], $validated['items']) 
                : ($validated['rating'] ?? null);
                
            $record = ProjectRecord::create([
                'student_id' => $validated['student_id'],
                'section_subject_id' => $validated['section_subject_id'],
                'professor_id' => $professorId,
                'grading_period' => $validated['grading_period'],
                'project_number' => $validated['project_number'] ?? 1,
                'project_title' => $validated['project_title'] ?? null,
                'rating' => $rating,
            ]);

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Project record created successfully',
                'data' => new ProjectRecordResource($record),
            ], 201);
        }
    }

    /**
     * Get a single project record by ID.
     * Students can only view their own records when class standing is finalized.
     * Professors can only see their own records; admins can see all.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        // Students: can only view their own finalized records
        if ($user->role === 'student') {
            $record = ProjectRecord::with(['student.user', 'sectionSubject.subject'])->find($id);

            if (!$record) {
                return response()->json(['message' => 'Project record not found'], 404);
            }

            if ($record->student_id !== $user->student->student_id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            if ($record->classStanding->status !== 'finalized') {
                return response()->json(['message' => 'Not yet available'], 403);
            }

            return (new ProjectRecordResource($record))->response();
        }

        $professorId = $this->getProfessorId();
        
        $query = ProjectRecord::with(['student.user', 'sectionSubject.subject']);
        
        if ($professorId) {
            $query->where('professor_id', $professorId);
        }
        
        $projectRecord = $query->find($id);
        
        if (!$projectRecord) {
            return response()->json(['message' => 'Project record not found'], 404);
        }
        
        return (new ProjectRecordResource($projectRecord))->response();
    }

    /**
     * Update project record(s).
     * Supports two formats:
     * - Single: id field with rating/project_title
     * - Bulk: grades array with project_record_id and rating for each
     */
    public function update(UpdateProjectRecordRequest $request, int $id): JsonResponse
    {
        $professorId = $this->getProfessorId();
        $validated = $request->validated();
        
        // Check if bulk format (grades array) or single record
        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $recordIds = array_column($validated['grades'], 'project_record_id');
            $records = ProjectRecord::whereIn('id', $recordIds)
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->get()
                ->keyBy('id');
            
            try { 
                DB::transaction(function () use ($validated, $records) { 
                foreach ($validated['grades'] as $gradeData) { 
                    if ($record = $records->get($gradeData['project_record_id'])) {
                        $record->update([
                            'project_number' => $validated['project_number'] ?? $record->project_number,
                            'project_title' => $validated['project_title'] ?? $record->project_title,
                            'rating' => isset($gradeData['pts'], $gradeData['items']) 
                                ? $this->calculateRating($gradeData['pts'], $gradeData['items']) 
                                : ($gradeData['rating'] ?? $record->rating),
                        ]);
                    }
                }
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records' . $e->getMessage()], 500);
            }

            $records = ProjectRecord::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();

            return response()->json([
                'message' => 'Records updated successfully',
                'data' => ProjectRecordResource::collection($records)
            ]);
        
        } else {
            // Single record update
            $record = ProjectRecord::where('id', $id)
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->first();
            
            if (!$record) {
                return response()->json(['message' => 'Project record not found'], 404);
            }
            
            $rating = isset($validated['pts'], $validated['items']) 
                ? $this->calculateRating($validated['pts'], $validated['items']) 
                : ($validated['rating'] ?? $record->rating);
            
            $record->update([
                'project_number' => $validated['project_number'] ?? $record->project_number,
                'project_title' => $validated['project_title'] ?? $record->project_title,
                'rating' => $rating,
            ]);
            
            $record->load(['student.user', 'sectionSubject.subject']);
            
            return response()->json([
                'message' => 'Project record updated successfully',
                'data' => new ProjectRecordResource($record),
            ]);
        }
    }

    /**
     * Delete a project record by ID.
     * Professors can only delete their own records; admins can delete all.
     */
    public function destroy(int $id): JsonResponse
    {
        $professorId = $this->getProfessorId();
        
        $query = ProjectRecord::query();
        
        if ($professorId) {
            $query->where('professor_id', $professorId);
        }
        
        $projectRecord = $query->find($id);
        
        if (!$projectRecord) {
            return response()->json(['message' => 'Project record not found'], 404);
        }
        
        $projectRecord->delete();
        
        return response()->json(null, 204);
    }
}
