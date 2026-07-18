<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ombor harakatlari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('warehouse_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('objects');
            $table->foreignId('product_id')->constrained('products');
            $table->string('type'); // App\Enums\WarehouseMovementType
            $table->bigInteger('quantity'); // musbat qiymat, yo'nalish type orqali
            $table->foreignId('from_object_id')->nullable()->constrained('objects');
            $table->foreignId('to_object_id')->nullable()->constrained('objects');
            $table->text('note')->nullable();
            $table->string('recipient_name')->nullable(); // tovarni qabul qilgan shaxs
            $table->foreignId('created_by')->constrained('users');
            $table->date('movement_date');
            $table->timestamps();
            $table->softDeletes();

            $table->index('object_id');
            $table->index('product_id');
            $table->index('type');
            $table->index('movement_date');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('warehouse_movements');
    }
};
