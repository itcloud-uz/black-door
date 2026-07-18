<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventarizatsiya tekshiruv elementlari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('inventory_check_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_check_id')->constrained('inventory_checks')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->bigInteger('expected_qty');
            $table->bigInteger('actual_qty');
            $table->bigInteger('difference'); // actual - expected
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index('inventory_check_id');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_check_items');
    }
};
