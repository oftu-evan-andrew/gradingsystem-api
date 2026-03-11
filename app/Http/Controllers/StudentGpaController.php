<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentGpaController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\StudentGpa::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,student_id',
            'school_year' => 'required|string|max:20',
            'semester' => 'required|integer|between:1,2',
            'semester_gpa' => 'required|numeric|between:1.00,5.00',
            'cumulative_gpa' => 'required|numeric|between:1.00,5.00'
        ]);
        
        $record = \App\Models\StudentGpa::create($validated);
        return response()->json($record, 201);
    }

    public function show(\App\Models\StudentGpa $studentGpa)
    {
        return response()->json($studentGpa);
    }

    public function update(Request $request, \App\Models\StudentGpa $studentGpa)
    {
        $validated = $request->validate([
            'semester_gpa' => 'numeric|between:1.00,5.00',
            'cumulative_gpa' => 'numeric|between:1.00,5.00'
        ]);
        
        $studentGpa->update($validated);
        return response()->json($studentGpa);
    }

    public function destroy(\App\Models\StudentGpa $studentGpa)
    {
        $studentGpa->delete();
        return response()->json(null, 204);
    }
}
