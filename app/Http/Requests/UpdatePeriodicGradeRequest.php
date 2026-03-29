<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePeriodicGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:periodic_grade',
            'id' => 'required_without:grades|sometimes|required|exists:periodic_grades,id',
            'periodic_grade' => 'nullable|numeric|between:1.00,5.00',
            'status' => 'in:draft,submitted,finalized',
            'submitted_at' => 'nullable|date',
            'submitted_by' => 'nullable|uuid|exists:professors,professor_id',
            'last_modified_by' => 'nullable|uuid|exists:professors,professor_id',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.periodic_grade_id' => 'required_with:grades|exists:periodic_grades,id',
            'grades.*.periodic_grade' => 'nullable|numeric|between:1.00,5.00',
            'grades.*.status' => 'in:draft,submitted,finalized',
        ];
    }
}
