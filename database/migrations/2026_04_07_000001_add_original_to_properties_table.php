<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('properties')) {
            return; // properties table owned by the Python side; nothing to do yet
        }
        if (!Schema::hasColumn('properties', 'original')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->boolean('original')->default(true)->after('specials');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('properties') && Schema::hasColumn('properties', 'original')) {
            Schema::table('properties', function (Blueprint $table) {
                $table->dropColumn('original');
            });
        }
    }
};
