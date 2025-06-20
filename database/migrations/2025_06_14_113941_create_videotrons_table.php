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
        Schema::create('videotrons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->comment('Nama/ID unik videotron, e.g., VTR-JKT-01');
            $table->string('location_name');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 11, 7)->nullable();
            $table->string('resolution')->nullable()->comment('e.g., 1920x1080');
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('videotrons');
    }
};
