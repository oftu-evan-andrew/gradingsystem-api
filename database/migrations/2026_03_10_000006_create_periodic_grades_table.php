<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('periodic_grades', function (Blueprint $table) {
            $table->id('periodic_grade_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('class_standing_id');
            $table->tinyInteger('grading_period');
            $table->decimal('periodic_grade', 5, 2)->nullable();
            $table->enum('status', ['draft', 'submitted', 'finalized'])->default('draft');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('submitted_at')->nullable();
            $table->unsignedBigInteger('submitted_by')->nullable();
            $table->unsignedBigInteger('last_modified_by')->nullable();
            $table->timestamp('last_modified_at')->nullable();

            $table->unique(['student_id', 'class_standing_id', 'grading_period'], 'uq_periodic_grades');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('class_standing_id')->references('id')->on('class_standing')->onDelete('cascade');
            $table->foreign('submitted_by')->references('professor_id')->on('professors')->onDelete('set null');
            $table->foreign('last_modified_by')->references('professor_id')->on('professors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('periodic_grades');
    }
};
