<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    protected $primaryKey = 'section_id';
    
    public function course() {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function students() {
        return $this->hasMany(Student::class, 'section_id');
    }

    public function sectionSubject() { 
        return $this->hasMany(SectionSubject::class, 'section_id');
    }
}
