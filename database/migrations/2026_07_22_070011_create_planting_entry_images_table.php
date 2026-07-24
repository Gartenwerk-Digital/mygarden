<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planting_entry_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('planting_entry_id')->constrained('planting_entries')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('planting_entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planting_entry_images');
    }
};
