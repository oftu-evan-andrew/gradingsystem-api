<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassStanding extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'student_id',
        'section_subject_id',
        'grading_period',
        'attendance_score',
        'recitation_score',
        'quiz_score',
        'project_score',
        'major_exam_score'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }

    public function periodicGrades()
    {
        return $this->hasMany(PeriodicGrade::class, 'class_standing_id');
    }
}
