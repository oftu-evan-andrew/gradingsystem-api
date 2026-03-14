<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePeriodicGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:periodic_grade',
            'student_id' => 'required_without:grades|sometimes|required|uuid|exists:students,student_id',
            'class_standing_id' => 'required|uuid|exists:class_standings,id',
            'grading_period' => 'required|integer|between:1,3',
            'periodic_grade' => 'required_without:grades|sometimes|required|numeric|between:1.00,5.00',
            'status' => 'required|in:draft,submitted,finalized',
            'submitted_at' => 'nullable|date',
            'submitted_by' => 'nullable|uuid|exists:professors,professor_id',
            'last_modified_by' => 'nullable|uuid|exists:professors,professor_id',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.student_id' => 'required_with:grades|uuid|exists:students,student_id',
            'grades.*.periodic_grade' => 'required_with:grades|numeric|between:1.00,5.00',
            'grades.*.status' => 'required_with:grades|in:draft,submitted,finalized',
        ];
    }
}
