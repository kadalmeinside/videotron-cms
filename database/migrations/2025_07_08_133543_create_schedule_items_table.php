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
            
            // --- PERBAIKAN UTAMA ---
            // Langsung terhubung ke videotron dan media.
            $table->foreignUuid('videotron_id')->constrained('videotrons')->onDelete('cascade');
            $table->foreignId('media_id')->constrained('media')->onDelete('cascade');

            // Menyimpan tanggal dan waktu mulai yang presisi dalam satu kolom.
            $table->timestamp('play_at'); 

            // Menyimpan durasi untuk membantu kalkulasi di frontend dan backend.
            $table->unsignedInteger('duration_in_seconds');
            
            $table->timestamps();
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
