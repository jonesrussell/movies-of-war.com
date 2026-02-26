<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeaturedSlotQueue>
 */
class FeaturedSlotQueueFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'slot' => fake()->randomElement(['hero', 'pick_of_week']),
            'position' => 1,
            'selection_method' => 'auto',
            'scheduled_for' => now()->next('Sunday'),
        ];
    }

    public function manual(): static
    {
        return $this->state(fn (array $attributes) => [
            'selection_method' => 'manual',
        ]);
    }
}
