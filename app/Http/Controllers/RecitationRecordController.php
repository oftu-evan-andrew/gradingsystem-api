<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecitationRecordRequest;
use App\Http\Requests\UpdateRecitationRecordRequest;
use App\Http\Resources\RecitationRecordResource;
use App\Http\Resources\RecitationRecordCollection;
use App\Models\RecitationRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;


class RecitationRecordController extends Controller implements HasMiddleware
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

    /**
     * Get all recitation records with pagination.
     * Students see only their own finalized records.
     * Professors see only their own records; admins see all.
     */
    public function index(): RecitationRecordCollection
    {
        $user = Auth::user();
        $request = request();

        // Students: view only their finalized recitation records
        if ($user->role === 'student') {
            $records = RecitationRecord::with(['sectionSubject.subject'])
                ->where('student_id', $user->student->student_id)
                ->when($request->section_subject_id, fn($q) => $q->where('section_subject_id', $request->section_subject_id))
                ->when($request->grading_period, fn($q) => $q->where('grading_period', $request->grading_period))
                ->wherehas('classStanding', fn($cs) => $cs->where('status', 'finalized'))
                ->paginate(15);

            return new RecitationRecordCollection($records);
        }

        // Professors/Admins: existing scoping logic
        $professorId = $this->getProfessorId();
        
        $recitationRecords = RecitationRecord::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
            ->when($request->section_subject_id, fn($q) => $q->where('section_subject_id', $request->section_subject_id))
            ->when($request->grading_period, fn($q) => $q->where('grading_period', $request->grading_period))
            ->paginate(15);
        
        return new RecitationRecordCollection($recitationRecords);
    }

    /**
     * Store a new recitation record or multiple records (bulk).
     * Supports two formats:
     * - Single: student_id, rating (direct fields)
     * - Bulk: grades array with multiple student records
     */
    public function store(StoreRecitationRecordRequest $request): JsonResponse
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
                    $record = RecitationRecord::firstOrNew([
                        'student_id' => $grade['student_id'],
                        'section_subject_id' => $validated['section_subject_id'],
                        'grading_period' => $validated['grading_period'],
                    ]);
                    $record->professor_id = $professorId;
                    $record->rating = $grade['rating'];
                    $record->remarks = $grade['remarks'] ?? $validated['remarks'] ?? null;
                    $record->save();
                    $createdRecords[] = $record;
                }
                
                return $createdRecords;
                });
            } catch (\Exception $e) { 
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }
            
            $records = RecitationRecord::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();
            
            return response()->json([
                'message' => 'Recitation records created successfully',
                'data' => RecitationRecordResource::collection($records),
            ], 201);
        } else {
            if (!isset($validated['student_id'])) {
                return response()->json(['message' => 'student_id is required'], 422);
            }
            $record = RecitationRecord::create([
                'student_id' => $validated['student_id'],
                'section_subject_id' => $validated['section_subject_id'],
                'professor_id' => $professorId,
                'grading_period' => $validated['grading_period'],
                'rating' => $validated['rating'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Recitation record created successfully',
                'data' => new RecitationRecordResource($record),
            ], 201);
        }
    }

    /**
     * Get a single recitation record by ID.
     * Students can only view their own records when class standing is finalized.
     * Professors can only see their own records; admins can see all.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        // Students: can only view their own finalized records
        if ($user->role === 'student') {
            $record = RecitationRecord::with(['student.user', 'sectionSubject.subject'])->find($id);

            if (!$record) {
                return response()->json(['message' => 'Recitation record not found'], 404);
            }

            if ($record->student_id !== $user->student->student_id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            if ($record->classStanding->status !== 'finalized') {
                return response()->json(['message' => 'Not yet available'], 403);
            }

            return (new RecitationRecordResource($record))->response();
        }

        $professorId = $this->getProfessorId();
        
        $query = RecitationRecord::with(['student.user', 'sectionSubject.subject']);
        
        if ($professorId) {
            $query->where('professor_id', $professorId);
        }
        
        $recitationRecord = $query->find($id);
        
        if (!$recitationRecord) {
            return response()->json(['message' => 'Recitation record not found'], 404);
        }
        
        return (new RecitationRecordResource($recitationRecord))->response();
    }

    /**
     * Update recitation record(s).
     * Supports two formats:
     * - Single: id field with rating
     * - Bulk: grades array with recitation_record_id and rating for each
     */
    public function update(UpdateRecitationRecordRequest $request, int $id): JsonResponse
    {
        $professorId = $this->getProfessorId();
        $validated = $request->validated();
        
        // Check if bulk format (grades array) or single record
        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $recordIds = array_column($validated['grades'], 'recitation_record_id');
            $records = RecitationRecord::whereIn('id', $recordIds)
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->get()
                ->keyBy('id');
            
            try {
                DB::transaction(function () use ($validated, $records) { 
                foreach ($validated['grades'] as $gradeData) { 
                    if ($record = $records->get($gradeData['recitation_record_id'])) {
                        $record->update([
                            'rating' => $gradeData['rating'] ?? $record->rating,
                            'remarks' => $validated['remarks'] ?? $record->remarks,
                        ]);
                    }
            }
            });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }
            
            $records = RecitationRecord::with(['student.user', 'sectionSubject.subject']) 
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();
            
            return response()->json([
                'message' => 'Records updated successfully',
                'data' => RecitationRecordResource::collection($records),
                ], 201);

        } else {
            // Single record update
            $record = RecitationRecord::where('id', $id)
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->first();
            
            if (!$record) {
                return response()->json(['message' => 'Recitation record not found'], 404);
            }
            
            $record->update([
                'rating' => $validated['rating'] ?? $record->rating,
                'remarks' => $validated['remarks'] ?? $record->remarks,
            ]);
            
            $record->load(['student.user', 'sectionSubject.subject']);
            
            return response()->json([
                'message' => 'Recitation record updated successfully',
                'data' => new RecitationRecordResource($record),
            ]);
        }
    }

    /**
     * Delete a recitation record by ID.
     * Professors can only delete their own records; admins can delete all.
     */
    public function destroy(int $id): JsonResponse
    {
        $professorId = $this->getProfessorId();
        
        $query = RecitationRecord::query();
        
        if ($professorId) {
            $query->where('professor_id', $professorId);
        }
        
        $recitationRecord = $query->find($id);
        
        if (!$recitationRecord) {
            return response()->json(['message' => 'Recitation record not found'], 404);
        }
        
        $recitationRecord->delete();
        
        return response()->json(null, 204);
    }
}
