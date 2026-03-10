<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('secret', 100);
            $table->boolean('password_client')->default(true);
            $table->boolean('revoked')->default(false);
            $table->timestamps();
        });

        DB::table('oauth_clients')->insert([
            'id' => 1,
            'name' => 'default-password-client',
            'secret' => env('OAUTH_DEFAULT_CLIENT_SECRET', 'dev-client-secret-change-me'),
            'password_client' => true,
            'revoked' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_clients');
    }
};
