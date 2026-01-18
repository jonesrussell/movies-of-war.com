<?php

declare(strict_types=1);

namespace App\Enums;

enum MovieStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Published',
            self::Archived => 'Archived',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'yellow',
            self::Published => 'green',
            self::Archived => 'zinc',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Draft => 'pencil',
            self::Published => 'check-circle',
            self::Archived => 'archive',
        };
    }

    public function isVisible(): bool
    {
        return $this === self::Published;
    }

    public function canPublish(): bool
    {
        return $this === self::Draft;
    }

    public function canUnpublish(): bool
    {
        return $this === self::Published;
    }

    public function canArchive(): bool
    {
        return $this !== self::Archived;
    }

    public function canRestore(): bool
    {
        return $this === self::Archived;
    }
}
