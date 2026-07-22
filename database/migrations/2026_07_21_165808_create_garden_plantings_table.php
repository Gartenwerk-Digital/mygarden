<?php

use App\Enums\PlantingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('garden_plantings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('garden_id')->constrained('gardens')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->restrictOnDelete();
            $table->foreignId('plant_species_id')->constrained('plant_species')->restrictOnDelete();
            $table->foreignId('plant_variety_id')->nullable()->constrained('plant_varieties')->restrictOnDelete();
            $table->string('status', 20)->default(PlantingStatus::Planned->value);
            $table->timestamp('removed_at')->nullable();
            $table->decimal('quantity', 10, 2)->nullable();
            $table->string('quantity_unit', 20)->nullable();
            $table->string('nickname', 100)->nullable();
            $table->text('notes')->nullable();
            $table->date('planted_at')->nullable();
            $table->date('acquired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('garden_id');
            $table->index('area_id');
            $table->index('plant_species_id');
            $table->index(['garden_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('garden_plantings');
    }
};
