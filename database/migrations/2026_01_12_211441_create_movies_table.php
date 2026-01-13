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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->integer('release_year');
            $table->date('release_date')->nullable();
            $table->text('synopsis');
            $table->integer('runtime')->nullable()->comment('Runtime in minutes');
            $table->string('country')->nullable();
            $table->string('conflict')->nullable()->comment('War/conflict era');
            $table->string('poster_path')->nullable();
            $table->string('poster_url')->nullable();
            $table->string('trailer_url')->nullable();
            $table->string('imdb_id')->nullable();
            $table->boolean('is_upcoming')->default(false);
            $table->timestamps();

            $table->index('slug');
            $table->index('release_year');
            $table->index('is_upcoming');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
