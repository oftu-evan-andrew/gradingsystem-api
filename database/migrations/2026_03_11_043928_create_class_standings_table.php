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
        Schema::create('class_standings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('section_subject_id');
            $table->tinyInteger('grading_period')->comment('1=Prelim 2=Midterm 3=Finals');
            $table->decimal('attendance_score', 5, 2)->nullable()->comment('Avg of attendance_records.rating for this period');
            $table->decimal('recitation_score', 5, 2)->nullable()->comment('Avg of recitation_records.rating for this period');
            $table->decimal('quiz_score', 5, 2)->nullable()->comment('Avg of quiz_records.rating for this period');
            $table->decimal('project_score', 5, 2)->nullable()->comment('Avg of project_records.rating for this period');
            $table->decimal('major_exam_score', 5, 2)->nullable()->comment('Entered directly by the professor');
            $table->timestamps();

            $table->unique(['student_id', 'section_subject_id', 'grading_period'], 'uq_class_standing');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('section_subject_id')->references('id')->on('section_subjects')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_standings');
    }
};
