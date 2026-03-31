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
         Schema::table('patient_vitals', function (Blueprint $table) {
            // Present symptoms
            $table->text('present_symptoms')->nullable()->after('nurse_notes');

            // Past Illnesses — stored as JSON array of checked items
            $table->json('past_illnesses_flags')->nullable()->after('present_symptoms');
            $table->text('past_illnesses_remarks')->nullable()->after('past_illnesses_flags');

            // History
            $table->string('family_history', 255)->nullable()->after('past_illnesses_remarks');
            $table->string('accidents_injuries', 255)->nullable()->after('family_history');
            $table->string('surgical_history_detail', 255)->nullable()->after('accidents_injuries');

            // Allergies
            $table->json('allergies_flags')->nullable()->after('surgical_history_detail'); // ['none','food','drug','others']
            $table->string('allergies_others', 100)->nullable()->after('allergies_flags');

            // Menstrual / OB (for female)
            $table->string('menstrual_cycle', 50)->nullable()->after('allergies_others'); // regular/irregular/menopause/postmenopausal
            $table->string('lmp', 50)->nullable()->after('menstrual_cycle');
            $table->string('ob_gravida', 20)->nullable()->after('lmp');
            $table->string('ob_para', 20)->nullable()->after('ob_gravida');
            $table->boolean('ob_nulligravida')->default(false)->after('ob_para');

            // Personal/Social History
            $table->string('tobacco_use', 20)->nullable()->after('ob_nulligravida'); // current/former/never
            $table->string('alcohol_use', 20)->nullable()->after('tobacco_use');     // current/former/never

            // Additional vitals for form
            $table->string('conversational_hearing', 20)->nullable()->after('alcohol_use'); // Normal/Defective
            $table->string('visual_acuity_near_right', 20)->nullable()->after('conversational_hearing');
            $table->string('visual_acuity_near_left', 20)->nullable()->after('visual_acuity_near_right');
            $table->string('color_vision_result', 50)->nullable()->after('visual_acuity_near_left');

            // PE Findings per system (Normal = true, Abnormal = false, null = not checked)
            $table->json('pe_findings_normal')->nullable()->after('color_vision_result');
            // JSON: { eyes: true, nose_sinuses: true, neck_thyroid: true, mouth_throat: true,
            //         chest_breast: true, lungs: true, heart: true, abdomen: true,
            //         back: true, anus: true, genitals: true, extremities: true, skin: true }
            $table->text('pe_findings_remarks')->nullable()->after('pe_findings_normal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patient_vitals', function (Blueprint $table) {
            $table->dropColumn([
                'present_symptoms', 'past_illnesses_flags', 'past_illnesses_remarks',
                'family_history', 'accidents_injuries', 'surgical_history_detail',
                'allergies_flags', 'allergies_others',
                'menstrual_cycle', 'lmp', 'ob_gravida', 'ob_para', 'ob_nulligravida',
                'tobacco_use', 'alcohol_use',
                'conversational_hearing',
                'visual_acuity_near_right', 'visual_acuity_near_left', 'color_vision_result',
                'pe_findings_normal', 'pe_findings_remarks',
            ]);
        });
    }
};
