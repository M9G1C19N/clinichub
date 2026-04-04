<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Add 'locked' status so non-active rooms are invisible to room screens
        // until the previous room in the routing sequence completes.
        DB::statement("
            ALTER TABLE queue_room_assignments
            MODIFY COLUMN status ENUM(
                'locked',
                'waiting',
                'directing',
                'calling',
                'serving',
                'completed',
                'no_show',
                'skipped'
            ) NOT NULL DEFAULT 'waiting'
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE queue_room_assignments
            MODIFY COLUMN status ENUM(
                'waiting',
                'directing',
                'calling',
                'serving',
                'completed',
                'no_show',
                'skipped'
            ) NOT NULL DEFAULT 'waiting'
        ");
    }
};
