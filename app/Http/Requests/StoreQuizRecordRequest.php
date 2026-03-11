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
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|between:1,3',
            'quiz_number' => 'required|integer|min:1',
            'quiz_title' => 'nullable|string|max:150',
            'grades' => 'required|array|min:1',
            'grades.*.student_id' => 'required|uuid|exists:students,student_id',
            'grades.*.rating' => 'required|numeric|between:0,100',
        ];
    }
}
