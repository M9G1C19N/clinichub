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
         Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->date('result_date')->nullable()->after('request_date');
            $table->time('result_time')->nullable()->after('result_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->dropColumn(['result_date', 'result_time']);
        });
    }
};
