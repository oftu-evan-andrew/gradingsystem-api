<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Professor extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $primaryKey = 'professor_id';

    protected $fillable = [
        'user_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function sections()
    {
        return $this->hasMany(SectionSubject::class, 'professor_id');
    }
}
