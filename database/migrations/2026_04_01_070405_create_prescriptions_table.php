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
            Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('rx_number', 30)->unique(); // RX-2026-00001
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('patient_visit_id')->constrained('patient_visits')->restrictOnDelete();
            $table->foreignId('doctor_id')->constrained('users')->restrictOnDelete();
            // Prescription items stored as JSON array
            // [{drug, dosage, form, quantity, frequency, duration, instructions}]
            $table->json('items');
            // Doctor credentials snapshot at time of writing
            $table->string('doctor_name', 150);
            $table->string('doctor_prc', 50)->nullable();
            $table->string('doctor_ptr', 50)->nullable();
            $table->string('doctor_s2', 50)->nullable();
            $table->string('doctor_specialization', 100)->nullable();
            // Patient snapshot
            $table->string('patient_name', 200);
            $table->string('patient_age_sex', 20);
            $table->string('patient_address')->nullable();
            // Metadata
            $table->text('notes')->nullable();
            $table->date('rx_date');
            $table->boolean('is_controlled')->default(false); // S2 prescription
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescriptions');
    }
};
