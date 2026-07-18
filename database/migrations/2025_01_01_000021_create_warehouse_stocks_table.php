<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ombor zahiralari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('objects');
            $table->foreignId('product_id')->constrained('products');
            $table->bigInteger('quantity')->default(0); // asosiy birlik * 1000 aniqlik uchun
            $table->timestamps();

            $table->unique(['object_id', 'product_id']);
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_stocks');
    }
};
