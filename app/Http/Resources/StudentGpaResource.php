<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentGpaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'school_year' => $this->school_year,
            'semester' => $this->semester,
            'cumulative_gpa' => $this->cumulative_gpa !== null ? (float) $this->cumulative_gpa : null,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'student' => $this->whenLoaded('student', function () {
                return [
                    'id' => $this->student->student_id,
                    'first_name' => $this->student->user->first_name ?? null,
                    'last_name' => $this->student->user->last_name ?? null,
                ];
            }),
        ];
    }
}
