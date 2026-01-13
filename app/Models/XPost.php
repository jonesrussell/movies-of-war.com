<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class XPost extends Model
{
    /** @use HasFactory<\Database\Factories\XPostFactory> */
    use HasFactory;

    const STATUS_DRAFT = 'draft';

    const STATUS_SCHEDULED = 'scheduled';

    const STATUS_PUBLISHED = 'published';

    const STATUS_FAILED = 'failed';

    const STATUS_CANCELLED = 'cancelled';

    const MAX_TWEET_LENGTH = 280;

    protected $fillable = [
        'content',
        'thread_parts',
        'media_urls',
        'status',
        'scheduled_for',
        'published_at',
        'x_post_id',
        'error_message',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'thread_parts' => 'array',
            'media_urls' => 'array',
            'scheduled_for' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_SCHEDULED);
    }

    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeReadyToPublish($query)
    {
        return $query->scheduled()
            ->where('scheduled_for', '<=', now());
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_SCHEDULED;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function canPublish(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_SCHEDULED, self::STATUS_FAILED]);
    }

    public function hasThread(): bool
    {
        return ! empty($this->thread_parts);
    }

    public function hasMedia(): bool
    {
        return ! empty($this->media_urls);
    }

    public function getFullThreadContent(): array
    {
        $parts = [$this->content];

        if ($this->hasThread()) {
            $parts = array_merge($parts, $this->thread_parts);
        }

        return array_filter($parts);
    }

    public function markAsScheduled(\DateTime $scheduledFor): void
    {
        $this->update([
            'status' => self::STATUS_SCHEDULED,
            'scheduled_for' => $scheduledFor,
        ]);
    }

    public function markAsPublished(string $xPostId): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
            'x_post_id' => $xPostId,
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function cancel(): void
    {
        if ($this->isScheduled()) {
            $this->update(['status' => self::STATUS_CANCELLED]);
        }
    }
}
