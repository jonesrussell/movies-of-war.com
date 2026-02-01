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
        Schema::table('movies', function (Blueprint $table) {
            $table->decimal('tmdb_vote_average', 3, 1)->nullable()->after('imdb_id');
            $table->unsignedInteger('tmdb_vote_count')->nullable()->after('tmdb_vote_average');
            $table->string('director')->nullable()->after('tmdb_vote_count');
            $table->json('writers')->nullable()->after('director');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn(['tmdb_vote_average', 'tmdb_vote_count', 'director', 'writers']);
        });
    }
};
