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
        Schema::create('recitation_records', function (Blueprint $table) {
            $table->id();
            $table->uuid('student_id');
            $table->uuid('section_subject_id');
            $table->uuid('professor_id');
            $table->tinyInteger('grading_period')->comment('1=Prelim 2=Midterm 3=Finals');
            $table->decimal('rating', 5, 2);
            $table->string('remarks', 255)->nullable();
            $table->timestamps();

            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('section_subject_id')->references('id')->on('section_subjects')->onDelete('cascade');
            $table->foreign('professor_id')->references('professor_id')->on('professors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recitation_records');
    }
};
