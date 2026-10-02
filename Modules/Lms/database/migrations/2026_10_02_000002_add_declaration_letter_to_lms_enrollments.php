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
        Schema::table('lms_enrollments', function (Blueprint $table) {
            $table->string('declaration_letter_path')->nullable();
            $table->timestamp('declaration_signed_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lms_enrollments', function (Blueprint $table) {
            $table->dropColumn(['declaration_letter_path', 'declaration_signed_at']);
        });
    }
};
