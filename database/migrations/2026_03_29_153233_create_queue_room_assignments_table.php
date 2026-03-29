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
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();

            // Ticket number e.g. A-001, B-042
            $table->string('ticket_number', 15)->unique();

            $table->foreignId('patient_id')
                  ->constrained('patients')
                  ->restrictOnDelete();

            $table->foreignId('patient_visit_id')
                  ->nullable()
                  ->constrained('patient_visits')
                  ->nullOnDelete();

            $table->foreignId('queue_counter_id')
                  ->nullable()
                  ->constrained('queue_counters')
                  ->nullOnDelete();

            // Visit classification
            $table->enum('visit_type', [
                'opd', 'pre_employment', 'follow_up', 'lab_only'
            ]);

            // Priority level
            $table->enum('priority', [
                'regular', 'senior', 'pwd', 'pregnant', 'urgent'
            ])->default('regular');

            // Overall ticket status
            $table->enum('status', [
                'waiting',    // Ticket issued, not yet routed
                'routing',    // Engine calculating room sequence
                'in_progress',// Patient visiting rooms
                'completed',  // All rooms done
                'cancelled',  // Cancelled by staff
                'no_show',    // Patient did not come
            ])->default('waiting');

            // Services requested — array of service codes
            $table->json('services_requested')->nullable();

            // Timestamps
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Who issued the ticket
            $table->foreignId('issued_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_room_assignments');
    }
};
