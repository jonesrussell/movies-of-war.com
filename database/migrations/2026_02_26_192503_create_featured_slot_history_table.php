<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_slot_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->nullable()->constrained()->nullOnDelete();
            $table->string('slot')->comment('hero, pick_of_week');
            $table->string('selection_method')->comment('auto, manual');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['slot', 'started_at']);
            $table->index('movie_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_slot_history');
    }
};
