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
        Schema::create('media_playlist', function (Blueprint $table) {
            $table->foreignId('media_id')->constrained('media')->onDelete('cascade');
            $table->foreignId('playlist_id')->constrained('playlists')->onDelete('cascade');
            $table->integer('play_order')->default(0)->comment('Urutan putar dalam playlist');
            
            // Primary key gabungan untuk mencegah duplikasi
            $table->primary(['media_id', 'playlist_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_playlist');
    }
};
