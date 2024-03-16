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
        Schema::create('cashinginfos', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('cash_at_hand', 10, 2);
            $table->decimal('momo_payments', 10, 2);
            $table->decimal('direct_banked_transactions', 10, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashinginfos');
    }
};
