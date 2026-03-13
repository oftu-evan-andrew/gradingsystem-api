<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAttendanceRecordRequest extends FormRequest
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
            'type' => 'required|in:attendance',
            'professor_id' => 'sometimes|required|uuid|exists:professors,professor_id',
            'student_id' => 'required_without:grades|sometimes|required|uuid|exists:students,student_id',
            'section_subject_id' => 'required|uuid|exists:section_subjects,id',
            'grading_period' => 'required|integer|between:1,3',
            'attendance_date' => 'required_without:grades|sometimes|required|date',
            'status' => 'required_without:grades|sometimes|required|in:present,late,absent', 
            'rating' => 'required_without:grades|sometimes|required|numeric|between:0,100',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.student_id' => 'required_with:grades|uuid|exists:students,student_id',
            'grades.*.rating' => 'required_with:grades|numeric|between:0,100',
            'grades.*.status' => 'required_with:grades|in:present,late,absent'
        ];
    }
}
