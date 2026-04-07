<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('package_discounts', function (Blueprint $table) {
            $table->id();
            $table->string('package_key')->unique();          // pre_emp_pkg_1, pre_emp_pkg_2, pre_emp_pkg_3
            $table->string('package_name');
            $table->json('service_codes');                    // array of service codes included in the package
            $table->decimal('package_price', 10, 2)->default(0.00); // discounted total for the package
            $table->decimal('addon_drugtest_price', 10, 2)->nullable(); // Pkg 3 only: discounted drug test price when added as addon
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('package_discounts');
    }
};
