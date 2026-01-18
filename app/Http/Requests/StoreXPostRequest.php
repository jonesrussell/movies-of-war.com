<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\XPostStatus;
use App\Models\XPost;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreXPostRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', XPost::class);
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
                Rule::enum(XPostStatus::class)->only([
                    XPostStatus::Draft,
                    XPostStatus::Scheduled,
                ]),
            ],
            'scheduled_for' => [
                'required_if:status,'.XPostStatus::Scheduled->value,
                'nullable',
                'date',
                'after:+1 minute',
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
            'scheduled_for.after' => 'The scheduled time must be in the future.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'publish_immediately' => $this->boolean('publish_immediately'),
        ]);
    }
}
