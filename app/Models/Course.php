<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Course extends Model
{
    protected $primaryKey = 'course_id';
    protected $fillable = ['course_name'];

    public function sections() { 
        return $this->hasMany(Section::class, 'course_id');
    }
}
