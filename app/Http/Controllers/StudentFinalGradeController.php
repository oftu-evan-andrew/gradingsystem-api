<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentFinalGradeController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\StudentFinalGrade::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,student_id',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'final_grade' => 'nullable|numeric|between:1.00,5.00',
            'status' => 'required|in:draft,submitted,finalized',
            'submitted_at' => 'nullable|date',
            'submitted_by' => 'nullable|uuid|exists:professors,professor_id',
            'last_modified_by' => 'nullable|uuid|exists:professors,professor_id'
        ]);
        
        $record = \App\Models\StudentFinalGrade::create($validated);
        return response()->json($record, 201);
    }

    public function show(\App\Models\StudentFinalGrade $studentFinalGrade)
    {
        return response()->json($studentFinalGrade);
    }

    public function update(Request $request, \App\Models\StudentFinalGrade $studentFinalGrade)
    {
        $validated = $request->validate([
            'final_grade' => 'nullable|numeric|between:1.00,5.00',
            'status' => 'in:draft,submitted,finalized',
            'submitted_at' => 'nullable|date',
            'submitted_by' => 'nullable|uuid|exists:professors,professor_id',
            'last_modified_by' => 'nullable|uuid|exists:professors,professor_id'
        ]);
        
        $studentFinalGrade->update($validated);
        return response()->json($studentFinalGrade);
    }

    public function destroy(\App\Models\StudentFinalGrade $studentFinalGrade)
    {
        $studentFinalGrade->delete();
        return response()->json(null, 204);
    }
}
