<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_gpa', function (Blueprint $table) {
            $table->id('gpa_id');
            $table->unsignedBigInteger('student_id');
            $table->string('school_year', 20);
            $table->tinyInteger('semester');
            $table->decimal('semester_gpa', 5, 2);
            $table->decimal('cumulative_gpa', 5, 2);
            $table->timestamp('computed_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['student_id', 'school_year', 'semester'], 'uq_gpa');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_gpa');
    }
};
