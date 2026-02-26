<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FeaturedSlotHistory>
 */
class FeaturedSlotHistoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'slot' => fake()->randomElement(['hero', 'pick_of_week']),
            'selection_method' => 'auto',
            'started_at' => now()->subWeek(),
            'ended_at' => now(),
        ];
    }

    public function current(): static
    {
        return $this->state(fn (array $attributes) => [
            'started_at' => now(),
            'ended_at' => null,
        ]);
    }
}
