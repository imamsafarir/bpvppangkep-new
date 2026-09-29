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
        Schema::create('sosmedhub_social_settings', function (Blueprint $table) {
            $table->id();
            $table->string('provider_name');
            $table->string('app_id')->nullable();
            $table->string('app_secret')->nullable();
            $table->string('page_id')->nullable();
            $table->text('access_token')->nullable();
            $table->string('ig_user_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sosmedhub_social_settings');
    }
};
