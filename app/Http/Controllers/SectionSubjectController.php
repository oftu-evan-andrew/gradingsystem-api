<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SectionSubjectController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\SectionSubject::with(['section', 'subject', 'professor'])->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'section_id' => 'required|uuid|exists:sections,section_id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'professor_id' => 'required|uuid|exists:professors,professor_id',
            'semester' => 'required|integer|between:1,2'
        ]);
        
        $sectionSubject = \App\Models\SectionSubject::create($validated);
        return response()->json($sectionSubject, 201);
    }

    public function show(\App\Models\SectionSubject $sectionSubject)
    {
        return response()->json($sectionSubject->load(['section', 'subject', 'professor']));
    }

    public function update(Request $request, \App\Models\SectionSubject $sectionSubject)
    {
        $validated = $request->validate([
            'professor_id' => 'uuid|exists:professors,professor_id',
            'semester' => 'integer|between:1,2'
        ]);
        
        $sectionSubject->update($validated);
        return response()->json($sectionSubject);
    }

    public function destroy(\App\Models\SectionSubject $sectionSubject)
    {
        $sectionSubject->delete();
        return response()->json(null, 204);
    }
}
