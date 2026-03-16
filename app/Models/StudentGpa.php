<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentGpa extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'student_id',
        'school_year',
        'semester',
        'cumulative_gpa'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }
}
