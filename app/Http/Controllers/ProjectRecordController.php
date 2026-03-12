<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRecordRequest;
use App\Http\Requests\UpdateProjectRecordRequest;
use App\Http\Resources\ProjectRecordResource;
use App\Http\Resources\ProjectRecordCollection;
use App\Models\ProjectRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectRecordController extends Controller
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

    public function index(): ProjectRecordCollection
    {
        $professorId = $this->getProfessorId();
        
        $projectRecords = ProjectRecord::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
            ->paginate(15);
        
        return new ProjectRecord($projectRecords);
    }

    public function store(StoreProjectRecordRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== 'professor' && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $professorId = $user->role === 'admin' 
            ? $request->input('professor_id') 
            : $user->professor->professor_id;
        
        $validated = $request->validated();
        $grades = $validated['grades'];
        unset($validated['grades']);
        
        $records = DB::transaction(function () use ($validated, $grades, $professorId) {
            $createdRecords = [];
            
            foreach ($grades as $grade) {
                $record = ProjectRecord::create([
                    'student_id' => $grade['student_id'],
                    'section_subject_id' => $validated['section_subject_id'],
                    'professor_id' => $professorId,
                    'grading_period' => $validated['grading_period'],
                    'project_number' => $validated['project_number'],
                    'project_title' => $validated['project_title'] ?? null,
                    'rating' => $grade['rating'],
                ]);
                $createdRecords[] = $record;
            }
            
            return $createdRecords;
        });
        
        $records = ProjectRecord::with(['student.user', 'sectionSubject.subject'])
            ->whereIn('id', array_map(fn($r) => $r->id, $records))
            ->get();
        
        return response()->json([
            'message' => 'Project records created successfully',
            'data' => ProjectRecord::collection($records),
        ], 201);
    }

    public function show(int $id): JsonResponse
    {
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

    public function update(UpdateProjectRecordRequest $request): JsonResponse
    {
        $professorId = $this->getProfessorId();
        $validated = $request->validated();
        
        $recordIds = array_column($validated['grades'], 'project_record_id');
        $records = ProjectRecord::whereIn('id', $recordIds)
            ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
            ->get()
            ->keyBy('id');
        
        DB::transaction(function () use ($validated, $records) { 
            foreach ($validated['grades'] as $gradeData) { 
                if ($record = $records->get($gradeData['project_record_id'])) {
                    $record->update([
                        'rating' => $gradeData['rating'],
                        'project_title' => $validated['project_title'] ?? $record->project_title
                    ]);
                }
            }
        });
        
        return response()->json(['message' => 'Records updated successfully']);
    }

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
