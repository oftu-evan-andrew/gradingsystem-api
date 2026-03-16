<?php

namespace App\Models;

use App\Services\GradeCalculationService;
use Illuminate\Database\Eloquent\Model;

class StudentFinalGrade extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'student_id',
        'section_subject_id',
        'final_grade',
        'status',
        'submitted_at',
        'submitted_by',
        'last_modified_by'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }

    public function submittedBy()
    {
        return $this->belongsTo(Professor::class, 'submitted_by');
    }

    public function lastModifiedBy()
    {
        return $this->belongsTo(Professor::class, 'last_modified_by');
    }

    protected static function booted() { 
        static::updated(function ($finalGrade) {
            if ($finalGrade->wasChanged('status') && $finalGrade->status === 'finalized') {
                $student = $finalGrade->student; 

                // Calculate Cumulative GPA
                $cumulativeGpa = app(GradeCalculationService::class) 
                    ->calculateCumulativeGpa($student);
                
                // Find or create StudentGpa for this semester
                $sectionSubject = $finalGrade->sectionSubject;
                $gpa = StudentGpa::firstOrNew([
                    'student_id' => $student->id,
                    'school_year' => $sectionSubject->section->school_year ?? null,
                    'semester' => $sectionSubject->semester ?? null,
                ]);

                $gpa->cumulative_gpa = $cumulativeGpa;
                $gpa->save();
            }
        });
    }
}
