<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('featured_slot_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained()->cascadeOnDelete();
            $table->string('slot')->comment('hero, pick_of_week');
            $table->unsignedInteger('position');
            $table->string('selection_method')->default('auto')->comment('auto, manual');
            $table->date('scheduled_for');
            $table->timestamps();

            $table->unique(['slot', 'position']);
            $table->index('movie_id');
            $table->index('scheduled_for');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('featured_slot_queue');
    }
};
