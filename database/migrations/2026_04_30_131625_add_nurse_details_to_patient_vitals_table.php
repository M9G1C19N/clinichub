<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_vitals', function (Blueprint $table) {
            $table->string('tobacco_use_details')->nullable()->after('tobacco_use');
            $table->string('alcohol_use_details')->nullable()->after('alcohol_use');
            $table->text('pe_remarks')->nullable()->after('pe_findings_remarks');
            $table->json('pe_findings_details')->nullable()->after('pe_findings_normal');
            $table->string('visual_acuity_right_corrected', 20)->nullable()->after('visual_acuity_right');
            $table->string('visual_acuity_left_corrected', 20)->nullable()->after('visual_acuity_left');
            $table->string('visual_acuity_near_right_corrected', 20)->nullable()->after('visual_acuity_near_right');
            $table->string('visual_acuity_near_left_corrected', 20)->nullable()->after('visual_acuity_near_left');
        });
    }

    public function down(): void
    {
        Schema::table('patient_vitals', function (Blueprint $table) {
            $table->dropColumn([
                'tobacco_use_details', 'alcohol_use_details',
                'pe_remarks', 'pe_findings_details',
                'visual_acuity_right_corrected', 'visual_acuity_left_corrected',
                'visual_acuity_near_right_corrected', 'visual_acuity_near_left_corrected',
            ]);
        });
    }
};
