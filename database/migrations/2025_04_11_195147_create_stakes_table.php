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
        Schema::create('stakes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('crypto_id')->constrained()->onDelete('cascade');
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 18, 8);
            $table->decimal('apr', 8, 2);
            $table->integer('lock_time_days');
            $table->timestamp('start_date')->nullable();
            $table->timestamp('end_date')->nullable();
            $table->decimal('profit', 18, 8)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_profit_calculation')->nullable();
            $table->json('profit_snapshot')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'crypto_id', 'is_active']);
            $table->index('end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stakes');
    }
};
