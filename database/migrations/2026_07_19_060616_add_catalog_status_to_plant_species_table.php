<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('plant_species', function (Blueprint $table): void {
            $table->string('catalog_status', 20)->default('private')->after('origin');
        });

        // System entries are always approved; existing user entries stay private
        // (the local seeder explicitly sets demo user entries to approved)
        DB::table('plant_species')->where('origin', 'system')->update(['catalog_status' => 'approved']);
    }

    public function down(): void
    {
        Schema::table('plant_species', function (Blueprint $table): void {
            $table->dropColumn('catalog_status');
        });
    }
};
