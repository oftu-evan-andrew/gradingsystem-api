<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodicGrade extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'student_id',
        'class_standing_id',
        'grading_period',
        'periodic_grade',
        'status',
        'submitted_at',
        'submitted_by',
        'last_modified_by'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function classStanding()
    {
        return $this->belongsTo(ClassStanding::class, 'class_standing_id');
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
