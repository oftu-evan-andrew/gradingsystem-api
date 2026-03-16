<?php

namespace App\Models;

use App\Services\GradeCalculationService;
use Illuminate\Database\Eloquent\Model;

class ClassStanding extends Model
{
    use \Illuminate\Database\Eloquent\Concerns\HasUuids;

    protected $fillable = [
        'student_id',
        'section_subject_id',
        'grading_period',
        'attendance_score',
        'recitation_score',
        'quiz_score',
        'project_score',
        'major_exam_score',
        'status'
    ];

    protected $casts = ['status' => 'string'];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }

    public function periodicGrades()
    {
        return $this->hasMany(PeriodicGrade::class, 'class_standing_id');
    }

    protected static function booted() {
        static::saved(function ($classStanding) { 
            // Calculate PeriodicGrade when score change
            $periodicGrade = PeriodicGrade::firstOrNew([
                'student_id' => $classStanding->student_id,
                'class_standing_id' => $classStanding->id,
                'grading_period' => $classStanding->grading_period
            ]);

            $periodicGrade->periodic_grade = app(GradeCalculationService::class) 
                ->calculatePeriodicGrade($classStanding);
            $periodicGrade->save();
        });

        static::updated(function ($classStanding) {
            // Recalculate if status was changed to submitted/finalized
            if ($classStanding->wasChanged('status')) {
                $periodicGrade = PeriodicGrade::where('class_standing_id', $classStanding->id)->first();
                if ($periodicGrade) {
                    $periodicGrade->status = $classStanding->status;
                    $periodicGrade->save();
                }
            }
        });
    }
}
