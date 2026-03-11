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
        Schema::create('section_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('section_id');
            $table->unsignedBigInteger('subject_id');
            $table->uuid('professor_id');
            $table->tinyInteger('semester')->comment('1 or 2');
            $table->timestamps();

            $table->unique(['section_id', 'subject_id', 'semester'], 'uq_section_subject_semester');
            $table->foreign('section_id')->references('section_id')->on('sections')->onDelete('cascade');
            $table->foreign('subject_id')->references('id')->on('subjects')->onDelete('cascade');
            $table->foreign('professor_id')->references('professor_id')->on('professors')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_subjects');
    }
};
