<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $primaryKey = 'section_id';

    protected $fillable = [
        'section_name',
        'year_level',
        'course_id',
        'school_year'
    ];

    public function course()
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'section_id');
    }

    public function sectionSubjects()
    {
        return $this->hasMany(SectionSubject::class, 'section_id');
    }
}
