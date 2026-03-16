<?php

namespace App\Http\Controllers;

use App\Models\StudentFinalGrade;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreStudentFinalGradeRequest;
use App\Http\Requests\UpdateStudentFinalGradeRequest;
use App\Http\Resources\StudentFinalGradeResource;
use App\Http\Resources\StudentFinalGradeCollection;


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

    public function index()
    {
        $finalGrades = StudentFinalGrade::with(['student.user', 'sectionSubject.subject'])
            ->paginate(15);

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
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
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

    public function show(StudentFinalGrade $studentFinalGrade)
    {
        $this->authorize('view', $studentFinalGrade);
        $studentFinalGrade->load(['student.user', 'sectionSubject.subject']);
        return new StudentFinalGradeResource($studentFinalGrade);
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

            try {
                DB::transaction(function () use ($validated, $records) {
                    foreach ($validated['grades'] as $gradeData) {
                        if ($record = $records->get($gradeData['student_final_grade_id'])) {
                            $this->authorize('finalize', $record);
                            $record->update([
                                'final_grade' => $gradeData['final_grade'] ?? $record->final_grade,
                                'status' => $gradeData['status'] ?? $record->status,
                                'submitted_at' => $gradeData['submitted_at'] ?? $record->submitted_at,
                                'last_modified_by' => $gradeData['last_modified_by'] ?? $record->last_modified_by,
                            ]);
                        }
                    }
                });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to update records: ' . $e->getMessage()], 500);
            }

            $records = StudentFinalGrade::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
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

            $record->update([
                'final_grade' => $validated['final_grade'] ?? $record->final_grade,
                'status' => $validated['status'] ?? $record->status,
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

    public function destroy(int $id): JsonResponse
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

        $studentFinalGrade->status = 'finalized';
        $studentFinalGrade->save();
        $studentFinalGrade->load(['student.user', 'sectionSubject.subject']);

        return response()->json([
            'message' => 'Grade finalized successfully',
            'data' => new StudentFinalGradeResource($studentFinalGrade)
        ]);
    }
}
