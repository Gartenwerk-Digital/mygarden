<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plant_varieties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('plant_species_id')->constrained()->restrictOnDelete();
            $table->string('origin', 20);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 150);
            $table->string('breeder', 150)->nullable();
            $table->unsignedSmallInteger('year_introduced')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['plant_species_id', 'name']);
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plant_varieties');
    }
};
