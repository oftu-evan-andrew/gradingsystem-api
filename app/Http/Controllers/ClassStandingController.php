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
use App\Models\PeriodicGrade;
use App\Models\SectionSubject;
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
    public function index(Request $request)
    {
        $professorId = $this->getProfessorId();

        $classStandings = ClassStanding::with(['student.user', 'sectionSubject.subject'])
            ->when($request->section_subject_id, fn($q, $ssId) => $q->where('section_subject_id', $ssId))
            ->when($request->grading_period, fn($q, $period) => $q->where('grading_period', $period))
            ->when($professorId, fn($q) => $q->whereHas('sectionSubject', fn($sq) => 
                $sq->where('professor_id', $professorId)
            ))
            ->paginate(1000);

        return new ClassStandingCollection($classStandings);
    }

    // Store a new class standing record or multiple records.
    // Supports two formats: 
        // Single: student_id, section_subject_id, grading_period with scores.
        // Bulk: grades array with multiple student records. 
    public function store(StoreClassStandingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['grades']) && is_array($validated['grades']) && !empty($validated['grades'])) {
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
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();

            return response()->json([
                'message' => 'Class standing records created successfully',
                'data' => ClassStandingResource::collection($records),
            ], 201);
        } else { 
            if (!isset($validated['student_id'])) {
                return response()->json(['message' => 'student_id is required'], 422);
            }
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
        
        if (isset($validated['grades']) && is_array($validated['grades']) && !empty($validated['grades'])) {
            // First, ensure all ClassStanding records exist
            $gradesToCreate = [];
            $existingIds = [];
            
            foreach ($validated['grades'] as $gradeData) {
                if (empty($gradeData['class_standing_id'])) {
                    // Need to create a new ClassStanding record
                    $gradesToCreate[] = [
                        'student_id' => $gradeData['student_id'],
                        'section_subject_id' => $validated['section_subject_id'],
                        'grading_period' => $validated['grading_period'],
                    ];
                } else {
                    $existingIds[] = $gradeData['class_standing_id'];
                }
            }
            
            // Create missing ClassStanding records
            $createdRecords = [];
            if (!empty($gradesToCreate)) {
                foreach ($gradesToCreate as $gradeToCreate) {
                    $existingCs = ClassStanding::where('student_id', $gradeToCreate['student_id'])
                        ->where('section_subject_id', $gradeToCreate['section_subject_id'])
                        ->where('grading_period', $gradeToCreate['grading_period'])
                        ->first();
                    
                    if (!$existingCs) {
                        $createdRecords[] = ClassStanding::create($gradeToCreate);
                    } else {
                        $existingIds[] = $existingCs->id;
                    }
                }
            }
            
            // Now fetch all records (existing + newly created)
            $records = ClassStanding::whereIn('id', $existingIds)->get()
                ->keyBy('id');

            try {
                DB::transaction(function() use ($validated, $records) {
                    foreach ($validated['grades'] as $gradeData) {
                        $record = null;
                        
                        if (!empty($gradeData['class_standing_id'])) {
                            // Update existing record
                            $record = $records->get($gradeData['class_standing_id']);
                        } elseif (!empty($gradeData['student_id'])) {
                            // Find the newly created record
                            $record = ClassStanding::where('student_id', $gradeData['student_id'])
                                ->where('section_subject_id', $validated['section_subject_id'])
                                ->where('grading_period', $validated['grading_period'])
                                ->first();
                        }
                        
                        if ($record) {
                            $this->authorize('finalize', $record);
                            
                            // Calculate major_exam_score from pts/items if provided
                            $majorExamScore = $record->major_exam_score;
                            if (isset($gradeData['major_exam_pts']) || isset($gradeData['major_exam_items'])) {
                                $pts = isset($gradeData['major_exam_pts']) ? (float) $gradeData['major_exam_pts'] : null;
                                $items = isset($gradeData['major_exam_items']) ? (float) $gradeData['major_exam_items'] : null;
                                $majorExamScore = $this->calculateRating($pts, $items);
                            } elseif (isset($gradeData['major_exam_score'])) {
                                $majorExamScore = $gradeData['major_exam_score'];
                            }
                            
                            $updateData = [
                                'attendance_score' => $gradeData['attendance_score'] ?? $record->attendance_score,
                                'recitation_score' => $gradeData['recitation_score'] ?? $record->recitation_score,
                                'quiz_score' => $gradeData['quiz_score'] ?? $record->quiz_score,
                                'project_score' => $gradeData['project_score'] ?? $record->project_score,
                                'major_exam_score' => $majorExamScore,
                            ];
                            
                            if (isset($gradeData['status'])) {
                                $updateData['status'] = $gradeData['status'];
                            }
                            
                            $record->update($updateData);
                        }
                    }
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to update records: ' . $e->getMessage()], 500);
            }

            $records = ClassStanding::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, $records->values()->toArray()))
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

            $updateData = [
                'attendance_score' => $validated['attendance_score'] ?? $record->attendance_score,
                'recitation_score' => $validated['recitation_score'] ?? $record->recitation_score, 
                'quiz_score' => $validated['quiz_score'] ?? $record->quiz_score,
                'project_score' => $validated['project_score'] ?? $record->project_score,
                'major_exam_score' => isset($validated['major_exam_pts']) 
                    ? $this->calculateRating($validated['major_exam_pts'], $validated['major_exam_items']) 
                    : $record->major_exam_score, 
            ];
            
            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            }
            
            $record->update($updateData);

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

    /**
     * Bulk finalize ClassStandings and PeriodicGrades for a section+grading_period.
     * Admin-only endpoint. Finalizes all students' grades for the specified period,
     * allowing students to view their grades for that grading period.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function finalizeBulk(Request $request): JsonResponse
    {
        // Validate request inputs
        $validated = $request->validate([
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|min:1|max:3',
        ]);

        // Only admins can perform bulk finalization
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden - Admin access required'], 403);
        }

        // Fetch the section subject to get section and subject info for response
        $sectionSubject = SectionSubject::with(['section', 'subject'])
            ->find($validated['section_subject_id']);

        // Get all class standings for this section+period
        $classStandings = ClassStanding::where('section_subject_id', $validated['section_subject_id'])
            ->where('grading_period', $validated['grading_period'])
            ->get();

        // Check if there are any class standings to finalize
        if ($classStandings->isEmpty()) {
            return response()->json(['message' => 'No class standings found for this section and period'], 404);
        }

        // Check if all students have submitted their grades
        // Only submitted grades can be finalized
        $pendingStudents = $classStandings->filter(fn($cs) => $cs->status !== 'submitted');

        if ($pendingStudents->isNotEmpty()) {
            // Return list of students who haven't submitted yet
            $pendingList = $pendingStudents->map(fn($cs) => [
                'student_id' => $cs->student_id,
                'student_name' => $cs->student->user->name ?? 'Unknown',
                'current_status' => $cs->status,
            ]);

            return response()->json([
                'message' => 'Cannot finalize. Some students have not submitted their grades.',
                'pending_students' => $pendingList->values(),
            ], 422);
        }

        // Perform bulk finalization in a transaction (all-or-nothing)
        try {
            DB::transaction(function () use ($classStandings, $validated) {
                // Step 1: Finalize all class standings for this section+period
                ClassStanding::where('section_subject_id', $validated['section_subject_id'])
                    ->where('grading_period', $validated['grading_period'])
                    ->update(['status' => 'finalized']);

                // Step 2: Finalize all periodic grades for this section+period
                // This links class standings to their periodic grades via student+grading_period
                $periodicGrades = PeriodicGrade::whereIn('class_standing_id', $classStandings->pluck('id'))
                    ->get();

                PeriodicGrade::whereIn('id', $periodicGrades->pluck('id'))
                    ->update(['status' => 'finalized']);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to finalize grades: ' . $e->getMessage()], 500);
        }

        // Get fresh data for response summary
        $classStandingsCount = $classStandings->count();
        $periodicGradesCount = PeriodicGrade::whereIn('class_standing_id', $classStandings->pluck('id'))->count();

        return response()->json([
            'message' => "Finalized {$classStandingsCount} class standings and {$periodicGradesCount} periodic grades for Section {$sectionSubject->section->section_name} - {$sectionSubject->subject->subject_name}, " . $this->getGradingPeriodName($validated['grading_period']),
            'data' => [
                'class_standings_finalized' => $classStandingsCount,
                'periodic_grades_finalized' => $periodicGradesCount,
                'section_subject_id' => $validated['section_subject_id'],
                'grading_period' => $validated['grading_period'],
                'grading_period_name' => $this->getGradingPeriodName($validated['grading_period']),
            ],
        ]);
    }

    /**
     * Bulk reject class standings for a section_subject.
     * Admin-only endpoint. Rejects (sets to draft) all submitted class standings
     * for the section, allowing professors to edit and resubmit.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function rejectBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|min:1|max:3',
        ]);

        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden - Admin access required'], 403);
        }

        $sectionSubject = SectionSubject::with(['section', 'subject'])
            ->find($validated['section_subject_id']);

        $classStandings = ClassStanding::where('section_subject_id', $validated['section_subject_id'])
            ->where('grading_period', $validated['grading_period'])
            ->where('status', 'submitted')
            ->get();

        if ($classStandings->isEmpty()) {
            return response()->json(['message' => 'No submitted class standings found to reject'], 404);
        }

        $rejectedCount = 0;
        foreach ($classStandings as $cs) {
            $cs->status = 'draft';
            $cs->save();
            $rejectedCount++;
        }

        return response()->json([
            'message' => "Rejected {$rejectedCount} class standings for Section {$sectionSubject->section->section_name} - {$sectionSubject->subject->subject_name}, {$this->getGradingPeriodName($validated['grading_period'])}",
            'rejected_count' => $rejectedCount,
            'section_subject_id' => $validated['section_subject_id'],
            'grading_period' => $validated['grading_period'],
            'grading_period_name' => $this->getGradingPeriodName($validated['grading_period']),
        ]);
    }

    /**
     * Professor-only endpoint to unsubmit (revert to draft) their own submitted grades.
     * Only reverts grades that are 'submitted' - cannot revert 'finalized' grades.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function unsubmitBulk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|min:1|max:3',
        ]);

        // Only professors can unsubmit their own grades
        if ($request->user()->role !== 'professor') {
            return response()->json(['message' => 'Forbidden - Professor access required'], 403);
        }

        $professor = $request->user()->professor;
        if (!$professor) {
            return response()->json(['message' => 'Professor profile not found'], 404);
        }

        // Verify professor teaches this section subject
        $sectionSubject = SectionSubject::with(['section', 'subject'])
            ->where('id', $validated['section_subject_id'])
            ->where('professor_id', $professor->professor_id)
            ->first();

        if (!$sectionSubject) {
            return response()->json(['message' => 'You do not teach this subject'], 403);
        }

        // Get only submitted grades (not finalized)
        $classStandings = ClassStanding::where('section_subject_id', $validated['section_subject_id'])
            ->where('grading_period', $validated['grading_period'])
            ->where('status', 'submitted')
            ->get();

        if ($classStandings->isEmpty()) {
            return response()->json(['message' => 'No submitted class standings found to unsubmit'], 404);
        }

        $unsubscribedCount = 0;
        foreach ($classStandings as $cs) {
            $cs->status = 'draft';
            $cs->save();
            $unsubscribedCount++;
        }

        return response()->json([
            'message' => "Unsubmitted {$unsubscribedCount} class standings for Section {$sectionSubject->section->section_name} - {$sectionSubject->subject->subject_name}, {$this->getGradingPeriodName($validated['grading_period'])}",
            'unsubscribed_count' => $unsubscribedCount,
            'section_subject_id' => $validated['section_subject_id'],
            'grading_period' => $validated['grading_period'],
            'grading_period_name' => $this->getGradingPeriodName($validated['grading_period']),
        ]);
    }

    /**
     * Helper method to convert grading period number to name.
     *
     * @param int $period
     * @return string
     */
    private function getGradingPeriodName(int $period): string
    {
        return match ($period) {
            1 => 'Prelims',
            2 => 'Midterms',
            3 => 'Finals',
            default => "Period {$period}",
        };
    }
    
    private function calculateRating(?float $pts, ?float $items): ?float
    {
        if ($pts === null || $items === null || $items === 0) {
            return null;
        }
        return round(($pts / $items) * 50 + 50, 2);
    }
    
}
