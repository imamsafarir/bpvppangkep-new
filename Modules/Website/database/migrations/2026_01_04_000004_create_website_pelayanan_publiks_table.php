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
        Schema::create('website_pelayanan_publiks', function (Blueprint $table) {
            $table->id();
            $table->text('maklumat_pelayanan')->nullable();
            $table->text('standar_pelayanan')->nullable();
            $table->string('foto_alur_pelayanan')->nullable();
            $table->text('deskripsi_alur_pelayanan')->nullable();
            $table->text('survey_kepuasan_masyarakat')->nullable();
            $table->text('survey_kebutuhan_pelatihan')->nullable();
            $table->text('survey_kebekerjaan')->nullable();
            $table->text('indeks_kepuasan_masyarakat')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_pelayanan_publiks');
    }
};
