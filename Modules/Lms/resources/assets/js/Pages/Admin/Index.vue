<script setup>
import { ref, computed } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import {
    GraduationCap,
    Plus,
    Search,
    Filter,
    Copy,
    Settings2,
    Trash2,
    Calendar,
    Users,
    BookOpen,
    Video,
    CheckCircle2,
    Clock,
    Award,
    MoreVertical,
    FileSpreadsheet,
    Edit3,
    AlertCircle,
    Lock,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    courses: Object,
    stats: Object,
    categories: Array,
    filters: Object,
    authUser: Object,
});

// Search & Filter state
const search = ref(props.filters.search || "");
const selectedStatus = ref(props.filters.status || "all");
const selectedCategory = ref(props.filters.category || "all");

const applyFilters = () => {
    router.get(
        "/admin/lms",
        {
            search: search.value || undefined,
            status:
                selectedStatus.value !== "all"
                    ? selectedStatus.value
                    : undefined,
            category:
                selectedCategory.value !== "all"
                    ? selectedCategory.value
                    : undefined,
        },
        { preserveState: true, replace: true },
    );
};

const resetFilters = () => {
    search.value = "";
    selectedStatus.value = "all";
    selectedCategory.value = "all";
    applyFilters();
};

// Modal State: Create / Edit Course
const showCourseModal = ref(false);
const isEditing = ref(false);
const currentCourseId = ref(null);

const form = useForm({
    title: "",
    category: "",
    batch_name: "",
    instructor_name: "",
    description: "",
    start_date: "",
    end_date: "",
    zoom_date: "",
    zoom_start_time: "",
    zoom_end_time: "",
    zoom_link: "",
    zoom_meeting_id: "",
    zoom_passcode: "",
    status: "draft",
    cover_image: null,
});

const openCreateModal = () => {
    isEditing.value = false;
    currentCourseId.value = null;
    form.reset();
    form.clearErrors();
    form.status = "draft";
    showCourseModal.value = true;
};

const openEditModal = (course) => {
    isEditing.value = true;
    currentCourseId.value = course.id;
    form.clearErrors();
    form.title = course.title || "";
    form.category = course.category || "";
    form.batch_name = course.batch_name || "";
    form.instructor_name = course.instructor_name || "";
    form.description = course.description || "";
    form.start_date = course.start_date
        ? course.start_date.substring(0, 10)
        : "";
    form.end_date = course.end_date ? course.end_date.substring(0, 10) : "";
    form.status = course.status || "draft";
    form.cover_image = null;
    showCourseModal.value = true;
};

const submitCourse = () => {
    if (isEditing.value) {
        form.put(`/admin/lms/${currentCourseId.value}`, {
            onSuccess: () => {
                showCourseModal.value = false;
            },
        });
    } else {
        form.post("/admin/lms", {
            onSuccess: () => {
                showCourseModal.value = false;
            },
        });
    }
};

// Duplicate Course Modal State & Handlers
const showDuplicateModal = ref(false);
const duplicateSourceCourse = ref(null);
const duplicateForm = useForm({
    title: "",
    batch_name: "",
    instructor_name: "",
    start_date: "",
    end_date: "",
});

const openDuplicateModal = (course) => {
    duplicateSourceCourse.value = course;
    duplicateForm.clearErrors();
    duplicateForm.title = `${course.title} (Salinan)`;
    duplicateForm.batch_name = course.batch_name || "";
    duplicateForm.instructor_name =
        props.authUser?.name || course.instructor_name || "";
    duplicateForm.start_date = course.start_date
        ? course.start_date.substring(0, 10)
        : "";
    duplicateForm.end_date = course.end_date
        ? course.end_date.substring(0, 10)
        : "";
    showDuplicateModal.value = true;
};

const submitDuplicate = () => {
    if (!duplicateSourceCourse.value) return;
    duplicateForm.post(
        `/admin/lms/${duplicateSourceCourse.value.id}/duplicate`,
        {
            onSuccess: () => {
                showDuplicateModal.value = false;
            },
        },
    );
};

// Delete Course Action
const deletingCourseId = ref(null);
const deleteCourse = (course) => {
    if (
        confirm(
            `Apakah Anda yakin ingin menghapus kelas "${course.title}"? Data peserta dan materi di kelas ini akan dinonaktifkan.`,
        )
    ) {
        deletingCourseId.value = course.id;
        router.delete(`/admin/lms/${course.id}`, {
            preserveScroll: true,
            onFinish: () => {
                deletingCourseId.value = null;
            },
        });
    }
};

const handleCoverChange = (e) => {
    form.cover_image = e.target.files[0] || null;
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
    <DashboardLayout>
        <Head title="Manajemen LMS - BPVP Pangkep" />

        <div class="space-y-6 pb-12">
            <!-- Header Section -->
            <div
                class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b pb-5"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span
                            class="p-2 bg-indigo-50 text-indigo-600 rounded-lg dark:bg-indigo-950 dark:text-indigo-400"
                        >
                            <GraduationCap class="w-6 h-6" />
                        </span>
                        <div>
                            <h1
                                class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white"
                            >
                                Learning Management System (LMS)
                            </h1>
                            <p
                                class="text-sm text-slate-500 dark:text-slate-400"
                            >
                                Kelola kelas vokasi, modul pembelajaran mandiri,
                                presensi Online Meeting tatap muka, dan
                                sertifikat TTE resmi.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <Link
                        v-if="
                            authUser?.roles?.includes('super_admin') ||
                            authUser?.role === 'super_admin'
                        "
                        href="/admin/lms/participants"
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 rounded-lg text-sm font-semibold text-purple-700 dark:text-purple-300 bg-purple-50 hover:bg-purple-100 dark:bg-purple-950/40 dark:hover:bg-purple-900/50 border border-purple-200 dark:border-purple-800 shadow-sm transition-all"
                    >
                        <Users
                            class="w-4 h-4 text-purple-600 dark:text-purple-400"
                        />
                        <span>Data Seluruh Peserta</span>
                    </Link>
                    <button
                        @click="openCreateModal"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-all focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                    >
                        <Plus class="w-4 h-4" />
                        Buat Kelas Baru
                    </button>
                </div>
            </div>

            <!-- Overarching Stats Bar -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <p
                        class="text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Total Kelas
                    </p>
                    <p
                        class="text-2xl font-bold text-slate-900 dark:text-white mt-1"
                    >
                        {{ stats.total_courses || 0 }}
                    </p>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <p
                        class="text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Kelas Aktif
                    </p>
                    <p
                        class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"
                    >
                        {{ stats.active_courses || 0 }}
                    </p>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <p
                        class="text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Absen Online Buka
                    </p>
                    <div class="flex items-center gap-2 mt-1">
                        <p
                            class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                        >
                            {{ stats.live_zoom_open || 0 }}
                        </p>
                        <span
                            v-if="stats.live_zoom_open > 0"
                            class="flex h-2.5 w-2.5 relative"
                        >
                            <span
                                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"
                            ></span>
                            <span
                                class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"
                            ></span>
                        </span>
                    </div>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <p
                        class="text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Total Peserta
                    </p>
                    <p
                        class="text-2xl font-bold text-slate-900 dark:text-white mt-1"
                    >
                        {{ stats.total_participants || 0 }}
                    </p>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <p
                        class="text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Pendaftaran Kelas
                    </p>
                    <p
                        class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1"
                    >
                        {{ stats.total_enrollments || 0 }}
                    </p>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <p
                        class="text-xs font-medium text-slate-500 dark:text-slate-400"
                    >
                        Sertifikat Terbit
                    </p>
                    <p
                        class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1"
                    >
                        {{ stats.total_certified || 0 }}
                    </p>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div
                class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row gap-4 items-center justify-between"
            >
                <div class="relative w-full md:w-96">
                    <Search
                        class="w-4 h-4 absolute left-3 top-3 text-slate-400"
                    />
                    <input
                        v-model="search"
                        @keyup.enter="applyFilters"
                        type="text"
                        placeholder="Cari judul kelas, angkatan, atau kategori..."
                        class="w-full pl-9 pr-4 py-2 text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    />
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
                    <!-- Status Filter -->
                    <select
                        v-model="selectedStatus"
                        @change="applyFilters"
                        class="text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="all">Semua Status</option>
                        <option value="published">Tayang (Published)</option>
                        <option value="draft">Draf (Draft)</option>
                        <option value="archived">Diarsipkan</option>
                    </select>

                    <!-- Category Filter -->
                    <select
                        v-model="selectedCategory"
                        @change="applyFilters"
                        class="text-sm bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg px-3 py-2 text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <option value="all">Semua Kategori</option>
                        <option
                            v-for="cat in categories"
                            :key="cat"
                            :value="cat"
                        >
                            {{ cat }}
                        </option>
                    </select>

                    <button
                        @click="resetFilters"
                        class="text-xs text-slate-500 hover:text-slate-800 dark:hover:text-white px-2 py-1"
                    >
                        Reset
                    </button>
                </div>
            </div>

            <!-- Card Grid View (Main Dashboard Workspace) -->
            <div
                v-if="courses.data && courses.data.length > 0"
                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
            >
                <div
                    v-for="course in courses.data"
                    :key="course.id"
                    class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all flex flex-col justify-between overflow-hidden group"
                >
                    <div>
                        <!-- Cover / Header Banner -->
                        <div
                            class="relative h-44 w-full bg-gradient-to-r from-slate-900 via-indigo-950 to-blue-950 overflow-hidden"
                        >
                            <img
                                v-if="course.cover_image"
                                :src="'/storage/' + course.cover_image"
                                :alt="course.title"
                                class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300"
                            />
                            <div
                                v-else
                                class="w-full h-full flex flex-col items-center justify-center text-white/40 p-4 text-center"
                            >
                                <GraduationCap class="w-12 h-12 mb-1" />
                                <span
                                    class="text-xs font-semibold uppercase tracking-wider text-white/60"
                                    >BPVP Pangkep LMS</span
                                >
                            </div>

                            <!-- Status Badge -->
                            <div
                                class="absolute top-3 left-3 flex items-center gap-2"
                            >
                                <span
                                    v-if="course.status === 'published'"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/90 text-white backdrop-blur-sm"
                                >
                                    Tayang
                                </span>
                                <span
                                    v-else-if="course.status === 'draft'"
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-500/90 text-white backdrop-blur-sm"
                                >
                                    Draft
                                </span>
                                <span
                                    v-else
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-600/90 text-white backdrop-blur-sm"
                                >
                                    Arsip
                                </span>

                                <!-- Live Online Meeting Pulse Indicator -->
                                <span
                                    v-if="course.is_zoom_attendance_open"
                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-600 text-white shadow-lg animate-pulse"
                                >
                                    <Video class="w-3.5 h-3.5" />
                                    ABSEN ONLINE BUKA
                                </span>
                            </div>

                            <!-- Batch Pill -->
                            <div
                                v-if="course.batch_name"
                                class="absolute top-3 right-3"
                            >
                                <span
                                    class="px-2.5 py-1 rounded-full text-xs font-medium bg-black/50 text-white backdrop-blur-sm"
                                >
                                    {{ course.batch_name }}
                                </span>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="p-5 space-y-4">
                            <div>
                                <div
                                    class="flex items-center gap-2 flex-wrap text-xs mb-1"
                                >
                                    <span
                                        v-if="course.category"
                                        class="font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider"
                                    >
                                        {{ course.category }}
                                    </span>
                                    <span
                                        v-if="course.category"
                                        class="text-slate-300 dark:text-slate-600"
                                        >&bull;</span
                                    >
                                    <span
                                        class="inline-flex items-center gap-1 font-bold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/50 px-2 py-0.5 rounded-md border border-amber-200/60 dark:border-amber-900/40 text-[11px]"
                                    >
                                        <Calendar
                                            class="w-3 h-3 text-amber-500"
                                        />
                                        {{ course.duration_in_days || 1 }} Hari
                                        Pelatihan
                                    </span>
                                </div>
                                <h3
                                    class="text-lg font-bold text-slate-900 dark:text-white line-clamp-2 mt-0.5 group-hover:text-indigo-600 transition-colors"
                                >
                                    {{ course.title }}
                                </h3>
                                <div class="flex flex-col gap-1 mt-1.5">
                                    <p
                                        v-if="course.instructor_name"
                                        class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1"
                                    >
                                        <span>Instruktur:</span>
                                        <span
                                            class="font-medium text-slate-700 dark:text-slate-300"
                                            >{{ course.instructor_name }}</span
                                        >
                                    </p>
                                    <p
                                        v-if="
                                            course.start_date || course.end_date
                                        "
                                        class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1.5"
                                    >
                                        <Clock
                                            class="w-3.5 h-3.5 text-slate-400 shrink-0"
                                        />
                                        <span>{{
                                            formatDateRange(
                                                course.start_date,
                                                course.end_date,
                                            )
                                        }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Metrics Summary Grid on Card -->
                            <div
                                class="grid grid-cols-3 gap-2 py-3 px-3 bg-slate-50 dark:bg-slate-800/60 rounded-lg text-center border border-slate-100 dark:border-slate-800"
                            >
                                <div>
                                    <p
                                        class="text-[11px] text-slate-500 dark:text-slate-400"
                                    >
                                        Materi
                                    </p>
                                    <p
                                        class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-0.5"
                                    >
                                        {{ course.modules_count || 0 }} Modul /
                                        {{ course.lessons_count || 0 }} Bab
                                    </p>
                                </div>
                                <div
                                    class="border-x border-slate-200 dark:border-slate-700"
                                >
                                    <p
                                        class="text-[11px] text-slate-500 dark:text-slate-400"
                                    >
                                        Peserta
                                    </p>
                                    <p
                                        class="text-sm font-bold text-indigo-600 dark:text-indigo-400 mt-0.5"
                                    >
                                        {{ course.enrollments_count || 0 }}
                                        Orang
                                    </p>
                                </div>
                                <div>
                                    <p
                                        class="text-[11px] text-slate-500 dark:text-slate-400"
                                    >
                                        Lulus / Sertifikat
                                    </p>
                                    <p
                                        class="text-sm font-bold text-emerald-600 dark:text-emerald-400 mt-0.5"
                                    >
                                        {{
                                            course.completed_enrollments_count ||
                                            0
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div
                        class="p-5 pt-0 border-t border-slate-100 dark:border-slate-800/60 flex items-center justify-between gap-2 mt-2"
                    >
                        <!-- Primary Action: 1-Window Workspace (Only if can_manage) -->
                        <Link
                            v-if="course.can_manage"
                            :href="`/admin/lms/${course.id}/workspace`"
                            class="flex-1 inline-flex items-center justify-center gap-2 px-3 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm"
                        >
                            <Settings2 class="w-4 h-4" />
                            Kelola Kelas
                        </Link>
                        <div
                            v-else
                            class="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold text-slate-400 bg-slate-100 dark:bg-slate-800 dark:text-slate-500 cursor-not-allowed select-none"
                            title="Hanya instruktur penanggung jawab atau Super Admin yang dapat mengelola kelas ini"
                        >
                            <Lock class="w-3.5 h-3.5" />
                            <span>Instruktur Lain</span>
                        </div>

                        <!-- Duplicate Button (Always Available) -->
                        <button
                            @click="openDuplicateModal(course)"
                            title="Duplikasi Kelas beserta seluruh materinya"
                            class="p-2 text-slate-600 hover:text-indigo-600 dark:text-slate-400 dark:hover:text-indigo-400 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        >
                            <Copy class="w-4 h-4" />
                        </button>

                        <!-- Edit Info (Only if can_manage) -->
                        <button
                            v-if="course.can_manage"
                            @click="openEditModal(course)"
                            title="Edit Info Kelas"
                            class="p-2 text-slate-600 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        >
                            <Edit3 class="w-4 h-4" />
                        </button>

                        <!-- Delete (Only if can_manage) -->
                        <button
                            v-if="course.can_manage"
                            :disabled="deletingCourseId === course.id"
                            @click="deleteCourse(course)"
                            title="Hapus Kelas"
                            class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition-colors disabled:opacity-50 cursor-pointer"
                        >
                            <Loader2
                                v-if="deletingCourseId === course.id"
                                class="w-4 h-4 animate-spin text-rose-600"
                            />
                            <Trash2 v-else class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div
                v-else
                class="bg-white dark:bg-slate-900 rounded-2xl border border-dashed border-slate-300 dark:border-slate-800 p-12 text-center"
            >
                <div
                    class="w-16 h-16 mx-auto bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-full flex items-center justify-center mb-4"
                >
                    <GraduationCap class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-bold text-slate-900 dark:text-white">
                    Belum Ada Kelas LMS Ditemukan
                </h3>
                <p
                    class="text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto mt-1"
                >
                    Mulai dengan membuat kelas pelatihan baru atau sesuaikan
                    filter pencarian Anda di atas.
                </p>
                <button
                    @click="openCreateModal"
                    class="inline-flex items-center gap-2 mt-5 px-4 py-2 rounded-lg text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 transition-colors shadow-sm"
                >
                    <Plus class="w-4 h-4" />
                    Buat Kelas Pertama
                </button>
            </div>

            <!-- Pagination -->
            <div
                v-if="courses.links && courses.links.length > 3"
                class="flex justify-center mt-6"
            >
                <nav class="flex items-center gap-1">
                    <template v-for="(link, i) in courses.links" :key="i">
                        <Link
                            v-if="link.url"
                            :href="link.url"
                            v-html="link.label"
                            class="px-3.5 py-2 text-xs font-semibold rounded-lg border transition-colors"
                            :class="
                                link.active
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800'
                            "
                        />
                        <span
                            v-else
                            v-html="link.label"
                            class="px-3.5 py-2 text-xs font-semibold text-slate-400 border border-transparent"
                        />
                    </template>
                </nav>
            </div>
        </div>

        <!-- Modal: Buat / Edit Kelas Baru -->
        <div
            v-if="showCourseModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl border border-slate-200 dark:border-slate-800"
            >
                <!-- Modal Header -->
                <div
                    class="flex items-center justify-between p-6 border-b border-slate-200 dark:border-slate-800"
                >
                    <h2
                        class="text-lg font-bold text-slate-900 dark:text-white"
                    >
                        {{
                            isEditing
                                ? "Edit Informasi Kelas LMS"
                                : "Buat Kelas Pelatihan LMS Baru"
                        }}
                    </h2>
                    <button
                        @click="showCourseModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold"
                    >
                        &times;
                    </button>
                </div>

                <!-- Modal Body Form -->
                <form
                    @submit.prevent="submitCourse"
                    class="overflow-y-auto p-6 space-y-4 flex-1"
                >
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Judul Kelas Pelatihan
                            <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            required
                            placeholder="Contoh: Pelatihan Teknisi AC Residential Angkatan I"
                            class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <p
                            v-if="form.errors.title"
                            class="text-xs text-rose-500 mt-1"
                        >
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Kategori Pelatihan
                            </label>
                            <input
                                v-model="form.category"
                                type="text"
                                placeholder="Contoh: Refrigerasi / IT / Las"
                                class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Nama Angkatan / Batch
                            </label>
                            <input
                                v-model="form.batch_name"
                                type="text"
                                placeholder="Contoh: Angkatan I 2026"
                                class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Nama Instruktur / Pengajar
                            </label>
                            <input
                                v-model="form.instructor_name"
                                type="text"
                                placeholder="Contoh: Ir. Ahmad Fauzi, M.T."
                                class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Status Kelas
                            </label>
                            <select
                                v-model="form.status"
                                class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            >
                                <option value="draft">
                                    Draf (Belum Terlihat Siswa)
                                </option>
                                <option value="published">
                                    Tayang (Aktif)
                                </option>
                                <option value="archived">
                                    Diarsipkan (Selesai)
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Tanggal Mulai
                            </label>
                            <input
                                v-model="form.start_date"
                                type="date"
                                class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Tanggal Selesai
                            </label>
                            <input
                                v-model="form.end_date"
                                type="date"
                                class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <!-- Cover Image Upload -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Foto Sampul Kelas (Cover Image)
                        </label>
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleCoverChange"
                            class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-slate-800 dark:file:text-slate-200"
                        />
                    </div>

                    <!-- Description -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Deskripsi Ringkas Kelas
                        </label>
                        <textarea
                            v-model="form.description"
                            rows="3"
                            placeholder="Tuliskan tujuan pelatihan, kompetensi yang dicapai, atau petunjuk belajar..."
                            class="w-full text-sm px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        ></textarea>
                    </div>

                    <!-- Modal Actions -->
                    <div
                        class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3"
                    >
                        <button
                            type="button"
                            @click="showCourseModal = false"
                            class="px-4 py-2.5 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="px-5 py-2.5 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm disabled:opacity-50 inline-flex items-center gap-2 cursor-pointer"
                        >
                            <Loader2
                                v-if="form.processing"
                                class="w-4 h-4 animate-spin shrink-0"
                            />
                            <span>{{
                                form.processing
                                    ? "Menyimpan..."
                                    : isEditing
                                      ? "Simpan Perubahan"
                                      : "Buat Kelas & Buka Workspace"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal: Duplikasi Kelas -->
        <div
            v-if="showDuplicateModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 dark:border-slate-800 space-y-5 animate-in fade-in zoom-in-95 duration-150"
            >
                <div
                    class="flex items-center justify-between border-b pb-4 dark:border-slate-800"
                >
                    <div class="flex items-center gap-2.5">
                        <span
                            class="p-2 bg-indigo-50 text-indigo-600 dark:bg-indigo-950 dark:text-indigo-400 rounded-lg"
                        >
                            <Copy class="w-5 h-5" />
                        </span>
                        <div>
                            <h3
                                class="text-lg font-bold text-slate-900 dark:text-white"
                            >
                                Duplikasi Kelas Pelatihan
                            </h3>
                            <p class="text-xs text-slate-500">
                                Salin seluruh Unit & Elemen Kompetensi ke kelas
                                baru Anda.
                            </p>
                        </div>
                    </div>
                    <button
                        @click="showDuplicateModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-lg font-bold"
                    >
                        &times;
                    </button>
                </div>

                <!-- Info Notice -->
                <div
                    class="p-3 bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-900/60 rounded-xl text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2"
                >
                    <AlertCircle class="w-4 h-4 shrink-0 mt-0.5" />
                    <span>
                        Seluruh kurikulum (Unit Kompetensi, Elemen materi, dan
                        Kuis) dari kelas sumber akan otomatis diduplikasi ke
                        kelas baru ini milik Anda.
                    </span>
                </div>

                <form @submit.prevent="submitDuplicate" class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Judul Kelas Baru *
                        </label>
                        <input
                            v-model="duplicateForm.title"
                            type="text"
                            required
                            class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                        />
                        <p
                            v-if="duplicateForm.errors.title"
                            class="text-xs text-rose-500 mt-1"
                        >
                            {{ duplicateForm.errors.title }}
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Angkatan / Gelombang
                            </label>
                            <input
                                v-model="duplicateForm.batch_name"
                                type="text"
                                placeholder="Contoh: Gelombang II 2026"
                                class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Nama Instruktur
                            </label>
                            <input
                                v-model="duplicateForm.instructor_name"
                                type="text"
                                class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Tanggal Mulai Pelatihan
                            </label>
                            <input
                                v-model="duplicateForm.start_date"
                                type="date"
                                class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Tanggal Selesai Pelatihan
                            </label>
                            <input
                                v-model="duplicateForm.end_date"
                                type="date"
                                class="w-full text-xs px-3.5 py-2.5 rounded-lg border border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    </div>

                    <div
                        class="pt-4 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-3"
                    >
                        <button
                            type="button"
                            @click="showDuplicateModal = false"
                            class="px-4 py-2.5 text-xs font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="duplicateForm.processing"
                            class="px-5 py-2.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors shadow-sm disabled:opacity-50 flex items-center gap-1.5 cursor-pointer"
                        >
                            <Loader2
                                v-if="duplicateForm.processing"
                                class="w-3.5 h-3.5 animate-spin shrink-0"
                            />
                            <Copy v-else class="w-3.5 h-3.5" />
                            <span>{{
                                duplicateForm.processing
                                    ? "Menduplikasi..."
                                    : "Duplikat Sekarang"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
