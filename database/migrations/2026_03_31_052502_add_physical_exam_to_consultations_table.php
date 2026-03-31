<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
         // Fix visit_type enum to include new types
        DB::statement("ALTER TABLE consultations MODIFY COLUMN visit_type
            ENUM('opd','pre_employment','annual_pe','exit_pe','follow_up','lab_only')
            NOT NULL DEFAULT 'opd'");

        Schema::table('consultations', function (Blueprint $table) {
            // PE-specific header fields
            $table->string('position_applied', 150)->nullable()->after('employer_company');
            $table->string('requesting_physician', 150)->nullable()->after('position_applied');

            // Physical Examination findings
            $table->text('pe_heent')->nullable()->after('pe_recommendation');
            $table->text('pe_chest_lungs')->nullable()->after('pe_heent');
            $table->text('pe_heart')->nullable()->after('pe_chest_lungs');
            $table->text('pe_abdomen')->nullable()->after('pe_heart');
            $table->text('pe_extremities')->nullable()->after('pe_abdomen');
            $table->text('pe_neurological')->nullable()->after('pe_extremities');
            $table->text('pe_genitourinary')->nullable()->after('pe_neurological');
            $table->text('pe_skin')->nullable()->after('pe_genitourinary');
            $table->text('pe_others')->nullable()->after('pe_skin');

            // Medical/Surgical History
            $table->text('past_illnesses')->nullable()->after('pe_others');
            $table->text('surgical_history')->nullable()->after('past_illnesses');
            $table->text('allergies')->nullable()->after('surgical_history');
            $table->text('current_medications')->nullable()->after('allergies');
            $table->text('family_history')->nullable()->after('current_medications');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'position_applied', 'requesting_physician',
                'pe_heent','pe_chest_lungs','pe_heart','pe_abdomen',
                'pe_extremities','pe_neurological','pe_genitourinary',
                'pe_skin','pe_others',
                'past_illnesses','surgical_history','allergies',
                'current_medications','family_history',
            ]);
        });
    }
};
