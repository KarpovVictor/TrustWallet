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
        Schema::table('cryptos', function (Blueprint $table) {
            $table->string('icon')->nullable()->change();
            $table->string('network_icon')->nullable()->change();
            $table->string('address')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cryptos', function (Blueprint $table) {
            $table->string('icon')->change();
            $table->string('network_icon')->change();
            $table->string('address')->change();
        });
    }
};
