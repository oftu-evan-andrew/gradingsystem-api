<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_standing', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('section_subject_id');
            $table->tinyInteger('grading_period');
            $table->decimal('attendance_score', 5, 2)->nullable();
            $table->decimal('recitation_score', 5, 2)->nullable();
            $table->decimal('quiz_score', 5, 2)->nullable();
            $table->decimal('project_score', 5, 2)->nullable();
            $table->decimal('major_exam_score', 5, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('last_updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['student_id', 'section_subject_id', 'grading_period'], 'uq_class_standing');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('section_subject_id')->references('id')->on('section_subjects')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_standing');
    }
};
