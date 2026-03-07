<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $primaryKey = 'subject_id';

    public function sectionSubject() { 
        return $this->hasMany(SectionSubject::class, 'subject_id');
    }

    public function grades() {
        return $this->hasMany(Grade::class, 'subject_id');
    }
}
