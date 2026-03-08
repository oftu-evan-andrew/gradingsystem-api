<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $primaryKey = 'grade_id';

    protected $fillable = ['submitted_at', 'submitted_by', 'last_modified_by', 'last_modified_at'];

    public function student() {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function subject() { 
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function professor() { 
        return $this->belongsTo(Professor::class, 'professor_id');
    }
}
