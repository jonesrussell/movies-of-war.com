<?php

declare(strict_types=1);

namespace App\Enums;

enum SlotType: string
{
    case Hero = 'hero';
    case PickOfWeek = 'pick_of_week';

    public function label(): string
    {
        return match ($this) {
            self::Hero => 'Hero',
            self::PickOfWeek => 'Pick of the Week',
        };
    }

    public function strategy(): SlotStrategy
    {
        return match ($this) {
            self::Hero => SlotStrategy::Upcoming,
            self::PickOfWeek => SlotStrategy::HighestRated,
        };
    }
}
