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
       Schema::create('queue_room_assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('queue_ticket_id')
                  ->constrained('queue_tickets')
                  ->cascadeOnDelete();

            $table->foreignId('patient_visit_id')
                  ->nullable()
                  ->constrained('patient_visits')
                  ->nullOnDelete();

            // Which room
            $table->enum('room', [
                'laboratory',
                'xray_utz',
                'drug_test',
                'interview_room',
            ]);

            // Room-specific queue number: LAB-042, XRAY-001
            $table->string('queue_number', 15);

            // Order patient visits this room (1,2,3,4)
            $table->tinyInteger('routing_sequence');

            // Queue load at time of routing (for audit)
            $table->tinyInteger('room_load_at_routing')->default(0);

            // Priority inherited from ticket
            $table->enum('priority', [
                'regular', 'senior', 'pwd', 'pregnant', 'urgent'
            ])->default('regular');

            // Room-level status
            $table->enum('status', [
                'waiting',    // Queued in this room
                'directing',  // Being directed to room
                'calling',    // Room calling patient
                'serving',    // Currently being served
                'completed',  // Done in this room
                'no_show',    // Did not respond after 3 calls
                'skipped',    // Manually skipped
            ])->default('waiting');

            // How many times called
            $table->tinyInteger('call_count')->default(0);

            // Timestamps
            $table->timestamp('broadcast_at')->nullable();
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Who served the patient in this room
            $table->foreignId('served_by')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
