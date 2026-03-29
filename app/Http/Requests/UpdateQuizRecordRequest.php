<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRecordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => 'required|in:quiz',
            'id' => 'required_without:grades|sometimes|required|exists:quiz_records,id',
            'quiz_number' => 'nullable|integer|min:1',
            'quiz_title' => 'nullable|string|max:150',
            'pts' => 'nullable|numeric|min:0',
            'items' => 'nullable|numeric|min:1',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.quiz_record_id' => 'required_with:grades|exists:quiz_records,id',
            'grades.*.pts' => 'nullable|numeric|min:0',
            'grades.*.items' => 'nullable|numeric|min:1'
        ];
    }
}
