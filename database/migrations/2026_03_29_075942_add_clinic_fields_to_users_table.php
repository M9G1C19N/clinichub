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
        Schema::table('users', function (Blueprint $table) {
           $table->string('employee_id', 30)->unique()->nullable()->after('name');
            $table->string('specialization', 100)->nullable()->after('employee_id');
            $table->string('prc_number', 50)->nullable()->after('specialization');
            $table->string('ptr_number', 50)->nullable()->after('prc_number');
            $table->enum('department', [
                'laboratory', 'xray_utz', 'drug_test', 'reception', 'nursing', 'admin', 'doctor'
            ])->nullable()->after('ptr_number');
            $table->string('photo_path', 255)->nullable()->after('department');
            $table->boolean('is_active')->default(true)->after('photo_path');
            $table->boolean('must_change_password')->default(false)->after('is_active');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'employee_id', 'specialization', 'prc_number', 'ptr_number',
                'department', 'photo_path', 'is_active', 'must_change_password'
            ]);
            $table->dropSoftDeletes();
        });
    }
};
