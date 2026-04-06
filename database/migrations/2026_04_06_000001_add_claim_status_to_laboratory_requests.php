<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add claim tracking columns to laboratory_requests
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->enum('claim_status', ['waiting', 'claimed', 'unclaimed'])
                  ->nullable()
                  ->after('released_by')
                  ->comment('Physical pickup tracking: waiting=ready for patient, claimed=patient collected, unclaimed=patient did not return');
            $table->timestamp('claimed_at')->nullable()->after('claim_status');
            $table->foreignId('claimed_by')->nullable()->after('claimed_at')
                  ->constrained('users')->nullOnDelete();
        });

        // Backfill: existing released records that have no claim_status get 'waiting'
        DB::statement("
            UPDATE laboratory_requests
            SET claim_status = 'waiting'
            WHERE status = 'released'
              AND claim_status IS NULL
              AND deleted_at IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->dropForeign(['claimed_by']);
            $table->dropColumn(['claim_status', 'claimed_at', 'claimed_by']);
        });
    }
};
