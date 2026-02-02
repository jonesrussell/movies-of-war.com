<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\Review;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateReviewRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $review = $this->route('review');

        return $review instanceof Review && $this->user()?->can('update', $review);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'numeric', 'min:0', 'max:4'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:50'],
            'has_spoilers' => ['sometimes', 'boolean'],
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
            'rating.required' => 'Please select a star rating.',
            'rating.min' => 'Rating must be at least 0 stars.',
            'rating.max' => 'Rating cannot exceed 4 stars.',
            'content.required' => 'Please write your review.',
            'content.min' => 'Your review must be at least 50 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'has_spoilers' => $this->boolean('has_spoilers'),
        ]);
    }
}
