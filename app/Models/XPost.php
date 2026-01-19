<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\XPostStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property-read int $id
 * @property string|null $content
 * @property array<string>|null $thread_parts
 * @property array<string>|null $media_urls
 * @property XPostStatus $status
 * @property \Illuminate\Support\Carbon|null $scheduled_for
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property string|null $x_post_id
 * @property string|null $error_message
 * @property int|null $user_id
 */
class XPost extends Model
{
    /** @use HasFactory<\Database\Factories\XPostFactory> */
    use HasFactory;

    /**
     * @deprecated Use XPostStatus::Draft instead
     */
    public const STATUS_DRAFT = 'draft';

    /**
     * @deprecated Use XPostStatus::Scheduled instead
     */
    public const STATUS_SCHEDULED = 'scheduled';

    /**
     * @deprecated Use XPostStatus::Published instead
     */
    public const STATUS_PUBLISHED = 'published';

    /**
     * @deprecated Use XPostStatus::Failed instead
     */
    public const STATUS_FAILED = 'failed';

    /**
     * @deprecated Use XPostStatus::Cancelled instead
     */
    public const STATUS_CANCELLED = 'cancelled';

    public const MAX_TWEET_LENGTH = 280;

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
            'status' => XPostStatus::class,
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(XAnalytics::class);
    }

    public function latestAnalytics(): HasOne
    {
        return $this->hasOne(XAnalytics::class)->latest('recorded_at');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    /**
     * @param  Builder<XPost>  $query
     * @return Builder<XPost>
     */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where('status', XPostStatus::Draft);
    }

    /**
     * @param  Builder<XPost>  $query
     * @return Builder<XPost>
     */
    public function scopeScheduled(Builder $query): Builder
    {
        return $query->where('status', XPostStatus::Scheduled);
    }

    /**
     * @param  Builder<XPost>  $query
     * @return Builder<XPost>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', XPostStatus::Published);
    }

    /**
     * @param  Builder<XPost>  $query
     * @return Builder<XPost>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', XPostStatus::Failed);
    }

    /**
     * @param  Builder<XPost>  $query
     * @return Builder<XPost>
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('status', XPostStatus::Cancelled);
    }

    /**
     * @param  Builder<XPost>  $query
     * @return Builder<XPost>
     */
    public function scopeReadyToPublish(Builder $query): Builder
    {
        return $query->scheduled()
            ->where('scheduled_for', '<=', now());
    }

    // =========================================================================
    // State Checks
    // =========================================================================

    public function isScheduled(): bool
    {
        return $this->status === XPostStatus::Scheduled;
    }

    public function isPublished(): bool
    {
        return $this->status === XPostStatus::Published;
    }

    public function isDraft(): bool
    {
        return $this->status === XPostStatus::Draft;
    }

    public function hasFailed(): bool
    {
        return $this->status === XPostStatus::Failed;
    }

    public function isCancelled(): bool
    {
        return $this->status === XPostStatus::Cancelled;
    }

    public function canPublish(): bool
    {
        return $this->status->canPublish();
    }

    public function canSchedule(): bool
    {
        return $this->status->canSchedule();
    }

    public function canCancel(): bool
    {
        return $this->status->canCancel();
    }

    public function canEdit(): bool
    {
        return $this->status->canEdit();
    }

    // =========================================================================
    // Content Helpers
    // =========================================================================

    public function hasThread(): bool
    {
        return ! empty($this->thread_parts);
    }

    public function hasMedia(): bool
    {
        return ! empty($this->media_urls);
    }

    /**
     * @return array<string>
     */
    public function getFullThreadContent(): array
    {
        $parts = [$this->content];

        if ($this->hasThread()) {
            $parts = array_merge($parts, $this->thread_parts);
        }

        return array_filter($parts);
    }

    // =========================================================================
    // State Transitions
    // =========================================================================

    public function markAsScheduled(\DateTimeInterface $scheduledFor): void
    {
        $this->update([
            'status' => XPostStatus::Scheduled,
            'scheduled_for' => $scheduledFor,
        ]);
    }

    public function markAsPublished(string $xPostId): void
    {
        $this->update([
            'status' => XPostStatus::Published,
            'published_at' => now(),
            'x_post_id' => $xPostId,
            'error_message' => null,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => XPostStatus::Failed,
            'error_message' => $errorMessage,
        ]);
    }

    public function cancel(): void
    {
        if ($this->canCancel()) {
            $this->update(['status' => XPostStatus::Cancelled]);
        }
    }
}
