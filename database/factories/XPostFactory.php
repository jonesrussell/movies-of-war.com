<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\XPost;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\XPost>
 */
class XPostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'content' => $this->faker->text(280),
            'status' => XPost::STATUS_DRAFT,
            'user_id' => User::factory(),
        ];
    }

    /**
     * Indicate that the post is scheduled for future publication.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => XPost::STATUS_SCHEDULED,
            'scheduled_for' => now()->addHours($this->faker->numberBetween(1, 48)),
        ]);
    }

    /**
     * Indicate that the post is scheduled for past time (ready to publish).
     */
    public function readyToPublish(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => XPost::STATUS_SCHEDULED,
            'scheduled_for' => now()->subMinutes($this->faker->numberBetween(1, 30)),
        ]);
    }

    /**
     * Indicate that the post has been published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => XPost::STATUS_PUBLISHED,
            'published_at' => now()->subHours($this->faker->numberBetween(1, 24)),
            'x_post_id' => (string) $this->faker->numerify('####################'),
        ]);
    }

    /**
     * Indicate that the post has failed to publish.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => XPost::STATUS_FAILED,
            'error_message' => 'Failed to connect to X API',
        ]);
    }

    /**
     * Indicate that the post has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => XPost::STATUS_CANCELLED,
        ]);
    }

    /**
     * Indicate that the post is a thread with multiple parts.
     */
    public function withThread(): static
    {
        return $this->state(fn (array $attributes) => [
            'thread_parts' => [
                $this->faker->text(280),
                $this->faker->text(280),
                $this->faker->text(280),
            ],
        ]);
    }

    /**
     * Indicate that the post has media attachments.
     */
    public function withMedia(): static
    {
        return $this->state(fn (array $attributes) => [
            'media_urls' => [
                'posters/image1.jpg',
                'posters/image2.jpg',
            ],
        ]);
    }
}
