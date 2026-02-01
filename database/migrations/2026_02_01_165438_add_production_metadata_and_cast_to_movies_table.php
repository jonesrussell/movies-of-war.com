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
            $table->string('production_status')->nullable()->after('writers');
            $table->string('original_language')->nullable()->after('production_status');
            $table->unsignedBigInteger('budget')->nullable()->after('original_language');
            $table->unsignedBigInteger('revenue')->nullable()->after('budget');
            $table->json('cast')->nullable()->after('revenue');
            $table->json('crew')->nullable()->after('cast');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn([
                'production_status',
                'original_language',
                'budget',
                'revenue',
                'cast',
                'crew',
            ]);
        });
    }
};
