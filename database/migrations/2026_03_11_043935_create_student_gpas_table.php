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
        Schema::create('student_gpas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->string('school_year', 20)->comment('e.g. 2024-2025');
            $table->tinyInteger('semester')->comment('1 or 2');
            $table->decimal('semester_gpa', 5, 2)->comment('GPA for this semester only');
            $table->decimal('cumulative_gpa', 5, 2)->comment('Running GPA from enrollment through this semester');
            $table->timestamps();

            $table->unique(['student_id', 'school_year', 'semester'], 'uq_gpa');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_gpas');
    }
};
