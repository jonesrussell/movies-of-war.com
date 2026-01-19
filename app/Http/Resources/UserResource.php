<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->when(
                $this->shouldShowEmail($request),
                $this->email
            ),
            'is_admin' => $this->is_admin,
            'avatar' => $this->avatar ?? null,
            'email_verified_at' => $this->when(
                $this->email_verified_at !== null,
                fn () => $this->email_verified_at->toIso8601String()
            ),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }

    /**
     * Determine if email should be visible.
     * Email is visible if the request user is the same user or is an admin.
     */
    protected function shouldShowEmail(Request $request): bool
    {
        $authUser = $request->user();

        if (! $authUser) {
            return false;
        }

        return $authUser->id === $this->id || $authUser->is_admin;
    }
}
