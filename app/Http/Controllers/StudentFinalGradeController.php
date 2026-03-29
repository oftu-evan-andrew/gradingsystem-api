<?php

namespace App\Http\Controllers;

use App\Models\StudentFinalGrade;
use App\Models\StudentGpa;
use App\Services\GradeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreStudentFinalGradeRequest;
use App\Http\Requests\UpdateStudentFinalGradeRequest;
use App\Http\Resources\StudentFinalGradeResource;
use App\Http\Resources\StudentFinalGradeCollection;
use App\Models\PeriodicGrade;
use App\Models\Student;


class StudentFinalGradeController extends Controller implements HasMiddleware
{
    // This is still incomplete
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

    // Verifies professor Id, then fetches list of final grades that are
    // has the matching professor Id
    public function index()
    {   
        $professorId = $this->getProfessorId();

        $finalGrades = StudentFinalGrade::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->whereHas('sectionSubject', fn($sq) => 
                $sq->where('professor_id', $professorId)
            ))
            ->paginate(1000);
        
        return new StudentFinalGradeCollection($finalGrades);
    }

    // Store a new final grade or multiple records (bulk)
    // Supports two formats:
    // - Single: student_id, section_subject_id with final_grade
    // - Bulk: grades array with multiple student records
    public function store(StoreStudentFinalGradeRequest $request)
    {
        $validated = $request->validated();

        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $grades = $validated['grades'];
            unset($validated['grades']);

            try {
                $records = DB::transaction(function () use ($validated, $grades) {
                    $createdRecords = [];

                    foreach ($grades as $grade) {
                        $createdRecords[] = StudentFinalGrade::create([
                            'student_id' => $grade['student_id'],
                            'section_subject_id' => $validated['section_subject_id'],
                            'final_grade' => $grade['final_grade'] ?? null,
                            'status' => $grade['status'] ?? 'draft'
                        ]);
                    }
                    return $createdRecords;
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }

            $records = StudentFinalGrade::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();

            return response()->json([
                'message' => 'Final grades created successfully',
                'data' => StudentFinalGradeResource::collection($records),
            ], 201);
        } else {
            $record = StudentFinalGrade::create($validated);
            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Final grade created successfully',
                'data' => new StudentFinalGradeResource($record)
            ], 201);
        }
    }

    public function show($id)
    {
        $studentFinalGradde = StudentFinalGrade::with(['student.user', 'sectionSubject.subject'])
            ->find($id);  
        
        if (!$studentFinalGradde) { 
            return response()->json(['message' => 'Class standing not found'], 404);
        }

        $this->authorize('view', $studentFinalGradde);

        return (new StudentFinalGradeResource($studentFinalGradde))->response();
    }

    // Update final grade(s) - single or bulk
    // Supports two formats:
    // - Single: id field with final_grade, status
    // - Bulk: grades array with student_final_grade_id and fields for each
    public function update(UpdateStudentFinalGradeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $recordIds = array_column($validated['grades'], 'student_final_grade_id');
            $records = StudentFinalGrade::whereIn('id', $recordIds)->get()->keyBy('id');
            $studentsToRecalculate = [];

            try {
                StudentFinalGrade::withoutEvents(function () use ($validated, $records, &$studentsToRecalculate) {
                    foreach ($validated['grades'] as $gradeData) {
                        if ($record = $records->get($gradeData['student_final_grade_id'])) {
                            $this->authorize('finalize', $record);
                            $previousStatus = $record->status;
                            $newStatus = $gradeData['status'] ?? $record->status;

                            $record->update([
                                'final_grade' => $gradeData['final_grade'] ?? $record->final_grade,
                                'status' => $newStatus,
                                'submitted_at' => $gradeData['submitted_at'] ?? $record->submitted_at,
                                'last_modified_by' => $gradeData['last_modified_by'] ?? $record->last_modified_by,
                            ]);

                            if ($previousStatus !== 'finalized' && $newStatus === 'finalized') {
                                $studentsToRecalculate[$record->student_id] = $record->student;
                            }
                        }
                    }
                });

                foreach ($studentsToRecalculate as $student) { 
                    $this->recalculateGpa($student);
                }
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to update records: ' . $e->getMessage()], 500);
            }

            $records = StudentFinalGrade::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();

            return response()->json([
                'message' => 'Records updated successfully',
                'data' => StudentFinalGradeResource::collection($records)
            ], 201);
        } else {
            $record = StudentFinalGrade::find($validated['id']);

            if (!$record) {
                return response()->json(['message' => 'Final grade not found'], 404);
            }

            $this->authorize('finalize', $record);

            $previousStatus = $record->status;
            $newStatus = $validated['status'] ?? $record->status;

            $record->update([
                'final_grade' => $validated['final_grade'] ?? $record->final_grade,
                'status' => $newStatus,
                'submitted_at' => $validated['submitted_at'] ?? $record->submitted_at,
                'last_modified_by' => $validated['last_modified_by'] ?? $record->last_modified_by,
            ]);

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Final grade updated successfully',
                'data' => new StudentFinalGradeResource($record)
            ]);
        }
    }

    public function destroy($id): JsonResponse
    {
        $finalGrade = StudentFinalGrade::find($id);

        if (!$finalGrade) {
            return response()->json(['message' => 'Periodic grade not found'], 404);
        }
        
        // Checks if the user is an admin or a professor, finalize policy checks it. 
        $this->authorize('finalize', $finalGrade);

        $finalGrade->delete();

        return response()->json(null, 204);
    }

    public function finalize(Request $request, int $id): JsonResponse
    {
        $studentFinalGrade = StudentFinalGrade::find($id);

        if (!$studentFinalGrade) {
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorize('finalize', $studentFinalGrade);

        $studentFinalGrade->status = 'submitted';
        $studentFinalGrade->save();
        $studentFinalGrade->load(['student.user', 'sectionSubject.subject']);

        return response()->json([
            'message' => 'Grade finalized successfully',
            'data' => new StudentFinalGradeResource($studentFinalGrade)
        ]);
    }

    /**
     * Approve a final grade (Admin only).
     * Checks if ALL grading periods (prelims, midterm, finals) are finalized
     * before allowing approval. Triggers GPA recalculation.
     */
    public function approve($id): JsonResponse { 
        $finalGrade = StudentFinalGrade::find($id);

        if (!$finalGrade) { 
            return response()->json(['message' => 'Not found'], 404);
        }

        $this->authorize('finalize', $finalGrade);

        // Checks if all periodic grades for this student+subject are finalized. 
        $allPeriodicGrades = PeriodicGrade::where('student_id', $finalGrade->student_id)
            ->whereHas('classStanding', function ($query) use ($finalGrade) {
                $query->where('section_subject_id', $finalGrade->section_subject_id);
            })
            ->get();

        $allFinalized = $allPeriodicGrades->every(fn($pg) => $pg->status === 'finalized');

        if (!$allFinalized) { 
            $pendingPeriods = $allPeriodicGrades 
                ->where('status', '!=', 'finalized')
                ->pluck('grading_period')
                ->toArray();
            
            return response()->json([
                'message' => 'Cannot approve. Not all grading periods are finalized', 
                'pending_periods' => $pendingPeriods
            ], 422);
        }

        $finalGrade->status = 'finalized';
        $finalGrade->save();
        $finalGrade->load(['student.user', 'sectionSubject.subject']);

        return response()->json([
            'message' => 'Grade approved successfully',
            'data' => new StudentFinalGradeResource($finalGrade)
        ]);
    }

    /**
     * Bulk approve final grades for a section_subject.
     * Admin-only endpoint. Approves all students' final grades for the section,
     * and calculates GPA for each student.
     *
     * Prerequisites: All grading periods (Prelims, Midterms, Finals) must be
     * finalized via the finalizeBulk endpoint before calling this.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function approveBulk(Request $request): JsonResponse
    {
        // Validate request input
        $validated = $request->validate([
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
        ]);

        // Only admins can perform bulk approval
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Forbidden - Admin access required'], 403);
        }

        // Get all final grades for this section_subject
        $finalGrades = StudentFinalGrade::where('section_subject_id', $validated['section_subject_id'])
            ->with('student')
            ->get();

        // Check if there are any final grades to approve
        if ($finalGrades->isEmpty()) {
            return response()->json(['message' => 'No final grades found for this section'], 404);
        }

        // Track students with pending grading periods
        $studentsWithPendingPeriods = [];

        // Check if ALL grading periods are finalized for EACH student
        foreach ($finalGrades as $finalGrade) {
            $allPeriodicGrades = PeriodicGrade::where('student_id', $finalGrade->student_id)
                ->whereHas('classStanding', function ($query) use ($finalGrade) {
                    $query->where('section_subject_id', $finalGrade->section_subject_id);
                })
                ->get();

            $allFinalized = $allPeriodicGrades->every(fn($pg) => $pg->status === 'finalized');

            if (!$allFinalized) {
                // Get list of pending periods for this student
                $pendingPeriods = $allPeriodicGrades
                    ->where('status', '!=', 'finalized')
                    ->map(fn($pg) => [
                        'period' => $pg->grading_period,
                        'period_name' => $this->getGradingPeriodName($pg->grading_period),
                    ])
                    ->values();

                $studentsWithPendingPeriods[] = [
                    'student_id' => $finalGrade->student_id,
                    'student_name' => $finalGrade->student->user->name ?? 'Unknown',
                    'pending_periods' => $pendingPeriods,
                ];
            }
        }

        // If any student has pending periods, reject the entire bulk approval
        if (!empty($studentsWithPendingPeriods)) {
            return response()->json([
                'message' => 'Cannot approve. Some students have not finalized all grading periods.',
                'pending_students' => $studentsWithPendingPeriods,
            ], 422);
        }

        // Perform bulk approval in a transaction (all-or-nothing)
        try {
            DB::transaction(function () use ($finalGrades) {
                // Step 1: Approve all final grades (set status to 'finalized')
                StudentFinalGrade::where('section_subject_id', $finalGrades->first()->section_subject_id)
                    ->update(['status' => 'finalized']);

                // Step 2: Recalculate GPA for each student
                foreach ($finalGrades as $finalGrade) {
                    $this->recalculateGpa($finalGrade->student);
                }
            });
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to approve grades: ' . $e->getMessage()], 500);
        }

        return response()->json([
            'message' => "Approved {$finalGrades->count()} final grades, GPA calculated",
            'data' => [
                'final_grades_approved' => $finalGrades->count(),
                'gpa_calculated' => true,
                'section_subject_id' => $validated['section_subject_id'],
            ],
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

    /**
     * Recalculate and update the student's cumulative GPA.
     * Used after bulk grade operations to optimize GPA calculations.
     */
    private function recalculateGpa(Student $student): void { 
        $gpa = app(GradeCalculationService::class)->calculateCumulativeGpa($student);

        $finalGrade = $student->studentFinalGrades() 
            ->where('status', 'finalized')
            ->latest()
            ->first();

        if ($finalGrade && $finalGrade->sectionSubject) {
            StudentGpa::updateOrCreate(
                [
                    'student_id' => $student->id,
                    'school_year' => $finalGrade->sectionSubject->section->school_year ?? null,
                    'semester' => $finalGrade->sectionSubject->semester ?? null,
                ],
                ['cumulative_gpa' => $gpa]
            );
        }
    }
}
