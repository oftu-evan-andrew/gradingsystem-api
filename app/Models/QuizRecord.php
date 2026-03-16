<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Services\GradeCalculationService;

class QuizRecord extends Model
{
    protected $fillable = [
        'student_id',
        'section_subject_id',
        'professor_id',
        'grading_period',
        'quiz_number',
        'quiz_title',
        'rating'
    ];


    protected static function booted() {
        static::saved(function ($record) {
            $gradeService = app(GradeCalculationService::class);

            $averages = $gradeService->calculateComponentAverages(
                $record->student_id,
                $record->section_subject_id,
                $record->grading_period
            );

            $classStanding = ClassStanding::firstOrNew([
                'student_id' => $record->student_id,
                'section_subject_id' => $record->section_subject_id, 
                'grading_period' => $record->grading_period,
            ]);

            $classStanding->quiz_score = $averages['quiz'];
            $classStanding->save();
        });
    }

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function sectionSubject()
    {
        return $this->belongsTo(SectionSubject::class, 'section_subject_id');
    }

    public function professor()
    {
        return $this->belongsTo(Professor::class, 'professor_id');
    }
}
