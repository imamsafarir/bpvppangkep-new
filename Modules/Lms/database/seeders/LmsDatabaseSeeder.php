<?php

namespace Modules\Lms\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Lms\Models\Course;
use Modules\Lms\Models\Enrollment;
use Modules\Lms\Models\Lesson;
use Modules\Lms\Models\LessonProgress;
use Modules\Lms\Models\Module;
use Modules\Lms\Models\Participant;

class LmsDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Course 1
        $course1 = Course::firstOrCreate(
            ['slug' => 'pelatihan-teknisi-refrigerasi-dan-tata-udara-ac-residential'],
            [
                'title' => 'Pelatihan Teknisi Refrigerasi dan Tata Udara (AC Residential)',
                'category' => 'Teknik Pendingin',
                'batch_name' => 'Angkatan I 2026',
                'instructor_name' => 'Ir. Bambang Triyono, S.T., M.Eng.',
                'description' => 'Pelatihan berbasis kompetensi teknisi pendingin ruangan: pengoperasian, pemeliharaan preventif, perbaikan kebocoran refrigerant, dan sertifikasi BNSP.',
                'start_date' => now()->subDays(5)->format('Y-m-d'),
                'end_date' => now()->addDays(20)->format('Y-m-d'),
                'zoom_link' => 'https://zoom.us/j/81234567890?pwd=bpvppangkep2026',
                'zoom_meeting_id' => '812 3456 7890',
                'zoom_passcode' => 'bpvp2026',
                'is_zoom_attendance_open' => false,
                'status' => 'published',
                'certificate_number_format' => 'BPVP-PKP/LMS/{YEAR}/REF-1/{ID}',
            ]
        );

        // Modules & Lessons for Course 1
        $mod1 = Module::firstOrCreate(
            ['course_id' => $course1->id, 'title' => 'Modul 1: Prinsip Dasar Sistem Refrigerasi & K3'],
            [
                'description' => 'Pengenalan komponen utama pendingin ruangan dan prosedur K3 keselamatan kerja teknisi.',
                'order_index' => 1,
            ]
        );

        $l1 = Lesson::firstOrCreate(
            ['module_id' => $mod1->id, 'title' => 'Prinsip Kerja Siklus Kompresi Uap Refrigerant'],
            [
                'course_id' => $course1->id,
                'content_type' => 'article',
                'estimated_duration_minutes' => 15,
                'order_index' => 1,
                'content_text' => "Sistem refrigerasi kompresi uap bekerja dengan memindahkan kalor dari ruangan bertemperatur rendah ke lingkungan bertemperatur tinggi.\n\nEmpat komponen utama siklus refrigerasi:\n1. Kompresor: Menaikkan tekanan dan suhu refrigeran berfasa gas.\n2. Kondensor: Mengembunkan refrigeran bertekanan tinggi menjadi cair dengan melepaskan panas ke udara luar.\n3. Katup Ekspansi / Pipa Kapiler: Menurunkan tekanan dan suhu refrigeran cair secara mendadak.\n4. Evaporator: Menyerap kalor dari dalam ruangan sehingga udara menjadi sejuk dan dingin.\n\nPastikan selalu mengenakan sarung tangan isolasi dan kacamata pelindung saat bekerja dengan fluida refrigeran bertekanan.",
            ]
        );

        $l2 = Lesson::firstOrCreate(
            ['module_id' => $mod1->id, 'title' => 'Video Praktik Penggunaan Manifold Gauge & Deteksi Tekanan'],
            [
                'course_id' => $course1->id,
                'content_type' => 'video',
                'estimated_duration_minutes' => 20,
                'order_index' => 2,
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'content_text' => 'Video panduan pemasangan selang biru (low pressure) pada service port suction dan pembacaan manometer secara tepat.',
            ]
        );

        $mod2 = Module::firstOrCreate(
            ['course_id' => $course1->id, 'title' => 'Modul 2: Prosedur Pengosongan (Vakum) & Pengisian Freon R32'],
            [
                'description' => 'Tata cara evakuasi udara kelembapan dengan pompa vakum hingga 500 micron.',
                'order_index' => 2,
            ]
        );

        $l3 = Lesson::firstOrCreate(
            ['module_id' => $mod2->id, 'title' => 'SOP Pemvakuman Sistem AC Sesuai Standar Ramah Lingkungan'],
            [
                'course_id' => $course1->id,
                'content_type' => 'article',
                'estimated_duration_minutes' => 25,
                'order_index' => 1,
                'content_text' => "Pemvakuman sistem AC mutlak dilakukan sebelum membuka kran service valve atau mengisi freon baru.\n\nLangkah-langkah SOP:\n1. Pasang manifold gauge pada port pengisian unit outdoor.\n2. Hubungkan selang kuning ke pompa vakum dua tingkat (two-stage vacuum pump).\n3. Nyalakan pompa vakum dan buka kran manifold.\n4. Pertahankan proses vakum minimal selama 15 menit hingga jarum mencapai -30 inHg (-76 cmHg) atau 500 micron pada digital vacuum gauge.\n5. Tutup kran manifold sebelum mematikan mesin vakum untuk mencegah oli vakum terhisap ke dalam sistem.",
            ]
        );

        // Course 2: IT Web Fullstack
        $course2 = Course::firstOrCreate(
            ['slug' => 'pelatihan-pemrograman-web-fullstack-laravel-vue'],
            [
                'title' => 'Pelatihan Pemrograman Web Fullstack Berbasis Laravel & Vue',
                'category' => 'Teknologi Informasi',
                'batch_name' => 'Angkatan II 2026',
                'instructor_name' => 'Imam Safari, S.Kom.',
                'description' => 'Pelatihan komprehensif rancang bangun aplikasi web modern menggunakan arsitektur Laravel 12, Inertia.js, Vue 3, dan Tailwind CSS.',
                'start_date' => now()->format('Y-m-d'),
                'end_date' => now()->addDays(30)->format('Y-m-d'),
                'zoom_link' => 'https://zoom.us/j/89912345678',
                'zoom_meeting_id' => '899 1234 5678',
                'zoom_passcode' => 'pangkepweb',
                'is_zoom_attendance_open' => true,
                'zoom_attendance_opened_at' => now(),
                'status' => 'published',
                'certificate_number_format' => 'BPVP-PKP/LMS/{YEAR}/IT-2/{ID}',
            ]
        );

        $mod2_1 = Module::firstOrCreate(
            ['course_id' => $course2->id, 'title' => 'Modul 1: Konsep Dasar SPA Modern dengan Inertia.js'],
            [
                'description' => 'Memahami komunikasi tanpa refresh halaman antara Laravel Backend dan Vue 3 Frontend.',
                'order_index' => 1,
            ]
        );

        $l2_1 = Lesson::firstOrCreate(
            ['module_id' => $mod2_1->id, 'title' => 'Arsitektur Inertia Monolith vs Headless API'],
            [
                'course_id' => $course2->id,
                'content_type' => 'article',
                'estimated_duration_minutes' => 15,
                'order_index' => 1,
                'content_text' => "Inertia.js menjembatani arsitektur server-side routing Laravel dengan komponen reaktif Vue.js tanpa perlu membuat endpoint REST API yang terpisah secara manual.\n\nKeuntungan utama:\n- Tidak perlu otentikasi JWT / OAuth yang kompleks untuk web internal.\n- Validasi form Laravel langsung diteruskan ke form helper Vue.\n- Transisi navigasi instan tanpa refresh halaman penuh.",
            ]
        );

        // Participants Demo
        $p1 = Participant::updateOrCreate(
            ['email' => 'peserta@bpvppangkep.id'],
            [
                'name' => 'MUHAMMAD IKHLAS, S.T.',
                'nik' => '7310041205980001',
                'phone' => '081234567890',
                'agency_or_institution' => 'CV. Celebes Teknik Pangkep',
                'gender' => 'L',
            ]
        );

        $p2 = Participant::updateOrCreate(
            ['email' => 'nurhaliza@bpvppangkep.id'],
            [
                'name' => 'NURHALIZA PUTRI, A.Md.',
                'nik' => '7310045508990002',
                'phone' => '085298765432',
                'agency_or_institution' => 'Dinas Ketenagakerjaan Pangkep',
                'gender' => 'P',
            ]
        );

        // Enrollment 1: Course 1 - Fully Completed & Certified via Zoom
        $e1 = Enrollment::firstOrCreate(
            ['course_id' => $course1->id, 'participant_id' => $p1->id],
            [
                'status' => 'completed',
                'attendance_path' => 'live_zoom',
                'attendance_at' => now()->subDays(2),
                'progress_percentage' => 100.0,
                'completed_at' => now()->subDays(2),
                'certificate_number' => 'BPVP-PKP/LMS/2026/REF-1/00001',
                'certificate_hash' => 'bpvp_tte_ref1_98a7b6c5d4e3f210a',
                'certificate_issued_at' => now()->subDays(2),
            ]
        );

        // Mark lessons complete for e1
        foreach ([$l1, $l2, $l3] as $les) {
            LessonProgress::firstOrCreate(
                ['enrollment_id' => $e1->id, 'lesson_id' => $les->id],
                ['is_completed' => true, 'completed_at' => now()->subDays(2)]
            );
        }

        // Enrollment 2: Course 2 - Enrolled & Ready for Zoom or Self Study
        $e2 = Enrollment::firstOrCreate(
            ['course_id' => $course2->id, 'participant_id' => $p1->id],
            [
                'status' => 'enrolled',
                'attendance_path' => 'none',
                'progress_percentage' => 0.0,
            ]
        );

        LessonProgress::firstOrCreate(
            ['enrollment_id' => $e2->id, 'lesson_id' => $l2_1->id],
            ['is_completed' => false]
        );

        // Participant 2 enrolled in course 1
        $e3 = Enrollment::firstOrCreate(
            ['course_id' => $course1->id, 'participant_id' => $p2->id],
            [
                'status' => 'in_progress',
                'attendance_path' => 'none',
                'progress_percentage' => 33.33,
            ]
        );

        LessonProgress::firstOrCreate(
            ['enrollment_id' => $e3->id, 'lesson_id' => $l1->id],
            ['is_completed' => true, 'completed_at' => now()->subDays(1)]
        );
        LessonProgress::firstOrCreate(
            ['enrollment_id' => $e3->id, 'lesson_id' => $l2->id],
            ['is_completed' => false]
        );
        LessonProgress::firstOrCreate(
            ['enrollment_id' => $e3->id, 'lesson_id' => $l3->id],
            ['is_completed' => false]
        );
    }
}
