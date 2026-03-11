<?php

namespace App\Models;

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
}
