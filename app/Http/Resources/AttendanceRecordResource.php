<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceRecordResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'section_subject_id' => $this->section_subject_id,
            'professor_id' => $this->professor_id,
            'grading_period' => $this->grading_period,
            'attendance_date' => $this->attendance_date?->toISOString(),
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
