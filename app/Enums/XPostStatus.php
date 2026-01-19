<?php

declare(strict_types=1);

namespace App\Enums;

enum XPostStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Published = 'published';
    case Failed = 'failed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Scheduled => 'Scheduled',
            self::Published => 'Published',
            self::Failed => 'Failed',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'zinc',
            self::Scheduled => 'blue',
            self::Published => 'green',
            self::Failed => 'red',
            self::Cancelled => 'yellow',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil',
            self::Scheduled => 'clock',
            self::Published => 'check-circle',
            self::Failed => 'x-circle',
            self::Cancelled => 'ban',
        };
    }

    public function canPublish(): bool
    {
        return in_array($this, [self::Draft, self::Scheduled, self::Failed], true);
    }

    public function canSchedule(): bool
    {
        return in_array($this, [self::Draft, self::Failed], true);
    }

    public function canCancel(): bool
    {
        return $this === self::Scheduled;
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::Draft, self::Scheduled, self::Failed], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Published, self::Cancelled], true);
    }
}
