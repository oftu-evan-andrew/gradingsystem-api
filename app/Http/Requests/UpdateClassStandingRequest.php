<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassStandingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:class_standing',
            'id' => 'required_without:grades|sometimes|required|exists:class_standings,id',
            'status' => 'nullable|in:draft,submitted,finalized',
            'attendance_score' => 'nullable|numeric|between:0,100',
            'recitation_score' => 'nullable|numeric|between:0,100',
            'quiz_score' => 'nullable|numeric|between:0,100',
            'project_score' => 'nullable|numeric|between:0,100',
            'major_exam_pts' => 'nullable|numeric|min:0',
            'major_exam_items' => 'nullable|numeric|min:1',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.class_standing_id' => 'required_with:grades|exists:class_standings,id',
            'grades.*.status' => 'nullable|in:draft,submitted,finalized',
            'grades.*.attendance_score' => 'nullable|numeric|between:0,100',
            'grades.*.recitation_score' => 'nullable|numeric|between:0,100',
            'grades.*.quiz_score' => 'nullable|numeric|between:0,100',
            'grades.*.project_score' => 'nullable|numeric|between:0,100',
            'grades.*.major_exam_pts' => 'nullable|numeric|min:0',
            'grades.*.major_exam_items' => 'nullable|numeric|min:1',
        ];
    }
}
