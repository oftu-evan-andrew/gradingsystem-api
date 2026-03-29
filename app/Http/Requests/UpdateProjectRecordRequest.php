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
            'id' => 'required_without:grades|sometimes|required|exists:project_records,id',
            'project_number' => 'nullable|integer|min:1',
            'project_title' => 'nullable|string|max:150',
            'pts' => 'nullable|numeric|min:0',
            'items' => 'nullable|numeric|min:1',
            'rating' => 'required_without:grades|sometimes|numeric|between:0,100',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.project_record_id' => 'required_with:grades|exists:project_records,id',
            'grades.*.rating' => 'required_with:grades|numeric|between:0,100',
        ];
    }
}
