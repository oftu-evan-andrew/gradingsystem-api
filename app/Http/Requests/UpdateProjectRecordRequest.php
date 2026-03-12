<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectRecordRequest extends FormRequest
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
            'project_title' => 'nullable|string|max:150',
            'grades' => 'required|array|min:1',
            'grades.*.project_record_id' => 'required|exists:project_records,id',
            'grades.*.rating' => 'required|numeric|between:0,100',
        ];
    }
}
