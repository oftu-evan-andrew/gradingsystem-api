<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->after('status');
            $table->unsignedBigInteger('submitted_by')->nullable()->after('submitted_at');
            $table->unsignedBigInteger('last_modified_by')->nullable()->after('submitted_by');
            $table->timestamp('last_modified_at')->nullable()->after('last_modified_by');

            $table->foreign('submitted_by')->references('professor_id')->on('professors')->onDelete('set null');
            $table->foreign('last_modified_by')->references('professor_id')->on('professors')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('grades', function (Blueprint $table) {
            $table->dropForeign(['submitted_by']);
            $table->dropForeign(['last_modified_by']);
            $table->dropColumn(['submitted_at', 'submitted_by', 'last_modified_by', 'last_modified_at']);
        });
    }
};
