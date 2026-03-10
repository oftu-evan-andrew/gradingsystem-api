<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recitation_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('section_subject_id');
            $table->unsignedBigInteger('professor_id');
            $table->tinyInteger('grading_period');
            $table->decimal('rating', 5, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('recorded_at')->useCurrent();
            $table->string('remarks', 255)->nullable();

            $table->foreign('student_id')->references('student_id')->on('students')->onDelete('cascade');
            $table->foreign('section_subject_id')->references('id')->on('section_subjects')->onDelete('cascade');
            $table->foreign('professor_id')->references('professor_id')->on('professors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recitation_records');
    }
};
