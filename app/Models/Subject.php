<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $fillable = [
        'subject_name',
        'subject_code',
        'units',
        'is_minor'
    ];

    public function sectionSubjects()
    {
        return $this->hasMany(SectionSubject::class, 'subject_id');
    }
}
