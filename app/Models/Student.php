<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $primaryKey = 'student_id';

    protected $fillable = [
        'user_id',
        'section_id',
        'is_irregular'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function section()
    {
        return $this->belongsTo(Section::class, 'section_id');
    }
}
