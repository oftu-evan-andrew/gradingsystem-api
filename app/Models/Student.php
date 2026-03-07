<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $primaryKey = 'student_id';

    public function section() { 
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function grades() { 
        return $this->hasMany(Grade::class, 'student_id');
    }

    public function users() { 
        return $this->belongsTo(User::class, 'user_id');
    }
}
