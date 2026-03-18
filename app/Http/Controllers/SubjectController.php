<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use Illuminate\Http\Request;

class SubjectController extends Controller
{
    public function index(Request $request)
    {
       $this->authorize('finalize', Subject::class);

       $query = Subject::query();

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('subject_name', 'like', "%{$search}%");
        }

        if ($request->has('sort_by')) {
            $sort = $request->sort_by === 'desc' ? 'desc' : 'asc';
            $query->orderBy('created_at', $sort);
        }
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->authorize('finalize', Subject::class);
        $validated = $request->validate([
            'subject_name' => 'required|string|max:150',
            'subject_code' => 'required|string|max:20|unique:subjects',
            'units' => 'required|integer',
            'is_minor' => 'boolean'
        ]);
        
        $subject = Subject::create($validated);
        return response()->json($subject, 201);
    }

    public function show(Subject $subject)
    {
        $this->authorize('finalize', $subject);
        return response()->json($subject->load('sectionSubjects.section', 'sectionSubjects.professor'));
    }

    public function update(Request $request, Subject $subject)
    {
        $this->authorize('finalize', $subject);
        $validated = $request->validate([
            'subject_name' => 'string|max:150',
            'subject_code' => 'string|max:20|unique:subjects,subject_code,' . $subject->id,
            'units' => 'integer',
            'is_minor' => 'boolean'
        ]);
        
        $subject->update($validated);
        return response()->json($subject);
    }

    public function destroy(Subject $subject)
    {
        $this->authorize('finalize', $subject);
        $subject->delete();
        return response()->json(null, 204);
    }
}
