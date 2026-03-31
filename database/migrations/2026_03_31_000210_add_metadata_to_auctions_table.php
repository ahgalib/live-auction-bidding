<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            $table->string('title')->nullable()->after('id');
            $table->decimal('min_increment', 12, 2)->default(1)->after('starting_price');
            $table->string('category')->nullable()->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('auctions', function (Blueprint $table): void {
            $table->dropColumn(['title', 'min_increment', 'category']);
        });
    }
};
