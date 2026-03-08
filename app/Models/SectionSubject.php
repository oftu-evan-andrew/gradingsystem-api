<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SectionSubject extends Model
{
    protected $primaryKey = 'id';
    protected $fillable = ['section_id', 'subject_id', 'semester', 'professor_id'];
    
    public function professor() { 
        return $this->belongsTo(Professor::class);
    }

    public function section() { 
        return $this->belongsTo(Section::class, 'section_id');
    }

    public function subject() {
        return $this->belongsTo(Subject::class, 'subject_id');
    }
}
