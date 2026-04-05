<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── laboratory_requests ──────────────────────────
        DB::statement("
            ALTER TABLE laboratory_requests
            MODIFY COLUMN status ENUM('pending','collecting','processing','released')
            NOT NULL DEFAULT 'pending'
        ");
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('request_date');
        });

        // ── imaging_requests ─────────────────────────────
        DB::statement("
            ALTER TABLE imaging_requests
            MODIFY COLUMN status ENUM('pending','collecting','processing','released')
            NOT NULL DEFAULT 'pending'
        ");
        Schema::table('imaging_requests', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('requested_by');
        });

        // ── drug_test_requests ───────────────────────────
        DB::statement("
            ALTER TABLE drug_test_requests
            MODIFY COLUMN status ENUM('pending','collecting','processing','released','cancelled')
            NOT NULL DEFAULT 'pending'
        ");
        Schema::table('drug_test_requests', function (Blueprint $table) {
            $table->timestamp('collected_at')->nullable()->after('collected_by');
        });
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE laboratory_requests
            MODIFY COLUMN status ENUM('pending','processing','released')
            NOT NULL DEFAULT 'pending'
        ");
        Schema::table('laboratory_requests', function (Blueprint $table) {
            $table->dropColumn('collected_at');
        });

        DB::statement("
            ALTER TABLE imaging_requests
            MODIFY COLUMN status ENUM('pending','processing','released')
            NOT NULL DEFAULT 'pending'
        ");
        Schema::table('imaging_requests', function (Blueprint $table) {
            $table->dropColumn('collected_at');
        });

        DB::statement("
            ALTER TABLE drug_test_requests
            MODIFY COLUMN status ENUM('pending','processing','released','cancelled')
            NOT NULL DEFAULT 'pending'
        ");
        Schema::table('drug_test_requests', function (Blueprint $table) {
            $table->dropColumn('collected_at');
        });
    }
};
