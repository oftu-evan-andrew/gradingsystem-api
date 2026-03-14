<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PeriodicGradeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'class_standing_id' => $this->class_standing_id,
            'grading_period' => $this->grading_period,
            'periodic_grade' => $this->periodic_grade !== null ? (float) $this->periodic_grade : null,
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
            'class_standing' => $this->whenLoaded('classStanding', function () {
                return [
                    'id' => $this->classStanding->id,
                    'grading_period' => $this->classStanding->grading_period,
                ];
            }),
        ];
    }
}
