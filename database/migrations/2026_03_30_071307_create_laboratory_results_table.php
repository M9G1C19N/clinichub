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
         Schema::create('laboratory_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_request_id')
                  ->constrained('laboratory_requests')->cascadeOnDelete();
            $table->foreignId('lab_test_id')
                  ->constrained('lab_tests')->restrictOnDelete();
            $table->string('result_value', 100)->nullable();
            $table->string('unit', 30)->nullable();
            $table->string('normal_range_display', 100)->nullable(); // shown on report
            $table->boolean('is_abnormal')->default(false);
            $table->enum('abnormal_flag', ['H', 'L', 'C'])->nullable(); // High/Low/Critical
            $table->text('remarks')->nullable();
            $table->foreignId('performed_by')
                  ->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_results');
    }
};
