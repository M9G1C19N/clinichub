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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')
                  ->constrained('invoices')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->enum('method', [
                'cash', 'gcash', 'maya', 'card', 'philhealth', 'other'
            ])->default('cash');
            $table->string('reference_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('received_by')
                  ->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
