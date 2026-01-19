<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\XPostStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\XPost
 */
class XPostResource extends JsonResource
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
            'content' => $this->content,
            'thread_parts' => $this->thread_parts,
            'media_urls' => $this->media_urls,
            'status' => $this->status->value,
            'scheduled_for' => $this->scheduled_for?->toIso8601String(),
            'published_at' => $this->published_at?->toIso8601String(),
            'x_post_id' => $this->x_post_id,
            'error_message' => $this->error_message,
            'user_id' => $this->user_id,
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'user' => new UserResource($this->whenLoaded('user')),

            // Computed status helpers
            'is_draft' => $this->status === XPostStatus::Draft,
            'is_scheduled' => $this->status === XPostStatus::Scheduled,
            'is_published' => $this->status === XPostStatus::Published,
            'is_failed' => $this->status === XPostStatus::Failed,
            'is_cancelled' => $this->status === XPostStatus::Cancelled,

            // Computed capability flags
            'can_publish' => $this->canPublish(),
            'can_schedule' => $this->canSchedule(),
            'can_cancel' => $this->canCancel(),
            'can_edit' => $this->canEdit(),

            // Content helpers
            'has_thread' => $this->hasThread(),
            'has_media' => $this->hasMedia(),
            'thread_count' => $this->hasThread() ? count($this->thread_parts) + 1 : 1,
        ];
    }
}
