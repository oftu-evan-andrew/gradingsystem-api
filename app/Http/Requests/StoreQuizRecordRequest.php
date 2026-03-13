<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:quiz',
            'professor_id' => 'sometimes|required|uuid|exists:professors,professor_id',
            'student_id' => 'required_without:grades|sometimes|required|uuid|exists:students,student_id',
            'quiz_number' => 'required_without:grades|sometimes|required|integer|min:1',
            'quiz_title' => 'nullable|string|max:150',
            'rating' => 'required_without:grades|sometimes|required|numeric|between:0,100',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|between:1,3',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.student_id' => 'required_with:grades|uuid|exists:students,student_id',
            'grades.*.rating' => 'required_with:grades|numeric|between:0,100',
        ];
    }
}
