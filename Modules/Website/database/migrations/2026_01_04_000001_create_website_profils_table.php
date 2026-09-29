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
        Schema::create('website_profils', function (Blueprint $table) {
            $table->id();
            $table->string('chief_name')->nullable();
            $table->string('chief_nip')->nullable();
            $table->string('chief_photo_path')->nullable();
            $table->text('sambutan_kepala')->nullable();
            $table->text('tentang_kami')->nullable();
            $table->text('ppid')->nullable();
            $table->text('tugas_fungsi')->nullable();
            $table->text('visi_misi')->nullable();
            $table->string('struktur_organisasi')->nullable();
            $table->json('pejabat_struktural')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_profils');
    }
};
