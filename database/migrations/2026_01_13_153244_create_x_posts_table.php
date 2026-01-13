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
        Schema::create('x_posts', function (Blueprint $table) {
            $table->id();
            $table->text('content')->nullable()->comment('Main tweet text (max 280 chars)');
            $table->json('thread_parts')->nullable()->comment('Array of additional tweets for threads');
            $table->json('media_urls')->nullable()->comment('Array of local file paths or external URLs to attach');
            $table->enum('status', ['draft', 'scheduled', 'published', 'failed', 'cancelled'])->default('draft');
            $table->timestamp('scheduled_for')->nullable()->comment('When to publish the post');
            $table->timestamp('published_at')->nullable()->comment('When the post was actually published');
            $table->string('x_post_id')->nullable()->comment('The tweet ID returned by X API after publish');
            $table->text('error_message')->nullable()->comment('Error message if publishing failed');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->timestamps();

            // Indexes for performance
            $table->index(['status', 'scheduled_for']);
            $table->index('published_at');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('x_posts');
    }
};
