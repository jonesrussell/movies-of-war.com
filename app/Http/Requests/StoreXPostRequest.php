<?php

namespace App\Http\Requests;

use App\Models\XPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreXPostRequest extends FormRequest
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
            'content' => [
                'required_without:thread_parts',
                'nullable',
                'string',
                'max:'.XPost::MAX_TWEET_LENGTH,
            ],
            'thread_parts' => ['nullable', 'array', 'max:25'],
            'thread_parts.*' => ['required', 'string', 'max:'.XPost::MAX_TWEET_LENGTH],
            'media_urls' => ['nullable', 'array', 'max:4'],
            'media_urls.*' => ['required', 'string'],
            'status' => [
                'sometimes',
                Rule::in([
                    XPost::STATUS_DRAFT,
                    XPost::STATUS_SCHEDULED,
                ]),
            ],
            'scheduled_for' => [
                'required_if:status,'.XPost::STATUS_SCHEDULED,
                'nullable',
                'date',
                function ($attribute, $value, $fail) {
                    if ($value && \Carbon\Carbon::parse($value)->lte(now()->addMinute())) {
                        $fail('The scheduled time must be at least 1 minute in the future.');
                    }
                },
            ],
            'publish_immediately' => ['sometimes', 'boolean'],
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
            'content.required_without' => 'Either main content or thread parts must be provided.',
            'content.max' => 'Tweet content cannot exceed '.XPost::MAX_TWEET_LENGTH.' characters.',
            'thread_parts.max' => 'A thread cannot have more than 25 tweets.',
            'thread_parts.*.max' => 'Each tweet in the thread cannot exceed '.XPost::MAX_TWEET_LENGTH.' characters.',
            'media_urls.max' => 'You can attach a maximum of 4 media files per post.',
            'scheduled_for.required_if' => 'A scheduled post must have a publish time.',
            'scheduled_for.0' => 'The scheduled time must be at least 1 minute in the future.',
        ];
    }
}
