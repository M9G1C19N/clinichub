<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('esignatures', function (Blueprint $table) {
            $table->decimal('signature_scale', 3, 2)->default(1.00)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('esignatures', function (Blueprint $table) {
            $table->dropColumn('signature_scale');
        });
    }
};
