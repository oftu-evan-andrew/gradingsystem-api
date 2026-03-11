<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectRecordController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\ProjectRecord::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,student_id',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'professor_id' => 'required|uuid|exists:professors,professor_id',
            'grading_period' => 'required|integer|between:1,3',
            'project_number' => 'required|integer|min:1',
            'project_title' => 'nullable|string|max:150',
            'rating' => 'required|numeric|between:0,100'
        ]);
        
        $record = \App\Models\ProjectRecord::create($validated);
        return response()->json($record, 201);
    }

    public function show(\App\Models\ProjectRecord $projectRecord)
    {
        return response()->json($projectRecord);
    }

    public function update(Request $request, \App\Models\ProjectRecord $projectRecord)
    {
        $validated = $request->validate([
            'project_title' => 'nullable|string|max:150',
            'rating' => 'numeric|between:0,100'
        ]);
        
        $projectRecord->update($validated);
        return response()->json($projectRecord);
    }

    public function destroy(\App\Models\ProjectRecord $projectRecord)
    {
        $projectRecord->delete();
        return response()->json(null, 204);
    }
}
