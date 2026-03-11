<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecitationRecord extends Model
{
    protected $fillable = [
        'student_id',
        'section_subject_id',
        'professor_id',
        'grading_period',
        'rating',
        'remarks'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }
}
