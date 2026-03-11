<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AttendanceRecordController extends Controller
{
    public function index()
    {
        return response()->json(\App\Models\AttendanceRecord::all());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|uuid|exists:students,student_id',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'professor_id' => 'required|uuid|exists:professors,professor_id',
            'grading_period' => 'required|integer|between:1,3',
            'attendance_date' => 'required|date',
            'status' => 'required|in:present,late,absent',
            'rating' => 'required|numeric|between:0,100'
        ]);
        
        $record = \App\Models\AttendanceRecord::create($validated);
        return response()->json($record, 201);
    }

    public function show(\App\Models\AttendanceRecord $attendanceRecord)
    {
        return response()->json($attendanceRecord);
    }

    public function update(Request $request, \App\Models\AttendanceRecord $attendanceRecord)
    {
        $validated = $request->validate([
            'status' => 'in:present,late,absent',
            'rating' => 'numeric|between:0,100'
        ]);
        
        $attendanceRecord->update($validated);
        return response()->json($attendanceRecord);
    }

    public function destroy(\App\Models\AttendanceRecord $attendanceRecord)
    {
        $attendanceRecord->delete();
        return response()->json(null, 204);
    }
}
