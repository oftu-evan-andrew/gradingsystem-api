<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class QuizRecordController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\QuizRecord::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,student_id',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'professor_id' => 'required|uuid|exists:professors,professor_id',
            'grading_period' => 'required|integer|between:1,3',
            'quiz_number' => 'required|integer|min:1',
            'quiz_title' => 'nullable|string|max:150',
            'rating' => 'required|numeric|between:0,100'
        ]);
        
        $record = \App\Models\QuizRecord::create($validated);
        return response()->json($record, 201);
    }

    public function show(\App\Models\QuizRecord $quizRecord)
    {
        return response()->json($quizRecord);
    }

    public function update(Request $request, \App\Models\QuizRecord $quizRecord)
    {
        $validated = $request->validate([
            'quiz_title' => 'nullable|string|max:150',
            'rating' => 'numeric|between:0,100'
        ]);
        
        $quizRecord->update($validated);
        return response()->json($quizRecord);
    }

    public function destroy(\App\Models\QuizRecord $quizRecord)
    {
        $quizRecord->delete();
        return response()->json(null, 204);
    }
}
