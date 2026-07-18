<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Maosh to'lovlari jadvalini yaratish.
     */
    public function up(): void
    {
        Schema::create('salary_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('object_id')->constrained('objects');
            $table->foreignId('employee_id')->constrained('object_employees');
            $table->string('currency', 3); // App\Enums\Currency
            $table->bigInteger('amount'); // sent/tiyinda
            $table->date('period_start');
            $table->date('period_end');
            $table->text('note')->nullable();
            $table->timestamp('paid_at');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();

            $table->index('object_id');
            $table->index('employee_id');
            $table->index(['period_start', 'period_end']);
        });
    }

    /**
     * Migratsiyani bekor qilish.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_payments');
    }
};
