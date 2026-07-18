<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Obyekt kassa hisoblari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('object_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('objects');
            $table->string('name');
            $table->string('type'); // App\Enums\CashAccountType
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('object_id');
            $table->index('is_active');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_cash_accounts');
    }
};
