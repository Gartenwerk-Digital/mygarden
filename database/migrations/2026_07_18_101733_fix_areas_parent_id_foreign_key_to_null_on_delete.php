<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('areas', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->foreign('parent_id')->references('id')->on('areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('areas', function (Blueprint $table): void {
            $table->dropForeign(['parent_id']);
            $table->foreign('parent_id')->references('id')->on('areas')->restrictOnDelete();
        });
    }
};
