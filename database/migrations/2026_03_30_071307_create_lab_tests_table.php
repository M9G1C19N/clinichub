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
       Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('test_name', 150);
            $table->string('test_code', 20)->unique();
            $table->enum('category', [
                'hematology', 'chemistry', 'urinalysis',
                'serology', 'stool', 'thyroid', 'other'
            ]);
            $table->string('unit', 30)->nullable();
            $table->string('normal_range_male', 100)->nullable();
            $table->string('normal_range_female', 100)->nullable();
            $table->string('normal_range_general', 100)->nullable();
            $table->decimal('normal_min', 10, 3)->nullable();
            $table->decimal('normal_max', 10, 3)->nullable();
            $table->boolean('is_text_result')->default(false); // UA color, consistency etc.
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lab_tests');
    }
};
