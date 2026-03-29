<?php

namespace App\Http\Controllers;

use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectionController extends Controller
{
    public function subjects(Section $section) {
        $this->authorize('view', $section);

        return response()->json($section->sectionSubjects()->with(['subject', 'professor.user'])->paginate(15));
    }

    public function students (Section $section) {
        $this->authorize('viewAny', $section);
        
        return response()->json($section->students()->with('user')->paginate(15));
    }

    public function index(Request $request)
    {
       $professorId = $this->getProfessorId();
       $this->authorize('viewAny', Section::class);

       $query = Section::with(['course'])
            ->when($professorId, fn($q) => $q->whereHas('sectionSubjects', fn($sq) =>
                $sq->where('professor_id', $professorId)
            ));

        if ($request->has('search')) {
            $search = $request->search;
            $query->where('section_name', 'like', "%{$search}%");
        }

        if ($request->has('course_id')) { 
            $query->where('course_id', $request->course_id);
        }

        if ($request->has('year_level')) {
            $query->where('year_level', $request->year_level);
        }

        if ($request->has('sort_by')) {
            $sort = $request->sort_by === 'desc' ? 'desc' : 'asc';
            $query->orderBy('created_at', $sort);
        }

        return response()->json($query->paginate($request->input('per_page', 500)));
    }

    public function store(Request $request)
    {
        $this->authorize('finalize', Section::class);
        $validated = $request->validate([
            'section_name' => 'required|string|max:50',
            'year_level' => 'required|integer|between:1,4',
            'course_id' => 'required|exists:courses,id',
            'school_year' => 'required|string|max:20'
        ]);
        
        $section = Section::create($validated);
        return response()->json($section, 201);
    }

    public function show(Section $section)
    {
        $this->authorize('view', $section);
        return response()->json($section->load('course'));
    }

    public function update(Request $request, Section $section)
    {
        $this->authorize('update', $section);
        $validated = $request->validate([
            'section_name' => 'string|max:50',
            'year_level' => 'integer|between:1,4',
            'course_id' => 'exists:courses,id',
            'school_year' => 'string|max:20'
        ]);
        
        $section->update($validated);
        return response()->json($section);
    }

    public function destroy(Section $section)
    {
        $this->authorize('delete', $section);
        $section->delete();
        return response()->json(null, 204);
    }

    private function getProfessorId(): ?string
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return null;
        }
        
        return $user->professor->professor_id ?? null;
    }
}
