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
        Schema::create('sections', function (Blueprint $table) {
            $table->uuid('section_id')->primary();
            $table->string('section_name', 50);
            $table->tinyInteger('year_level')->comment('1-4');
            $table->unsignedBigInteger('course_id');
            $table->string('school_year', 20);
            $table->timestamps();

            $table->unique(['section_name', 'school_year'], 'uq_sections_name_year');
            $table->foreign('course_id')->references('id')->on('courses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
