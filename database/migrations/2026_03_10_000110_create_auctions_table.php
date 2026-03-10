<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auctions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('starting_price', 12, 2);
            $table->decimal('current_price', 12, 2);
            $table->foreignId('current_winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('end_time');
            $table->enum('status', ['draft', 'active', 'pending_payment', 'closed'])->default('draft');
            $table->timestamps();
            $table->index(['status', 'end_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auctions');
    }
};
