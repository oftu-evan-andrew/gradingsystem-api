<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\StudentGpa;
use App\Models\Student;
use App\Services\GradeCalculationService;
use App\Http\Resources\StudentGpaResource;
use App\Http\Resources\StudentGpaCollection;

class StudentGpaController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', StudentGpa::class);
        $gpas = StudentGpa::with('student.user')->get();
        return new StudentGpaCollection($gpas);
    }

    public function show(StudentGpa $studentGpa)
    {
        $this->authorize('finalize', $studentGpa);
        return new StudentGpaResource($studentGpa->load('student.user'));
    }

    public function destroy(StudentGpa $studentGpa)
    {
        $this->authorize('finalize', $studentGpa);
        $studentGpa->delete();
        return response()->json(null, 204);
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,id',
            'school_year' => 'required|string|max:20',
            'semester' => 'required|integer|between:1,2',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        $this->authorize('create', StudentGpa::class);

        $gradeService = new GradeCalculationService();
        $cumulativeGpa = $gradeService->calculateCumulativeGpa($student);

        if ($cumulativeGpa === null) {
            return response()->json([
                'message' => 'No finalized grades found for this student',
                'student_id' => $student->student_id,
            ], 422);
        }

        $gpaRecord = StudentGpa::updateOrCreate(
            [
                'student_id' => $student->student_id,
                'school_year' => $validated['school_year'],
                'semester' => $validated['semester'],
            ],
            ['cumulative_gpa' => $cumulativeGpa]
        );

        return (new StudentGpaResource($gpaRecord->load('student.user')))
            ->response()
            ->setStatusCode(201);
    }
}
