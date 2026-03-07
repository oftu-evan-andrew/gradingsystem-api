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
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('subject_id')->constrained()->onDelete('cascade');
            $table->foreignId('professor_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('grading_period');
            $table->tinyInteger('semester');
            $table->string('school_year', 20);
            $table->decimal('attendance_grade', 5, 2)->nullable();
            $table->decimal('recitation_grade', 5, 2)->nullable();
            $table->decimal('quiz_grade', 5, 2)->nullable();
            $table->decimal('quarter_exam_grade', 5, 2)->nullable();
            $table->decimal('project_grade', 5, 2)->nullable();
            $table->enum('status', ['draft', 'submitted', 'finalized'])->default('draft');
            $table->unique(['student_id', 'subject_id', 'grading_period', 'semester', 'school_year'], 'grades_composite_index');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
