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
        Schema::table('qc_results', function (Blueprint $table) {
            $table->foreign(['check_id'], 'qc_results_check_id_fkey')->references(['id'])->on('qc_templates')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['session_id'], 'qc_results_session_id_fkey')->references(['id'])->on('qc_sessions')->onUpdate('no action')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('qc_results', function (Blueprint $table) {
            $table->dropForeign('qc_results_check_id_fkey');
            $table->dropForeign('qc_results_session_id_fkey');
        });
    }
};
