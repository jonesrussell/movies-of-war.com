<?php

declare(strict_types=1);

namespace App\Enums;

enum TagType: string
{
    case Genre = 'genre';
    case Theme = 'theme';
    case Era = 'era';

    public function label(): string
    {
        return match ($this) {
            self::Genre => 'Genre',
            self::Theme => 'Theme',
            self::Era => 'Era',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Genre => 'blue',
            self::Theme => 'purple',
            self::Era => 'amber',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Genre => 'Film genre classification (e.g., Action, Drama)',
            self::Theme => 'Thematic elements (e.g., Heroism, Sacrifice)',
            self::Era => 'Historical period (e.g., WWII, Vietnam)',
        };
    }
}
