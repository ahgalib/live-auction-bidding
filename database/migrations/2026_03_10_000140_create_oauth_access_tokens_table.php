<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_access_tokens', function (Blueprint $table) {
            $table->string('id', 64)->primary();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('oauth_clients')->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->json('scopes')->nullable();
            $table->boolean('revoked')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();
            $table->index(['user_id', 'revoked', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_access_tokens');
    }
};
