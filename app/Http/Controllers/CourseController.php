<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Course::all());
    }

    public function store(Request $request)
    {
        // Add basic validation and creation
        $validated = $request->validate([
            'course_name' => 'required|string|max:100'
        ]);
        
        $course = \App\Models\Course::create($validated);
        return response()->json($course, 201);
    }

    public function show(\App\Models\Course $course)
    {
        return response()->json($course);
    }

    public function update(Request $request, \App\Models\Course $course)
    {
        $validated = $request->validate([
            'course_name' => 'required|string|max:100'
        ]);
        
        $course->update($validated);
        return response()->json($course);
    }

    public function destroy(\App\Models\Course $course)
    {
        $course->delete();
        return response()->json(null, 204);
    }
}
