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
        Schema::create('imaging_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 20)->unique();
            $table->foreignId('patient_id')
                  ->constrained('patients')->restrictOnDelete();
            $table->foreignId('patient_visit_id')
                  ->constrained('patient_visits')->restrictOnDelete();
            $table->enum('imaging_type', [
                'chest_xray_pa',
                'kub',
                'ultrasound_abdomen',
                'ultrasound_ob',
                'ultrasound_pelvis',
                'ecg',
                'other',
            ]);
            $table->foreignId('requested_by')
                  ->constrained('users')->restrictOnDelete();
            $table->text('radiographic_findings')->nullable();
            $table->text('impression')->nullable();
            $table->boolean('is_provisional')->default(false);
            $table->enum('status', [
                'pending', 'processing', 'completed', 'released'
            ])->default('pending');
            // Staff details
            $table->string('rad_tech_name', 100)->nullable();
            $table->string('rad_tech_license', 50)->nullable();
            $table->string('radiologist_name', 100)->nullable();
            $table->string('radiologist_license', 50)->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')
                  ->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imaging_requests');
    }
};
