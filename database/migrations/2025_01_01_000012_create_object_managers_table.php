<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Obyekt menejerlari jadvalini yaratish (1:1 bog'lanish).
     */
    public function up(): void
    {
        Schema::create('object_managers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->unique()->constrained('objects')->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamps();
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('object_managers');
    }
};
