<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Valyuta kurslari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('currency_rates', function (Blueprint $table) {
            $table->id();
            $table->bigInteger('rate_uzs_per_usd'); // 1 USD = X tiyin (masalan, 1265000 = 12 650.00 UZS)
            $table->foreignId('set_by')->constrained('users');
            $table->date('effective_date');
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique('effective_date');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('currency_rates');
    }
};
