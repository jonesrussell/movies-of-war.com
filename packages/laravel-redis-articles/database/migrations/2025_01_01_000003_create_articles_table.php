<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_source_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('excerpt')->nullable();
            $table->longText('content')->nullable();
            $table->string('url')->unique();
            $table->string('external_id')->nullable()->unique();
            $table->string('image_url')->nullable();
            $table->string('author')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('crawled_at')->nullable();
            $table->json('metadata')->nullable();
            $table->integer('view_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['news_source_id']);
        });

        // Only add fulltext index if database driver supports it
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('articles', function (Blueprint $table) {
                $table->fullText(['title', 'excerpt', 'content']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
