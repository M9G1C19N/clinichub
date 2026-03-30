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
         Schema::create('drug_test_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code_number', 20)->unique();       // e.g. R-2660
            $table->string('accession_number', 20)->nullable();
            $table->foreignId('patient_id')
                  ->constrained('patients')->restrictOnDelete();
            $table->foreignId('patient_visit_id')
                  ->constrained('patient_visits')->restrictOnDelete();
            $table->string('company', 150)->nullable();

            // Test info
            $table->enum('test_purpose', [
                'pre_employment','return_to_duty','random',
                'reasonable_suspicion','follow_up','post_accident','mandatory'
            ])->default('pre_employment');
            $table->json('drugs_to_test');  // ['thc','met','thc_met','thc_coc_pcp_opi_amp']

            // Specimen
            $table->enum('specimen_type', ['urine','blood','other'])->default('urine');
            $table->timestamp('specimen_time')->nullable();
            $table->boolean('temp_in_range')->nullable(); // 32-38°C
            $table->string('specimen_volume', 20)->nullable();
            $table->string('specimen_appearance', 100)->nullable();
            $table->enum('specimen_sampling', ['single','split'])->default('single');
            $table->enum('specimen_collection', ['observed','unobserved'])->default('unobserved');

            // Staff
            $table->string('collector_name', 100)->nullable();
            $table->string('collector_license', 50)->nullable();
            $table->string('head_of_lab_name', 100)->nullable();
            $table->string('head_of_lab_license', 50)->nullable();
            $table->foreignId('collected_by')
                  ->nullable()->constrained('users')->nullOnDelete();

            // Chain of custody
            $table->string('remarks', 255)->nullable();

            // Result
            $table->enum('result', [
                'negative','positive','cancelled',
                'refusal','diluted','substituted','adulterated'
            ])->nullable();
            $table->text('result_remarks')->nullable();

            // Patient certification
            $table->boolean('certification_signed')->default(false);
            $table->date('certification_date')->nullable();

            // Status
            $table->enum('status', ['pending','processing','released','cancelled'])
                  ->default('pending');
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
        Schema::dropIfExists('drug_test_requests');
    }
};
