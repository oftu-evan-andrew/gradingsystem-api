<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Professor extends Model
{
    protected $primaryKey = 'professor_id';
    protected $fillable = ['first_name', 'last_name', 'email'];

    public function sections() {
        return $this->hasMany(Section::class, 'professor_id');
    }

    public function grades() {
        return $this->hasMany(Grade::class, 'professor_id');
    }

    public function users() { 
        return $this->belongsTo(User::class, 'user_id');
    }
}
