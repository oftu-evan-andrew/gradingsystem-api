<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professors', function (Blueprint $table) {
            $table->renameColumn('id', 'professor_id');
        });
    }

    public function down(): void
    {
        Schema::table('professors', function (Blueprint $table) {
            $table->renameColumn('professor_id', 'id');
        });
    }
};
