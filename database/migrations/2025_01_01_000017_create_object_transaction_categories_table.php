<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Obyekt tranzaksiya kategoriyalari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('object_transaction_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->nullable()->constrained('objects')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('object_transaction_categories')->nullOnDelete();
            $table->string('name');
            $table->string('type'); // 'income' yoki 'expense'
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('object_id');
            $table->index('type');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_transaction_categories');
    }
};
