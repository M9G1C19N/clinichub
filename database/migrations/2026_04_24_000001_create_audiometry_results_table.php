<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audiometry_results', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 20)->unique();
            $table->foreignId('patient_id')->constrained('patients')->restrictOnDelete();
            $table->foreignId('patient_visit_id')->constrained('patient_visits')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();

            // Instrument info
            $table->string('audiometer_used', 100)->nullable();
            $table->string('last_calibrated', 100)->nullable();
            $table->string('examiner', 100)->nullable();
            $table->text('reason_for_audiometry')->nullable();

            // Case History (8 questions)
            $table->boolean('ch_hearing_loss')->default(false);
            $table->string('ch_hearing_loss_when', 100)->nullable();
            $table->boolean('ch_hearing_aid')->default(false);
            $table->boolean('ch_better_ear')->default(false);
            $table->string('ch_better_ear_which', 100)->nullable();
            $table->boolean('ch_sudden_progression')->default(false);
            $table->boolean('ch_ringing_noises')->default(false);
            $table->boolean('ch_drainage')->default(false);
            $table->boolean('ch_pain_discomfort')->default(false);
            $table->boolean('ch_medical_consultation')->default(false);

            // Visual/Otoscopic Inspection
            $table->text('otoscopic_right_ear')->nullable();
            $table->text('otoscopic_left_ear')->nullable();

            // Pure Tone Audiometry — Right Ear (dB HL per frequency)
            $table->decimal('re_250',  6, 1)->nullable();
            $table->decimal('re_500',  6, 1)->nullable();
            $table->decimal('re_1000', 6, 1)->nullable();
            $table->decimal('re_1500', 6, 1)->nullable();
            $table->decimal('re_2000', 6, 1)->nullable();
            $table->decimal('re_3000', 6, 1)->nullable();
            $table->decimal('re_4000', 6, 1)->nullable();
            $table->decimal('re_8000', 6, 1)->nullable();
            $table->decimal('re_average', 6, 1)->nullable();

            // Pure Tone Audiometry — Left Ear
            $table->decimal('le_250',  6, 1)->nullable();
            $table->decimal('le_500',  6, 1)->nullable();
            $table->decimal('le_1000', 6, 1)->nullable();
            $table->decimal('le_1500', 6, 1)->nullable();
            $table->decimal('le_2000', 6, 1)->nullable();
            $table->decimal('le_3000', 6, 1)->nullable();
            $table->decimal('le_4000', 6, 1)->nullable();
            $table->decimal('le_8000', 6, 1)->nullable();
            $table->decimal('le_average', 6, 1)->nullable();

            // Remarks & Recommendations
            $table->text('remarks_right_ear')->nullable();
            $table->text('remarks_left_ear')->nullable();
            $table->text('recommendations')->nullable();

            // Staff / Signatures
            $table->string('examined_by_name', 100)->nullable();
            $table->string('examined_by_license', 50)->nullable();
            $table->string('examined_by_signature')->nullable();
            $table->string('noted_by_name', 100)->nullable();
            $table->string('noted_by_license', 50)->nullable();
            $table->string('noted_by_signature')->nullable();

            // Status lifecycle
            $table->enum('status', ['collecting', 'draft', 'released'])->default('collecting');
            $table->date('result_date')->nullable();
            $table->string('result_time', 10)->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();

            // Pickup tracking
            $table->enum('claim_status', ['waiting', 'claimed', 'unclaimed'])->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audiometry_results');
    }
};
