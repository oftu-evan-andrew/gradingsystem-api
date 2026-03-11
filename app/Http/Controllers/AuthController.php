<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Models\User;
use App\Models\Professor;
use App\Models\Student;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function register(Request $request) {
        // Authorization check 
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'Action denied. Admin rights required'], 403);
        }

        // Validation request
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'string', 'in:student,professor'],
            'section_id' => [
                'required_if:role,student', 
                'nullable', 
                'exists:sections,section_id'
            ]
        ]);

        // Database transaction
        return DB::transaction(function () use ($request) {

            // Create User
            $user = User::create([
                'first_name' => $request->first_name,
                'last_name'  => $request->last_name,
                'email'      => $request->email,
                'password'   => Hash::make($request->password),
                'role'       => $request->role,
            ]);

            // Student sub-profile
            if ($request->role === 'student') {
                Student::create([
                    'user_id' => $user->id,
                    'section_id' => $request->section_id
                ]);

            // Professor sub-profile
            } elseif ($request->role === 'professor') {
                Professor::create([
                    'user_id' => $user->id,
                ]);
            }

            return response()->json([
                'message' => ucfirst($request->role) . ' registered successfully!',
                'user' => $user->load($request->role) 
            ], 201);
        });
    } 

    public function login(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) { 
            return response()->json([
                'message' => 'Invalid login credentials.'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load(match($user->role) {
                'student' => 'student',
                'professor' => 'professor',
                default => [],
            })
        ]);
    }

    public function logout(Request $request) {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
