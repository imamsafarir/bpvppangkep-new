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
        // 1. Indexes for lms_lesson_progress
        if (Schema::hasTable('lms_lesson_progress')) {
            Schema::table('lms_lesson_progress', function (Blueprint $table) {
                $table->index(['enrollment_id', 'is_completed'], 'idx_llp_enrollment_completed');
            });
        }

        // 2. Indexes for lms_quiz_attempts
        if (Schema::hasTable('lms_quiz_attempts')) {
            Schema::table('lms_quiz_attempts', function (Blueprint $table) {
                $table->index(['enrollment_id', 'quiz_id'], 'idx_lqa_enrollment_quiz');
                $table->index(['enrollment_id', 'is_passed'], 'idx_lqa_enrollment_passed');
            });
        }

        // 3. Indexes for lms_enrollments
        if (Schema::hasTable('lms_enrollments')) {
            Schema::table('lms_enrollments', function (Blueprint $table) {
                $table->index(['participant_id', 'status'], 'idx_lenr_participant_status');
                $table->index(['course_id', 'status'], 'idx_lenr_course_status');
            });
        }

        // 4. Indexes for lms_modules
        if (Schema::hasTable('lms_modules')) {
            Schema::table('lms_modules', function (Blueprint $table) {
                $table->index(['course_id', 'day_number'], 'idx_lmod_course_day');
            });
        }

        // 5. Indexes for lms_lessons
        if (Schema::hasTable('lms_lessons')) {
            Schema::table('lms_lessons', function (Blueprint $table) {
                $table->index(['course_id', 'module_id'], 'idx_lles_course_module');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('lms_lessons')) {
            Schema::table('lms_lessons', function (Blueprint $table) {
                $table->dropIndex('idx_lles_course_module');
            });
        }

        if (Schema::hasTable('lms_modules')) {
            Schema::table('lms_modules', function (Blueprint $table) {
                $table->dropIndex('idx_lmod_course_day');
            });
        }

        if (Schema::hasTable('lms_enrollments')) {
            Schema::table('lms_enrollments', function (Blueprint $table) {
                $table->dropIndex('idx_lenr_participant_status');
                $table->dropIndex('idx_lenr_course_status');
            });
        }

        if (Schema::hasTable('lms_quiz_attempts')) {
            Schema::table('lms_quiz_attempts', function (Blueprint $table) {
                $table->dropIndex('idx_lqa_enrollment_quiz');
                $table->dropIndex('idx_lqa_enrollment_passed');
            });
        }

        if (Schema::hasTable('lms_lesson_progress')) {
            Schema::table('lms_lesson_progress', function (Blueprint $table) {
                $table->dropIndex('idx_llp_enrollment_completed');
            });
        }
    }
};
