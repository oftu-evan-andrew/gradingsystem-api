<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuizRecordRequest;
use App\Http\Requests\UpdateQuizRecordRequest;
use App\Http\Resources\QuizRecordResource;
use App\Http\Resources\QuizRecordCollection;
use App\Models\QuizRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuizRecordController extends Controller
{
    public function __construct() {
        $this->middleware(function ($request, $next) {
            if (!in_array($request->user()->role, ['professor', 'admin'])) {
                return response()->json(['message' => 'Forbidden'], 403);
            }
            return $next($request);
        });
    }

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

    public function store(StoreQuizRecordRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== 'professor' && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $professorId = $user->role === 'admin' 
            ? $request->input('professor_id') 
            : $user->professor->professor_id;
        
        $validated = $request->validated();
        
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
                        'rating' => $grade['rating'],
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
            $record = QuizRecord::create([
                'student_id' => $validated['student_id'],
                'section_subject_id' => $validated['section_subject_id'],
                'professor_id' => $professorId,
                'grading_period' => $validated['grading_period'],
                'quiz_number' => $validated['quiz_number'],
                'quiz_title' => $validated['quiz_title'] ?? null,
                'rating' => $validated['rating'],
            ]);

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Quiz record created successfully',
                'data' => new QuizRecordResource($record),
            ], 201);
        }
    }

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

    public function update(UpdateQuizRecordRequest $request): JsonResponse
    {
        $professorId = $this->getProfessorId();
        $validated = $request->validated();
        
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
                            'rating' => $gradeData['rating'],
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
            $record = QuizRecord::where('id', $validated['id'])
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->first();
            
            if (!$record) {
                return response()->json(['message' => 'Quiz record not found'], 404);
            }
            
            $record->update([
                'rating' => $validated['rating'] ?? $record->rating,
                'quiz_title' => $validated['quiz_title'] ?? $record->quiz_title,
            ]);
            
            $record->load(['student.user', 'sectionSubject.subject']);
            
            return response()->json([
                'message' => 'Quiz record updated successfully',
                'data' => new QuizRecordResource($record),
            ]);
        }
    }

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
}
