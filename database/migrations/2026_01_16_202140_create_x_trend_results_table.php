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
        Schema::create('x_trend_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trend_keyword_id')->constrained('x_trend_keywords')->onDelete('cascade');
            $table->string('tweet_id')->index();
            $table->string('author_username');
            $table->text('content');
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('retweet_count')->default(0);
            $table->unsignedInteger('reply_count')->default(0);
            $table->timestamp('created_at')->nullable()->comment('Tweet creation time');
            $table->string('url')->nullable();
            $table->boolean('is_actioned')->default(false)->index();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            $table->index(['trend_keyword_id', 'created_at']);
            $table->index('is_actioned');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('x_trend_results');
    }
};
