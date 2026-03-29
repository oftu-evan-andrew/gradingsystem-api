<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttendanceRecordRequest;
use App\Http\Requests\UpdateAttendanceRecordRequest;
use App\Http\Resources\AttendanceRecordResource;
use App\Http\Resources\AttendanceRecordCollection;
use App\Models\AttendanceRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;


class AttendanceRecordController extends Controller implements HasMiddleware
{
    /**
     * Authorization middleware - Apply middleware to restrict access.
     * Only professors and admins can access these endpoints.
     */
    public static function middleware(): array
    {
        return [
            new Middleware(function ($request, $next) {
                if (!in_array($request->user()->role, ['professor', 'admin'])) {
                    return response()->json(['message' => 'Forbidden'], 403);
                }
                return $next($request);
            }),
        ];
    }

    /**
     * Get the professor ID based on the authenticated user.
     * Returns null for admins (to allow access to all records).
     * Returns the professor's ID for professors.
     */
    private function getProfessorId(): ?string
    {
        $user = Auth::user();
        
        if ($user->role === 'admin') {
            return null;
        }
        
        return $user->professor->professor_id ?? null;
    }

    /**
     * Get all attendance records with pagination.
     * Students see only their own finalized records.
     * Professors see only their own records; admins see all.
     */
    public function index(): AttendanceRecordCollection
    {
        $user = Auth::user();
        $request = request();

        // Students: view only their finalized attendance records
        if ($user->role === 'student') {
            $records = AttendanceRecord::with(['sectionSubject.subject'])
                ->where('student_id', $user->student->student_id)
                ->when($request->section_subject_id, fn($q) => $q->where('section_subject_id', $request->section_subject_id))
                ->when($request->grading_period, fn($q) => $q->where('grading_period', $request->grading_period))
                ->wherehas('classStanding', fn($cs) => $cs->where('status', 'finalized'))
                ->paginate(15);

            return new AttendanceRecordCollection($records);
        }

        // Professors/Admins: existing scoping logic
        $professorId = $this->getProfessorId();
        
        $attendanceRecords = AttendanceRecord::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
            ->when($request->section_subject_id, fn($q) => $q->where('section_subject_id', $request->section_subject_id))
            ->when($request->grading_period, fn($q) => $q->where('grading_period', $request->grading_period))
            ->paginate(15);
        
        return new AttendanceRecordCollection($attendanceRecords);
    }

    /**
     * Store a new attendance record or multiple records (bulk).
     * Supports two formats:
     * - Single: student_id, attendance_date, status, rating (direct fields)
     * - Bulk: grades array with multiple student records (each can have different status)
     */
    public function store(StoreAttendanceRecordRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        if ($user->role !== 'professor' && $user->role !== 'admin') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $professorId = $user->role === 'admin' 
            ? $request->input('professor_id') 
            : $user->professor->professor_id;
        
        $validated = $request->validated();
        
        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $grades = $validated['grades'];
            unset($validated['grades']);
            
            try { 
                $records = DB::transaction(function () use ($validated, $grades, $professorId) {
                $createdRecords = [];
                
                foreach ($grades as $grade) {
                    $record = AttendanceRecord::firstOrNew([
                        'student_id' => $grade['student_id'],
                        'section_subject_id' => $validated['section_subject_id'],
                        'grading_period' => $validated['grading_period'],
                        'attendance_date' => $validated['attendance_date'],
                    ]);
                    $record->professor_id = $professorId;
                    $record->status = $grade['status'] ?? $validated['status'];
                    $record->rating = $grade['rating'];
                    $record->save();
                    $createdRecords[] = $record;
                }
                
                return $createdRecords;
            });
            } catch (\Exception $e) { 
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }
            
            $records = AttendanceRecord::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();
            
            return response()->json([
                'message' => 'Attendance records created successfully',
                'data' => AttendanceRecordResource::collection($records),
            ], 201);
        } else {
            if (!isset($validated['student_id'])) {
                return response()->json(['message' => 'student_id is required'], 422);
            }
            $record = AttendanceRecord::firstOrNew([
                'student_id' => $validated['student_id'],
                'section_subject_id' => $validated['section_subject_id'],
                'attendance_date' => $validated['attendance_date'],
            ]);
            $record->professor_id = $professorId;
            $record->grading_period = $validated['grading_period'];
            $record->status = $validated['status'] ?? 'present';
            $record->rating = $validated['rating'];
            $record->save();

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Attendance record created successfully',
                'data' => new AttendanceRecordResource($record),
            ], 201);
        }
    }

    /**
     * Get a single attendance record by ID.
     * Students can only view their own records when class standing is finalized.
     * Professors can only see their own records; admins can see all.
     */
    public function show(int $id): JsonResponse
    {
        $user = Auth::user();

        // Students: can only view their own finalized records
        if ($user->role === 'student') {
            $record = AttendanceRecord::with(['student.user', 'sectionSubject.subject'])->find($id);

            if (!$record) {
                return response()->json(['message' => 'Attendance record not found'], 404);
            }

            if ($record->student_id !== $user->student->student_id) {
                return response()->json(['message' => 'Forbidden'], 403);
            }

            if ($record->classStanding->status !== 'finalized') {
                return response()->json(['message' => 'Not yet available'], 403);
            }

            return (new AttendanceRecordResource($record))->response();
        }

        $professorId = $this->getProfessorId();
        
        $query = AttendanceRecord::with(['student.user', 'sectionSubject.subject']);
        
        if ($professorId) {
            $query->where('professor_id', $professorId);
        }
        
        $attendanceRecord = $query->find($id);
        
        if (!$attendanceRecord) {
            return response()->json(['message' => 'Attendance record not found'], 404);
        }
        
        return (new AttendanceRecordResource($attendanceRecord))->response();
    }

    /**
     * Update attendance record(s).
     * Supports two formats:
     * - Single: id field with rating/status
     * - Bulk: grades array with attendance_record_id, rating, and status for each
     */
    public function update(UpdateAttendanceRecordRequest $request, int $id): JsonResponse
    {
        $professorId = $this->getProfessorId();
        $validated = $request->validated();
        
        // Check if bulk format (grades array) or single record
        if (isset($validated['grades']) && is_array($validated['grades'])) {
            $recordIds = array_column($validated['grades'], 'attendance_record_id');
            $records = AttendanceRecord::whereIn('id', $recordIds)
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->get()
                ->keyBy('id');
            
            try { 
                DB::transaction(function () use ($validated, $records) { 
                foreach ($validated['grades'] as $gradeData) { 
                    if ($record = $records->get($gradeData['attendance_record_id'])) {
                        $record->update([
                            'attendance_date' => $validated['attendance_date'] ?? $record->attendance_date,
                            'rating' => $gradeData['rating'] ?? $record->rating,
                            'status' => $gradeData['status'] ?? $record->status,
                        ]);
                    }
            }
            });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }

            $records = AttendanceRecord::with(['student.user', 'sectionSubject.subject']) 
                ->whereIn('id', array_map(fn($r) => is_array($r) ? $r['id'] : $r->id, is_array($records) ? $records : $records->toArray()))
                ->get();
            
            return response()->json([
                'message' => 'Records updated successfully',
                'data' => AttendanceRecordResource::collection($records),
                ]);
                
        } else {
            // Single record update
            $record = AttendanceRecord::where('id', $id)
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->first();
            
            if (!$record) {
                return response()->json(['message' => 'Attendance record not found'], 404);
            }
            
            $record->update([
                'attendance_date' => $validated['attendance_date'] ?? $record->attendance_date,
                'rating' => $validated['rating'] ?? $record->rating,
                'status' => $validated['status'] ?? $record->status,
            ]);
            
            $record->load(['student.user', 'sectionSubject.subject']);
            
            return response()->json([
                'message' => 'Attendance record updated successfully',
                'data' => new AttendanceRecordResource($record),
            ]);
        }
    }

    /**
     * Delete an attendance record by ID.
     * Professors can only delete their own records; admins can delete all.
     */
    public function destroy(int $id): JsonResponse
    {
        $professorId = $this->getProfessorId();
        
        $query = AttendanceRecord::query();
        
        if ($professorId) {
            $query->where('professor_id', $professorId);
        }
        
        $attendanceRecord = $query->find($id);
        
        if (!$attendanceRecord) {
            return response()->json(['message' => 'Attendance record not found'], 404);
        }
        
        $attendanceRecord->delete();
        
        return response()->json(null, 204);
    }
}
