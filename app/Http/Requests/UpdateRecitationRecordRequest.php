<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRecitationRecordRequest extends FormRequest
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
            'type' => 'required|in:recitation',
            'id' => 'required_without:grades|sometimes|required|exists:recitation_records,id',
            'rating' => 'required_without:grades|sometimes|numeric|between:0,100',
            'grades' => 'sometimes|required|array|min:1',
            'grades.*.recitation_record_id' => 'required_with:grades|exists:recitation_records,id',
            'grades.*.rating' => 'required_with:grades|numeric|between:0,100',
        ];
    }
}
