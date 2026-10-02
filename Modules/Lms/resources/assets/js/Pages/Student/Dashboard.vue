<script setup>
import { ref } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import {
    GraduationCap,
    BookOpen,
    CheckCircle2,
    Clock,
    Award,
    LogOut,
    ArrowRight,
    User,
    Calendar,
    Video,
    FileText,
    Lock,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    participant: Object,
    enrollments: Array,
    stats: Object,
});

const isLoggingOut = ref(false);
const logout = () => {
    isLoggingOut.value = true;
    router.post(
        "/lms/logout",
        {},
        {
            onFinish: () => {
                isLoggingOut.value = false;
            },
        },
    );
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return "";
    try {
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "short",
            year: "numeric",
        });
    } catch (e) {
        return dateStr;
    }
};

const formatDateRange = (start, end) => {
    if (!start && !end) return "";
    if (start && !end) return formatDateIndo(start);
    if (!start && end) return formatDateIndo(end);
    if (start === end) return formatDateIndo(start);
    return `${formatDateIndo(start)} - ${formatDateIndo(end)}`;
};
</script>

<template>
    <div
        class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col"
    >
        <Head title="Dashboard Siswa LMS - BPVP Pangkep" />

        <!-- Top Navigation Bar -->
        <header
            class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-sm"
        >
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="p-2 bg-indigo-600 text-white rounded-xl shadow-md"
                    >
                        <GraduationCap class="w-6 h-6" />
                    </span>
                    <div>
                        <h1
                            class="text-base font-black text-slate-900 dark:text-white leading-tight"
                        >
                            LMS BPVP Pangkep
                        </h1>
                        <p
                            class="text-[11px] text-slate-500 dark:text-slate-400"
                        >
                            Ruang Belajar & Presensi Vokasi
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-4">
                    <div class="hidden sm:block text-right">
                        <p
                            class="text-xs font-bold text-slate-900 dark:text-white"
                        >
                            {{ participant.name }}
                        </p>
                        <p class="text-[10px] text-slate-500 font-mono">
                            {{ participant.email }}
                        </p>
                    </div>

                    <button
                        @click="logout"
                        :disabled="isLoggingOut"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-rose-600 hover:text-rose-700 bg-rose-50 dark:bg-rose-950/50 hover:bg-rose-100 dark:hover:bg-rose-900/60 rounded-lg transition-colors disabled:opacity-50"
                        title="Keluar dari akun LMS"
                    >
                        <Loader2 v-if="isLoggingOut" class="w-3.5 h-3.5 animate-spin" />
                        <LogOut v-else class="w-3.5 h-3.5" />
                        <span class="hidden sm:inline">{{ isLoggingOut ? "Keluar..." : "Keluar" }}</span>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content Container -->
        <main
            class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8"
        >
            <!-- Welcome Banner -->
            <div
                class="bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 rounded-2xl p-6 sm:p-8 text-white shadow-xl relative overflow-hidden"
            >
                <div class="relative z-10 max-w-2xl space-y-3">
                    <span
                        class="px-2.5 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 ring-1 ring-indigo-400/30"
                    >
                        Selamat Datang di Portal LMS
                    </span>
                    <h2
                        class="text-2xl sm:text-3xl font-black tracking-tight text-white"
                    >
                        Halo, {{ participant.name }}!
                    </h2>
                    <p
                        class="text-xs sm:text-sm text-slate-300 leading-relaxed"
                    >
                        Silakan ikuti sesi Zoom tatap muka tepat waktu atau
                        pelajari unit kompetensi secara mandiri. Sertifikat
                        resmi ber-QR TTE akan langsung aktif setelah Anda
                        melakukan presensi.
                    </p>
                </div>
                <div
                    class="absolute -right-10 -bottom-10 opacity-10 text-white pointer-events-none"
                >
                    <GraduationCap class="w-72 h-72" />
                </div>
            </div>

            <!-- Stats Metrics Bar -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div
                    class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4"
                >
                    <span
                        class="p-3 bg-indigo-50 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400 rounded-xl"
                    >
                        <BookOpen class="w-6 h-6" />
                    </span>
                    <div>
                        <p
                            class="text-xs font-semibold text-slate-500 dark:text-slate-400"
                        >
                            Kelas Diikuti
                        </p>
                        <p
                            class="text-2xl font-black text-slate-900 dark:text-white mt-0.5"
                        >
                            {{ stats.total_enrolled || 0 }}
                        </p>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4"
                >
                    <span
                        class="p-3 bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 rounded-xl"
                    >
                        <CheckCircle2 class="w-6 h-6" />
                    </span>
                    <div>
                        <p
                            class="text-xs font-semibold text-slate-500 dark:text-slate-400"
                        >
                            Kelas Selesai / Lulus
                        </p>
                        <p
                            class="text-2xl font-black text-emerald-600 dark:text-emerald-400 mt-0.5"
                        >
                            {{ stats.completed || 0 }}
                        </p>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm flex items-center gap-4"
                >
                    <span
                        class="p-3 bg-amber-50 dark:bg-amber-950 text-amber-600 dark:text-amber-400 rounded-xl"
                    >
                        <Award class="w-6 h-6" />
                    </span>
                    <div>
                        <p
                            class="text-xs font-semibold text-slate-500 dark:text-slate-400"
                        >
                            Sertifikat Diterbitkan
                        </p>
                        <p
                            class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-0.5"
                        >
                            {{ stats.completed || 0 }}
                        </p>
                    </div>
                </div>
            </div>

            <!-- Enrolled Courses Section -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Kelas Pelatihan Anda
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Pilih kelas di bawah ini untuk memulai belajar atau
                            melakukan absensi online.
                        </p>
                    </div>
                </div>

                <div
                    v-if="enrollments && enrollments.length > 0"
                    class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                >
                    <div
                        v-for="e in enrollments"
                        :key="e.id"
                        class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden"
                    >
                        <div>
                            <!-- Header Cover -->
                            <div
                                class="relative h-40 bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950"
                            >
                                <img
                                    v-if="e.course?.cover_image"
                                    :src="'/storage/' + e.course.cover_image"
                                    :alt="e.course?.title"
                                    class="w-full h-full object-cover"
                                />
                                <div
                                    v-else
                                    class="w-full h-full flex items-center justify-center text-white/30"
                                >
                                    <GraduationCap class="w-12 h-12" />
                                </div>

                                <!-- Status Badge Overlay -->
                                <div class="absolute top-3 left-3">
                                    <!-- Draft Badge -->
                                    <span
                                        v-if="e.course?.status === 'draft'"
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-bold bg-slate-900/90 text-amber-300 shadow-md border border-amber-500/30 backdrop-blur-sm"
                                    >
                                        <Lock
                                            class="w-3.5 h-3.5 text-amber-400"
                                        />
                                        DRAFT / BELUM DIBUKA
                                    </span>
                                    <span
                                        v-else-if="e.status === 'completed'"
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-emerald-500 text-white shadow-md"
                                    >
                                        <CheckCircle2 class="w-3.5 h-3.5" />
                                        SUDAH MENGIKUTI
                                    </span>
                                    <span
                                        v-else-if="
                                            e.status !== 'completed' &&
                                            e.course?.is_zoom_attendance_open
                                        "
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-rose-600 text-white shadow-md animate-pulse"
                                    >
                                        <Video class="w-3.5 h-3.5" />
                                        ABSEN ONLINE TERBUKA
                                    </span>
                                    <span
                                        v-else-if="
                                            e.status !== 'completed' &&
                                            e.course?.zoom_status === 'live'
                                        "
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-black bg-rose-600 text-white shadow-md animate-pulse"
                                    >
                                        <Video class="w-3.5 h-3.5" />
                                        LIVE ONLINE MEETING
                                    </span>
                                    <span
                                        v-else-if="
                                            e.course?.zoom_status === 'ended'
                                        "
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-600/90 text-white backdrop-blur-sm"
                                    >
                                        {{
                                            e.progress_percentage > 0
                                                ? "SEDANG BELAJAR MANDIRI"
                                                : "JALUR 2: BELAJAR MANDIRI"
                                        }}
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-black/60 text-white backdrop-blur-sm"
                                    >
                                        {{
                                            e.course?.zoom_status === "upcoming"
                                                ? "TERJADWAL ONLINE MEETING"
                                                : e.progress_percentage > 0
                                                  ? "SEDANG BELAJAR"
                                                  : "TERDAFTAR"
                                        }}
                                    </span>
                                </div>

                                <div
                                    v-if="e.course?.batch_name"
                                    class="absolute top-3 right-3"
                                >
                                    <span
                                        class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-white/20 text-white backdrop-blur-sm"
                                    >
                                        {{ e.course.batch_name }}
                                    </span>
                                </div>
                            </div>

                            <!-- Course Info Body -->
                            <div class="p-5 space-y-4">
                                <div>
                                    <div
                                        class="flex items-center gap-2 flex-wrap text-xs mb-1"
                                    >
                                        <span
                                            v-if="e.course?.category"
                                            class="text-[11px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider"
                                        >
                                            {{ e.course.category }}
                                        </span>
                                        <span
                                            v-if="e.course?.category"
                                            class="text-slate-300 dark:text-slate-600"
                                            >&bull;</span
                                        >
                                        <span
                                            class="inline-flex items-center gap-1 font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-md border border-amber-200/60 dark:border-amber-900/40 text-[10px]"
                                        >
                                            <Calendar
                                                class="w-3 h-3 text-amber-500"
                                            />
                                            {{
                                                e.course?.duration_in_days || 1
                                            }}
                                            Hari Pelatihan
                                        </span>
                                    </div>
                                    <h4
                                        class="text-base font-bold text-slate-900 dark:text-white line-clamp-2 mt-0.5"
                                    >
                                        {{ e.course?.title }}
                                    </h4>
                                    <div class="flex flex-col gap-1 mt-1.5">
                                        <p
                                            v-if="e.course?.instructor_name"
                                            class="text-xs text-slate-500 dark:text-slate-400"
                                        >
                                            Instruktur:
                                            <strong>{{
                                                e.course.instructor_name
                                            }}</strong>
                                        </p>
                                        <p
                                            v-if="
                                                e.course?.start_date ||
                                                e.course?.end_date
                                            "
                                            class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5"
                                        >
                                            <Clock
                                                class="w-3.5 h-3.5 text-slate-400 shrink-0"
                                            />
                                            <span>{{
                                                formatDateRange(
                                                    e.course.start_date,
                                                    e.course.end_date,
                                                )
                                            }}</span>
                                        </p>
                                    </div>
                                </div>

                                <!-- Progress Bar Section -->
                                <div
                                    class="space-y-1.5 p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl"
                                >
                                    <div
                                        class="flex items-center justify-between text-xs font-bold"
                                    >
                                        <span
                                            class="text-slate-600 dark:text-slate-400"
                                            >Progress Pembelajaran:</span
                                        >
                                        <span
                                            :class="
                                                e.progress_percentage >= 100
                                                    ? 'text-emerald-600'
                                                    : 'text-indigo-600'
                                            "
                                        >
                                            {{ e.progress_percentage || 0 }}%
                                        </span>
                                    </div>
                                    <div
                                        class="w-full bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden"
                                    >
                                        <div
                                            class="h-full rounded-full transition-all duration-500"
                                            :class="
                                                e.progress_percentage >= 100
                                                    ? 'bg-emerald-500'
                                                    : 'bg-indigo-600'
                                            "
                                            :style="{
                                                width: `${e.progress_percentage || 0}%`,
                                            }"
                                        ></div>
                                    </div>
                                    <p
                                        v-if="e.status === 'completed'"
                                        class="text-[10px] text-emerald-600 dark:text-emerald-400 font-semibold mt-1"
                                    >
                                        &check; Lulus via
                                        {{
                                            e.attendance_path === "live_zoom"
                                                ? "Sesi Zoom Tatap Muka"
                                                : "Pembelajaran Mandiri"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Card Actions -->
                        <div class="p-5 pt-0 space-y-2">
                            <!-- Case 1: Course is Draft (Cannot be entered) -->
                            <div v-if="e.course?.status === 'draft'">
                                <button
                                    type="button"
                                    disabled
                                    class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-slate-400 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700/60 cursor-not-allowed select-none"
                                >
                                    <Lock class="w-3.5 h-3.5 text-slate-400" />
                                    <span
                                        >Kelas Belum Dibuka (Masih Draft)</span
                                    >
                                </button>
                                <p
                                    class="text-[10px] text-center text-slate-400 dark:text-slate-500 mt-1"
                                >
                                    Admin pelatihan belum membuka kelas ini
                                    untuk peserta.
                                </p>
                            </div>

                            <!-- Case 2: Course is Published -->
                            <template v-else>
                                <!-- Download Certificate button if issued -->
                                <a
                                    v-if="
                                        e.status === 'completed' &&
                                        e.certificate_hash
                                    "
                                    :href="`/lms/certificates/${e.id}/download`"
                                    target="_blank"
                                    class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-amber-900 bg-amber-400 hover:bg-amber-300 transition-colors shadow-sm"
                                >
                                    <Award class="w-4 h-4" />
                                    <span>Unduh Sertifikat PDF Resmi</span>
                                </a>

                                <!-- Primary Enter Classroom Action -->
                                <Link
                                    :href="`/lms/learn/${e.course?.slug}`"
                                    class="w-full inline-flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white transition-all shadow-sm"
                                    :class="
                                        e.course?.zoom_status === 'live'
                                            ? 'bg-rose-600 hover:bg-rose-700 animate-pulse'
                                            : 'bg-indigo-600 hover:bg-indigo-700'
                                    "
                                >
                                    <span v-if="e.status === 'completed'">
                                        Buka Kembali Unit Kompetensi
                                    </span>
                                    <span
                                        v-else-if="
                                            e.course?.zoom_status === 'live'
                                        "
                                    >
                                        Gabung Sesi Live Zoom (Jalur 1)
                                    </span>
                                    <span
                                        v-else-if="
                                            e.course?.zoom_status === 'ended'
                                        "
                                    >
                                        Lanjut Belajar Mandiri (Jalur 2) & Absen
                                    </span>
                                    <span
                                        v-else-if="
                                            e.course?.zoom_status === 'upcoming'
                                        "
                                    >
                                        Masuk Ruang Kelas (Jalur 1 Zoom)
                                    </span>
                                    <span v-else>
                                        Masuk Ruang Belajar & Absen
                                    </span>
                                    <ArrowRight class="w-3.5 h-3.5" />
                                </Link>
                            </template>
                        </div>
                    </div>
                </div>

                <div
                    v-else
                    class="bg-white dark:bg-slate-900 p-12 text-center rounded-2xl border border-dashed border-slate-300 dark:border-slate-800"
                >
                    <BookOpen class="w-12 h-12 mx-auto text-slate-400 mb-3" />
                    <h4
                        class="text-base font-bold text-slate-800 dark:text-slate-200"
                    >
                        Belum Ada Kelas Terdaftar
                    </h4>
                    <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">
                        Alamat email Anda belum didaftarkan ke dalam kelas
                        pelatihan manapun oleh Admin BPVP Pangkep.
                    </p>
                </div>
            </div>
        </main>
    </div>
</template>
