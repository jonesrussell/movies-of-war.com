<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add composite indexes to movies table for common query patterns
        Schema::table('movies', function (Blueprint $table): void {
            // For published movies sorted by release date (homepage, browse page)
            $table->index(['status', 'release_date'], 'movies_status_release_date_index');

            // For filtering by conflict within published movies
            $table->index(['status', 'conflict'], 'movies_status_conflict_index');

            // For filtering by country within published movies
            $table->index(['status', 'country'], 'movies_status_country_index');

            // For upcoming movies queries
            $table->index(['status', 'is_upcoming', 'release_date'], 'movies_status_upcoming_release_date_index');
        });

        // Add composite indexes to x_posts table for common query patterns
        Schema::table('x_posts', function (Blueprint $table): void {
            // For finding ready-to-publish posts (scheduler)
            // Note: (status, scheduled_for) index already exists, but let's ensure it's optimal
            $table->index(['status', 'scheduled_for', 'id'], 'x_posts_ready_to_publish_index');
        });

        // Add composite indexes to tags table
        Schema::table('tags', function (Blueprint $table): void {
            // For filtering tags by type and sorting by name
            $table->index(['type', 'name'], 'tags_type_name_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table): void {
            $table->dropIndex('movies_status_release_date_index');
            $table->dropIndex('movies_status_conflict_index');
            $table->dropIndex('movies_status_country_index');
            $table->dropIndex('movies_status_upcoming_release_date_index');
        });

        Schema::table('x_posts', function (Blueprint $table): void {
            $table->dropIndex('x_posts_ready_to_publish_index');
        });

        Schema::table('tags', function (Blueprint $table): void {
            $table->dropIndex('tags_type_name_index');
        });
    }
};
