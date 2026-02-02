<?php

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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->decimal('rating', 2, 1);
            $table->string('title')->nullable();
            $table->text('content');
            $table->boolean('has_spoilers')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('helpful_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'movie_id']);
            $table->index(['movie_id', 'created_at']);
            $table->index(['movie_id', 'helpful_count']);
            $table->index('rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
