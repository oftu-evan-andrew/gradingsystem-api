<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Subject::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'subject_name' => 'required|string|max:150',
            'subject_code' => 'required|string|max:20|unique:subjects',
            'units' => 'required|integer',
            'is_minor' => 'boolean'
        ]);
        
        $subject = \App\Models\Subject::create($validated);
        return response()->json($subject, 201);
    }

    public function show(\App\Models\Subject $subject)
    {
        return response()->json($subject);
    }

    public function update(Request $request, \App\Models\Subject $subject)
    {
        $validated = $request->validate([
            'subject_name' => 'string|max:150',
            'subject_code' => 'string|max:20|unique:subjects,subject_code,' . $subject->id,
            'units' => 'integer',
            'is_minor' => 'boolean'
        ]);
        
        $subject->update($validated);
        return response()->json($subject);
    }

    public function destroy(\App\Models\Subject $subject)
    {
        $subject->delete();
        return response()->json(null, 204);
    }
}
