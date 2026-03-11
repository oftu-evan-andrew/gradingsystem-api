<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class StudentController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Student::with(['user', 'section'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id|unique:students,user_id',
            'section_id' => 'required|uuid|exists:sections,section_id',
            'is_irregular' => 'boolean'
        ]);
        
        $student = \App\Models\Student::create($validated);
        return response()->json($student, 201);
    }

    public function show(\App\Models\Student $student)
    {
        return response()->json($student->load(['user', 'section']));
    }

    public function update(Request $request, \App\Models\Student $student)
    {
        $validated = $request->validate([
            'section_id' => 'uuid|exists:sections,section_id',
            'is_irregular' => 'boolean'
        ]);
        
        $student->update($validated);
        return response()->json($student);
    }

    public function destroy(\App\Models\Student $student)
    {
        $student->delete();
        return response()->json(null, 204);
    }
}
