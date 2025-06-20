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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->boolean('is_approved')->default(false);
            $table->enum('source_type', ['local', 'youtube', 'vimeo']);
            $table->text('source_value');
            $table->integer('duration')->comment('Durasi dalam detik');
            $table->string('top_banner_path')->nullable();
            $table->string('bottom_banner_path')->nullable();
            $table->text('running_text')->nullable();
            $table->enum('theme_type', ['solid', 'gradient'])->default('solid');
            $table->string('theme_color_1')->default('#000000');
            $table->string('theme_color_2')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
