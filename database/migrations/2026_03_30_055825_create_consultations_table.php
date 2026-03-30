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
        Schema::create('consultations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                  ->constrained('patients')->restrictOnDelete();
            $table->foreignId('patient_visit_id')
                  ->constrained('patient_visits')->restrictOnDelete();

            // ── Visit Type ─────────────────────────
            $table->enum('visit_type', [
                'opd', 'pre_employment', 'follow_up', 'lab_only'
            ]);

            // ── OPD — SOAP Notes ───────────────────
            $table->text('chief_complaint')->nullable();
            $table->text('soap_subjective')->nullable();  // what patient says
            $table->text('soap_objective')->nullable();   // what doctor observes
            $table->text('soap_assessment')->nullable();  // diagnosis
            $table->text('soap_plan')->nullable();        // treatment plan

            // ── ICD-10 Diagnosis ───────────────────
            $table->string('icd10_code', 10)->nullable();
            $table->string('icd10_description', 255)->nullable();
            $table->enum('diagnosis_type', [
                'primary', 'secondary', 'provisional'
            ])->default('primary');

            // ── Pre-Employment Classification ──────
            $table->enum('pe_classification', [
                'A', 'B', 'C', 'D', 'E'
            ])->nullable();
            $table->text('pe_findings')->nullable();         // summary of findings
            $table->text('pe_recommendation')->nullable();   // doctor's recommendation
            $table->string('employer_company', 150)->nullable();

            // ── General ───────────────────────────
            $table->text('doctor_notes')->nullable();
            $table->boolean('is_finalized')->default(false);
            $table->timestamp('finalized_at')->nullable();

            // ── Who ───────────────────────────────
            $table->foreignId('doctor_id')
                  ->constrained('users')->restrictOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consultations');
    }
};
