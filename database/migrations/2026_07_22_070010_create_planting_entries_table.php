<?php

use App\Enums\EntryType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planting_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('garden_planting_id')->constrained('garden_plantings')->cascadeOnDelete();
            $table->string('entry_type', 20)->default(EntryType::Observation->value);
            $table->text('notes')->nullable();
            $table->date('occurred_at');
            $table->timestamps();

            $table->index('garden_planting_id');
            $table->index(['garden_planting_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planting_entries');
    }
};
