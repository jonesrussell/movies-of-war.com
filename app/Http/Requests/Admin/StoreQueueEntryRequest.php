<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Enums\MovieStatus;
use App\Enums\SlotType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreQueueEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Admin middleware handles authorization
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'movie_id' => [
                'required',
                Rule::exists('movies', 'id')->where('status', MovieStatus::Published->value),
            ],
            'slot' => ['required', Rule::enum(SlotType::class)],
            'position' => ['required', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'movie_id.exists' => 'The selected movie must be published.',
            'slot.enum' => 'The slot must be either hero or pick_of_week.',
            'position.min' => 'The position must be at least 1.',
        ];
    }
}
