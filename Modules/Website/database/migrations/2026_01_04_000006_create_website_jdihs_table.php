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
        Schema::create('website_jdihs', function (Blueprint $table) {
            $table->id();
            $table->string('status_peraturan');
            $table->string('judul_peraturan');
            $table->string('nomor_peraturan');
            $table->string('file_path');
            $table->text('tentang')->nullable();
            $table->unsignedBigInteger('jumlah_diunduh')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_jdihs');
    }
};
