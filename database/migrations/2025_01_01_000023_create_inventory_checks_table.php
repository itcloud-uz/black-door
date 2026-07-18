<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Inventarizatsiya tekshiruvlari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('inventory_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('objects');
            $table->foreignId('checked_by')->constrained('users');
            $table->timestamp('checked_at');
            $table->string('status')->default('pending'); // pending / approved
            $table->text('note')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index('object_id');
            $table->index('status');
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_checks');
    }
};
