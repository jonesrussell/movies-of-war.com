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
        Schema::create('x_oauth2_tokens', function (Blueprint $table) {
            $table->id();
            $table->text('access_token')->comment('OAuth 2.0 user context access token');
            $table->text('refresh_token')->nullable()->comment('Refresh token if offline.access scope was granted');
            $table->timestamp('expires_at')->nullable()->comment('When the access token expires');
            $table->string('token_type')->default('Bearer');
            $table->string('scope')->nullable()->comment('Granted scopes (tweet.read, tweet.write, etc.)');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('x_oauth2_tokens');
    }
};
