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
        Schema::create('synced_play_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('videotron_id')->constrained('videotrons')->onDelete('cascade');
            $table->foreignId('media_id')->nullable();
            $table->string('event_type');
            $table->text('message');
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('synced_play_logs');
    }
};
