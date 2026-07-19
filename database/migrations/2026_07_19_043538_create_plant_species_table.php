<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_species', function (Blueprint $table): void {
            $table->id();
            $table->string('origin', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('common_name', 150);
            $table->string('botanical_name', 200)->nullable()->unique();
            $table->string('alternate_name', 150)->nullable();
            $table->string('category', 30);
            $table->text('description')->nullable();
            $table->string('lifecycle', 30)->nullable();
            $table->string('hardiness_note', 255)->nullable();
            $table->json('sun_exposures')->nullable();
            $table->json('soil_types')->nullable();
            $table->json('moisture_levels')->nullable();
            $table->unsignedSmallInteger('height_min_cm')->nullable();
            $table->unsignedSmallInteger('height_max_cm')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('category');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_species');
    }
};
