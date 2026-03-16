<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentFinalGradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'section_subject_id' => $this->section_subject_id,
            'final_grade' => $this->final_grade !== null ? (float) $this->final_grade : null,
            'status' => $this->status,
            'submitted_at' => $this->submitted_at?->toISOString(),
            'submitted_by' => $this->submitted_by,
            'last_modified_by' => $this->last_modified_by,
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
                    'subject' => $this->sectionSubject->subject ? [
                        'id' => $this->sectionSubject->subject->id,
                        'subject_name' => $this->sectionSubject->subject->subject_name,
                        'subject_code' => $this->sectionSubject->subject->subject_code,
                    ] : null,
                ];
            }),
        ];
    }
}
