<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('qc_templates', function (Blueprint $table) {
            $table->string('id', 10)->primary();
            $table->string('build_type', 50);
            $table->string('category', 80);
            $table->string('name', 150);
            $table->string('tool', 100)->nullable();
            $table->decimal('target')->nullable();
            $table->string('operator', 5)->nullable();
            $table->string('unit', 20)->nullable();
            $table->timestamp('created_at')->nullable()->default(DB::raw("now()"));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qc_templates');
    }
};
