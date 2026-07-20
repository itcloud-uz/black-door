<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('face_id_enabled')->default(false);
            $table->text('face_embedding')->nullable();
            $table->integer('failed_face_attempts')->default(0);
            $table->timestamp('face_locked_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'face_id_enabled',
                'face_embedding',
                'failed_face_attempts',
                'face_locked_until',
            ]);
        });
    }
};
