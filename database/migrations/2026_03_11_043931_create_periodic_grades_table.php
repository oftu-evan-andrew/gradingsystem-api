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
        Schema::create('periodic_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('class_standing_id');
            $table->tinyInteger('grading_period')->comment('1=Prelim 2=Midterm 3=Finals');
            $table->decimal('periodic_grade', 5, 2)->nullable()->comment('Final computed grade for this period');
            $table->enum('status', ['draft', 'submitted', 'finalized'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->uuid('submitted_by')->nullable();
            $table->uuid('last_modified_by')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'class_standing_id', 'grading_period'], 'uq_periodic_grades');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('class_standing_id')->references('id')->on('class_standings')->onDelete('cascade');
            $table->foreign('submitted_by')->references('professor_id')->on('professors')->onDelete('set null');
            $table->foreign('last_modified_by')->references('professor_id')->on('professors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodic_grades');
    }
};
