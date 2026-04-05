<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_visits', function (Blueprint $table) {
            $table->boolean('synced_from_field')->default(false)->after('is_field_visit');
            $table->timestamp('synced_at')->nullable()->after('synced_from_field');
            $table->string('field_device_id', 100)->nullable()->after('synced_at');
            $table->unsignedBigInteger('field_temp_id')->nullable()->after('field_device_id');
            $table->foreignId('field_sync_log_id')
                  ->nullable()
                  ->after('field_temp_id')
                  ->constrained('field_sync_logs')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('patient_visits', function (Blueprint $table) {
            $table->dropForeign(['field_sync_log_id']);
            $table->dropColumn([
                'synced_from_field',
                'synced_at',
                'field_device_id',
                'field_temp_id',
                'field_sync_log_id',
            ]);
        });
    }
};
