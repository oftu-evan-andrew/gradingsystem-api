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
            'quiz_title' => 'nullable|string|max:150',
            'rating' => 'required_without:grades|sometimes|numeric|between:0,100',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.quiz_record_id' => 'required_with:grades|exists:quiz_records,id',
            'grades.*.rating' => 'required_with:grades|numeric|between:0,100',
        ];
    }
}
