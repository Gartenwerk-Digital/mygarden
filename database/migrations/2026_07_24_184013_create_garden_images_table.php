<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garden_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('garden_id')->constrained('gardens')->cascadeOnDelete();
            $table->string('path', 500);
            $table->string('mime_type', 50);
            $table->unsignedBigInteger('size');
            $table->unsignedSmallInteger('sort_order')->default(0);
            // is_cover: true = Titelbild, NULL = normales Galeriebild. Niemals false speichern.
            // Der Unique-Index (garden_id, is_cover) garantiert dank MySQL-NULL-Semantik
            // maximal ein Titelbild pro Garden bei beliebig vielen NULL-Zeilen.
            $table->boolean('is_cover')->nullable();
            $table->timestamps();

            $table->unique(['garden_id', 'is_cover']);
            $table->index(['garden_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garden_images');
    }
};
