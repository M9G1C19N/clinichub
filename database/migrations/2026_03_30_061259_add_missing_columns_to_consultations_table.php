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
            $table->timestamp('consultation_date')->useCurrent()->after('visit_type');
            $table->date('follow_up_date')->nullable()->after('doctor_notes');
            $table->boolean('essentially_normal')->default(false)->after('pe_recommendation');
            $table->enum('status', ['ongoing', 'completed', 'cancelled'])
                  ->default('ongoing')->after('is_finalized');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('consultations', function (Blueprint $table) {
            $table->dropColumn(['consultation_date', 'follow_up_date', 'essentially_normal', 'status']);
        });
    }
};
