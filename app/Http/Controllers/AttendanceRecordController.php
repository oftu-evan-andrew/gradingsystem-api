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


class AttendanceRecordController extends Controller
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
     * Professors see only their own records; admins see all.
     */
    public function index(): AttendanceRecordCollection
    {
        $professorId = $this->getProfessorId();
        
        $attendanceRecords = AttendanceRecord::with(['student.user', 'sectionSubject.subject'])
            ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
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
                    $record = AttendanceRecord::create([
                        'student_id' => $grade['student_id'],
                        'section_subject_id' => $validated['section_subject_id'],
                        'professor_id' => $professorId,
                        'grading_period' => $validated['grading_period'],
                        'attendance_date' => $validated['attendance_date'],
                        'status' => $grade['status'] ?? $validated['status'],
                        'rating' => $grade['rating'],
                    ]);
                    $createdRecords[] = $record;
                }
                
                return $createdRecords;
            });
            } catch (\Exception $e) { 
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }
            
            $records = AttendanceRecord::with(['student.user', 'sectionSubject.subject'])
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
                ->get();
            
            return response()->json([
                'message' => 'Attendance records created successfully',
                'data' => AttendanceRecordResource::collection($records),
            ], 201);
        } else {
            $record = AttendanceRecord::create([
                'student_id' => $validated['student_id'],
                'section_subject_id' => $validated['section_subject_id'],
                'professor_id' => $professorId,
                'grading_period' => $validated['grading_period'],
                'attendance_date' => $validated['attendance_date'],
                'status' => $validated['status'],
                'rating' => $validated['rating'],
            ]);

            $record->load(['student.user', 'sectionSubject.subject']);

            return response()->json([
                'message' => 'Attendance record created successfully',
                'data' => new AttendanceRecordResource($record),
            ], 201);
        }
    }

    /**
     * Get a single attendance record by ID.
     * Professors can only see their own records; admins can see all.
     */
    public function show(int $id): JsonResponse
    {
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
    public function update(UpdateAttendanceRecordRequest $request): JsonResponse
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
                            'rating' => $gradeData['rating'],
                            'status' => $gradeData['status'] ?? $record->status,
                        ]);
                    }
            }
            });
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to create records: ' . $e->getMessage()], 500);
            }

            $records = AttendanceRecord::with(['student.user', 'sectionSubject.subject']) 
                ->whereIn('id', array_map(fn($r) => $r->id, $records))
                ->get();
            
            return response()->json([
                'message' => 'Records updated successfully',
                'data' => AttendanceRecordResource::collection($records),
                ]);
                
        } else {
            // Single record update
            $record = AttendanceRecord::where('id', $validated['id'])
                ->when($professorId, fn($q) => $q->where('professor_id', $professorId))
                ->first();
            
            if (!$record) {
                return response()->json(['message' => 'Attendance record not found'], 404);
            }
            
            $record->update([
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
