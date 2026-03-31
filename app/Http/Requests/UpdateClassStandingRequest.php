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
            'section_subject_id' => 'sometimes|uuid|exists:section_subjects,id',
            'grading_period' => 'sometimes|integer|between:1,3',
            'status' => 'nullable|in:draft,submitted,finalized',
            'attendance_score' => 'nullable|numeric|between:0,100',
            'recitation_score' => 'nullable|numeric|between:0,100',
            'quiz_score' => 'nullable|numeric|between:0,100',
            'project_score' => 'nullable|numeric|between:0,100',
            'major_exam_pts' => 'nullable|numeric|min:0',
            'major_exam_items' => 'nullable|numeric|min:1',
            'major_exam_score' => 'nullable|numeric|between:0,100',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.class_standing_id' => 'nullable|uuid|exists:class_standings,id',
            'grades.*.student_id' => 'nullable|uuid|exists:students,student_id',
            'grades.*.section_subject_id' => 'nullable|uuid|exists:section_subjects,id',
            'grades.*.grading_period' => 'nullable|integer|between:1,3',
            'grades.*.status' => 'nullable|in:draft,submitted,finalized',
            'grades.*.attendance_score' => 'nullable|numeric|between:0,100',
            'grades.*.recitation_score' => 'nullable|numeric|between:0,100',
            'grades.*.quiz_score' => 'nullable|numeric|between:0,100',
            'grades.*.project_score' => 'nullable|numeric|between:0,100',
            'grades.*.major_exam_pts' => 'nullable|numeric|min:0',
            'grades.*.major_exam_items' => 'nullable|numeric|min:1',
            'grades.*.major_exam_score' => 'nullable|numeric|between:0,100',
        ];
    }
}
