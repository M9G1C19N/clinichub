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
        Schema::create('patients', function (Blueprint $table) {
            $table->id();

            // Auto-generated patient code: PT-YYYY-NNNNN
            $table->string('patient_code', 20)->unique();

            // Personal Info
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->date('date_of_birth');
            $table->enum('sex', ['male', 'female']);
            $table->enum('civil_status', ['single', 'married', 'widowed', 'separated']);

            // Contact
            $table->string('contact_number', 15)->nullable();
            $table->string('email', 150)->nullable();
            $table->text('address')->nullable();

            // Medical Info
            $table->string('philhealth_number', 20)->nullable();
            $table->string('blood_type', 5)->nullable();
            $table->string('occupation', 100)->nullable();

            // Emergency Contact
            $table->string('emergency_contact_name', 150)->nullable();
            $table->string('emergency_contact_number', 15)->nullable();

            // Photo
            $table->string('photo_path', 255)->nullable();

            // Visit type default
            $table->enum('visit_type_default', ['opd', 'pre_employment'])->default('opd');

            // Status
            $table->boolean('is_active')->default(true);

            // Who registered this patient
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
        Schema::dropIfExists('patients');
    }
};
