<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStudentFinalGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'final_grade' => 'nullable|numeric|between:1.00,5.00',
            'status' => 'in:draft,submitted,finalized',
            'submitted_at' => 'nullable|date',
            'submitted_by' => 'nullable|uuid|exists:professors,professor_id',
            'last_modified_by' => 'nullable|uuid|exists:professors,professor_id',
        ];
    }
}
