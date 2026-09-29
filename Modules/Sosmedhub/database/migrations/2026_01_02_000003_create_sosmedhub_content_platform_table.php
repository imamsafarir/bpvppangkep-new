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
        Schema::create('sosmedhub_content_platform', function (Blueprint $table) {
            $table->id();
            $table->foreignId('content_id')->constrained('sosmedhub_contents')->cascadeOnDelete();
            $table->foreignId('platform_id')->constrained('sosmedhub_platforms')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sosmedhub_content_platform');
    }
};
