<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRecordRequest;
use App\Http\Requests\UpdateQuizRecordRequest;
use App\Http\Resources\QuizRecordResource;
use App\Http\Resources\QuizRecordCollection;
use App\Models\QuizRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\Middleware;

class QuizRecordController extends Controller implements HasMiddleware
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

    public function index(): QuizRecordCollection
    {
        $professorId = $this->getProfessorId();
        
        $quizRecords = QuizRecord::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
            ->paginate(15);
        
        return new QuizRecordCollection($quizRecords);
    }

    /**
     * Store a new quiz record or multiple records (bulk).
     * Supports two formats:
     * - Single: student_id, quiz_number, rating (direct fields)
     * - Bulk: grades array with multiple student records
     */
    public function store(StoreQuizRecordRequest $request): JsonResponse
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
                    $createdRecords[] = QuizRecord::create([
                        'student_id' => $grade['student_id'],
                        'section_subject_id' => $validated['section_subject_id'],
                        'professor_id' => $professorId,
                        'grading_period' => $validated['grading_period'],
                        'quiz_number' => $validated['quiz_number'],
                        'quiz_title' => $validated['quiz_title'] ?? null,
                        'rating' => $this->calculateRating($grade['pts'] ?? null, $grade['items'] ?? null),
                    ]);
                }
                return $createdRecords;
            });

            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }

            $records = QuizRecord::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
                ->get();

            return response()->json([
                'message' => 'Quiz records created successfully',
                'data' => QuizRecordResource::collection($records),
            ], 201);
        } else {
            // Single record creation
            $record = QuizRecord::create([
                'student_id' => $validated['student_id'],
                'section_subject_id' => $validated['section_subject_id'],
                'professor_id' => $professorId,
                'grading_period' => $validated['grading_period'],
                'quiz_number' => $validated['quiz_number'],
                'quiz_title' => $validated['quiz_title'] ?? null,
                'rating' => $this->calculateRating($validated['pts'] ?? null, $validated['items'] ?? null),
            ]);

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Quiz record created successfully',
                'data' => new QuizRecordResource($record),
            ], 201);
        }
    }

    /**
     * Get a single quiz record by ID.
     * Professors can only see their own records; admins can see all.
     */
    public function show(int $id): JsonResponse
    {
        $professorId = $this->getProfessorId();
        
        $query = QuizRecord::with(['student.user', 'sectionSubject.subject']);
        
        if ($professorId) {
            $query->where('professor_id', $professorId);
        }
        
        $quizRecord = $query->find($id);
        
        if (!$quizRecord) {
            return response()->json(['message' => 'Quiz record not found'], 404);
        }
        
        return (new QuizRecordResource($quizRecord))->response();
    }

    /**
     * Update quiz record(s).
     * Supports two formats:
     * - Single: id field with rating/quiz_title
     * - Bulk: grades array with quiz_record_id and rating for each
     */
    public function update(UpdateQuizRecordRequest $request): JsonResponse
    {
        $professorId = $this->getProfessorId();
        $validated = $request->validated();
        
        // Check if bulk format (grades array) or single record
        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $recordIds = array_column($validated['grades'], 'quiz_record_id');
            $records = QuizRecord::whereIn('id', $recordIds)
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->get()
                ->keyBy('id');
            
            try { 
                DB::transaction(function () use ($validated, $records) { 
                foreach ($validated['grades'] as $gradeData) { 
                    if ($record = $records->get($gradeData['quiz_record_id'])) {
                        $record->update([
                            'rating' => $this->calculateRating($gradeData['pts'] ?? null, $gradeData['items'] ?? null),
                            'quiz_title' => $validated['quiz_title'] ?? $record->quiz_title
                        ]);
                    }
            }
            });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }

            $records = QuizRecord::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
                ->get();
            
            return response()->json([
                'message' => 'Records updated successfully',
                'data' => QuizRecordResource::collection($records),
                ], 201);

        } else {
            // Single record update
            $record = QuizRecord::where('id', $validated['id'])
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->first();
            
            if (!$record) {
                return response()->json(['message' => 'Quiz record not found'], 404);
            }
            
            $record->update([
                'rating' => $this->calculateRating($validated['pts'] ?? null, $validated['items'] ?? null),
                'quiz_title' => $validated['quiz_title'] ?? $record->quiz_title,
            ]);
            
            $record->load(['student.user', 'sectionSubject.subject']);
            
            return response()->json([
                'message' => 'Quiz record updated successfully',
                'data' => new QuizRecordResource($record),
            ]);
        }
    }

    /**
     * Delete a quiz record by ID.
     * Professors can only delete their own records; admins can delete all.
     */
    public function destroy(int $id): JsonResponse
    {
        $professorId = $this->getProfessorId();
        
        $query = QuizRecord::query();
        
        if ($professorId) {
            $query->where('professor_id', $professorId);
        }
        
        $quizRecord = $query->find($id);
        
        if (!$quizRecord) {
            return response()->json(['message' => 'Quiz record not found'], 404);
        }
        
        $quizRecord->delete();
        
        return response()->json(null, 204);
    }

    private function calculateRating(?float $pts, ?float $items): ?float {
        if ($pts === null || $items === null || $items === 0) {
            return null;
        }
        return round(($pts / $items) * 50 + 50, 2);
    }
}
