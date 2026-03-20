<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\CourseController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\SectionController;
use App\Http\Controllers\ProfessorController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SectionSubjectController;
use App\Http\Controllers\AttendanceRecordController;
use App\Http\Controllers\RecitationRecordController;
use App\Http\Controllers\QuizRecordController;
use App\Http\Controllers\ProjectRecordController;
use App\Http\Controllers\ClassStandingController;
use App\Http\Controllers\PeriodicGradeController;
use App\Http\Controllers\StudentFinalGradeController;
use App\Http\Controllers\StudentGpaController;
use App\Http\Controllers\AuthController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/auth/login', [AuthController::class, 'login']);



Route::middleware('auth:sanctum')->group(function () {
    // Authentication
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::apiResource('courses', CourseController::class);
    Route::apiResource('subjects', SubjectController::class);
    Route::apiResource('sections', SectionController::class);
    Route::apiResource('professors', ProfessorController::class);
    Route::apiResource('students', StudentController::class);
    Route::apiResource('section-subjects', SectionSubjectController::class);
    
    // Finalize student performance
    Route::post('class-standings/{id}/finalize', [ClassStandingController::class, 'finalize']);
    Route::post('periodic-grades/{id}/finalize', [PeriodicGradeController::class, 'finalize']);
    Route::post('student-final-grades/{id}/finalize', [StudentFinalGradeController::class, 'finalize']);

    // Bulk finalize (admin-only middleware check in controller)
    Route::post('class-standings/bulk/finalize', [ClassStandingController::class, 'finalizeBulk']);
    Route::post('student-final-grades/bulk/approve', [StudentFinalGradeController::class, 'approveBulk']);

    // Grading Records
    Route::middleware(['can:access-professor-content'])->group(function () {
        Route::apiResource('attendance-records', AttendanceRecordController::class);
        Route::apiResource('recitation-records', RecitationRecordController::class);
        Route::apiResource('quiz-records', QuizRecordController::class);
        Route::apiResource('project-records', ProjectRecordController::class); 
    });
    
    
    // Computations and Aggregations
    Route::apiResource('class-standings', ClassStandingController::class);
    Route::apiResource('periodic-grades', PeriodicGradeController::class);
    Route::apiResource('student-final-grades', StudentFinalGradeController::class);
    
    Route::get('student-gpas', [StudentGpaController::class, 'index']);
    Route::get('student-gpas/{studentGpa}', [StudentGpaController::class, 'show']);
    Route::delete('student-gpas/{studentGpa}', [StudentGpaController::class, 'destroy']);
    Route::post('student-gpas/calculate', [StudentGpaController::class, 'calculate']);
});
