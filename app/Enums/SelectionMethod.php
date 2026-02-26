<?php

declare(strict_types=1);

namespace App\Enums;

enum SelectionMethod: string
{
    case Auto = 'auto';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Auto => 'Auto',
            self::Manual => 'Manual',
        };
    }
}
