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
        Schema::create('sosmedhub_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('editor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('instruktur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('pegawai_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('jenis_konten')->nullable();
            $table->string('nama_kegiatan');
            $table->date('tanggal_kegiatan');
            $table->date('rencana_tayang')->nullable();
            $table->text('brief')->nullable();
            $table->text('caption')->nullable();
            $table->string('link_referensi')->nullable();
            $table->text('link_media_mentah')->nullable();
            $table->text('link_hasil_edit')->nullable();
            $table->string('link_postingan')->nullable();
            $table->string('status')->default('Draft');
            $table->date('tanggal_posting')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sosmedhub_contents');
    }
};
