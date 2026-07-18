<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kontragent-teg bog'lanish jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('counterparty_tag', function (Blueprint $table) {
            $table->foreignId('counterparty_id')->constrained('counterparties')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('counterparty_tags')->cascadeOnDelete();

            $table->primary(['counterparty_id', 'tag_id']);
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('counterparty_tag');
    }
};
