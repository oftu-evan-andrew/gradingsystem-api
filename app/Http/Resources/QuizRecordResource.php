<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuizRecordResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'section_subject_id' => $this->section_subject_id,
            'professor_id' => $this->professor_id,
            'grading_period' => $this->grading_period,
            'quiz_number' => $this->quiz_number,
            'quiz_title' => $this->quiz_title,
            'rating' => (float) $this->rating,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->student_id,
                    'first_name' => $this->student->user->first_name ?? null,
                    'last_name' => $this->student->user->last_name ?? null,
                ];
            }),
            'section_subject' => $this->whenLoaded('sectionSubject', function () {
                return [
                    'id' => $this->sectionSubject->id,
                    'subject_name' => $this->sectionSubject->subject->subject_name ?? null,
                ];
            }),
        ];
    }
}
