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
        Schema::create('shortlink_links', function (Blueprint $table) {
            $table->id();
            $table->string('pegawai_name');
            $table->string('code', 20)->unique();
            $table->text('destination_url');
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->boolean('is_capture_active')->default(false);
            $table->json('capture_fields')->nullable();
            $table->string('custom_title')->nullable();
            $table->text('custom_description')->nullable();
            $table->string('custom_button_text')->nullable();
            $table->text('spreadsheet_webhook_url')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shortlink_links');
    }
};
