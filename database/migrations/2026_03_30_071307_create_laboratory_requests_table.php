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
        Schema::create('laboratory_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 20)->unique();
            $table->foreignId('patient_id')
                  ->constrained('patients')->restrictOnDelete();
            $table->foreignId('patient_visit_id')
                  ->constrained('patient_visits')->restrictOnDelete();
            $table->foreignId('requested_by')
                  ->constrained('users')->restrictOnDelete();
            $table->date('request_date');
            $table->enum('priority', ['routine', 'urgent', 'stat'])->default('routine');
            $table->enum('status', [
                'pending', 'processing', 'completed', 'released', 'cancelled'
            ])->default('pending');
            $table->text('clinical_notes')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->foreignId('released_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('examined_by_name', 100)->nullable();   // lab tech name
            $table->string('examined_by_license', 50)->nullable(); // PRC license
            $table->string('noted_by_name', 100)->nullable();      // pathologist name
            $table->string('noted_by_license', 50)->nullable();    // pathologist license
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('laboratory_requests');
    }
};
