<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use App\Models\ClassStanding;
use App\Models\PeriodicGrade;
use App\Models\StudentFinalGrade;
use App\Models\StudentGpa;
use App\Models\QuizRecord;
use App\Models\RecitationRecord;
use App\Models\AttendanceRecord;
use App\Models\ProjectRecord;

class StudentPortalController extends Controller
{
    public function getMyGrades(Request $request)
    {
        $user = Auth::user();
        if ($user->role !== 'student') {
            return response()->json(['message' => 'Forbidden - Only students can access this portal'], 403);
        }

        $student = Student::with([
            'section.course',
            'section.sectionSubjects.subject',
            'section.sectionSubjects.professor.user'
        ])->where('user_id', $user->id)->first();

        if (!$student) {
            return response()->json(['message' => 'Student record not found'], 404);
        }

        $classStandings = ClassStanding::where('student_id', $student->student_id)
            ->where('status', 'finalized')
            ->get()
            ->groupBy('section_subject_id');

        $gpa = StudentGpa::where('student_id', $student->student_id)->latest()->first();

        $subjectsData = [];
        $sectionSubjects = $student->section->sectionSubjects ?? collect();

        foreach ($sectionSubjects as $ss) {
            $subjectClassStandings = $classStandings->get($ss->id) ?? collect();
            $sortedStandings = $subjectClassStandings->sortBy('grading_period')->values();

            $periodsData = [];
            foreach ($sortedStandings as $standing) {
                $period = $standing->grading_period;
                $classStandingIds = $subjectClassStandings->pluck('id')->toArray();

                $periodic = PeriodicGrade::where('student_id', $student->student_id)
                    ->whereIn('class_standing_id', $classStandingIds)
                    ->where('grading_period', $period)
                    ->where('status', 'finalized')
                    ->first();

                $quizItems = QuizRecord::where('student_id', $student->student_id)
                    ->where('section_subject_id', $ss->id)
                    ->where('grading_period', $period)
                    ->orderBy('quiz_number')
                    ->get()
                    ->map(fn($r) => [
                        'label' => $r->quiz_title ?? "Quiz {$r->quiz_number}",
                        'score' => (float) $r->rating,
                        'total' => 100,
                    ])
                    ->toArray();

                $recitationItems = RecitationRecord::where('student_id', $student->student_id)
                    ->where('section_subject_id', $ss->id)
                    ->where('grading_period', $period)
                    ->orderBy('id')
                    ->get()
                    ->map(fn($r, $i) => [
                        'label' => $r->recitation_title ?? "Recitation " . ($i + 1),
                        'score' => (float) $r->rating,
                        'total' => 100,
                    ])
                    ->values()
                    ->toArray();

                $attendanceItems = AttendanceRecord::where('student_id', $student->student_id)
                    ->where('section_subject_id', $ss->id)
                    ->where('grading_period', $period)
                    ->orderBy('id')
                    ->get()
                    ->map(fn($r, $i) => [
                        'label' => $r->attendance_title ?? "Session " . ($i + 1),
                        'score' => (float) $r->rating,
                        'total' => 100,
                    ])
                    ->values()
                    ->toArray();

                $projectItems = ProjectRecord::where('student_id', $student->student_id)
                    ->where('section_subject_id', $ss->id)
                    ->where('grading_period', $period)
                    ->orderBy('id')
                    ->get()
                    ->map(fn($r, $i) => [
                        'label' => $r->project_title ?? "Project " . ($i + 1),
                        'score' => (float) $r->rating,
                        'total' => 100,
                    ])
                    ->values()
                    ->toArray();

                $quizItems = !empty($quizItems) ? $quizItems : [['label' => 'Total Score', 'score' => (float)($standing->quiz_score ?? 0), 'total' => 100]];
                $recitationItems = !empty($recitationItems) ? $recitationItems : [['label' => 'Total Score', 'score' => (float)($standing->recitation_score ?? 0), 'total' => 100]];
                $attendanceItems = !empty($attendanceItems) ? $attendanceItems : [['label' => 'Total Score', 'score' => (float)($standing->attendance_score ?? 0), 'total' => 100]];
                $projectItems = !empty($projectItems) ? $projectItems : [['label' => 'Total Score', 'score' => (float)($standing->project_score ?? 0), 'total' => 100]];

                $periodsData[] = [
                    'period' => $period,
                    'periodName' => $this->getGradingPeriodName($period),
                    'components' => [
                        'quiz' => ['weight' => 0.40, 'items' => $quizItems],
                        'recitation' => ['weight' => 0.30, 'items' => $recitationItems],
                        'attendance' => ['weight' => 0.10, 'items' => $attendanceItems],
                        'project' => ['weight' => 0.20, 'items' => $projectItems],
                    ],
                    'periodicRating' => $periodic ? (float) $periodic->periodic_grade : null,
                    'classStanding' => [
                        'attendance_score' => $standing->attendance_score,
                        'recitation_score' => $standing->recitation_score,
                        'quiz_score' => $standing->quiz_score,
                        'project_score' => $standing->project_score,
                        'major_exam_score' => $standing->major_exam_score,
                    ],
                ];
            }

            $fg = StudentFinalGrade::where('student_id', $student->student_id)
                ->where('section_subject_id', $ss->id)
                ->where('status', 'finalized')
                ->first();

            $finalRating = $fg ? $fg->final_grade : null;
            $gradeStr = $this->convertToGradeString($finalRating);

            $subjectsData[] = [
                'id' => $ss->id,
                'code' => $ss->subject->subject_code ?? 'N/A',
                'title' => $ss->subject->subject_name ?? 'N/A',
                'units' => $ss->subject->units ?? 0,
                'instructor' => [
                    'name' => $ss->professor?->user 
                        ? trim($ss->professor->user->first_name . ' ' . $ss->professor->user->last_name) 
                        : 'TBA',
                    'dept' => $ss->professor?->department ?? 'General',
                ],
                'schedule' => 'TBA',
                'room' => 'TBA',
                'periods' => $periodsData,
                'finalRating' => $finalRating !== null ? (float) $finalRating : null,
                'grade' => $gradeStr,
            ];
        }

        return response()->json([
            'student' => [
                'name' => $user->name ?? $user->first_name . ' ' . $user->last_name,
                'studentId' => $student->student_id,
                'program' => $student->section->course->course_name ?? 'N/A',
                'year' => 'Year ' . ($student->section->year_level ?? '1'),
                'semester' => $student->section->school_year ?? 'N/A',
                'section' => $student->section->section_name ?? 'N/A',
                'cumulativeGPA' => $gpa ? (float) $gpa->cumulative_gpa : 0.0,
            ],
            'subjects' => $subjectsData,
        ]);
    }

    private function getGradingPeriodName(int $period): string
    {
        return match ($period) {
            1 => 'Prelims',
            2 => 'Midterms',
            3 => 'Finals',
            default => "Period {$period}",
        };
    }

    private function convertToGradeString($grade)
    {
        if (!$grade || $grade == 0) return 'N/A';
        if ($grade >= 97.5) return '1.00';
        if ($grade >= 94.5) return '1.25';
        if ($grade >= 91.5) return '1.50';
        if ($grade >= 88.5) return '1.75';
        if ($grade >= 85.5) return '2.00';
        if ($grade >= 82.5) return '2.25';
        if ($grade >= 79.5) return '2.50';
        if ($grade >= 76.5) return '2.75';
        if ($grade >= 74.5) return '3.00';
        return '5.00';
    }
}