<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('original_filename');
            $table->string('device_id', 100)->nullable();
            $table->integer('visits_imported')->default(0);
            $table->integer('patients_created')->default(0);
            $table->integer('patients_matched')->default(0);
            $table->integer('visits_skipped')->default(0);
            $table->json('skipped_details')->nullable();
            $table->foreignId('imported_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_sync_logs');
    }
};
