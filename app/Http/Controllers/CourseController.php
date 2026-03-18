<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public function index(Request $request)
    {
       $this->authorize('finalize', Course::class);

       $query = Course::query();

       if ($request->has('search')) {
            $search = $request->search;
            $query->where('course_name', 'like', "%{$search}%");
        }

       if ($request->has('sort_by')) {
            $sort = $request->sort_by === 'desc' ? 'desc' : 'asc';
            $query->orderBy('created_at', $sort);
        }

        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->authorize('finalize', Course::class);
        // Add basic validation and creation
        $validated = $request->validate([
            'course_name' => 'required|string|max:100'
        ]);
        
        $course = Course::create($validated);
        return response()->json($course, 201);
    }

    public function show(Course $course)
    {
        $this->authorize('finalize', $course);
        return response()->json($course->load('sections'));
    }

    public function update(Request $request, Course $course)
    {
        $this->authorize('finalize', $course);
        $validated = $request->validate([
            'course_name' => 'required|string|max:100'
        ]);
        
        $course->update($validated);
        return response()->json($course);
    }

    public function destroy(Course $course)
    {
        $this->authorize('finalize', $course);
        $course->delete();
        return response()->json(null, 204);
    }
}
