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
        if (!Schema::hasTable('qc_sessions')) {
        Schema::table('qc_sessions', function (Blueprint $table) {
            $table->foreign(['wo_id'], 'qc_sessions_wo_id_fkey')->references(['id'])->on('work_orders')->onUpdate('no action')->onDelete('cascade');
        });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('qc_sessions')) {
        Schema::table('qc_sessions', function (Blueprint $table) {
            $table->dropForeign('qc_sessions_wo_id_fkey');
        });
        }
    }
};
