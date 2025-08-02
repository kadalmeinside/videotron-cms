<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('videotrons', function (Blueprint $table) {
            $table->integer('app_version_code')->nullable()->after('last_seen_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('videotrons', function (Blueprint $table) {
            $table->dropColumn('app_version_code');
        });
    }
};
