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
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('appointment_number')->unique(); // APT-20260402-0001

            // Patient info — no account required
            $table->string('patient_name');
            $table->string('patient_email')->nullable();
            $table->string('patient_phone');
            $table->date('patient_dob')->nullable();
            $table->string('patient_gender')->nullable();

            // Booking details
            $table->string('service_type'); // general_consultation, laboratory, xray_utz, drug_test, physical_exam, prenatal, other
            $table->text('chief_complaint')->nullable();
            $table->date('preferred_date');
            $table->string('preferred_time')->nullable(); // morning, afternoon, or HH:MM

            // Status
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed', 'no_show'])
                  ->default('pending');

            // Staff actions
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->text('admin_notes')->nullable();

            // Links
            $table->foreignId('visit_id')->nullable()->constrained('patient_visits')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('patients')->nullOnDelete();

            $table->timestamps();

            $table->index(['preferred_date', 'status']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};
