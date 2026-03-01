<?php

declare(strict_types=1);

namespace App\Enums;

enum SlotStrategy: string
{
    case HighestRated = 'highest_rated';
    case Upcoming = 'upcoming';
}
