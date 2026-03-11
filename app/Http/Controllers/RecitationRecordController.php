<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RecitationRecordController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\RecitationRecord::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,student_id',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'professor_id' => 'required|uuid|exists:professors,professor_id',
            'grading_period' => 'required|integer|between:1,3',
            'rating' => 'required|numeric|between:0,100',
            'remarks' => 'nullable|string|max:255'
        ]);
        
        $record = \App\Models\RecitationRecord::create($validated);
        return response()->json($record, 201);
    }

    public function show(\App\Models\RecitationRecord $recitationRecord)
    {
        return response()->json($recitationRecord);
    }

    public function update(Request $request, \App\Models\RecitationRecord $recitationRecord)
    {
        $validated = $request->validate([
            'rating' => 'numeric|between:0,100',
            'remarks' => 'nullable|string|max:255'
        ]);
        
        $recitationRecord->update($validated);
        return response()->json($recitationRecord);
    }

    public function destroy(\App\Models\RecitationRecord $recitationRecord)
    {
        $recitationRecord->delete();
        return response()->json(null, 204);
    }
}
