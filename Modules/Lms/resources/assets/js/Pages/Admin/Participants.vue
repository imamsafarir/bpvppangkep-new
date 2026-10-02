<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import {
    Users,
    Search,
    Filter,
    ArrowUpDown,
    Eye,
    Edit3,
    Trash2,
    GraduationCap,
    Award,
    CheckCircle2,
    Clock,
    BookOpen,
    AlertTriangle,
    ShieldAlert,
    X,
    Phone,
    Mail,
    MapPin,
    CreditCard,
    Building2,
    Calendar,
    ChevronLeft,
    ChevronRight,
    Download,
    ExternalLink,
    RefreshCw,
    Sparkles,
    Check,
    AlertCircle,
    UserCheck,
    Layers,
    FileSpreadsheet,
    FileText,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    participants: Object,
    stats: Object,
    filters: Object,
    authUser: Object,
});

// -------------------------------------------------------------
// SEARCH, FILTER, & SORT STATE
// -------------------------------------------------------------
const search = ref(props.filters.search || "");
const selectedFilter = ref(props.filters.filter || "all");
const selectedSort = ref(props.filters.sort || "name_asc");
const selectedPerPage = ref(props.filters.per_page || 15);
const isLoading = ref(false);

let searchTimeout = null;
const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 400);
};

const applyFilters = () => {
    isLoading.value = true;
    router.get(
        "/admin/lms/participants",
        {
            search: search.value || undefined,
            filter:
                selectedFilter.value !== "all"
                    ? selectedFilter.value
                    : undefined,
            sort:
                selectedSort.value !== "name_asc"
                    ? selectedSort.value
                    : undefined,
            per_page:
                selectedPerPage.value !== 15
                    ? selectedPerPage.value
                    : undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                isLoading.value = false;
            },
        },
    );
};

const resetFilters = () => {
    search.value = "";
    selectedFilter.value = "all";
    selectedSort.value = "name_asc";
    selectedPerPage.value = 15;
    applyFilters();
};

// -------------------------------------------------------------
// MODAL 1: DETAIL VIEW (HISTORY & FULL PROFILE)
// -------------------------------------------------------------
const showDetailModal = ref(false);
const detailParticipant = ref(null);
const isLoadingDetail = ref(false);

const openDetailModal = async (participant) => {
    showDetailModal.value = true;
    isLoadingDetail.value = true;
    detailParticipant.value = null;

    try {
        const response = await fetch(
            `/admin/lms/participants/${participant.id}`,
            {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                },
            },
        );
        if (response.ok) {
            detailParticipant.value = await response.json();
        } else {
            console.error("Gagal memuat detail peserta");
        }
    } catch (err) {
        console.error("Error loading participant detail:", err);
    } finally {
        isLoadingDetail.value = false;
    }
};

const closeDetailModal = () => {
    showDetailModal.value = false;
    detailParticipant.value = null;
};

// -------------------------------------------------------------
// MODAL 2: EDIT PARTICIPANT DATA WITH CONFIRMATION
// -------------------------------------------------------------
const showEditModal = ref(false);
const showEditConfirmationDialog = ref(false);
const editingParticipantId = ref(null);

const editForm = useForm({
    name: "",
    email: "",
    nik: "",
    phone: "",
    gender: "L",
    agency_or_institution: "",
    address: "",
    training_transaction_code: "",
});

const openEditModal = (participant) => {
    // If coming from detail modal, close detail first
    if (showDetailModal.value) {
        showDetailModal.value = false;
    }

    editingParticipantId.value = participant.id;
    editForm.reset();
    editForm.clearErrors();

    editForm.name = participant.name || "";
    editForm.email = participant.email || "";
    editForm.nik = participant.nik || "";
    editForm.phone = participant.phone || "";
    editForm.gender = participant.gender || "L";
    editForm.agency_or_institution = participant.agency_or_institution || "";
    editForm.address = participant.address || "";
    editForm.training_transaction_code =
        participant.training_transaction_code || "";

    showEditModal.value = true;
};

const closeEditModal = () => {
    showEditModal.value = false;
    showEditConfirmationDialog.value = false;
    editingParticipantId.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const promptEditConfirmation = () => {
    // Trigger validation before showing confirmation
    if (!editForm.name || !editForm.email) {
        editForm.post(`/admin/lms/participants/${editingParticipantId.value}`); // Let backend/front trigger errors
        return;
    }
    showEditConfirmationDialog.value = true;
};

const submitEditForm = () => {
    editForm.put(`/admin/lms/participants/${editingParticipantId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            closeEditModal();
        },
    });
};

// -------------------------------------------------------------
// MODAL 3: DELETE PARTICIPANT WITH TYPE-CONFIRMATION
// -------------------------------------------------------------
const showDeleteModal = ref(false);
const deletingParticipant = ref(null);
const deleteConfirmationText = ref("");
const isDeleting = ref(false);

const openDeleteModal = (participant) => {
    if (showDetailModal.value) {
        showDetailModal.value = false;
    }
    deletingParticipant.value = participant;
    deleteConfirmationText.value = "";
    showDeleteModal.value = true;
};

const closeDeleteModal = () => {
    showDeleteModal.value = false;
    deletingParticipant.value = null;
    deleteConfirmationText.value = "";
    isDeleting.value = false;
};

const isDeleteConfirmationValid = computed(() => {
    if (!deletingParticipant.value) return false;
    const input = deleteConfirmationText.value.trim().toUpperCase();
    const expectedName = deletingParticipant.value.name.trim().toUpperCase();
    return input === "HAPUS" || input === expectedName;
});

const submitDeleteParticipant = () => {
    if (!isDeleteConfirmationValid.value || isDeleting.value) return;

    isDeleting.value = true;
    router.delete(`/admin/lms/participants/${deletingParticipant.value.id}`, {
        data: {
            confirmation: deleteConfirmationText.value.trim(),
        },
        preserveScroll: true,
        onFinish: () => {
            isDeleting.value = false;
            closeDeleteModal();
        },
    });
};

// Helper: initial avatar letter
const getInitial = (name) => {
    if (!name) return "P";
    const parts = name.trim().split(" ");
    if (parts.length >= 2) {
        return (parts[0][0] + parts[1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};
</script>

<template>
    <DashboardLayout>
        <Head title="Database Seluruh Peserta LMS - Superadmin" />

        <div class="space-y-6">
            <!-- Header Section -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 shadow-sm"
            >
                <div
                    class="flex flex-col md:flex-row md:items-center justify-between gap-4"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-2.5 flex-wrap">
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800/60"
                            >
                                <ShieldAlert class="w-3.5 h-3.5" />
                                <span>Akses Khusus Superadmin</span>
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800/60"
                            >
                                <Users class="w-3.5 h-3.5" />
                                <span>Master Database LMS</span>
                            </span>
                        </div>
                        <h1
                            class="text-2xl font-bold text-slate-900 dark:text-white tracking-tight"
                        >
                            Database & Histori Seluruh Peserta LMS
                        </h1>
                        <p class="text-sm text-slate-500 dark:text-slate-400">
                            Pantau riwayat partisipasi kelas, cek detail
                            keikutsertaan & kelulusan berdasarkan nama, sunting
                            data induk, atau lakukan penghapusan dengan
                            konfirmasi aman.
                        </p>
                    </div>

                    <div class="flex items-center gap-2.5 shrink-0">
                        <Link
                            href="/admin/lms"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-700/80 transition-colors shadow-sm"
                        >
                            <ChevronLeft class="w-4 h-4" />
                            <span>Kembali ke Daftar Kelas</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Overarching Stats Bar (KPI Overview) -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            Total Peserta Unik
                        </p>
                        <Users class="w-4 h-4 text-indigo-500" />
                    </div>
                    <p
                        class="text-2xl font-bold text-slate-900 dark:text-white mt-1"
                    >
                        {{ stats?.total_participants || 0 }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Induk peserta terdaftar
                    </p>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            Peserta Multi-Kelas
                        </p>
                        <Layers class="w-4 h-4 text-purple-500" />
                    </div>
                    <p
                        class="text-2xl font-bold text-purple-600 dark:text-purple-400 mt-1"
                    >
                        {{ stats?.multi_training_participants || 0 }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Mengikuti &gt; 1 pelatihan
                    </p>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            Total Partisipasi
                        </p>
                        <GraduationCap class="w-4 h-4 text-blue-500" />
                    </div>
                    <p
                        class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"
                    >
                        {{ stats?.total_enrollments || 0 }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Pendaftaran kelas total
                    </p>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            Kelulusan Selesai
                        </p>
                        <CheckCircle2 class="w-4 h-4 text-emerald-500" />
                    </div>
                    <p
                        class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 mt-1"
                    >
                        {{ stats?.total_completed || 0 }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Status pelatihan selesai
                    </p>
                </div>

                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm col-span-2 md:col-span-1"
                >
                    <div class="flex items-center justify-between">
                        <p
                            class="text-xs font-medium text-slate-500 dark:text-slate-400"
                        >
                            Sertifikat Diterbitkan
                        </p>
                        <Award class="w-4 h-4 text-amber-500" />
                    </div>
                    <p
                        class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1"
                    >
                        {{ stats?.total_certified || 0 }}
                    </p>
                    <p class="text-[11px] text-slate-400 mt-0.5">
                        Sertifikat bertanda tangan resmi
                    </p>
                </div>
            </div>

            <!-- Search, Filters, & Controls Card -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm"
            >
                <div
                    class="flex flex-col lg:flex-row items-stretch lg:items-center gap-3"
                >
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <Search
                            class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                        />
                        <input
                            type="text"
                            v-model="search"
                            @input="onSearchInput"
                            placeholder="Cari berdasarkan nama peserta, NIK, email, no HP, instansi, atau kode transaksi..."
                            class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-sm text-slate-900 dark:text-white placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all"
                        />
                        <button
                            v-if="search"
                            @click="
                                search = '';
                                applyFilters();
                            "
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <!-- Filter Dropdown -->
                    <div class="flex items-center gap-2">
                        <div class="relative min-w-[190px]">
                            <select
                                v-model="selectedFilter"
                                @change="applyFilters"
                                class="w-full appearance-none pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer"
                            >
                                <option value="all">
                                    Semua Kategori Peserta
                                </option>
                                <option value="multiple">
                                    Mengikuti &gt; 1 Kelas
                                </option>
                                <option value="single">
                                    Mengikuti Tepat 1 Kelas
                                </option>
                                <option value="completed">
                                    Telah Lulus / Selesai
                                </option>
                                <option value="in_progress">
                                    Sedang Dalam Pelatihan
                                </option>
                                <option value="certified">
                                    Memiliki Sertifikat Resmi
                                </option>
                                <option value="none">
                                    Belum Terdaftar di Kelas
                                </option>
                            </select>
                            <Filter
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                            />
                        </div>

                        <!-- Sort Dropdown -->
                        <div class="relative min-w-[170px]">
                            <select
                                v-model="selectedSort"
                                @change="applyFilters"
                                class="w-full appearance-none pl-9 pr-8 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer"
                            >
                                <option value="name_asc">Nama (A - Z)</option>
                                <option value="name_desc">Nama (Z - A)</option>
                                <option value="most_enrolled">
                                    Terbanyak Ikut Kelas
                                </option>
                                <option value="latest">
                                    Terdaftar Terbaru
                                </option>
                                <option value="oldest">
                                    Terdaftar Terlama
                                </option>
                            </select>
                            <ArrowUpDown
                                class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none"
                            />
                        </div>

                        <!-- Items Per Page -->
                        <div class="min-w-[100px]">
                            <select
                                v-model="selectedPerPage"
                                @change="applyFilters"
                                class="w-full py-2.5 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/80 text-xs font-semibold text-slate-700 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all cursor-pointer"
                            >
                                <option :value="15">15 data</option>
                                <option :value="25">25 data</option>
                                <option :value="50">50 data</option>
                                <option :value="100">100 data</option>
                            </select>
                        </div>

                        <!-- Reset Filter Button -->
                        <button
                            v-if="
                                search ||
                                selectedFilter !== 'all' ||
                                selectedSort !== 'name_asc' ||
                                selectedPerPage !== 15
                            "
                            @click="resetFilters"
                            title="Reset Filter"
                            class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-300 transition-colors"
                        >
                            <RefreshCw class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Participants Master Table -->
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden"
            >
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr
                                class="border-b border-slate-200 dark:border-slate-800 bg-slate-50/80 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400 text-[11px] font-bold uppercase tracking-wider"
                            >
                                <th class="py-3.5 px-4 w-12 text-center">No</th>
                                <th class="py-3.5 px-4">Nama & NIK Peserta</th>
                                <th class="py-3.5 px-4">Kontak & Instansi</th>
                                <th class="py-3.5 px-4 text-center">
                                    Keikutsertaan Kelas
                                </th>
                                <th class="py-3.5 px-4 text-center">
                                    Kelulusan &amp; Sertifikat
                                </th>
                                <th class="py-3.5 px-4 text-center">
                                    Surat Pernyataan
                                </th>
                                <th class="py-3.5 px-4">Kode Transaksi</th>
                                <th class="py-3.5 px-4 text-center w-36">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody
                            class="divide-y divide-slate-100 dark:divide-slate-800/60 text-xs text-slate-700 dark:text-slate-300"
                        >
                            <tr
                                v-if="
                                    !participants?.data ||
                                    participants.data.length === 0
                                "
                            >
                                <td colspan="8" class="py-16 text-center">
                                    <div
                                        class="flex flex-col items-center justify-center space-y-3 max-w-sm mx-auto"
                                    >
                                        <div
                                            class="w-12 h-12 rounded-2xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-slate-400"
                                        >
                                            <Users class="w-6 h-6" />
                                        </div>
                                        <div class="space-y-1">
                                            <p
                                                class="font-semibold text-slate-800 dark:text-slate-200"
                                            >
                                                Tidak Ada Peserta Ditemukan
                                            </p>
                                            <p
                                                class="text-xs text-slate-500 dark:text-slate-400"
                                            >
                                                Coba ubah kata kunci pencarian
                                                atau reset filter untuk
                                                menampilkan data peserta.
                                            </p>
                                        </div>
                                        <button
                                            @click="resetFilters"
                                            class="px-3.5 py-1.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 hover:bg-indigo-100"
                                        >
                                            Reset Filter
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr
                                v-for="(p, idx) in participants.data"
                                :key="p.id"
                                class="hover:bg-slate-50/70 dark:hover:bg-slate-800/40 transition-colors group"
                            >
                                <!-- Number -->
                                <td
                                    class="py-3.5 px-4 text-center font-mono text-slate-400 text-[11px]"
                                >
                                    {{
                                        (participants.current_page - 1) *
                                            participants.per_page +
                                        idx +
                                        1
                                    }}
                                </td>

                                <!-- Name & NIK -->
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-xl flex items-center justify-center font-bold text-xs shrink-0 select-none shadow-sm"
                                            :class="[
                                                p.total_enrollments_count > 1
                                                    ? 'bg-gradient-to-tr from-purple-600 to-indigo-600 text-white'
                                                    : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200',
                                            ]"
                                        >
                                            {{ getInitial(p.name) }}
                                        </div>
                                        <div>
                                            <div
                                                class="flex items-center gap-1.5 flex-wrap"
                                            >
                                                <button
                                                    @click="openDetailModal(p)"
                                                    class="font-bold text-slate-900 dark:text-white hover:text-indigo-600 dark:hover:text-indigo-400 transition-colors text-left"
                                                >
                                                    {{ p.name }}
                                                </button>
                                                <span
                                                    v-if="p.gender"
                                                    class="text-[10px] px-1.5 py-0.2 rounded font-mono font-semibold"
                                                    :class="
                                                        p.gender === 'L'
                                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'
                                                            : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'
                                                    "
                                                >
                                                    {{
                                                        p.gender === "L"
                                                            ? "Laki-laki"
                                                            : "Perempuan"
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="text-[11px] text-slate-500 font-mono mt-0.5"
                                            >
                                                NIK: {{ p.nik || "-" }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Contact & Institution -->
                                <td class="py-3.5 px-4">
                                    <div class="space-y-0.5">
                                        <div
                                            class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300"
                                        >
                                            <Mail
                                                class="w-3.5 h-3.5 text-slate-400 shrink-0"
                                            />
                                            <span
                                                class="truncate max-w-[200px]"
                                                :title="p.email"
                                                >{{ p.email }}</span
                                            >
                                        </div>
                                        <div
                                            v-if="p.phone"
                                            class="flex items-center gap-1.5 text-slate-500 text-[11px]"
                                        >
                                            <Phone
                                                class="w-3 h-3 text-slate-400 shrink-0"
                                            />
                                            <span>{{ p.phone }}</span>
                                        </div>
                                        <div
                                            v-if="p.agency_or_institution"
                                            class="flex items-center gap-1.5 text-slate-500 text-[11px]"
                                        >
                                            <Building2
                                                class="w-3 h-3 text-slate-400 shrink-0"
                                            />
                                            <span
                                                class="truncate max-w-[200px]"
                                                >{{
                                                    p.agency_or_institution
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                </td>

                                <!-- Enrollments Count (Berapa kali mengikuti) -->
                                <td class="py-3.5 px-4 text-center">
                                    <div
                                        class="inline-flex flex-col items-center"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-bold shadow-sm"
                                            :class="[
                                                p.total_enrollments_count > 1
                                                    ? 'bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 border border-purple-200 dark:border-purple-800'
                                                    : p.total_enrollments_count ===
                                                        1
                                                      ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800'
                                                      : 'bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
                                            ]"
                                        >
                                            <GraduationCap
                                                class="w-3.5 h-3.5"
                                            />
                                            <span
                                                >{{
                                                    p.total_enrollments_count
                                                }}
                                                Pelatihan</span
                                            >
                                        </span>
                                        <span
                                            v-if="p.total_enrollments_count > 1"
                                            class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold mt-0.5"
                                        >
                                            Multi-Kelas
                                        </span>
                                    </div>
                                </td>

                                <!-- Completed & Certified -->
                                <td class="py-3.5 px-4 text-center">
                                    <div
                                        class="flex items-center justify-center gap-1.5 flex-wrap"
                                    >
                                        <span
                                            v-if="
                                                p.completed_enrollments_count >
                                                0
                                            "
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                                            title="Pelatihan yang telah diselesaikan"
                                        >
                                            <CheckCircle2 class="w-3 h-3" />
                                            <span
                                                >{{
                                                    p.completed_enrollments_count
                                                }}
                                                Lulus</span
                                            >
                                        </span>
                                        <span
                                            v-if="
                                                p.certified_enrollments_count >
                                                0
                                            "
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300"
                                            title="Sertifikat yang telah diterbitkan"
                                        >
                                            <Award class="w-3 h-3" />
                                            <span
                                                >{{
                                                    p.certified_enrollments_count
                                                }}
                                                Sertifikat</span
                                            >
                                        </span>
                                        <span
                                            v-if="
                                                p.completed_enrollments_count ===
                                                    0 &&
                                                p.certified_enrollments_count ===
                                                    0
                                            "
                                            class="text-slate-400 text-[11px]"
                                        >
                                            {{
                                                p.in_progress_enrollments_count >
                                                0
                                                    ? "Sedang Belajar"
                                                    : "-"
                                            }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Surat Pernyataan -->
                                <td class="py-3.5 px-4 text-center">
                                    <span
                                        v-if="p.completed_enrollments_count > 0"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[11px] font-semibold bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300 cursor-pointer hover:bg-teal-100 transition-colors"
                                        @click="openDetailModal(p)"
                                        title="Lihat detail surat pernyataan di riwayat pelatihan"
                                    >
                                        <FileText class="w-3 h-3" />
                                        <span>Lihat Detail</span>
                                    </span>
                                    <span v-else class="text-slate-400 text-[11px]">-</span>
                                </td>

                                <!-- Transaction Code -->
                                <td class="py-3.5 px-4">
                                    <span
                                        v-if="p.training_transaction_code"
                                        class="font-mono text-[11px] px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400"
                                    >
                                        {{ p.training_transaction_code }}
                                    </span>
                                    <span v-else class="text-slate-400">-</span>
                                </td>

                                <!-- Actions -->
                                <td class="py-3.5 px-4 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <!-- View Detail / History -->
                                        <button
                                            type="button"
                                            @click="openDetailModal(p)"
                                            title="Lihat Histori & Informasi Lengkap"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 dark:hover:text-indigo-300 transition-colors"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </button>

                                        <!-- Edit Participant Profile -->
                                        <button
                                            type="button"
                                            @click="openEditModal(p)"
                                            title="Sunting Data Peserta"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/60 dark:hover:text-amber-300 transition-colors"
                                        >
                                            <Edit3 class="w-4 h-4" />
                                        </button>

                                        <!-- Delete Participant Permanently -->
                                        <button
                                            type="button"
                                            @click="openDeleteModal(p)"
                                            title="Hapus Data Peserta Permanen"
                                            class="p-1.5 rounded-lg text-slate-500 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 dark:hover:text-rose-300 transition-colors"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div
                    v-if="participants?.links && participants.total > 0"
                    class="py-3.5 px-4 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500"
                >
                    <div>
                        Menampilkan
                        <strong class="text-slate-800 dark:text-slate-200">{{
                            participants.from || 0
                        }}</strong>
                        sampai
                        <strong class="text-slate-800 dark:text-slate-200">{{
                            participants.to || 0
                        }}</strong>
                        dari
                        <strong class="text-slate-800 dark:text-slate-200">{{
                            participants.total || 0
                        }}</strong>
                        peserta
                    </div>

                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, lIdx) in participants.links"
                            :key="lIdx"
                            :href="link.url || '#'"
                            :class="[
                                'px-3 py-1.5 rounded-lg font-medium transition-colors select-none',
                                link.active
                                    ? 'bg-indigo-600 text-white font-bold'
                                    : link.url
                                      ? 'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300'
                                      : 'text-slate-300 dark:text-slate-700 cursor-not-allowed pointer-events-none',
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 1: DETAIL PARTICIPANT & FULL HISTORY -->
        <!-- ============================================================= -->
        <div
            v-if="showDetailModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
            @click.self="closeDetailModal"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-4xl my-8 overflow-hidden flex flex-col max-h-[90vh]"
            >
                <!-- Modal Header -->
                <div
                    class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-sm shadow"
                        >
                            {{ getInitial(detailParticipant?.name) }}
                        </div>
                        <div>
                            <h3
                                class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2"
                            >
                                <span>{{
                                    detailParticipant?.name || "Memuat..."
                                }}</span>
                                <span
                                    v-if="
                                        detailParticipant?.summary
                                            ?.total_enrolled > 1
                                    "
                                    class="text-[10px] px-2 py-0.5 rounded-full bg-purple-100 text-purple-700 dark:bg-purple-950 dark:text-purple-300 font-bold border border-purple-200 dark:border-purple-800"
                                >
                                    Multi-Kelas ({{
                                        detailParticipant.summary
                                            .total_enrolled
                                    }}x Ikut)
                                </span>
                            </h3>
                            <p class="text-xs text-slate-500">
                                Rincian lengkap identitas & riwayat
                                keikutsertaan pelatihan LMS
                            </p>
                        </div>
                    </div>

                    <button
                        @click="closeDetailModal"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-6 flex-1">
                    <!-- Loading state -->
                    <div
                        v-if="isLoadingDetail"
                        class="py-16 text-center text-slate-400"
                    >
                        <RefreshCw
                            class="w-8 h-8 animate-spin mx-auto text-indigo-500 mb-2"
                        />
                        <p class="text-sm font-semibold">
                            Memuat riwayat pelatihan peserta...
                        </p>
                    </div>

                    <template v-else-if="detailParticipant">
                        <!-- Profile Card Grid -->
                        <div
                            class="bg-slate-50 dark:bg-slate-800/50 rounded-xl p-4 border border-slate-200 dark:border-slate-800"
                        >
                            <h4
                                class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-3 flex items-center gap-1.5"
                            >
                                <UserCheck class="w-4 h-4 text-indigo-500" />
                                <span>Data Induk Peserta</span>
                            </h4>

                            <div
                                class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 text-xs"
                            >
                                <div>
                                    <span
                                        class="text-slate-400 block text-[11px]"
                                        >Nama Lengkap</span
                                    >
                                    <strong
                                        class="text-slate-800 dark:text-slate-200 text-sm font-semibold"
                                    >
                                        {{ detailParticipant.name }}
                                    </strong>
                                </div>

                                <div>
                                    <span
                                        class="text-slate-400 block text-[11px]"
                                        >Nomor Induk Kependudukan (NIK)</span
                                    >
                                    <span
                                        class="font-mono text-slate-700 dark:text-slate-300 font-semibold"
                                    >
                                        {{ detailParticipant.nik || "-" }}
                                    </span>
                                </div>

                                <div>
                                    <span
                                        class="text-slate-400 block text-[11px]"
                                        >Email Utama</span
                                    >
                                    <a
                                        :href="`mailto:${detailParticipant.email}`"
                                        class="text-indigo-600 dark:text-indigo-400 hover:underline font-medium"
                                    >
                                        {{ detailParticipant.email }}
                                    </a>
                                </div>

                                <div>
                                    <span
                                        class="text-slate-400 block text-[11px]"
                                        >Nomor Telepon / WhatsApp</span
                                    >
                                    <span
                                        class="text-slate-700 dark:text-slate-300 font-medium"
                                    >
                                        {{ detailParticipant.phone || "-" }}
                                    </span>
                                </div>

                                <div>
                                    <span
                                        class="text-slate-400 block text-[11px]"
                                        >Jenis Kelamin</span
                                    >
                                    <span
                                        class="text-slate-700 dark:text-slate-300"
                                    >
                                        {{
                                            detailParticipant.gender === "L"
                                                ? "Laki-laki"
                                                : detailParticipant.gender ===
                                                    "P"
                                                  ? "Perempuan"
                                                  : "-"
                                        }}
                                    </span>
                                </div>

                                <div>
                                    <span
                                        class="text-slate-400 block text-[11px]"
                                        >Asal Instansi / Lembaga</span
                                    >
                                    <span
                                        class="text-slate-700 dark:text-slate-300 font-medium"
                                    >
                                        {{
                                            detailParticipant.agency_or_institution ||
                                            "-"
                                        }}
                                    </span>
                                </div>

                                <div class="md:col-span-2">
                                    <span
                                        class="text-slate-400 block text-[11px]"
                                        >Alamat Domisili</span
                                    >
                                    <span
                                        class="text-slate-700 dark:text-slate-300"
                                    >
                                        {{ detailParticipant.address || "-" }}
                                    </span>
                                </div>

                                <div>
                                    <span
                                        class="text-slate-400 block text-[11px]"
                                        >Kode Transaksi Terdaftar</span
                                    >
                                    <span
                                        class="font-mono text-[11px] text-slate-600 dark:text-slate-400"
                                    >
                                        {{
                                            detailParticipant.training_transaction_code ||
                                            "-"
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Mini KPI Summary -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div
                                class="p-3 bg-indigo-50/70 dark:bg-indigo-950/40 rounded-xl border border-indigo-200/80 dark:border-indigo-800/60 text-center"
                            >
                                <span
                                    class="text-[11px] font-semibold text-indigo-700 dark:text-indigo-300 block"
                                >
                                    Total Diikuti
                                </span>
                                <span
                                    class="text-2xl font-bold text-indigo-900 dark:text-indigo-100"
                                >
                                    {{
                                        detailParticipant.summary.total_enrolled
                                    }}
                                </span>
                                <span
                                    class="text-[10px] text-indigo-600/70 block"
                                    >Pelatihan</span
                                >
                            </div>

                            <div
                                class="p-3 bg-emerald-50/70 dark:bg-emerald-950/40 rounded-xl border border-emerald-200/80 dark:border-emerald-800/60 text-center"
                            >
                                <span
                                    class="text-[11px] font-semibold text-emerald-700 dark:text-emerald-300 block"
                                >
                                    Lulus / Selesai
                                </span>
                                <span
                                    class="text-2xl font-bold text-emerald-900 dark:text-emerald-100"
                                >
                                    {{
                                        detailParticipant.summary
                                            .total_completed
                                    }}
                                </span>
                                <span
                                    class="text-[10px] text-emerald-600/70 block"
                                    >Kelas</span
                                >
                            </div>

                            <div
                                class="p-3 bg-amber-50/70 dark:bg-amber-950/40 rounded-xl border border-amber-200/80 dark:border-amber-800/60 text-center"
                            >
                                <span
                                    class="text-[11px] font-semibold text-amber-700 dark:text-amber-300 block"
                                >
                                    Sedang Berjalan
                                </span>
                                <span
                                    class="text-2xl font-bold text-amber-900 dark:text-amber-100"
                                >
                                    {{
                                        detailParticipant.summary
                                            .total_in_progress
                                    }}
                                </span>
                                <span
                                    class="text-[10px] text-amber-600/70 block"
                                    >Kelas Aktif</span
                                >
                            </div>

                            <div
                                class="p-3 bg-purple-50/70 dark:bg-purple-950/40 rounded-xl border border-purple-200/80 dark:border-purple-800/60 text-center"
                            >
                                <span
                                    class="text-[11px] font-semibold text-purple-700 dark:text-purple-300 block"
                                >
                                    Sertifikat Resmi
                                </span>
                                <span
                                    class="text-2xl font-bold text-purple-900 dark:text-purple-100"
                                >
                                    {{
                                        detailParticipant.summary
                                            .total_certified
                                    }}
                                </span>
                                <span
                                    class="text-[10px] text-purple-600/70 block"
                                    >Terverifikasi TTE</span
                                >
                            </div>
                        </div>

                        <!-- Riwayat Pelatihan (Full List / Table) -->
                        <div class="space-y-3">
                            <div class="flex items-center justify-between">
                                <h4
                                    class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2"
                                >
                                    <BookOpen class="w-4 h-4 text-indigo-600" />
                                    <span
                                        >Riwayat & Progres Seluruh
                                        Pelatihan</span
                                    >
                                    <span
                                        class="text-xs font-normal text-slate-400"
                                    >
                                        ({{
                                            detailParticipant.enrollments
                                                ?.length || 0
                                        }}
                                        pelatihan terdaftar)
                                    </span>
                                </h4>
                            </div>

                            <div
                                v-if="
                                    !detailParticipant.enrollments ||
                                    detailParticipant.enrollments.length === 0
                                "
                                class="p-8 text-center bg-slate-50 dark:bg-slate-800/40 rounded-xl border border-dashed border-slate-200 dark:border-slate-800 text-slate-400 text-xs"
                            >
                                Peserta ini belum pernah didaftarkan ke
                                pelatihan manapun.
                            </div>

                            <div v-else class="space-y-3">
                                <div
                                    v-for="(
                                        en, enIdx
                                    ) in detailParticipant.enrollments"
                                    :key="en.id"
                                    class="p-4 rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-indigo-300 dark:hover:border-indigo-700 transition-all shadow-sm space-y-3"
                                >
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                                    >
                                        <div>
                                            <div
                                                class="flex items-center gap-2 flex-wrap"
                                            >
                                                <h5
                                                    class="font-bold text-slate-900 dark:text-white text-sm"
                                                >
                                                    {{ en.course_title }}
                                                </h5>
                                                <span
                                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full uppercase"
                                                    :class="[
                                                        en.status ===
                                                        'completed'
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                            : en.status ===
                                                                'in_progress'
                                                              ? 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300'
                                                              : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                                                    ]"
                                                >
                                                    {{
                                                        en.status ===
                                                        "completed"
                                                            ? "Selesai / Lulus"
                                                            : en.status ===
                                                                "in_progress"
                                                              ? "Sedang Berjalan"
                                                              : "Terdaftar"
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="text-xs text-slate-500 flex items-center gap-2 mt-0.5"
                                            >
                                                <span
                                                    >Angkatan:
                                                    {{
                                                        en.course_batch || "-"
                                                    }}</span
                                                >
                                                <span>&bull;</span>
                                                <span
                                                    >Kategori:
                                                    {{
                                                        en.course_category ||
                                                        "-"
                                                    }}</span
                                                >
                                                <span>&bull;</span>
                                                <span
                                                    >Durasi:
                                                    {{
                                                        en.course_duration_days
                                                    }}
                                                    hari</span
                                                >
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <Link
                                                v-if="en.course_id"
                                                :href="`/admin/lms/${en.course_id}/workspace`"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2.5 py-1 text-[11px] font-semibold text-slate-700 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 rounded-lg transition-colors"
                                            >
                                                <span
                                                    >Buka Workspace Kelas</span
                                                >
                                                <ExternalLink class="w-3 h-3" />
                                            </Link>
                                        </div>
                                    </div>

                                    <!-- Progress & Metrics Grid -->
                                    <div
                                        class="grid grid-cols-2 md:grid-cols-4 gap-3 bg-slate-50 dark:bg-slate-800/40 p-3 rounded-lg text-xs"
                                    >
                                        <!-- Progress Belajar -->
                                        <div>
                                            <span
                                                class="text-slate-400 block text-[11px]"
                                                >Progres Pelajaran</span
                                            >
                                            <div
                                                class="flex items-center gap-2 mt-0.5"
                                            >
                                                <div
                                                    class="flex-1 bg-slate-200 dark:bg-slate-700 rounded-full h-2 overflow-hidden"
                                                >
                                                    <div
                                                        class="bg-indigo-600 h-2 rounded-full transition-all"
                                                        :style="{
                                                            width: `${en.progress_percentage}%`,
                                                        }"
                                                    ></div>
                                                </div>
                                                <span
                                                    class="font-bold text-slate-800 dark:text-slate-200 text-xs"
                                                >
                                                    {{
                                                        en.progress_percentage
                                                    }}%
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Jalur Presensi -->
                                        <div>
                                            <span
                                                class="text-slate-400 block text-[11px]"
                                                >Jalur Presensi</span
                                            >
                                            <div
                                                class="flex items-center gap-1 font-semibold mt-0.5"
                                                :class="
                                                    en.attendance_path ===
                                                    'live_zoom'
                                                        ? 'text-blue-600'
                                                        : 'text-amber-600'
                                                "
                                            >
                                                <span>{{
                                                    en.attendance_path ===
                                                    "live_zoom"
                                                        ? "Live Online Meeting"
                                                        : en.attendance_path ===
                                                            "self_study"
                                                          ? "Belajar Mandiri"
                                                          : "Belum Presensi"
                                                }}</span>
                                            </div>
                                            <span
                                                v-if="en.attendance_at"
                                                class="text-[10px] text-slate-400 block"
                                            >
                                                {{ en.attendance_at }}
                                            </span>
                                        </div>

                                        <!-- Unit Attendances & Quiz -->
                                        <div>
                                            <span
                                                class="text-slate-400 block text-[11px]"
                                                >Presensi Unit & Kuis</span
                                            >
                                            <div
                                                class="font-semibold text-slate-700 dark:text-slate-300 mt-0.5"
                                            >
                                                <span
                                                    >{{
                                                        en.module_attendances_count
                                                    }}
                                                    Unit Dihadiri</span
                                                >
                                            </div>
                                            <span
                                                v-if="
                                                    en.quiz_attempts_count > 0
                                                "
                                                class="text-[10px] text-slate-500 block"
                                            >
                                                Kuis:
                                                {{ en.highest_quiz_score }} pt
                                                ({{
                                                    en.has_passed_quiz
                                                        ? "Lulus"
                                                        : "Belum Lulus"
                                                }})
                                            </span>
                                        </div>

                                        <!-- Certificate Info -->
                                        <div>
                                            <span
                                                class="text-slate-400 block text-[11px]"
                                                >Sertifikat Resmi</span
                                            >
                                            <div v-if="en.certificate_number">
                                                <span
                                                    class="font-mono text-[10.5px] font-bold text-amber-600 dark:text-amber-400 block truncate"
                                                    :title="
                                                        en.certificate_number
                                                    "
                                                >
                                                    {{ en.certificate_number }}
                                                </span>
                                                <a
                                                    v-if="
                                                        en.certificate_download_url
                                                    "
                                                    :href="
                                                        en.certificate_download_url
                                                    "
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 text-[11px] text-indigo-600 dark:text-indigo-400 hover:underline font-semibold mt-0.5"
                                                >
                                                    <Download class="w-3 h-3" />
                                                    <span>Unduh PDF</span>
                                                </a>
                                            </div>
                                            <span
                                                v-else
                                                class="text-slate-400 text-[11px]"
                                                >Belum diterbitkan</span
                                            >
                                        </div>
                                    </div>

                                    <!-- Surat Pernyataan -->
                                    <div
                                        v-if="en.status === 'completed'"
                                        class="flex items-center justify-between gap-3 bg-teal-50/60 dark:bg-teal-950/30 border border-teal-200/80 dark:border-teal-800/50 rounded-lg px-3 py-2.5"
                                    >
                                        <div class="flex items-center gap-2">
                                            <FileText class="w-4 h-4 text-teal-600 dark:text-teal-400 shrink-0" />
                                            <div>
                                                <p class="text-[11px] font-bold text-teal-800 dark:text-teal-300">
                                                    Surat Pernyataan Komitmen Kerja
                                                </p>
                                                <p v-if="en.declaration_signed_at" class="text-[10px] text-teal-600 dark:text-teal-400">
                                                    Ditandatangani: {{ en.declaration_signed_at }}
                                                </p>
                                                <p v-else class="text-[10px] text-slate-400">
                                                    Belum ditandatangani
                                                </p>
                                            </div>
                                        </div>
                                        <a
                                            v-if="en.declaration_view_url"
                                            :href="en.declaration_view_url"
                                            target="_blank"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 text-[11px] font-semibold rounded-lg bg-teal-600 text-white hover:bg-teal-700 transition-colors shrink-0"
                                        >
                                            <ExternalLink class="w-3 h-3" />
                                            <span>Lihat Surat</span>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div
                    class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex items-center justify-between"
                >
                    <div class="flex items-center gap-2">
                        <button
                            v-if="detailParticipant"
                            type="button"
                            @click="openDeleteModal(detailParticipant)"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors"
                        >
                            <Trash2 class="w-4 h-4" />
                            <span>Hapus Peserta</span>
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            v-if="detailParticipant"
                            type="button"
                            @click="openEditModal(detailParticipant)"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 transition-colors shadow-sm"
                        >
                            <Edit3 class="w-4 h-4" />
                            <span>Sunting Profil</span>
                        </button>
                        <button
                            type="button"
                            @click="closeDetailModal"
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-white bg-slate-900 hover:bg-slate-800 transition-colors"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 2: EDIT PARTICIPANT PROFILE WITH CONFIRMATION -->
        <!-- ============================================================= -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm overflow-y-auto"
            @click.self="closeEditModal"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-2xl my-8 overflow-hidden"
            >
                <div
                    class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="p-2 rounded-xl bg-amber-50 dark:bg-amber-950/40 text-amber-600"
                        >
                            <Edit3 class="w-5 h-5" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                Sunting Profil Peserta LMS
                            </h3>
                            <p class="text-xs text-slate-500">
                                Perbarui data induk peserta secara akurat
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeEditModal"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <form
                    @submit.prevent="promptEditConfirmation"
                    class="p-6 space-y-4"
                >
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <!-- Nama Lengkap -->
                        <div class="sm:col-span-2">
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Nama Lengkap
                                <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="text"
                                v-model="editForm.name"
                                required
                                placeholder="Masukkan nama lengkap peserta"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            <p
                                v-if="editForm.errors.name"
                                class="text-rose-500 text-[11px] mt-1"
                            >
                                {{ editForm.errors.name }}
                            </p>
                        </div>

                        <!-- Email -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Email <span class="text-rose-500">*</span>
                            </label>
                            <input
                                type="email"
                                v-model="editForm.email"
                                required
                                placeholder="email@contoh.com"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            <p
                                v-if="editForm.errors.email"
                                class="text-rose-500 text-[11px] mt-1"
                            >
                                {{ editForm.errors.email }}
                            </p>
                        </div>

                        <!-- NIK -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Nomor Induk Kependudukan (NIK)
                            </label>
                            <input
                                type="text"
                                v-model="editForm.nik"
                                placeholder="16 digit NIK"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                            />
                            <p
                                v-if="editForm.errors.nik"
                                class="text-rose-500 text-[11px] mt-1"
                            >
                                {{ editForm.errors.nik }}
                            </p>
                        </div>

                        <!-- No HP / WA -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                No. Telepon / WhatsApp
                            </label>
                            <input
                                type="text"
                                v-model="editForm.phone"
                                placeholder="08xxxxxxxxxx"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                            <p
                                v-if="editForm.errors.phone"
                                class="text-rose-500 text-[11px] mt-1"
                            >
                                {{ editForm.errors.phone }}
                            </p>
                        </div>

                        <!-- Jenis Kelamin -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Jenis Kelamin
                            </label>
                            <select
                                v-model="editForm.gender"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                            >
                                <option value="L">Laki-laki</option>
                                <option value="P">Perempuan</option>
                            </select>
                        </div>

                        <!-- Asal Instansi / Lembaga -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Asal Instansi / Perusahaan
                            </label>
                            <input
                                type="text"
                                v-model="editForm.agency_or_institution"
                                placeholder="PT / Instansi / Umum"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Kode Transaksi Pelatihan -->
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Kode Transaksi Pelatihan
                            </label>
                            <input
                                type="text"
                                v-model="editForm.training_transaction_code"
                                placeholder="TRX-XXXXX"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 font-mono"
                            />
                        </div>

                        <!-- Alamat -->
                        <div class="sm:col-span-2">
                            <label
                                class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Alamat Lengkap
                            </label>
                            <textarea
                                rows="2"
                                v-model="editForm.address"
                                placeholder="Jalan, RT/RW, Kelurahan, Kecamatan, Kota/Kabupaten"
                                class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs text-slate-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"
                            ></textarea>
                        </div>
                    </div>

                    <div
                        class="p-3 bg-amber-50/70 dark:bg-amber-950/30 rounded-xl border border-amber-200 dark:border-amber-800/60 text-[11px] text-amber-800 dark:text-amber-200 flex items-center gap-2"
                    >
                        <AlertCircle class="w-4 h-4 shrink-0 text-amber-600" />
                        <span>
                            Perubahan email atau nama akan otomatis
                            tersinkronisasi ke akun login peserta yang terkait.
                        </span>
                    </div>

                    <div
                        class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2"
                    >
                        <button
                            type="button"
                            @click="closeEditModal"
                            class="px-4 py-2.5 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors cursor-pointer"
                        >
                            <Check class="w-4 h-4" />
                            <span>Simpan Perubahan</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Confirm Edit Dialog Prompt -->
        <div
            v-if="showEditConfirmationDialog"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 shadow-2xl w-full max-w-md p-6 space-y-4"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="p-2.5 rounded-2xl bg-indigo-100 dark:bg-indigo-950 text-indigo-600 dark:text-indigo-400"
                    >
                        <Sparkles class="w-6 h-6" />
                    </div>
                    <div>
                        <h4
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Konfirmasi Pembaruan Data
                        </h4>
                        <p class="text-xs text-slate-500">
                            Pastikan data peserta sudah benar
                        </p>
                    </div>
                </div>

                <div
                    class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl text-xs space-y-1 text-slate-700 dark:text-slate-300"
                >
                    <p>
                        Apakah Anda yakin ingin menyimpan pembaruan data untuk:
                    </p>
                    <p
                        class="font-bold text-indigo-600 dark:text-indigo-400 text-sm"
                    >
                        {{ editForm.name }}
                    </p>
                    <p class="text-slate-400 text-[11px]">
                        Email: {{ editForm.email }}
                    </p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2">
                    <button
                        type="button"
                        @click="showEditConfirmationDialog = false"
                        class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        Periksa Kembali
                    </button>
                    <button
                        type="button"
                        @click="submitEditForm"
                        :disabled="editForm.processing"
                        class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 shadow-sm transition-colors cursor-pointer disabled:opacity-50"
                    >
                        <Loader2 v-if="editForm.processing" class="w-4 h-4 animate-spin shrink-0" />
                        <Check v-else class="w-4 h-4" />
                        <span>{{
                            editForm.processing
                                ? "Menyimpan..."
                                : "Ya, Simpan Perubahan"
                        }}</span>
                    </button>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- MODAL 3: DELETE PARTICIPANT WITH STRING CONFIRMATION -->
        <!-- ============================================================= -->
        <div
            v-if="showDeleteModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
            @click.self="closeDeleteModal"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl border border-rose-200 dark:border-rose-900/50 shadow-2xl w-full max-w-lg overflow-hidden"
            >
                <div
                    class="p-5 bg-rose-50/80 dark:bg-rose-950/40 border-b border-rose-200 dark:border-rose-900/50 flex items-center justify-between"
                >
                    <div class="flex items-center gap-2.5">
                        <div
                            class="p-2 rounded-xl bg-rose-100 dark:bg-rose-900/60 text-rose-600 dark:text-rose-300"
                        >
                            <AlertTriangle class="w-5 h-5" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-rose-900 dark:text-rose-200"
                            >
                                Hapus Data Peserta Permanen
                            </h3>
                            <p class="text-xs text-rose-700 dark:text-rose-300">
                                Tindakan ini berbahaya dan tidak dapat
                                dibatalkan!
                            </p>
                        </div>
                    </div>
                    <button
                        @click="closeDeleteModal"
                        class="p-2 rounded-xl text-rose-400 hover:text-rose-600"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <div class="p-6 space-y-4 text-xs">
                    <div
                        class="p-3.5 bg-rose-50 dark:bg-rose-950/20 rounded-xl border border-rose-200 dark:border-rose-900/40 text-slate-700 dark:text-slate-300 space-y-2"
                    >
                        <p class="font-bold text-slate-900 dark:text-white">
                            Anda akan menghapus seluruh data peserta berikut:
                        </p>
                        <div
                            class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-rose-100 dark:border-rose-950"
                        >
                            <div
                                class="font-bold text-sm text-rose-600 dark:text-rose-400"
                            >
                                {{ deletingParticipant?.name }}
                            </div>
                            <div class="text-[11px] text-slate-500 font-mono">
                                {{ deletingParticipant?.email }} &bull; NIK:
                                {{ deletingParticipant?.nik || "-" }}
                            </div>
                        </div>
                        <ul
                            class="list-disc pl-4 space-y-0.5 text-[11px] text-slate-600 dark:text-slate-400"
                        >
                            <li>
                                Seluruh pendaftaran pelatihan (enrollments)
                                peserta ini akan terhapus.
                            </li>
                            <li>
                                Seluruh rekaman progres materi dan unit
                                kompetensi akan hilang.
                            </li>
                            <li>
                                Riwayat presensi Online Meeting dan unit akan
                                dihapus dari sistem.
                            </li>
                            <li>
                                Data kuis dan nomor sertifikat yang pernah
                                diperoleh akan terhapus.
                            </li>
                        </ul>
                    </div>

                    <div class="space-y-2">
                        <label
                            class="block font-semibold text-slate-800 dark:text-slate-200"
                        >
                            Ketik kata
                            <span
                                class="font-mono font-bold text-rose-600 bg-rose-50 px-1 py-0.5 rounded"
                                >HAPUS</span
                            >
                            atau nama persis peserta (<span
                                class="font-bold text-slate-900 dark:text-white"
                                >{{ deletingParticipant?.name }}</span
                            >) untuk mengonfirmasi:
                        </label>
                        <input
                            type="text"
                            v-model="deleteConfirmationText"
                            placeholder="Ketik HAPUS di sini..."
                            class="w-full px-3.5 py-2.5 rounded-xl border border-slate-300 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-xs font-semibold focus:outline-none focus:ring-2 focus:ring-rose-500"
                        />
                    </div>

                    <div
                        class="pt-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-end gap-2"
                    >
                        <button
                            type="button"
                            @click="closeDeleteModal"
                            class="px-4 py-2.5 rounded-xl font-semibold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                        >
                            Batal
                        </button>
                        <button
                            type="button"
                            @click="submitDeleteParticipant"
                            :disabled="!isDeleteConfirmationValid || isDeleting"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed shadow-md transition-all cursor-pointer"
                        >
                            <Loader2 v-if="isDeleting" class="w-4 h-4 animate-spin shrink-0" />
                            <Trash2 v-else class="w-4 h-4" />
                            <span>{{
                                isDeleting
                                    ? "Menghapus Data..."
                                    : "Hapus Permanen"
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
