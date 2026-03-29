<?php

namespace App\Http\Controllers;

use App\Models\Professor;
use Illuminate\Http\Request;

class ProfessorController extends Controller
{
    public function index(Request $request)
    {
       $this->authorize('viewAny', Professor::class);

       $query = Professor::with(['user']);

       if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
       }

       if ($request->has('sort_by')) {
            $sort = $request->sort_by === 'desc' ? 'desc' : 'asc';
            $query->orderBy('created_at', $sort);
       }
        return response()->json($query->paginate(15));
    }

    public function store(Request $request)
    {
        $this->authorize('create', Professor::class);
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id|unique:professors,user_id'
        ]);
        
        $professor = Professor::create($validated);
        return response()->json($professor, 201);
    }

    public function show(Professor $professor)
    {
        $this->authorize('view', $professor);
        return response()->json($professor->load('user', 'sections'));
    }

    public function update(Request $request, Professor $professor)
    {
        $this->authorize('update', $professor);
        $validated = $request->validate([
            'user_id' => 'uuid|exists:users,id|unique:professors,user_id,' . $professor->id
        ]);
        
        $professor->update($validated);
        return response()->json($professor);
    }

    public function destroy(Professor $professor)
    {   
        $this->authorize('delete', $professor);

        if ($professor->sections()->exists()) {
            return response()->json([
                'message' => 'Cannot delete professor with assigned sections/subjects'
            ], 403);
        }

        $professor->delete();
        return response()->json(null, 204);
    }
}
