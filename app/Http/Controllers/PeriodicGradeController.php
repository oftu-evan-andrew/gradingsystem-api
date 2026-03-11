<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PeriodicGradeController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\PeriodicGrade::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,student_id',
            'class_standing_id' => 'required|uuid|exists:class_standings,id',
            'grading_period' => 'required|integer|between:1,3',
            'periodic_grade' => 'nullable|numeric|between:1.00,5.00',
            'status' => 'required|in:draft,submitted,finalized',
            'submitted_at' => 'nullable|date',
            'submitted_by' => 'nullable|uuid|exists:professors,professor_id',
            'last_modified_by' => 'nullable|uuid|exists:professors,professor_id'
        ]);
        
        $record = \App\Models\PeriodicGrade::create($validated);
        return response()->json($record, 201);
    }

    public function show(\App\Models\PeriodicGrade $periodicGrade)
    {
        return response()->json($periodicGrade);
    }

    public function update(Request $request, \App\Models\PeriodicGrade $periodicGrade)
    {
        $validated = $request->validate([
            'periodic_grade' => 'nullable|numeric|between:1.00,5.00',
            'status' => 'in:draft,submitted,finalized',
            'submitted_at' => 'nullable|date',
            'submitted_by' => 'nullable|uuid|exists:professors,professor_id',
            'last_modified_by' => 'nullable|uuid|exists:professors,professor_id'
        ]);
        
        $periodicGrade->update($validated);
        return response()->json($periodicGrade);
    }

    public function destroy(\App\Models\PeriodicGrade $periodicGrade)
    {
        $periodicGrade->delete();
        return response()->json(null, 204);
    }
}
