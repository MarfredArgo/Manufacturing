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
        Schema::table('rework_orders', function (Blueprint $table) {
            $table->foreign(['wo_id'], 'rework_orders_wo_id_fkey')->references(['id'])->on('work_orders')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rework_orders', function (Blueprint $table) {
            $table->dropForeign('rework_orders_wo_id_fkey');
        });
    }
};
