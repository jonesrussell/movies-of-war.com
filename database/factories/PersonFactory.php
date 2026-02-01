<?php

namespace Database\Factories;

use App\Models\Person;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Person>
 */
class PersonFactory extends Factory
{
    protected $model = Person::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();
        $tmdbId = fake()->unique()->numberBetween(1, 999999);

        return [
            'tmdb_id' => $tmdbId,
            'name' => $name,
            'slug' => Str::slug($name).'-'.$tmdbId,
            'profile_path' => '/'.fake()->uuid().'.jpg',
            'biography' => fake()->paragraphs(3, true),
            'birthday' => fake()->dateTimeBetween('-80 years', '-20 years'),
            'deathday' => null,
            'place_of_birth' => fake()->city().', '.fake()->country(),
            'also_known_as' => [],
            'tmdb_last_synced_at' => now(),
        ];
    }
}
