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
        Schema::table('object_transactions', function (Blueprint $table) {
            $table->boolean('as_sub_manager')->default(false);
        });
        Schema::table('warehouse_movements', function (Blueprint $table) {
            $table->boolean('as_sub_manager')->default(false);
        });
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->boolean('as_sub_manager')->default(false);
        });
        Schema::table('inventory_checks', function (Blueprint $table) {
            $table->boolean('as_sub_manager')->default(false);
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->boolean('as_sub_manager')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('object_transactions', function (Blueprint $table) {
            $table->dropColumn('as_sub_manager');
        });
        Schema::table('warehouse_movements', function (Blueprint $table) {
            $table->dropColumn('as_sub_manager');
        });
        Schema::table('salary_payments', function (Blueprint $table) {
            $table->dropColumn('as_sub_manager');
        });
        Schema::table('inventory_checks', function (Blueprint $table) {
            $table->dropColumn('as_sub_manager');
        });
        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropColumn('as_sub_manager');
        });
    }
};
