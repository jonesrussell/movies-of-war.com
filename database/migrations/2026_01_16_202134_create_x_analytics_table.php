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
        Schema::create('x_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('x_post_id')->nullable()->constrained('x_posts')->onDelete('cascade');
            $table->string('tweet_id')->index();
            $table->unsignedInteger('impressions')->default(0);
            $table->unsignedInteger('likes')->default(0);
            $table->unsignedInteger('retweets')->default(0);
            $table->unsignedInteger('replies')->default(0);
            $table->unsignedInteger('bookmarks')->default(0);
            $table->unsignedInteger('quotes')->default(0);
            $table->unsignedInteger('profile_clicks')->default(0);
            $table->unsignedInteger('link_clicks')->default(0);
            $table->timestamp('recorded_at')->useCurrent();
            $table->timestamps();

            $table->index(['x_post_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('x_analytics');
    }
};
