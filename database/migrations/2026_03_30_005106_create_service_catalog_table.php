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
         Schema::create('service_catalog', function (Blueprint $table) {
            $table->id();
            $table->string('service_code', 20)->unique();
            $table->string('service_name', 150);
            $table->enum('category', [
                'laboratory', 'xray_utz', 'drug_test',
                'consultation', 'procedure', 'other'
            ]);
            $table->enum('room', [
                'laboratory', 'xray_utz', 'drug_test',
                'interview_room', 'none'
            ])->default('none');
            $table->decimal('base_price', 10, 2);
            $table->text('description')->nullable();
            $table->boolean('requires_fasting')->default(false);
            $table->tinyInteger('turnaround_hours')->default(4);
            $table->boolean('is_active')->default(true);
            $table->foreignId('price_changed_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->timestamp('price_changed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_catalog');
    }
};
