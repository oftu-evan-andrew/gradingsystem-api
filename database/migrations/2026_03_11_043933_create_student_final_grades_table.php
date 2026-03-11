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
        Schema::create('student_final_grades', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('student_id');
            $table->uuid('section_subject_id');
            $table->decimal('final_grade', 5, 2)->nullable();
            $table->enum('status', ['draft', 'submitted', 'finalized'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->uuid('submitted_by')->nullable();
            $table->uuid('last_modified_by')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'section_subject_id'], 'uq_final_grade');
            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('section_subject_id')->references('id')->on('section_subjects')->onDelete('cascade');
            $table->foreign('submitted_by')->references('professor_id')->on('professors')->onDelete('set null');
            $table->foreign('last_modified_by')->references('professor_id')->on('professors')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_final_grades');
    }
};
