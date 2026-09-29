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
        Schema::create('website_berita_dan_galeris', function (Blueprint $table) {
            $table->id();
            $table->string('jenis');
            $table->string('judul_berita')->nullable();
            $table->text('tags')->nullable();
            $table->text('konten_berita')->nullable();
            $table->text('file_foto')->nullable();
            $table->string('keterangan_galeri')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_berita_dan_galeris');
    }
};
