<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAttendanceRecordRequest extends FormRequest
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
            'id' => 'required_without:grades|sometimes|required|uuid|exists:attendance_records,id',
            'status' => 'required_without:grades|sometimes|required|in:present,late,absent',
            'rating' => 'required_without:grades|sometimes|required|numeric|between:0,100',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.attendance_record_id' => 'required_with:grades|exists:attendance_records,id',
            'grades.*.rating' => 'required_with:grades|numeric|between:0,100',
            'grades.*.status' => 'required_with:grades|in:present,late,absent',
        ];
    }
}
