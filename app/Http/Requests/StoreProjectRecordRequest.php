<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreProjectRecordRequest extends FormRequest
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
            'type' => 'required|in:project',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|between:1,3',
            'project_number' => 'required|integer|min:1',
            'project_title' => 'nullable|string|max:150',
            'grades' => 'required|array|min:1',
            'grades.*.student_id' => 'required|uuid|exists:students,student_id',
            'grades.*.rating' => 'required|numeric|between:0,100',
        ];
    }
}
