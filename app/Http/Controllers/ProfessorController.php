<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProfessorController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\Professor::with('user')->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|uuid|exists:users,id|unique:professors,user_id'
        ]);
        
        $professor = \App\Models\Professor::create($validated);
        return response()->json($professor, 201);
    }

    public function show(\App\Models\Professor $professor)
    {
        return response()->json($professor->load('user', 'sections'));
    }

    public function destroy(\App\Models\Professor $professor)
    {
        $professor->delete();
        return response()->json(null, 204);
    }
}
