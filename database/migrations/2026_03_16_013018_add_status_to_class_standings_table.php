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
        Schema::table('class_standings', function (Blueprint $table) {
            $table->enum('status', ['draft', 'submitted', 'finalized'])->default('draft')->after('major_exam_score');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('class_standings', function (Blueprint $table) {
            //
        });
    }
};
