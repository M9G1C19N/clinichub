<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Only add columns if they don't exist yet
            if (!Schema::hasColumn('users', 'employee_id')) {
                $table->string('employee_id', 30)->unique()->nullable()->after('name');
            }
            if (!Schema::hasColumn('users', 'specialization')) {
                $table->string('specialization', 100)->nullable()->after('employee_id');
            }
            if (!Schema::hasColumn('users', 'prc_number')) {
                $table->string('prc_number', 50)->nullable()->after('specialization');
            }
            if (!Schema::hasColumn('users', 'ptr_number')) {
                $table->string('ptr_number', 50)->nullable()->after('prc_number');
            }
            if (!Schema::hasColumn('users', 'department')) {
                $table->enum('department', [
                    'laboratory', 'xray_utz', 'drug_test',
                    'reception', 'nursing', 'admin', 'doctor'
                ])->nullable()->after('ptr_number');
            }
            if (!Schema::hasColumn('users', 'photo_path')) {
                $table->string('photo_path', 255)->nullable()->after('department');
            }
            if (!Schema::hasColumn('users', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('photo_path');
            }
            if (!Schema::hasColumn('users', 'must_change_password')) {
                $table->boolean('must_change_password')->default(false)->after('is_active');
            }
            if (!Schema::hasColumn('users', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id', 'specialization', 'prc_number',
                'ptr_number', 'department', 'photo_path',
                'is_active', 'must_change_password',
            ]);
            $table->dropSoftDeletes();
        });
    }
};
