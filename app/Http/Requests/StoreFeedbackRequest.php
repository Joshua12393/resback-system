<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedbackRequest extends FormRequest
{
    /**
     * Determine if the authenticated user is authorized to make this request.
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
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')->where(
                    fn ($query) => $query->where('is_active', true)->where('name', 'CCIS')
                ),
            ],
            'content' => ['required', 'string', 'min:10', 'max:2000'],
        ];
    }

    /**
     * Custom error messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.required' => 'Please write your feedback before submitting.',
            'content.min' => 'Your feedback must be at least 10 characters long.',
            'content.max' => 'Your feedback must not exceed 2,000 characters.',
            'category_id.required' => 'Please select the department or campus area for your feedback.',
            'category_id.exists' => 'Feedback submissions are currently available for CCIS only.',
        ];
    }
}
