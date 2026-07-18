<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kassa balanslari jadvalini yaratish (har bir kassa + valyuta uchun alohida qator).
     */
    public function up(): void
    {
        Schema::create('cash_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->cascadeOnDelete();
            $table->string('currency', 3); // App\Enums\Currency
            $table->bigInteger('balance')->default(0); // sent/tiyinda
            $table->timestamps();

            $table->unique(['cash_account_id', 'currency']);
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_balances');
    }
};
