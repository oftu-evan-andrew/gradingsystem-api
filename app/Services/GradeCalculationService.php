<?php 

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\ClassStanding;
use App\Models\Student;
use App\Models\SectionSubject;
use App\Models\PeriodicGrade;
use App\Models\ProjectRecord;
use App\Models\QuizRecord;
use App\Models\RecitationRecord;
use App\Models\StudentFinalGrade;

 
class GradeCalculationService {
    const ATTENDANCE_WEIGHT = 0.10;
    const RECITATION_WEIGHT = 0.30;
    const QUIZ_WEIGHT = 0.40;
    const PROJECT_WEIGHT = 0.20;

    public function calculateComponentAverages(string $studentId, string $sectionSubjectId, int $gradingPeriod): array
    { 
        return [ 
            'attendance' => AttendanceRecord::where('student_id', $studentId) 
                ->where('section_subject_id', $sectionSubjectId) 
                ->where('grading_period', $gradingPeriod) 
                ->avg('rating') ?? 0,
            
            'recitation' => RecitationRecord::where('student_id', $studentId) 
                ->where('section_subject_id', $sectionSubjectId) 
                ->where('grading_period', $gradingPeriod) 
                ->avg('rating') ?? 0,

            'quiz' => QuizRecord::where('student_id', $studentId) 
                ->where('section_subject_id', $sectionSubjectId) 
                ->where('grading_period', $gradingPeriod) 
                ->avg('rating') ?? 0,

            'project' => ProjectRecord::where('student_id', $studentId) 
                ->where('section_subject_id', $sectionSubjectId) 
                ->where('grading_period', $gradingPeriod) 
                ->avg('rating') ?? 0,
        ];
    }

    public function percentageToGrade(float $percentage) : float { 
        return round(5.0 - (($percentage / 100) * 4.0), 2);
    }

    public function calculateClassStanding(ClassStanding $classStanding): float {
        $attendance = $classStanding->attendance_score ?? 0;
        $recitation = $classStanding->recitation_score ?? 0;
        $quiz = $classStanding->quiz_score ?? 0;
        $project = $classStanding->project_score ?? 0;

        $weightedSum = ($attendance * self::ATTENDANCE_WEIGHT) 
                    + ($recitation * self::RECITATION_WEIGHT)
                    + ($quiz * self::QUIZ_WEIGHT) 
                    + ($project * self::PROJECT_WEIGHT);
        
        return round($weightedSum, 2);
    }

    public function calculatePeriodicGrade(ClassStanding $classStanding): ?float { 
        if ($classStanding->major_exam_score === null) {
            return null;
        }
        
        $weightedSum = $this->calculateClassStanding($classStanding);
        $majorExam = $classStanding->major_exam_score;
        $periodicGrade = (($weightedSum * 2 ) + $majorExam) / 3;

        return $this->percentageToGrade($periodicGrade);
    }

    public function calculateFinalGrade(Student $student, SectionSubject $sectionSubject): ?float { 
        $periodicGrades = PeriodicGrade::where('student_id', $student->id) 
            ->whereHas('classStanding', function ($query) use ($sectionSubject) { 
                $query->where('section_subject_id', $sectionSubject->id);
            })
            ->get();
        
        if ($periodicGrades->isEmpty()) { 
            return null;
        }

        return round($periodicGrades->avg('periodic_grade'), 2);
    }

    public function calculateCumulativeGpa(Student $student): ?float { 
        $finalGrades = StudentFinalGrade::where('student_id', $student->id) 
            ->where('status', 'finalized')
            ->with('sectionSubject.subject')
            ->get();

        if ($finalGrades->isEmpty()) {
            return null;
        }

        $totalPoints = 0;
        $totalUnits = 0; 

        foreach ($finalGrades as $fg) { 
            $units = $fg->sectionSubject->subject->units ?? 0;
            $totalPoints += ($fg->final_grade * $units);
            $totalUnits += $units;  
        }

        if ($totalUnits === 0) {
            return null;
        }

        return round($totalPoints / $totalUnits, 2);
    } 
}


