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
        Schema::table('imaging_requests', function (Blueprint $table) {
            $table->date('exam_date')->nullable()->after('id');
            $table->time('exam_time')->nullable()->after('exam_date');
     });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('imaging_requests', function (Blueprint $table) {
        $table->dropColumn(['exam_date', 'exam_time']);
    });
    }
};
