<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Obyekt tranzaksiyalari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('object_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('objects');
            $table->foreignId('object_cash_account_id')->constrained('object_cash_accounts');
            $table->foreignId('category_id')->nullable()->constrained('object_transaction_categories')->nullOnDelete();
            $table->string('counterparty_name')->nullable(); // oddiy matn, moliya kontragentlariga bog'lanmagan
            $table->string('type'); // App\Enums\TransactionType
            $table->string('currency', 3); // App\Enums\Currency
            $table->bigInteger('amount'); // musbat qiymat
            $table->bigInteger('balance_after'); // tranzaksiyadan keyingi balans
            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->date('transaction_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index('object_id');
            $table->index('object_cash_account_id');
            $table->index('currency');
            $table->index('type');
            $table->index('transaction_date');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_transactions');
    }
};
