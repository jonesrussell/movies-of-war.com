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
        Schema::create('x_curated_posts', function (Blueprint $table) {
            $table->id();
            $table->string('tweet_id')->unique()->index();
            $table->string('author_username');
            $table->text('content');
            $table->json('media_urls')->nullable();
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('retweet_count')->default(0);
            $table->timestamp('discovered_at')->useCurrent()->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('x_curated_posts');
    }
};
