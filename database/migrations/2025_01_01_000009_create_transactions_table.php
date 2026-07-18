<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tranzaksiyalar jadvalini yaratish (moliya moduli).
     */
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_account_id')->constrained('cash_accounts');
            $table->foreignId('counterparty_id')->nullable()->constrained('counterparties')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('transaction_categories')->nullOnDelete();
            $table->string('type'); // App\Enums\TransactionType
            $table->string('currency', 3); // App\Enums\Currency
            $table->bigInteger('amount'); // har doim musbat, yo'nalish type orqali
            $table->bigInteger('balance_after'); // tranzaksiyadan keyingi balans surati
            $table->text('note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('related_transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->date('transaction_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index('cash_account_id');
            $table->index('counterparty_id');
            $table->index('category_id');
            $table->index('currency');
            $table->index('type');
            $table->index('transaction_date');
            $table->index('created_by');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
