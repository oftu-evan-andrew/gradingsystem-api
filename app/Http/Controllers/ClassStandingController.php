<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ClassStandingController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\ClassStanding::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,student_id',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|between:1,3',
            'attendance_score' => 'nullable|numeric|between:0,100',
            'recitation_score' => 'nullable|numeric|between:0,100',
            'quiz_score' => 'nullable|numeric|between:0,100',
            'project_score' => 'nullable|numeric|between:0,100',
            'major_exam_score' => 'nullable|numeric|between:0,100'
        ]);
        
        $record = \App\Models\ClassStanding::create($validated);
        return response()->json($record, 201);
    }

    public function show(\App\Models\ClassStanding $classStanding)
    {
        return response()->json($classStanding);
    }

    public function update(Request $request, \App\Models\ClassStanding $classStanding)
    {
        $validated = $request->validate([
            'attendance_score' => 'nullable|numeric|between:0,100',
            'recitation_score' => 'nullable|numeric|between:0,100',
            'quiz_score' => 'nullable|numeric|between:0,100',
            'project_score' => 'nullable|numeric|between:0,100',
            'major_exam_score' => 'nullable|numeric|between:0,100'
        ]);
        
        $classStanding->update($validated);
        return response()->json($classStanding);
    }

    public function destroy(\App\Models\ClassStanding $classStanding)
    {
        $classStanding->delete();
        return response()->json(null, 204);
    }
}
