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
        Schema::table('tags', function (Blueprint $table) {
            if (! Schema::hasColumn('tags', 'color')) {
                $table->string('color')->nullable()->after('type');
            }
            if (! Schema::hasColumn('tags', 'description')) {
                $table->text('description')->nullable()->after('color');
            }
            if (! Schema::hasColumn('tags', 'article_count')) {
                $table->integer('article_count')->default(0)->after('description');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tags', function (Blueprint $table) {
            $columns = array_filter(
                ['color', 'description', 'article_count'],
                fn (string $col) => Schema::hasColumn('tags', $col)
            );
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
