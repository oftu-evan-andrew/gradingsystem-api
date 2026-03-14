<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClassStandingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'type' => 'required|in:class_standing',
            'professor_id' => 'sometimes|required|uuid|exists:professors,professor_id',
            'student_id' => 'required_without:grades|sometimes|required|uuid|exists:students,student_id',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|between:1,3',
            'attendance_score' => 'required_without:grades|sometimes|required|numeric|between:0,100',
            'recitation_score' => 'required_without:grades|sometimes|required|numeric|between:0,100',
            'quiz_score' => 'required_without:grades|sometimes|required|numeric|between:0,100',
            'project_score' => 'required_without:grades|sometimes|required|numeric|between:0,100',
            'major_exam_score' => 'required_without:grades|sometimes|required|numeric|between:0,100',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.student_id' => 'required_with:grades|uuid|exists:students,student_id',
            'grades.*.attendance_score' => 'required_with:grades|numeric|between:0,100',
            'grades.*.recitation_score' => 'required_with:grades|numeric|between:0,100',
            'grades.*.quiz_score' => 'required_with:grades|numeric|between:0,100',
            'grades.*.project_score' => 'required_with:grades|numeric|between:0,100',
            'grades.*.major_exam_score' => 'required_with:grades|numeric|between:0,100',
        ];
    }
}
