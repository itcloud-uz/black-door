<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Obyekt kassa balanslari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('object_cash_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_cash_account_id')->constrained('object_cash_accounts')->cascadeOnDelete();
            $table->string('currency', 3); // App\Enums\Currency
            $table->bigInteger('balance')->default(0); // sent/tiyinda
            $table->timestamps();

            $table->unique(['object_cash_account_id', 'currency']);
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_cash_balances');
    }
};
