<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->string('examined_by_signature')->nullable()->after('examined_by_license');
            $table->string('noted_by_signature')->nullable()->after('noted_by_license');
        });

        Schema::table('imaging_requests', function (Blueprint $table) {
            $table->string('rad_tech_signature')->nullable()->after('rad_tech_license');
            $table->string('radiologist_signature')->nullable()->after('radiologist_license');
        });

        Schema::table('drug_test_requests', function (Blueprint $table) {
            $table->string('collector_signature')->nullable()->after('collector_license');
            $table->string('head_of_lab_signature')->nullable()->after('head_of_lab_license');
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->dropColumn(['examined_by_signature', 'noted_by_signature']);
        });
        Schema::table('imaging_requests', function (Blueprint $table) {
            $table->dropColumn(['rad_tech_signature', 'radiologist_signature']);
        });
        Schema::table('drug_test_requests', function (Blueprint $table) {
            $table->dropColumn(['collector_signature', 'head_of_lab_signature']);
        });
    }
};
