<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('garden_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('areas')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('type', 30);
            $table->text('description')->nullable();
            $table->decimal('size_sqm', 10, 2)->nullable();
            $table->string('sun_exposure', 20)->nullable();
            $table->string('soil_type', 20)->nullable();
            $table->string('moisture_level', 20)->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};
