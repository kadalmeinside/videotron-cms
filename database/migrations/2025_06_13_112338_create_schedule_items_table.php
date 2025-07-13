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
        Schema::create('schedule_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('schedule_id')->constrained('schedules')->onDelete('cascade');
            
            $table->foreignId('media_id')->constrained('media')->onDelete('cascade');

            $table->timestamp('play_at'); 
            $table->unsignedInteger('duration_in_seconds');
            
            $table->timestamps();
            $table->index(['schedule_id', 'play_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_items');
    }
};
