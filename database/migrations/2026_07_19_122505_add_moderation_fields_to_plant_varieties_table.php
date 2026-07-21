<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plant_varieties', function (Blueprint $table): void {
            $table->timestamp('submitted_at')->nullable()->after('archived_at');
            $table->foreignId('reviewed_by')->nullable()->after('submitted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('moderation_note')->nullable()->after('reviewed_at');
            $table->foreignId('duplicate_of_id')->nullable()->after('moderation_note')->constrained('plant_varieties')->nullOnDelete();
        });

        // Backfill: existing pending_review varieties receive submitted_at ≈ updated_at
        DB::table('plant_varieties')
            ->where('catalog_status', 'pending_review')
            ->whereNull('submitted_at')
            ->update(['submitted_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('plant_varieties', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('duplicate_of_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn(['submitted_at', 'reviewed_at', 'moderation_note']);
        });
    }
};
