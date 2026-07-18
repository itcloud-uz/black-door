<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kontragent teglari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('counterparty_tags', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('counterparty_tags');
    }
};
