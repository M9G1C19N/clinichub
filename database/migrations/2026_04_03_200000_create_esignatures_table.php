<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esignatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 150)->nullable();           // e.g. "Medical Technologist"
            $table->string('license_number', 80)->nullable();   // PRC license
            $table->string('ptr_number', 80)->nullable();       // PTR
            $table->string('signature_path')->nullable();       // storage path to image
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('user_id'); // one signature record per user
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esignatures');
    }
};
