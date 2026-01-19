<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MovieStatus;
use App\Models\Movie;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreMovieRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Movie::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'release_year' => ['required', 'integer', 'min:1900', 'max:2100'],
            'release_date' => ['nullable', 'date'],
            'synopsis' => ['required', 'string', 'max:10000'],
            'runtime' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'country' => ['nullable', 'string', 'max:100'],
            'conflict' => ['nullable', 'string', 'max:100'],
            'poster_url' => ['nullable', 'url:https'],
            'trailer_url' => ['nullable', 'url:https'],
            'imdb_id' => ['nullable', 'string', 'max:20', 'regex:/^tt\d{7,}$/'],
            'is_upcoming' => ['boolean'],
            'status' => ['sometimes', Rule::enum(MovieStatus::class)],
            'tags' => ['array'],
            'tags.*' => ['integer', 'exists:tags,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'imdb_id.regex' => 'IMDB ID must be in format tt1234567 (e.g., tt0120815).',
            'title.required' => 'A movie title is required.',
            'release_year.required' => 'The release year is required.',
            'synopsis.required' => 'A synopsis is required for the movie.',
            'runtime.max' => 'Runtime cannot exceed 24 hours (1440 minutes).',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'imdb_id' => 'IMDB ID',
            'poster_url' => 'poster URL',
            'trailer_url' => 'trailer URL',
            'is_upcoming' => 'upcoming status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_upcoming' => $this->boolean('is_upcoming'),
        ]);
    }

    /**
     * Get the validated data with defaults applied.
     *
     * @return array<string, mixed>
     */
    public function validatedWithDefaults(): array
    {
        $validated = $this->validated();
        $validated['status'] ??= MovieStatus::Draft;

        return $validated;
    }
}
