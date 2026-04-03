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
        Schema::table('consultations', function (Blueprint $table) {
            $table->text('ecg_impression')->nullable()->after('pe_others');
            $table->text('ecg_findings')->nullable()->after('ecg_impression');
            $table->unsignedBigInteger('ecg_noted_by_user_id')->nullable()->after('ecg_findings');
            $table->string('ecg_noted_by_name')->nullable()->after('ecg_noted_by_user_id');
            $table->string('ecg_noted_by_license')->nullable()->after('ecg_noted_by_name');
            $table->string('ecg_noted_by_signature')->nullable()->after('ecg_noted_by_license');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn([
                'ecg_impression', 'ecg_findings',
                'ecg_noted_by_user_id', 'ecg_noted_by_name',
                'ecg_noted_by_license', 'ecg_noted_by_signature',
            ]);
        });
    }
};
