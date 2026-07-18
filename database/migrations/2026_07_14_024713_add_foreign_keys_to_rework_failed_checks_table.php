<?php

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
        if (!Schema::hasTable('rework_failed_checks')) {
        Schema::table('rework_failed_checks', function (Blueprint $table) {
            $table->foreign(['rework_id'], 'rework_failed_checks_rework_id_fkey')->references(['id'])->on('rework_orders')->onUpdate('no action')->onDelete('cascade');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('rework_failed_checks')) {
        Schema::table('rework_failed_checks', function (Blueprint $table) {
            $table->dropForeign('rework_failed_checks_rework_id_fkey');
        });
        }
    }
};
