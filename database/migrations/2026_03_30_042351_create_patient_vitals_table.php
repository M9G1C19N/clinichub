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
         Schema::create('patient_vitals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                  ->constrained('patients')->restrictOnDelete();
            $table->foreignId('patient_visit_id')
                  ->constrained('patient_visits')->restrictOnDelete();

            // ── Physical Measurements ──────────────
            $table->decimal('weight_kg', 5, 2)->nullable();
            $table->decimal('height_cm', 5, 2)->nullable();
            $table->decimal('bmi', 5, 2)->nullable(); // auto-computed

            // ── Blood Pressure ─────────────────────
            $table->smallInteger('blood_pressure_systolic')->nullable();
            $table->smallInteger('blood_pressure_diastolic')->nullable();

            // ── Other Vitals ───────────────────────
            $table->smallInteger('pulse_rate')->nullable();         // bpm
            $table->smallInteger('respiratory_rate')->nullable();   // breaths/min
            $table->decimal('temperature_celsius', 4, 1)->nullable();
            $table->smallInteger('oxygen_saturation')->nullable();  // SpO2 %
            $table->smallInteger('heart_rate')->nullable();         // bpm

            // ── Pre-Employment Specific ────────────
            $table->string('visual_acuity_right', 20)->nullable(); // e.g. 20/20
            $table->string('visual_acuity_left', 20)->nullable();
            $table->string('ishihara_result', 50)->nullable();     // Normal / Color Deficiency

            // ── Notes ─────────────────────────────
            $table->text('nurse_notes')->nullable();

            // ── Who recorded ──────────────────────
            $table->foreignId('recorded_by')
                  ->constrained('users')->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_vitals');
    }
};
