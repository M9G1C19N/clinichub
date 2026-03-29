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
       Schema::create('queue_counters', function (Blueprint $table) {
            $table->id();
            $table->string('counter_name', 50);          // "Counter 1 — OPD"
            $table->string('counter_code', 10)->unique(); // A, B, LAB, XRAY
            $table->json('assigned_types')->nullable();   // ["opd","pre_employment"]
            $table->foreignId('current_user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_counters');
    }
};
