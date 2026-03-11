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
            'quiz_title' => 'nullable|string|max:150',
            'grades' => 'required|array|min:1',
            'grades.*.quiz_record_id' => 'required|exists:quiz_records,id',
            'grades.*.rating' => 'required|numeric|between:0,100',
        ];
    }
}
