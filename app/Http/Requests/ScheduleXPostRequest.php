<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ScheduleXPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()?->is_admin ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'scheduled_for' => [
                'required',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && \Carbon\Carbon::parse($value)->lte(now()->addMinute())) {
                        $fail('The scheduled time must be at least 1 minute in the future.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'scheduled_for.required' => 'Please provide a scheduled publish time.',
            'scheduled_for.date' => 'The scheduled time must be a valid date.',
            'scheduled_for.0' => 'The scheduled time must be at least 1 minute in the future.',
        ];
    }
}
