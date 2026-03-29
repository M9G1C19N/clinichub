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
       Schema::create('patient_visits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->restrictOnDelete();

            $table->enum('visit_type', [
                'opd', 'pre_employment', 'follow_up', 'lab_only'
            ]);

            $table->string('employer_company', 150)->nullable();
            $table->boolean('referral_validated')->default(false);
            $table->json('services_selected')->nullable();

            $table->datetime('visit_date');

            $table->enum('status', [
                'pending', 'in_progress', 'completed', 'cancelled', 'no_show'
            ])->default('pending');

            // Result claim date calculated from turnaround hours
            $table->date('result_claim_date')->nullable();

            // Chief complaint for OPD
            $table->text('chief_complaint')->nullable();

            $table->foreignId('created_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patient_visits');
    }
};
