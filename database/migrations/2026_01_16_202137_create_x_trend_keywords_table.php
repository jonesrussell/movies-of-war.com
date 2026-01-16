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
        Schema::create('x_trend_keywords', function (Blueprint $table) {
            $table->id();
            $table->string('keyword')->index();
            $table->enum('type', ['hashtag', 'keyword', 'phrase'])->default('keyword');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_searched_at')->nullable();
            $table->unsignedInteger('match_count')->default(0);
            $table->timestamps();

            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('x_trend_keywords');
    }
};
