<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SectionController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Section::with('course')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_name' => 'required|string|max:50',
            'year_level' => 'required|integer|between:1,4',
            'course_id' => 'required|exists:courses,id',
            'school_year' => 'required|string|max:20'
        ]);
        
        $section = \App\Models\Section::create($validated);
        return response()->json($section, 201);
    }

    public function show(\App\Models\Section $section)
    {
        return response()->json($section->load('course'));
    }

    public function update(Request $request, \App\Models\Section $section)
    {
        $validated = $request->validate([
            'section_name' => 'string|max:50',
            'year_level' => 'integer|between:1,4',
            'course_id' => 'exists:courses,id',
            'school_year' => 'string|max:20'
        ]);
        
        $section->update($validated);
        return response()->json($section);
    }

    public function destroy(\App\Models\Section $section)
    {
        $section->delete();
        return response()->json(null, 204);
    }
}
