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
        Schema::create('x_auto_reply_rules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->json('trigger_keywords');
            $table->enum('trigger_type', ['mention', 'hashtag', 'keyword'])->default('mention');
            $table->text('reply_template');
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('priority')->default(0)->index();
            $table->timestamps();

            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('x_auto_reply_rules');
    }
};
