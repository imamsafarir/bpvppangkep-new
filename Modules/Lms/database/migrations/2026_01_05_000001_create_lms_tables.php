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
        // 1. Courses Table
        if (! Schema::hasTable('lms_courses')) {
            Schema::create('lms_courses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('title');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->string('category')->nullable();
                $table->string('cover_image')->nullable();
                $table->string('status')->default('draft'); // draft, published, archived
                $table->string('batch_name')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('instructor_name')->nullable();
                $table->text('zoom_link')->nullable();
                $table->string('zoom_meeting_id')->nullable();
                $table->string('zoom_passcode')->nullable();
                $table->dateTime('zoom_start_at')->nullable();
                $table->dateTime('zoom_end_at')->nullable();
                $table->boolean('is_zoom_attendance_open')->default(false);
                $table->timestamp('zoom_attendance_opened_at')->nullable();
                $table->integer('zoom_attendance_duration_minutes')->nullable()->default(30);
                $table->dateTime('zoom_attendance_closed_at')->nullable();
                $table->dateTime('zoom_attendance_scheduled_at')->nullable();
                $table->string('certificate_template_path')->nullable();
                $table->json('certificate_config')->nullable();
                $table->string('certificate_number_format')->default('BPVP-PKP/LMS/{YEAR}/{BATCH}/{ID}');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        // 2. Modules Table
        if (! Schema::hasTable('lms_modules')) {
            Schema::create('lms_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('delivery_mode')->default('asinkronus'); // 'sinkronus', 'asinkronus'
                $table->integer('duration_days')->default(1);
                $table->date('scheduled_date')->nullable();
                $table->string('start_time', 20)->nullable();
                $table->string('end_time', 20)->nullable();
                $table->text('zoom_link')->nullable();
                $table->string('zoom_meeting_id', 100)->nullable();
                $table->string('zoom_passcode', 100)->nullable();
                $table->string('zoom_status')->default('upcoming');
                $table->dateTime('zoom_start_at')->nullable();
                $table->dateTime('zoom_end_at')->nullable();
                $table->dateTime('zoom_attendance_opened_at')->nullable();
                $table->dateTime('zoom_attendance_closed_at')->nullable();
                $table->integer('zoom_attendance_duration_minutes')->nullable();
                $table->dateTime('zoom_attendance_scheduled_at')->nullable();
                $table->text('notes')->nullable();
                $table->integer('order_index')->default(0);
                $table->integer('day_number')->default(1);
                $table->timestamps();
            });
        }

        // 3. Lessons Table
        if (! Schema::hasTable('lms_lessons')) {
            Schema::create('lms_lessons', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('lms_modules')->cascadeOnDelete();
                $table->string('title');
                $table->string('content_type')->default('article'); // 'video', 'article', 'image'
                $table->longText('content_text')->nullable();
                $table->string('media_path')->nullable();
                $table->string('video_url')->nullable();
                $table->integer('estimated_duration_minutes')->default(10);
                $table->integer('order_index')->default(0);
                $table->timestamps();
            });
        }

        // 4. Participants Table
        if (! Schema::hasTable('lms_participants')) {
            Schema::create('lms_participants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('name');
                $table->string('email')->index();
                $table->string('training_transaction_code', 100)->nullable()->index();
                $table->string('nik', 30)->nullable()->index();
                $table->string('phone', 30)->nullable();
                $table->string('agency_or_institution')->nullable();
                $table->string('gender', 10)->nullable(); // L, P
                $table->text('address')->nullable();
                $table->timestamps();
            });
        }

        // 5. Enrollments Table
        if (! Schema::hasTable('lms_enrollments')) {
            Schema::create('lms_enrollments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('participant_id')->constrained('lms_participants')->cascadeOnDelete();
                $table->string('training_transaction_code', 100)->nullable()->index();
                $table->string('status')->default('enrolled'); // enrolled, in_progress, completed
                $table->string('attendance_path')->default('none'); // none, live_zoom, self_study
                $table->timestamp('attendance_at')->nullable();
                $table->decimal('progress_percentage', 5, 2)->default(0.00);
                $table->timestamp('completed_at')->nullable();
                $table->string('certificate_number')->nullable()->unique();
                $table->string('certificate_hash', 64)->nullable()->unique()->index();
                $table->timestamp('certificate_issued_at')->nullable();
                $table->timestamps();

                $table->unique(['course_id', 'participant_id']);
            });
        }

        // 6. Lesson Progress Table
        if (! Schema::hasTable('lms_lesson_progress')) {
            Schema::create('lms_lesson_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('enrollment_id')->constrained('lms_enrollments')->cascadeOnDelete();
                $table->foreignId('lesson_id')->constrained('lms_lessons')->cascadeOnDelete();
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();

                $table->unique(['enrollment_id', 'lesson_id']);
            });
        }

        // 7. Quizzes Table
        if (! Schema::hasTable('lms_quizzes')) {
            Schema::create('lms_quizzes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('lms_modules')->cascadeOnDelete();
                $table->string('title');
                $table->text('description')->nullable();
                $table->integer('passing_score')->default(70);
                $table->integer('time_limit_minutes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // 8. Quiz Questions Table
        if (! Schema::hasTable('lms_quiz_questions')) {
            Schema::create('lms_quiz_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('lms_quizzes')->cascadeOnDelete();
                $table->text('question_text');
                $table->string('question_type')->default('multiple_choice');
                $table->json('options');
                $table->string('correct_answer');
                $table->text('explanation')->nullable();
                $table->integer('points')->default(10);
                $table->integer('order_index')->default(0);
                $table->timestamps();
            });
        }

        // 9. Quiz Attempts Table
        if (! Schema::hasTable('lms_quiz_attempts')) {
            Schema::create('lms_quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_id')->constrained('lms_quizzes')->cascadeOnDelete();
                $table->foreignId('enrollment_id')->constrained('lms_enrollments')->cascadeOnDelete();
                $table->foreignId('participant_id')->constrained('lms_participants')->cascadeOnDelete();
                $table->float('score')->default(0);
                $table->boolean('is_passed')->default(false);
                $table->json('answers')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 10. Module Attendances Table
        if (! Schema::hasTable('lms_module_attendances')) {
            Schema::create('lms_module_attendances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('course_id')->constrained('lms_courses')->cascadeOnDelete();
                $table->foreignId('module_id')->constrained('lms_modules')->cascadeOnDelete();
                $table->foreignId('enrollment_id')->constrained('lms_enrollments')->cascadeOnDelete();
                $table->foreignId('participant_id')->constrained('lms_participants')->cascadeOnDelete();
                $table->integer('day_number')->default(1);
                $table->string('attendance_path')->default('live_zoom'); // live_zoom, self_study, manual
                $table->dateTime('attended_at');
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->unique(['enrollment_id', 'module_id'], 'enrollment_module_unique');
                $table->index(['course_id', 'day_number']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lms_module_attendances');
        Schema::dropIfExists('lms_quiz_attempts');
        Schema::dropIfExists('lms_quiz_questions');
        Schema::dropIfExists('lms_quizzes');
        Schema::dropIfExists('lms_lesson_progress');
        Schema::dropIfExists('lms_enrollments');
        Schema::dropIfExists('lms_participants');
        Schema::dropIfExists('lms_lessons');
        Schema::dropIfExists('lms_modules');
        Schema::dropIfExists('lms_courses');
    }
};
