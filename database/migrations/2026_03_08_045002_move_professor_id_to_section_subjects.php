<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropForeign(['professor_id']);
            $table->dropColumn('professor_id');
        });

        Schema::table('section_subjects', function (Blueprint $table) {
            $table->unsignedBigInteger('professor_id')->nullable()->after('subject_id');
            $table->foreign('professor_id')->references('professor_id')->on('professors')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('section_subjects', function (Blueprint $table) {
            $table->dropForeign(['professor_id']);
            $table->dropColumn('professor_id');
        });

        Schema::table('sections', function (Blueprint $table) {
            $table->unsignedBigInteger('professor_id')->nullable();
            $table->foreign('professor_id')->references('professor_id')->on('professors')->onDelete('cascade');
        });
    }
};
