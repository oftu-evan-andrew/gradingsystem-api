<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'section_id', 'is_irregular'];

    public function user() { 
        return $this->belongsTo(User::class, 'user_id');
    }

    public function section() { 
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function grades() { 
        return $this->hasMany(Grade::class, 'student_id');
    }
}
