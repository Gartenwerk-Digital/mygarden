<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 30)->unique()->after('name');
            $table->timestamp('username_changed_at')->nullable()->after('username');
            $table->text('bio')->nullable()->after('email');
            $table->string('avatar_path', 255)->nullable()->after('bio');
            $table->string('country', 2)->nullable()->after('avatar_path');
            $table->string('region', 100)->nullable()->after('country');
            $table->string('profile_visibility', 20)->default('private')->after('region');
            $table->timestamp('terms_accepted_at')->nullable()->after('profile_visibility');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'username',
                'username_changed_at',
                'bio',
                'avatar_path',
                'country',
                'region',
                'profile_visibility',
                'terms_accepted_at',
            ]);
        });
    }
};
