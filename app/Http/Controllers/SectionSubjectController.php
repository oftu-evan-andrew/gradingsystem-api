<?php

namespace App\Http\Controllers;

use App\Models\SectionSubject;
use Illuminate\Http\Request;

class SectionSubjectController extends Controller
{
    public function index(Request $request)
    {
       $this->authorize('viewAny', SectionSubject::class);

       $query = SectionSubject::with(['section', 'subject', 'professor.user']);

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('section', function($q) use ($search) {
                $q->where('section_name', 'like', "%{$search}%");
            })
            ->orWhereHas('subject', function($q) use ($search) {
                $q->where('subject_name', 'like', "%{$search}%")
                    ->orWhere('subject_code', 'like', "%{$search}%");
            })
            ->orWhereHas('professor.user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        if ($request->has('section_id')) { 
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('professor_id')) { 
            $query->where('professor_id', $request->professor_id);
        }

        if ($request->has('semester')) { 
            $query->where('semester', $request->semester);
        }

        if ($request->has('sort_by')) {
            $sort = $request->sort_by === 'desc' ? 'desc' : 'asc';
            $query->orderBy('created_at', $sort);
        }
        return response()->json($query->paginate($request->input('per_page', 500)));
    }

    public function store(Request $request)
    {
        $this->authorize('create', SectionSubject::class);
        $validated = $request->validate([
            'section_id' => 'required|uuid|exists:sections,section_id',
            'subject_id' => 'required|integer|exists:subjects,id',
            'professor_id' => 'required|uuid|exists:professors,professor_id',
            'semester' => 'required|integer|between:1,2'
        ]);
        
        $sectionSubject = SectionSubject::create($validated);
        return response()->json($sectionSubject, 201);
    }

    public function show(SectionSubject $sectionSubject)
    {
        $this->authorize('view', $sectionSubject);
        return response()->json($sectionSubject->load(['section', 'subject', 'professor']));
    }

    public function update(Request $request, SectionSubject $sectionSubject)
    {
        $this->authorize('update', $sectionSubject);
        $validated = $request->validate([
            'professor_id' => 'uuid|exists:professors,professor_id',
            'semester' => 'integer|between:1,2'
        ]);
        
        $sectionSubject->update($validated);
        return response()->json($sectionSubject);
    }

    public function destroy(SectionSubject $sectionSubject)
    {
        $this->authorize('delete', $sectionSubject);
        $sectionSubject->delete();
        return response()->json(null, 204);
    }
}
