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

class RecitationRecordController extends Controller
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

    public function index(): RecitationRecordCollection
    {
        $professorId = $this->getProfessorId();
        
        $recitationRecords = RecitationRecord::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
            ->paginate(15);
        
        return new RecitationRecordCollection($recitationRecords);
    }

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
                    $record = RecitationRecord::create([
                        'student_id' => $grade['student_id'],
                        'section_subject_id' => $validated['section_subject_id'],
                        'professor_id' => $professorId,
                        'grading_period' => $validated['grading_period'],
                        'rating' => $grade['rating'],
                    ]);
                    $createdRecords[] = $record;
                }
                
                return $createdRecords;
                });
            } catch (\Exception $e) { 
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }
            
            $records = RecitationRecord::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
                ->get();
            
            return response()->json([
                'message' => 'Recitation records created successfully',
                'data' => RecitationRecordResource::collection($records),
            ], 201);
        } else {
            $record = RecitationRecord::create([
                'student_id' => $validated['student_id'],
                'section_subject_id' => $validated['section_subject_id'],
                'professor_id' => $professorId,
                'grading_period' => $validated['grading_period'],
                'rating' => $validated['rating'],
            ]);

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Recitation record created successfully',
                'data' => new RecitationRecordResource($record),
            ], 201);
        }
    }

    public function show(int $id): JsonResponse
    {
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

    public function update(UpdateRecitationRecordRequest $request): JsonResponse
    {
        $professorId = $this->getProfessorId();
        $validated = $request->validated();
        
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
                            'rating' => $gradeData['rating']
                        ]);
                    }
                }
            });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }
            
            $records = RecitationRecord::with(['student.user', 'sectionSubject.subject']) 
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
                ->get();
            
            return response()->json([
                'message' => 'Records updated successfully',
                'data' => RecitationRecordResource::collection($records),
                ], 201);

        } else {
            $record = RecitationRecord::where('id', $validated['id'])
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->first();
            
            if (!$record) {
                return response()->json(['message' => 'Recitation record not found'], 404);
            }
            
            $record->update([
                'rating' => $validated['rating'] ?? $record->rating,
            ]);
            
            $record->load(['student.user', 'sectionSubject.subject']);
            
            return response()->json([
                'message' => 'Recitation record updated successfully',
                'data' => new RecitationRecordResource($record),
            ]);
        }
    }

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
