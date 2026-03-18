<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StudentController extends Controller
{
    public function index(Request $request)
    {
       $this->authorize('finalize', Student::class);
       $professorId = $this->getProfessorId();

       $query = Student::with(['user', 'section'])
                ->when($professorId, fn($q) => $q->whereHas('section', fn($sq) =>
                    $sq->whereHas('sectionSubjects', fn($ssq) => 
                        $ssq->where('professor_id', $professorId)
                )
            ));

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->has('section_id')) { 
            $query->where('section_id', $request->section_id);
        }

        if ($request->has('sort_by')) {
            $sort = $request->sort_by === 'desc' ? 'desc' : 'asc';
            $query->orderBy('created_at', $sort);
        }
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->authorize('finalize', Student::class);
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id|unique:students,user_id',
            'section_id' => 'required|uuid|exists:sections,section_id',
            'is_irregular' => 'boolean'
        ]);
        
        $student = Student::create($validated);
        return response()->json($student, 201);
    }

    public function show(Student $student)
    {
        $this->authorize('finalize', $student);
        return response()->json($student->load(['user', 'section']));
    }

    public function update(Request $request, Student $student)
    {
        $this->authorize('finalize', $student);
        $validated = $request->validate([
            'section_id' => 'uuid|exists:sections,section_id',
            'is_irregular' => 'boolean'
        ]);
        
        $student->update($validated);
        return response()->json($student);
    }

    public function destroy(Student $student)
    {
        $this->authorize('finalize', $student);
        $student->delete();
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
