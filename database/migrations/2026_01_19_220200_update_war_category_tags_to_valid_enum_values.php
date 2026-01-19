<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Update all 'war_category' tags to 'era' as they represent historical periods/events
        DB::table('tags')
            ->where('type', 'war_category')
            ->update(['type' => 'era']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert back to 'war_category' (though this will cause enum errors)
        DB::table('tags')
            ->where('type', 'era')
            ->whereIn('name', [
                'Battle Of The Bulge',
                'D Day',
                'Germany',
                'Japan',
                'Normandy',
                'Pacific',
                'Pearl Harbor',
                'Spielberg',
                'War Films',
            ])
            ->update(['type' => 'war_category']);
    }
};
