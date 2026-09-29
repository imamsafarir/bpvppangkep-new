<script setup>
import { ref, computed, watch } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell,
    DataTablePagination,
    DataTableColumnHeader,
} from "@/Components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    Calendar as CalendarIcon,
    ChevronLeft,
    ChevronRight,
    Plus,
    Pencil,
    Trash2,
    Search,
    X,
    ExternalLink,
    MessageSquare,
    AlertCircle,
    CheckCircle2,
    Clock,
    Share2,
    FileText,
    Layers,
    Download,
    Send,
    Globe,
    Eye,
    RefreshCw,
    SlidersHorizontal,
    Video,
    Sparkles,
    Check,
    Link as LinkIcon,
    Users,
    TrendingUp,
    Settings,
    FileSpreadsheet,
    Copy,
    ListFilter,
    FolderGit2,
    CheckSquare,
    Square,
    ArrowRight,
    AlertTriangle,
    Flame,
    History,
    FileCheck,
    Upload,
    FileUp,
    EyeOff,
    Key,
    ShieldCheck,
    Info,
} from "lucide-vue-next";
import { RichTextEditor } from "@/Components/ui/rich-text-editor";

const props = defineProps({
    activeTab: { type: String, default: "kalender" },
    currentMonth: { type: Number, default: () => new Date().getMonth() + 1 },
    currentYear: { type: Number, default: () => new Date().getFullYear() },
    calendarContents: { type: Array, default: () => [] },
    contents: Object,
    stats: Object,
    workflowInfo: { type: Object, default: () => ({}) },
    teamStats: Object,
    medsosStats: Object,
    platforms: Array,
    socialSettings: Array,
    statusOptions: Array,
    jenisKontenOptions: Array,
    team: Object,
    filters: Object,
});

const page = usePage();
const currentUser = computed(() => page.props.auth?.user || {});

const userRoles = computed(() => {
    const roles = Array.isArray(currentUser.value?.roles)
        ? currentUser.value.roles
        : String(currentUser.value?.role || "")
              .split(",")
              .map((r) => r.trim())
              .filter(Boolean);
    return roles;
});

const isSuperAdmin = computed(() => {
    return (
        userRoles.value.includes("super_admin") ||
        currentUser.value?.role === "super_admin" ||
        currentUser.value?.role === "Super Admin" ||
        currentUser.value?.is_superadmin === true
    );
});

// Status tayang konten
const isTayang = computed(() => form.status === "Tayang");

// Penanda apakah alur Planner sudah dikirimkan ke Editor (status bukan Draft lagi)
const isPlannerSubmitted = computed(() => {
    if (!editItem.value) return false;
    return form.status !== "Draft";
});

// Penanda apakah alur Editor sudah dikirimkan ke Admin Platform (status bukan Proses Editing / Revisi lagi)
const isEditorSubmitted = computed(() => {
    if (!editItem.value) return false;
    return !["Draft", "Proses Editing", "Revisi"].includes(form.status);
});

// Izin hak edit masing-masing bagian formulir:
// 1. Planner: hanya role medsos_planner (atau medsos_instruktur saat pengajuan bahan) dan HANYA saat status Draft.
const canEditPlanner = computed(() => {
    if (isSuperAdmin.value) return true;
    if (isTayang.value) return false;
    const hasRole =
        userRoles.value.includes("medsos_planner") ||
        (createMode.value === "bahan" &&
            userRoles.value.includes("medsos_instruktur"));
    return hasRole && form.status === "Draft";
});

// 2. Editor: hanya role medsos_editor dan HANYA saat status Proses Editing atau Revisi.
const canEditEditor = computed(() => {
    if (isSuperAdmin.value) return true;
    if (isTayang.value) return false;
    const hasRole = userRoles.value.includes("medsos_editor");
    return hasRole && ["Proses Editing", "Revisi"].includes(form.status);
});

// 3. Admin Platform: hanya role medsos_admin_platform dan HANYA saat status Menunggu Review.
const canEditAdminPlatform = computed(() => {
    if (isSuperAdmin.value) return true;
    if (isTayang.value) return false;
    const hasRole = userRoles.value.includes("medsos_admin_platform");
    return hasRole && form.status === "Menunggu Review";
});

// Kontrol membuka bagian/tab form modal tugas masing-masing:
const canOpenSection = (section) => {
    if (isSuperAdmin.value) return true;
    if (section === "planner") {
        return (
            userRoles.value.includes("medsos_planner") ||
            userRoles.value.includes("medsos_instruktur")
        );
    }
    if (section === "editor") {
        return userRoles.value.includes("medsos_editor");
    }
    if (section === "admin") {
        return userRoles.value.includes("medsos_admin_platform");
    }
    return false;
};

const setModalSection = (section) => {
    if (!canOpenSection(section)) {
        alert(
            "Hak Akses Terbatas: Anda hanya dapat membuka formulir bagian tugas role Anda.",
        );
        return;
    }
    modalSection.value = section;
};

const getInitialSectionForUser = (content = null) => {
    if (isSuperAdmin.value) {
        return content ? getSectionByStatus(content.status) : "planner";
    }
    if (userRoles.value.includes("medsos_editor")) {
        return "editor";
    }
    if (userRoles.value.includes("medsos_admin_platform")) {
        return "admin";
    }
    return "planner";
};

// Kontrol visibilitas tombol Simpan di bilah aksi bawah
const shouldShowSaveButton = computed(() => {
    if (isSuperAdmin.value) return true;
    if (isTayang.value) return false;
    if (modalSection.value === "planner") {
        return canEditPlanner.value;
    }
    if (modalSection.value === "editor") {
        return canEditEditor.value;
    }
    if (modalSection.value === "admin") {
        return canEditAdminPlatform.value;
    }
    return false;
});

// ==========================================
// TABS MANAJEMEN: 5 TAB UTAMA
// ==========================================
const currentTab = ref(props.activeTab || "kalender");

const switchTab = (tab) => {
    currentTab.value = tab;
    router.get(
        "/admin/sosmedhub",
        {
            tab: tab,
            month: activeMonth.value,
            year: activeYear.value,
            search: searchQuery.value,
            status: statusFilter.value,
            stage: stageFilter.value,
            platform_id: platformFilter.value,
            jenis_konten: jenisKontenFilter.value,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// ==========================================
// KALENDER KONTEN (BULANAN INTERAKTIF)
// ==========================================
const activeMonth = ref(props.currentMonth || new Date().getMonth() + 1);
const activeYear = ref(props.currentYear || new Date().getFullYear());

const monthNames = [
    "Januari",
    "Februari",
    "Maret",
    "April",
    "Mei",
    "Juni",
    "Juli",
    "Agustus",
    "September",
    "Oktober",
    "November",
    "Desember",
];

const weekDayNames = [
    "Senin",
    "Selasa",
    "Rabu",
    "Kamis",
    "Jumat",
    "Sabtu",
    "Minggu",
];

const todayDateStr = computed(() => {
    const d = new Date();
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, "0");
    const day = String(d.getDate()).padStart(2, "0");
    return `${y}-${m}-${day}`;
});

const calendarDays = computed(() => {
    const year = activeYear.value;
    const month = activeMonth.value - 1; // 0-indexed for Date

    const firstDay = new Date(year, month, 1);
    let startDayOffset = firstDay.getDay() - 1; // Monday = 0
    if (startDayOffset < 0) startDayOffset = 6;

    const totalDaysInMonth = new Date(year, month + 1, 0).getDate();
    const totalDaysInPrevMonth = new Date(year, month, 0).getDate();

    const days = [];

    // Hari dari bulan sebelumnya
    for (let i = startDayOffset - 1; i >= 0; i--) {
        const d = totalDaysInPrevMonth - i;
        const prevM = month === 0 ? 12 : month;
        const prevY = month === 0 ? year - 1 : year;
        const dateStr = `${prevY}-${String(prevM).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
        days.push({
            day: d,
            dateStr,
            isCurrentMonth: false,
            isToday: dateStr === todayDateStr.value,
        });
    }

    // Hari bulan aktif
    for (let d = 1; d <= totalDaysInMonth; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
        days.push({
            day: d,
            dateStr,
            isCurrentMonth: true,
            isToday: dateStr === todayDateStr.value,
        });
    }

    // Hari bulan berikutnya agar kelipatan 7
    const remaining = (7 - (days.length % 7)) % 7;
    for (let d = 1; d <= remaining; d++) {
        const nextM = month === 11 ? 1 : month + 2;
        const nextY = month === 11 ? year + 1 : year;
        const dateStr = `${nextY}-${String(nextM).padStart(2, "0")}-${String(d).padStart(2, "0")}`;
        days.push({
            day: d,
            dateStr,
            isCurrentMonth: false,
            isToday: dateStr === todayDateStr.value,
        });
    }

    return days;
});

// Mapping konten ke tanggal (Skenario C: 1 Tanggal Utama)
const calendarContentsByDate = computed(() => {
    const map = {};
    const list = props.calendarContents || [];
    for (const c of list) {
        const primaryDate = c.tanggal_kegiatan
            ? String(c.tanggal_kegiatan).substring(0, 10)
            : c.tanggal_posting
              ? String(c.tanggal_posting).substring(0, 10)
              : null;

        if (primaryDate) {
            if (!map[primaryDate]) map[primaryDate] = [];
            if (!map[primaryDate].some((x) => x.id === c.id)) {
                map[primaryDate].push(c);
            }
        }
    }
    return map;
});

const prevMonth = () => {
    if (activeMonth.value === 1) {
        activeMonth.value = 12;
        activeYear.value -= 1;
    } else {
        activeMonth.value -= 1;
    }
    refreshData();
};

const nextMonth = () => {
    if (activeMonth.value === 12) {
        activeMonth.value = 1;
        activeYear.value += 1;
    } else {
        activeMonth.value += 1;
    }
    refreshData();
};

const goToToday = () => {
    const now = new Date();
    activeYear.value = now.getFullYear();
    activeMonth.value = now.getMonth() + 1;
    refreshData();
};

// ==========================================
// FILTER, SEARCH & SORTING (DAFTAR KONTEN)
// ==========================================
const searchQuery = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status || "");
const stageFilter = ref(props.filters?.stage || "");
const platformFilter = ref(props.filters?.platform_id || "");
const jenisKontenFilter = ref(props.filters?.jenis_konten || "");
const sortBy = ref(props.filters?.sort_by || "tanggal_kegiatan");
const sortDir = ref(props.filters?.sort_dir || "desc");
const perPage = ref(props.filters?.per_page || 10);

const refreshData = () => {
    router.get(
        "/admin/sosmedhub",
        {
            tab: currentTab.value,
            month: activeMonth.value,
            year: activeYear.value,
            search: searchQuery.value,
            status: statusFilter.value,
            stage: stageFilter.value,
            platform_id: platformFilter.value,
            jenis_konten: jenisKontenFilter.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
            per_page: perPage.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const resetFilter = () => {
    searchQuery.value = "";
    statusFilter.value = "";
    stageFilter.value = "";
    platformFilter.value = "";
    jenisKontenFilter.value = "";
    refreshData();
};

const onSort = (column) => {
    if (sortBy.value === column) {
        sortDir.value = sortDir.value === "asc" ? "desc" : "asc";
    } else {
        sortBy.value = column;
        sortDir.value = "desc";
    }
    refreshData();
};

// ==========================================
// BULK ACTION (OPERASI MASSAL DAFTAR KONTEN)
// ==========================================
const selectedContentIds = ref([]);
const isAllSelected = computed(() => {
    const list = props.contents?.data || [];
    return list.length > 0 && selectedContentIds.value.length === list.length;
});

const toggleSelectAll = () => {
    const list = props.contents?.data || [];
    if (isAllSelected.value) {
        selectedContentIds.value = [];
    } else {
        selectedContentIds.value = list.map((item) => item.id);
    }
};

const toggleSelectContent = (id) => {
    const idx = selectedContentIds.value.indexOf(id);
    if (idx > -1) {
        selectedContentIds.value.splice(idx, 1);
    } else {
        selectedContentIds.value.push(id);
    }
};

const executeBulk = (action) => {
    if (!selectedContentIds.value.length) return;
    if (
        action === "delete" &&
        !confirm(`Hapus ${selectedContentIds.value.length} konten terpilih?`)
    ) {
        return;
    }
    router.post(
        "/admin/sosmedhub/bulk",
        {
            action: action,
            ids: selectedContentIds.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedContentIds.value = [];
            },
        },
    );
};

// ==========================================
// MODAL WORKFLOW: 3 BAGIAN (PLANNER, EDITOR, ADMIN PLATFORM)
// ==========================================
const isDialogOpen = ref(false);
const editItem = ref(null);
const modalSection = ref("planner"); // 'planner' | 'editor' | 'admin' | 'diskusi'

const form = useForm({
    nama_kegiatan: "",
    jenis_konten: "Video Reel / Shorts",
    tanggal_kegiatan: "",
    rencana_tayang: "",
    tanggal_posting: "",
    planner_id: "",
    editor_id: "",
    admin_id: "",
    instruktur_id: "",
    pegawai_id: "",
    brief: "",
    caption: "",
    link_referensi: "",
    link_media_mentah: "",
    link_hasil_edit: "",
    link_postingan: "",
    status: "Draft",
    tipe_konten: "final",
    platform_ids: [],
});

// Mode Pembuatan Konten: null (pilih mode), 'bahan' (pengajuan bahan mentah), 'final' (konten lengkap siap produksi)
const createMode = ref(null);

const selectCreateMode = (mode) => {
    createMode.value = mode;
    form.tipe_konten = mode;
    modalSection.value = "planner";
};

// Form Revisi Baru
const revisionForm = useForm({
    target_revisi: "Visual / Video",
    catatan: "",
});

// Form Komentar Tim
const commentForm = useForm({
    body: "",
});

// Helper Evaluasi Ketepatan Tanggal Tayang Konten
const evaluatePublishDelay = (targetDate, actualDate) => {
    if (!targetDate) return null;
    const cleanTarget = String(targetDate).substring(0, 10);
    const cleanActual = actualDate
        ? String(actualDate).substring(0, 10)
        : todayDateStr.value;

    const [ty, tm, td] = cleanTarget.split("-").map(Number);
    const [ay, am, ad] = cleanActual.split("-").map(Number);

    const target = new Date(ty, tm - 1, td);
    const actual = new Date(ay, am - 1, ad);

    const diffMs = actual.getTime() - target.getTime();
    const diffDays = Math.round(diffMs / (1000 * 60 * 60 * 24));

    if (diffDays <= 0) {
        return {
            isLate: false,
            days: 0,
            label: "Tepat Waktu",
            desc: "Tidak Ada Keterlambatan",
        };
    } else {
        return {
            isLate: true,
            days: diffDays,
            label: `Terlambat ${diffDays} Hari`,
            desc: `Melewati jadwal kegiatan (${cleanTarget})`,
        };
    }
};

const setTepatWaktu = () => {
    if (form.tanggal_kegiatan) {
        form.tanggal_posting = form.tanggal_kegiatan;
    } else {
        form.tanggal_posting = todayDateStr.value;
    }
};

// Definisi handler klik sel tanggal di kalender
const onDateCellClick = (dateStr) => {
    openCreate(dateStr);
};

// Buka Modal Tambah Konten (Menampilkan 2 Tombol Besar: Pengajuan Bahan vs Konten Lengkap)
const openCreate = (dateStr = null) => {
    editItem.value = null;
    createMode.value = null; // Menampilkan 2 tombol seleksi kiri-kanan terlebih dahulu
    modalSection.value = getInitialSectionForUser();
    form.reset();
    form.clearErrors();
    form.tanggal_kegiatan = dateStr || todayDateStr.value;
    form.rencana_tayang = dateStr || todayDateStr.value;
    form.tanggal_posting = "";
    form.status = "Draft";
    form.tipe_konten = "final";
    form.planner_id = "";
    form.editor_id = "";
    form.admin_id = "";
    form.instruktur_id = "";
    form.pegawai_id = "";
    form.brief = "";
    form.caption = "";
    form.link_referensi = "";
    form.link_media_mentah = "";
    form.link_hasil_edit = "";
    form.link_postingan = "";
    form.platform_ids = (props.platforms || [])
        .filter(
            (p) =>
                p.is_active &&
                ["facebook", "instagram"].includes(
                    (p.slug || p.name || "").toLowerCase(),
                ),
        )
        .map((p) => p.id);
    isDialogOpen.value = true;
};

// Helper menentukan halaman kerja modal sesuai status alur konten
const getSectionByStatus = (status) => {
    switch (status) {
        case "Proses Editing":
        case "Revisi":
            return "editor";
        case "Menunggu Review":
        case "Tayang":
            return "admin";
        case "Draft":
        default:
            return "planner";
    }
};

// Buka Modal Edit / Detail Konten (Membuka tab kerja sesuai status alur konten)
const openEdit = (content, section = null) => {
    editItem.value = content;
    createMode.value = content.instruktur_id ? "bahan" : "final";
    modalSection.value =
        section && canOpenSection(section)
            ? section
            : getInitialSectionForUser(content);
    form.clearErrors();
    form.nama_kegiatan = content.nama_kegiatan || "";
    form.jenis_konten = content.jenis_konten || "Video Reel / Shorts";
    form.tanggal_kegiatan = content.tanggal_kegiatan
        ? String(content.tanggal_kegiatan).substring(0, 10)
        : "";
    form.rencana_tayang = content.rencana_tayang
        ? String(content.rencana_tayang).substring(0, 10)
        : "";
    form.tanggal_posting = content.tanggal_posting
        ? String(content.tanggal_posting).substring(0, 10)
        : "";
    form.planner_id = content.planner_id || "";
    form.editor_id = content.editor_id || "";
    form.admin_id = content.admin_id || "";
    form.instruktur_id = content.instruktur_id || "";
    form.pegawai_id = content.pegawai_id || "";
    form.brief = content.brief || "";
    form.caption = content.caption || "";
    form.link_referensi = content.link_referensi || "";
    form.link_media_mentah = content.link_media_mentah || "";
    form.link_hasil_edit = content.link_hasil_edit || "";
    form.link_postingan = content.link_postingan || "";
    form.status = content.status || "Draft";
    form.tipe_konten = content.instruktur_id ? "bahan" : "final";
    form.platform_ids = (content.platforms || []).map((p) => p.id);
    isDialogOpen.value = true;
};

// Buka modal edit berdasarkan ID (dari kartu metrik deadline di atas)
const openEditById = (id, section = null) => {
    const found =
        (props.calendarContents || []).find((x) => x.id === id) ||
        (props.contents?.data || []).find((x) => x.id === id);
    if (found) {
        openEdit(found, section);
    } else {
        router.get(
            "/admin/sosmedhub",
            {
                tab: "daftar",
                search: String(id),
            },
            { preserveState: true, preserveScroll: true },
        );
    }
};

const filterByStage = (stage) => {
    currentTab.value = "daftar";
    stageFilter.value = stage;
    refreshData();
};

const toggleFormPlatform = (platformId) => {
    const idx = form.platform_ids.indexOf(platformId);
    if (idx > -1) {
        form.platform_ids.splice(idx, 1);
    } else {
        form.platform_ids.push(platformId);
    }
};

// Upload media file asynchronous via axios
const isUploadingMediaMentah = ref(false);
const isUploadingHasilEdit = ref(false);

const handleFileUpload = async (event, type) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const formData = new FormData();
    formData.append("file", file);
    formData.append("type", type); // 'mentah' | 'hasil'

    if (type === "mentah") isUploadingMediaMentah.value = true;
    else isUploadingHasilEdit.value = true;

    try {
        const response = await window.axios.post(
            "/admin/sosmedhub/upload-media",
            formData,
            {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            },
        );

        if (response.data && response.data.url) {
            if (type === "mentah") {
                form.link_media_mentah = response.data.url;
            } else {
                form.link_hasil_edit = response.data.url;
            }
        }
    } catch (err) {
        console.error("Upload error:", err);
        const msg =
            err.response?.data?.message ||
            "Gagal mengunggah berkas. Pastikan format dan ukuran sesuai (maks 100MB).";
        alert(msg);
    } finally {
        if (type === "mentah") isUploadingMediaMentah.value = false;
        else isUploadingHasilEdit.value = false;
        event.target.value = "";
    }
};

const submitForm = () => {
    if (editItem.value) {
        form.put(`/admin/sosmedhub/${editItem.value.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    } else {
        form.post("/admin/sosmedhub", {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    }
};

// 1. Planner Action: Kirim ke Editor (Otomatis beralih ke Proses Editing tanpa penugasan manual)
const kirimKeEditor = () => {
    if (!form.nama_kegiatan) {
        alert("Harap isi nama kegiatan liputan terlebih dahulu.");
        return;
    }
    form.status = "Proses Editing";
    submitForm();
};

// 2. Editor Action: Mulai Editing & Kirim ke Admin Platform
const mulaiEditing = () => {
    form.status = "Proses Editing";
    submitForm();
};

const kirimKeAdminPlatform = () => {
    if (
        !form.link_hasil_edit &&
        !confirm(
            "Tautan atau berkas hasil edit belum diisi. Tetap kirimkan ke Admin Platform untuk review?",
        )
    ) {
        return;
    }
    form.status = "Menunggu Review";
    submitForm();
};

// 3. Admin Platform Action: Selesaikan Konten
const selesaikanKonten = () => {
    if (
        !form.link_postingan &&
        !confirm(
            "Tautan live postingan media sosial belum diisi. Tetap selesaikan dan tandai konten sebagai Tayang?",
        )
    ) {
        return;
    }
    form.status = "Tayang";
    if (!form.tanggal_posting) {
        form.tanggal_posting = todayDateStr.value;
    }
    submitForm();
};

// Quick Stage Flow Actions
const setQuickStage = (newStatus) => {
    form.status = newStatus;
    if (newStatus === "Tayang" && !form.tanggal_posting) {
        form.tanggal_posting = todayDateStr.value;
    }
    submitForm();
};

// Kirim Revisi
const submitRevision = () => {
    if (!editItem.value || !revisionForm.catatan.trim()) return;
    revisionForm.post(`/admin/sosmedhub/${editItem.value.id}/revisions`, {
        preserveScroll: true,
        onSuccess: () => {
            revisionForm.reset();
            form.status = "Revisi";
        },
    });
};

// Kirim Komentar Tim
const submitComment = () => {
    if (!editItem.value || !commentForm.body.trim()) return;
    const targetId = editItem.value.id;
    commentForm.post(`/admin/sosmedhub/${targetId}/comments`, {
        preserveScroll: true,
        onSuccess: (page) => {
            commentForm.reset();
            const found =
                (page.props.calendarContents || []).find(
                    (x) => x.id === targetId,
                ) ||
                (page.props.contents?.data || []).find(
                    (x) => x.id === targetId,
                );
            if (found) {
                editItem.value = found;
            }
        },
    });
};

// Hapus Konten
const deleteContent = (content) => {
    if (confirm(`Hapus rencana konten "${content.nama_kegiatan}"?`)) {
        router.delete(`/admin/sosmedhub/${content.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                isDialogOpen.value = false;
            },
        });
    }
};

// Toggle Platform di Tab Pengaturan
const togglePlatformActive = (platform) => {
    router.patch(
        `/admin/sosmedhub/platforms/${platform.id}/toggle`,
        {},
        { preserveScroll: true },
    );
};

// Form Pengaturan Sosial (Meta Graph API)
const metaSetting = computed(() => {
    if (!Array.isArray(props.socialSettings)) return null;
    return (
        props.socialSettings.find(
            (s) => s.provider_name === "meta" || s.provider_name === "facebook",
        ) ||
        props.socialSettings[0] ||
        null
    );
});

const showMetaToken = ref(false);
const showMetaSecret = ref(false);
const metaTokenCopied = ref(false);

const socialSettingForm = useForm({
    provider_name: "meta",
    app_id: metaSetting.value?.app_id || "",
    app_secret: metaSetting.value?.app_secret || "",
    page_id: metaSetting.value?.page_id || "",
    access_token: metaSetting.value?.access_token || "",
    ig_user_id: metaSetting.value?.ig_user_id || "",
});

watch(
    () => props.socialSettings,
    () => {
        if (metaSetting.value) {
            socialSettingForm.provider_name = "meta";
            socialSettingForm.app_id = metaSetting.value.app_id || "";
            socialSettingForm.app_secret = metaSetting.value.app_secret || "";
            socialSettingForm.page_id = metaSetting.value.page_id || "";
            socialSettingForm.access_token =
                metaSetting.value.access_token || "";
            socialSettingForm.ig_user_id = metaSetting.value.ig_user_id || "";
        }
    },
    { deep: true, immediate: true },
);

const submitSocialSetting = () => {
    socialSettingForm.post("/admin/sosmedhub/settings/social", {
        preserveScroll: true,
        onSuccess: () => {
            alert("Pengaturan Kredensial Meta Graph API berhasil disimpan!");
        },
    });
};

const copyMetaToken = async () => {
    if (!socialSettingForm.access_token) return;
    try {
        await navigator.clipboard.writeText(socialSettingForm.access_token);
        metaTokenCopied.value = true;
        setTimeout(() => {
            metaTokenCopied.value = false;
        }, 2000);
    } catch (e) {
        // clipboard fallback
    }
};

// Copy feedback
const copiedKey = ref("");
const copyToClipboard = async (text, key) => {
    try {
        await navigator.clipboard.writeText(text);
        copiedKey.value = key;
        setTimeout(() => {
            if (copiedKey.value === key) copiedKey.value = "";
        }, 2000);
    } catch (e) {
        // fallback
    }
};

// Helper Warna & Badge Status (5 Status Alur Kerja Sinkron: Draft -> Proses Editing -> Revisi -> Menunggu Review -> Tayang)
const getStatusBadge = (status) => {
    switch (status) {
        case "Draft":
            return {
                color: "bg-blue-100 text-blue-900 border-blue-300",
                stage: "Planner",
            };
        case "Proses Editing":
            return {
                color: "bg-amber-100 text-amber-900 border-amber-300",
                stage: "Editor",
            };
        case "Revisi":
            return {
                color: "bg-rose-100 text-rose-900 border-rose-300",
                stage: "Editor",
            };
        case "Menunggu Review":
            return {
                color: "bg-purple-100 text-purple-900 border-purple-300",
                stage: "Admin",
            };
        case "Tayang":
            return {
                color: "bg-emerald-100 text-emerald-900 border-emerald-300",
                stage: "Admin",
            };
        default:
            return {
                color: "bg-zinc-100 text-zinc-700 border-zinc-200",
                stage: "Semua",
            };
    }
};

const getPlatformBadgeColor = (slug) => {
    switch (slug?.toLowerCase()) {
        case "instagram":
            return "bg-pink-50 text-pink-700 border-pink-200";
        case "tiktok":
            return "bg-zinc-900 text-white border-zinc-800";
        case "youtube":
            return "bg-red-50 text-red-700 border-red-200";
        case "facebook":
            return "bg-blue-50 text-blue-700 border-blue-200";
        case "x":
            return "bg-zinc-100 text-zinc-900 border-zinc-300";
        default:
            return "bg-zinc-50 text-zinc-700 border-zinc-200";
    }
};

const getActivePic = (item) => {
    if (!item)
        return { name: "Belum Ditugaskan", stage: "Planner", label: "Planner" };
    const stage = getStatusBadge(item.status)?.stage;
    if (stage === "Planner") {
        return {
            name:
                item.planner?.name ||
                item.instruktur?.name ||
                "Belum Ditugaskan",
            stage: "Planner",
            label: item.instruktur?.name ? "Instruktur" : "Planner",
        };
    }
    if (stage === "Editor") {
        return {
            name: item.editor?.name || "Belum Ditugaskan",
            stage: "Editor",
            label: "Editor",
        };
    }
    return {
        name: item.admin?.name || "Belum Ditugaskan",
        stage: "Admin Platform",
        label: "Admin Platform",
    };
};

const getActivePicName = (item) => {
    return getActivePic(item).name;
};

const getStageTheme = (status) => {
    const stage = getStatusBadge(status)?.stage;
    if (stage === "Planner") {
        return {
            stage: "Planner",
            cardClass:
                "bg-blue-100/90 border-2 border-blue-400 hover:border-blue-600 text-blue-950 shadow-xs hover:shadow-md",
            stageBadge: "bg-blue-700 text-white font-extrabold",
            statusBadge:
                "bg-blue-200/90 text-blue-950 border border-blue-300 font-bold",
            activeRoleClass: "bg-blue-700 text-white font-bold shadow-2xs",
            label: "PLANNER",
        };
    }
    if (stage === "Editor") {
        const isRevisi = status === "Revisi";
        if (isRevisi) {
            return {
                stage: "Editor",
                cardClass:
                    "bg-rose-100/90 border-2 border-rose-400 hover:border-rose-600 text-rose-950 shadow-xs hover:shadow-md",
                stageBadge: "bg-rose-700 text-white font-extrabold",
                statusBadge:
                    "bg-rose-200/90 text-rose-950 border border-rose-300 font-bold",
                activeRoleClass: "bg-rose-700 text-white font-bold shadow-2xs",
                label: "EDITOR (REVISI)",
            };
        }
        return {
            stage: "Editor",
            cardClass:
                "bg-amber-100/90 border-2 border-amber-400 hover:border-amber-600 text-amber-950 shadow-xs hover:shadow-md",
            stageBadge: "bg-amber-600 text-white font-extrabold",
            statusBadge:
                "bg-amber-200/90 text-amber-950 border border-amber-300 font-bold",
            activeRoleClass: "bg-amber-600 text-white font-bold shadow-2xs",
            label: "EDITOR",
        };
    }
    // Admin Platform (Menunggu Review, Tayang)
    if (status === "Tayang") {
        return {
            stage: "Admin",
            cardClass:
                "bg-emerald-100/90 border-2 border-emerald-400 hover:border-emerald-600 text-emerald-950 shadow-xs hover:shadow-md",
            stageBadge: "bg-emerald-700 text-white font-extrabold",
            statusBadge:
                "bg-emerald-200/90 text-emerald-950 border border-emerald-300 font-bold",
            activeRoleClass: "bg-emerald-700 text-white font-bold shadow-2xs",
            label: "ADMIN (TAYANG)",
        };
    }
    return {
        stage: "Admin",
        cardClass:
            "bg-purple-100/90 border-2 border-purple-400 hover:border-purple-600 text-purple-950 shadow-xs hover:shadow-md",
        stageBadge: "bg-purple-700 text-white font-extrabold",
        statusBadge:
            "bg-purple-200/90 text-purple-950 border border-purple-300 font-bold",
        activeRoleClass: "bg-purple-700 text-white font-bold shadow-2xs",
        label: "ADMIN (REVIEW)",
    };
};

const getCleanSnippet = (text, maxLength = 85) => {
    if (!text) return "";
    let clean = String(text).replace(/<[^>]*>/g, " ");
    clean = clean
        .replace(/&nbsp;/g, " ")
        .replace(/&amp;/g, "&")
        .replace(/&lt;/g, "<")
        .replace(/&gt;/g, ">")
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, "'");
    clean = clean.replace(/\s+/g, " ").trim();
    if (!clean) return "";
    if (clean.length > maxLength) {
        return clean.substring(0, maxLength).trim() + "...";
    }
    return clean;
};
</script>

<template>
    <Head title="Modul Sosmed Hub - BPVP Pangkep" />

    <DashboardLayout>
        <div class="space-y-6 w-full pb-16">
            <!-- 1. HEADER MODUL SOSMED HUB -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-zinc-200/80 shadow-2xs"
            >
                <div class="space-y-1">
                    <div class="flex items-center gap-2.5">
                        <div
                            class="p-2.5 rounded-xl bg-pink-50 border border-pink-100 text-pink-600"
                        >
                            <Sparkles class="w-5 h-5" />
                        </div>
                        <div>
                            <h1
                                class="text-xl font-bold tracking-tight text-zinc-900 flex items-center gap-2"
                            >
                                Modul Sosmed Hub
                                <Badge
                                    variant="outline"
                                    class="text-[10px] bg-pink-50 text-pink-700 border-pink-200"
                                >
                                    3 Bagian: Planner • Editor • Admin Platform
                                </Badge>
                            </h1>
                            <p class="text-xs text-zinc-500">
                                Alur manajemen konten kegiatan balai: kalender
                                bulanan, produksi visual & video, serta
                                publikasi multi-platform.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex items-center gap-2.5">
                    <a
                        href="/admin/sosmedhub/export"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold rounded-xl border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 shadow-2xs transition"
                    >
                        <FileSpreadsheet class="w-4 h-4 text-emerald-600" />
                        Export Excel / CSV
                    </a>
                    <Button
                        v-if="canOpenSection('planner')"
                        @click="openCreate()"
                        class="bg-pink-600 hover:bg-pink-700 text-white shadow-xs font-semibold gap-1.5 h-9 text-xs"
                    >
                        <Plus class="w-4 h-4" />
                        Buat Konten
                    </Button>
                </div>
            </div>

            <!-- 2. TIGA STATUS RINGKAS ALUR KERJA: PLANNER • EDITOR • ADMIN PLATFORM -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- KARTU 1: PLANNER -->
                <div
                    @click="filterByStage('planner')"
                    class="rounded-2xl border p-4 flex items-center justify-between cursor-pointer transition hover:shadow-xs select-none"
                    :class="
                        (workflowInfo?.planner?.total || 0) > 0
                            ? 'bg-rose-50/90 border-rose-300 text-rose-950 shadow-2xs ring-1 ring-rose-200'
                            : 'bg-white border-zinc-200 text-zinc-900 shadow-2xs'
                    "
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shrink-0"
                            :class="
                                (workflowInfo?.planner?.total || 0) > 0
                                    ? 'bg-rose-600 text-white'
                                    : 'bg-zinc-100 text-zinc-600 border border-zinc-200'
                            "
                        >
                            1
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold leading-tight">
                                    Planner
                                </h3>
                                <Badge
                                    v-if="
                                        (workflowInfo?.planner?.total || 0) > 0
                                    "
                                    class="bg-rose-600 text-white text-[10px] px-1.5 py-0 font-semibold"
                                >
                                    Perlu Dikerjakan
                                </Badge>
                                <Badge
                                    v-else
                                    class="bg-emerald-50 text-emerald-700 border-emerald-200 text-[10px] px-1.5 py-0 font-semibold"
                                >
                                    Aman
                                </Badge>
                            </div>
                            <p
                                class="text-xs mt-0.5"
                                :class="
                                    (workflowInfo?.planner?.total || 0) > 0
                                        ? 'text-rose-700'
                                        : 'text-zinc-500'
                                "
                            >
                                Sementara dikerjakan
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div
                            class="text-2xl font-black"
                            :class="
                                (workflowInfo?.planner?.total || 0) > 0
                                    ? 'text-rose-700'
                                    : 'text-zinc-800'
                            "
                        >
                            {{ workflowInfo?.planner?.total || 0 }}
                        </div>
                        <span
                            class="text-[11px] font-medium"
                            :class="
                                (workflowInfo?.planner?.total || 0) > 0
                                    ? 'text-rose-600'
                                    : 'text-zinc-400'
                            "
                        >
                            Konten
                        </span>
                    </div>
                </div>

                <!-- KARTU 2: EDITOR -->
                <div
                    @click="filterByStage('editor')"
                    class="rounded-2xl border p-4 flex items-center justify-between cursor-pointer transition hover:shadow-xs select-none"
                    :class="
                        (workflowInfo?.editor?.total || 0) > 0
                            ? 'bg-rose-50/90 border-rose-300 text-rose-950 shadow-2xs ring-1 ring-rose-200'
                            : 'bg-white border-zinc-200 text-zinc-900 shadow-2xs'
                    "
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shrink-0"
                            :class="
                                (workflowInfo?.editor?.total || 0) > 0
                                    ? 'bg-rose-600 text-white'
                                    : 'bg-zinc-100 text-zinc-600 border border-zinc-200'
                            "
                        >
                            2
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold leading-tight">
                                    Editor
                                </h3>
                                <Badge
                                    v-if="
                                        (workflowInfo?.editor?.total || 0) > 0
                                    "
                                    class="bg-rose-600 text-white text-[10px] px-1.5 py-0 font-semibold"
                                >
                                    Perlu Dikerjakan
                                </Badge>
                                <Badge
                                    v-else
                                    class="bg-emerald-50 text-emerald-700 border-emerald-200 text-[10px] px-1.5 py-0 font-semibold"
                                >
                                    Aman
                                </Badge>
                            </div>
                            <p
                                class="text-xs mt-0.5"
                                :class="
                                    (workflowInfo?.editor?.total || 0) > 0
                                        ? 'text-rose-700'
                                        : 'text-zinc-500'
                                "
                            >
                                Sementara dikerjakan
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <div
                            class="text-2xl font-black"
                            :class="
                                (workflowInfo?.editor?.total || 0) > 0
                                    ? 'text-rose-700'
                                    : 'text-zinc-800'
                            "
                        >
                            {{ workflowInfo?.editor?.total || 0 }}
                        </div>
                        <span
                            class="text-[11px] font-medium"
                            :class="
                                (workflowInfo?.editor?.total || 0) > 0
                                    ? 'text-rose-600'
                                    : 'text-zinc-400'
                            "
                        >
                            Konten
                        </span>
                    </div>
                </div>

                <!-- KARTU 3: ADMIN PLATFORM -->
                <div
                    @click="filterByStage('admin')"
                    class="rounded-2xl border p-4 flex items-center justify-between cursor-pointer transition hover:shadow-xs select-none"
                    :class="
                        (workflowInfo?.admin?.siap_tayang || 0) > 0
                            ? 'bg-rose-50/90 border-rose-300 text-rose-950 shadow-2xs ring-1 ring-rose-200'
                            : 'bg-white border-zinc-200 text-zinc-900 shadow-2xs'
                    "
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl flex items-center justify-center font-bold text-sm shrink-0"
                            :class="
                                (workflowInfo?.admin?.siap_tayang || 0) > 0
                                    ? 'bg-rose-600 text-white'
                                    : 'bg-zinc-100 text-zinc-600 border border-zinc-200'
                            "
                        >
                            3
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-sm font-bold leading-tight">
                                    Admin Platform
                                </h3>
                                <Badge
                                    v-if="
                                        (workflowInfo?.admin?.siap_tayang ||
                                            0) > 0
                                    "
                                    class="bg-rose-600 text-white text-[10px] px-1.5 py-0 font-semibold"
                                >
                                    Perlu Diposting
                                </Badge>
                                <Badge
                                    v-else
                                    class="bg-emerald-50 text-emerald-700 border-emerald-200 text-[10px] px-1.5 py-0 font-semibold"
                                >
                                    Aman
                                </Badge>
                            </div>
                            <p
                                class="text-xs mt-0.5"
                                :class="
                                    (workflowInfo?.admin?.siap_tayang || 0) > 0
                                        ? 'text-rose-700'
                                        : 'text-zinc-500'
                                "
                            >
                                Sementara diproses:
                                <strong class="font-bold">{{
                                    workflowInfo?.admin?.siap_tayang || 0
                                }}</strong>
                            </p>
                        </div>
                    </div>
                    <div class="text-right pl-4 border-l border-zinc-200/80">
                        <div class="text-2xl font-black text-emerald-600">
                            {{
                                workflowInfo?.admin?.tayang ||
                                stats?.tayang ||
                                0
                            }}
                        </div>
                        <span
                            class="text-[11px] font-semibold text-emerald-700"
                        >
                            Selesai Semuanya
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. NAVIGASI 5 TAB UTAMA (SESUAI INSTRUKSI SPESIFIK) -->
            <div
                class="flex items-center gap-1 bg-white p-1.5 rounded-2xl border border-zinc-200 shadow-2xs overflow-x-auto"
            >
                <button
                    @click="switchTab('kalender')"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl transition cursor-pointer shrink-0"
                    :class="
                        currentTab === 'kalender'
                            ? 'bg-pink-600 text-white shadow-xs'
                            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
                    "
                >
                    <CalendarIcon class="w-4 h-4" />
                    <span>Kalender Konten</span>
                    <span
                        v-if="calendarContents?.length"
                        class="px-1.5 py-0.2 text-[10px] rounded-full"
                        :class="
                            currentTab === 'kalender'
                                ? 'bg-pink-700 text-pink-100'
                                : 'bg-zinc-200 text-zinc-700'
                        "
                    >
                        {{ calendarContents.length }}
                    </span>
                </button>

                <button
                    @click="switchTab('daftar')"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl transition cursor-pointer shrink-0"
                    :class="
                        currentTab === 'daftar'
                            ? 'bg-pink-600 text-white shadow-xs'
                            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
                    "
                >
                    <FileText class="w-4 h-4" />
                    <span>Daftar Konten</span>
                    <span
                        v-if="stats?.total"
                        class="px-1.5 py-0.2 text-[10px] rounded-full"
                        :class="
                            currentTab === 'daftar'
                                ? 'bg-pink-700 text-pink-100'
                                : 'bg-zinc-200 text-zinc-700'
                        "
                    >
                        {{ stats.total }}
                    </span>
                </button>

                <button
                    @click="switchTab('statistik_tim')"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl transition cursor-pointer shrink-0"
                    :class="
                        currentTab === 'statistik_tim'
                            ? 'bg-pink-600 text-white shadow-xs'
                            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
                    "
                >
                    <Users class="w-4 h-4" />
                    <span>Statistik Tim</span>
                </button>

                <button
                    @click="switchTab('statistik_medsos')"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl transition cursor-pointer shrink-0"
                    :class="
                        currentTab === 'statistik_medsos'
                            ? 'bg-pink-600 text-white shadow-xs'
                            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
                    "
                >
                    <TrendingUp class="w-4 h-4" />
                    <span>Statistik Medsos</span>
                </button>

                <button
                    @click="switchTab('pengaturan')"
                    type="button"
                    class="flex items-center gap-2 px-4 py-2 text-xs font-semibold rounded-xl transition cursor-pointer shrink-0 ml-auto"
                    :class="
                        currentTab === 'pengaturan'
                            ? 'bg-pink-600 text-white shadow-xs'
                            : 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900'
                    "
                >
                    <Settings class="w-4 h-4" />
                    <span>Pengaturan</span>
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 1: KALENDER KONTEN BULANAN (SEMUA AKTIVITAS KEGIATAN) -->
            <!-- ======================================================== -->
            <div v-if="currentTab === 'kalender'" class="space-y-4">
                <!-- Header Toolbar Kalender -->
                <div
                    class="bg-white p-4 rounded-2xl border border-zinc-200 shadow-2xs flex flex-col md:flex-row items-center justify-between gap-4"
                >
                    <div
                        class="flex items-center gap-3 w-full md:w-auto justify-between md:justify-start"
                    >
                        <div
                            class="flex items-center gap-1.5 bg-zinc-100 p-1 rounded-xl"
                        >
                            <button
                                @click="prevMonth"
                                title="Bulan Sebelumnya"
                                class="p-1.5 rounded-lg hover:bg-white text-zinc-700 transition cursor-pointer shadow-2xs"
                            >
                                <ChevronLeft class="w-4 h-4" />
                            </button>
                            <button
                                @click="goToToday"
                                class="px-2.5 py-1 text-xs font-bold rounded-lg hover:bg-white text-zinc-800 transition cursor-pointer shadow-2xs"
                            >
                                Hari Ini
                            </button>
                            <button
                                @click="nextMonth"
                                title="Bulan Berikutnya"
                                class="p-1.5 rounded-lg hover:bg-white text-zinc-700 transition cursor-pointer shadow-2xs"
                            >
                                <ChevronRight class="w-4 h-4" />
                            </button>
                        </div>

                        <div
                            class="text-base sm:text-lg font-bold text-zinc-900 tracking-tight"
                        >
                            {{ monthNames[activeMonth - 1] }} {{ activeYear }}
                        </div>
                    </div>

                    <!-- Quick Filter Platform di Kalender -->
                    <div
                        class="flex flex-wrap items-center gap-2 w-full md:w-auto"
                    >
                        <select
                            v-model="platformFilter"
                            @change="refreshData"
                            class="h-8 px-2.5 text-xs font-medium bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                        >
                            <option value="">Semua Platform</option>
                            <option
                                v-for="p in platforms"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.name }}
                            </option>
                        </select>

                        <select
                            v-model="jenisKontenFilter"
                            @change="refreshData"
                            class="h-8 px-2.5 text-xs font-medium bg-zinc-50 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                        >
                            <option value="">Semua Format</option>
                            <option
                                v-for="j in jenisKontenOptions"
                                :key="j"
                                :value="j"
                            >
                                {{ j }}
                            </option>
                        </select>

                        <div
                            class="text-[11px] text-zinc-500 hidden lg:flex items-center gap-1.5 pl-2"
                        >
                            <span
                                class="inline-block w-2.5 h-2.5 rounded-full bg-blue-500"
                            ></span>
                            Planner
                            <span
                                class="inline-block w-2.5 h-2.5 rounded-full bg-amber-500"
                            ></span>
                            Editor
                            <span
                                class="inline-block w-2.5 h-2.5 rounded-full bg-emerald-500"
                            ></span>
                            Admin Platform
                        </div>
                    </div>
                </div>

                <!-- Petunjuk Interaktif -->
                <div
                    class="bg-blue-50/60 border border-blue-100 rounded-xl p-3 flex items-center justify-between text-xs text-blue-700"
                >
                    <div class="flex items-center gap-2">
                        <CalendarIcon class="w-4 h-4 text-blue-600 shrink-0" />
                        <span>
                            <strong>Petunjuk:</strong> Klik langsung pada kotak
                            tanggal untuk membuat agenda kegiatan konten baru
                            pada tanggal tersebut, atau klik kartu kegiatan
                            untuk membuka modal 3 bagian (Planner, Editor, Admin
                            Platform).
                        </span>
                    </div>
                    <span
                        class="hidden sm:inline-block font-semibold bg-white text-blue-700 border border-blue-200 px-2 py-0.5 rounded-md text-[10px]"
                    >
                        {{ calendarContents?.length || 0 }} Aktivitas
                    </span>
                </div>

                <!-- Monthly Calendar Grid -->
                <div
                    class="bg-white rounded-2xl border border-zinc-200 shadow-2xs overflow-hidden"
                >
                    <!-- Day Names Header -->
                    <div
                        class="grid grid-cols-7 border-b border-zinc-200 bg-zinc-50/80 text-center text-xs font-bold text-zinc-600 py-2.5"
                    >
                        <div v-for="day in weekDayNames" :key="day">
                            {{ day }}
                        </div>
                    </div>

                    <!-- Day Cells -->
                    <div
                        class="grid grid-cols-7 divide-x divide-y divide-zinc-200/70 items-stretch"
                    >
                        <div
                            v-for="(cell, idx) in calendarDays"
                            :key="idx"
                            @click="onDateCellClick(cell.dateStr)"
                            class="min-h-[130px] sm:min-h-[160px] p-2 transition group flex flex-col justify-start cursor-pointer select-none"
                            :class="[
                                cell.isCurrentMonth
                                    ? 'bg-white hover:bg-pink-50/15'
                                    : 'bg-zinc-50/50 hover:bg-zinc-100/50',
                                cell.isToday
                                    ? 'ring-2 ring-inset ring-pink-500/50 bg-pink-50/30'
                                    : '',
                            ]"
                        >
                            <!-- Date Number & Header -->
                            <div
                                class="flex items-center justify-between mb-2 shrink-0"
                            >
                                <span
                                    class="text-xs font-bold w-6 h-6 flex items-center justify-center rounded-lg transition"
                                    :class="[
                                        cell.isToday
                                            ? 'bg-pink-600 text-white font-extrabold shadow-2xs'
                                            : cell.isCurrentMonth
                                              ? 'text-zinc-800 group-hover:text-pink-600'
                                              : 'text-zinc-400',
                                    ]"
                                >
                                    {{ cell.day }}
                                </span>

                                <span
                                    class="opacity-0 group-hover:opacity-100 transition text-[10px] text-pink-600 font-semibold flex items-center gap-0.5"
                                >
                                    <Plus class="w-3 h-3" /> Buat
                                </span>
                            </div>

                            <!-- List of Activity Cards on this Date (Tampil Semua Kartu Dinamis) -->
                            <div class="space-y-2 flex-1 w-full">
                                <div
                                    v-for="item in calendarContentsByDate[
                                        cell.dateStr
                                    ] || []"
                                    :key="item.id"
                                    @click.stop="openEdit(item)"
                                    class="p-2.5 rounded-xl border-2 text-left transition-all duration-200 group/card relative flex flex-col gap-2.5 cursor-pointer"
                                    :class="
                                        getStageTheme(item.status).cardClass
                                    "
                                >
                                    <!-- Top: Platforms, Jenis Konten, Stage & Status Badge -->
                                    <div
                                        class="flex items-center justify-between gap-1 flex-wrap"
                                    >
                                        <div
                                            class="flex items-center gap-1.5 flex-wrap"
                                        >
                                            <!-- Brand Platform SVGs -->
                                            <div
                                                class="flex items-center gap-1 shrink-0"
                                            >
                                                <template
                                                    v-for="p in item.platforms ||
                                                    []"
                                                    :key="p.id"
                                                >
                                                    <!-- Instagram -->
                                                    <span
                                                        v-if="
                                                            p.slug ===
                                                            'instagram'
                                                        "
                                                        class="w-5 h-5 rounded-md bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] text-white flex items-center justify-center p-0.5 shadow-2xs shrink-0"
                                                        :title="
                                                            p.name ||
                                                            'Instagram'
                                                        "
                                                    >
                                                        <svg
                                                            class="w-3.5 h-3.5"
                                                            viewBox="0 0 24 24"
                                                            fill="none"
                                                            stroke="currentColor"
                                                            stroke-width="2.2"
                                                            stroke-linecap="round"
                                                            stroke-linejoin="round"
                                                        >
                                                            <rect
                                                                width="20"
                                                                height="20"
                                                                x="2"
                                                                y="2"
                                                                rx="5"
                                                                ry="5"
                                                            />
                                                            <path
                                                                d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"
                                                            />
                                                            <line
                                                                x1="17.5"
                                                                x2="17.51"
                                                                y1="6.5"
                                                                y2="6.5"
                                                            />
                                                        </svg>
                                                    </span>

                                                    <!-- TikTok -->
                                                    <span
                                                        v-else-if="
                                                            p.slug === 'tiktok'
                                                        "
                                                        class="w-5 h-5 rounded-md bg-black text-white flex items-center justify-center p-0.5 shadow-2xs shrink-0"
                                                        :title="
                                                            p.name || 'TikTok'
                                                        "
                                                    >
                                                        <svg
                                                            class="w-3.5 h-3.5 fill-current"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.29 0 .58.04.86.12V9.42a6.34 6.34 0 0 0-.86-.06 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V9a7.92 7.92 0 0 0 4.77 1.6V7.15c-.4 0-.8-.16-1.04-.46z"
                                                            />
                                                        </svg>
                                                    </span>

                                                    <!-- YouTube -->
                                                    <span
                                                        v-else-if="
                                                            p.slug === 'youtube'
                                                        "
                                                        class="w-5 h-5 rounded-md bg-[#FF0000] text-white flex items-center justify-center p-0.5 shadow-2xs shrink-0"
                                                        :title="
                                                            p.name || 'YouTube'
                                                        "
                                                    >
                                                        <svg
                                                            class="w-3.5 h-3.5 fill-current"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                                                            />
                                                        </svg>
                                                    </span>

                                                    <!-- Facebook -->
                                                    <span
                                                        v-else-if="
                                                            p.slug ===
                                                            'facebook'
                                                        "
                                                        class="w-5 h-5 rounded-md bg-[#1877F2] text-white flex items-center justify-center p-0.5 shadow-2xs shrink-0"
                                                        :title="
                                                            p.name || 'Facebook'
                                                        "
                                                    >
                                                        <svg
                                                            class="w-3.5 h-3.5 fill-current"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                                                            />
                                                        </svg>
                                                    </span>

                                                    <!-- X / Twitter -->
                                                    <span
                                                        v-else-if="
                                                            p.slug === 'x'
                                                        "
                                                        class="w-5 h-5 rounded-md bg-black text-white flex items-center justify-center p-0.5 shadow-2xs shrink-0"
                                                        :title="
                                                            p.name ||
                                                            'X (Twitter)'
                                                        "
                                                    >
                                                        <svg
                                                            class="w-3.5 h-3.5 fill-current"
                                                            viewBox="0 0 24 24"
                                                        >
                                                            <path
                                                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                                                            />
                                                        </svg>
                                                    </span>

                                                    <!-- Default -->
                                                    <span
                                                        v-else
                                                        class="w-5 h-5 rounded-md bg-zinc-800 text-white flex items-center justify-center p-0.5 shadow-2xs shrink-0"
                                                        :title="
                                                            p.name || 'Platform'
                                                        "
                                                    >
                                                        <Globe
                                                            class="w-3 h-3"
                                                        />
                                                    </span>
                                                </template>
                                            </div>

                                            <!-- Jenis Konten Badge -->
                                            <span
                                                v-if="item.jenis_konten"
                                                class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded bg-white/90 text-zinc-800 border border-black/10 shadow-2xs shrink-0"
                                            >
                                                {{ item.jenis_konten }}
                                            </span>
                                        </div>

                                        <!-- Stage & Status Badge -->
                                        <div
                                            class="flex items-center gap-1 shrink-0"
                                        >
                                            <span
                                                class="text-[9px] font-black uppercase px-2 py-0.5 rounded-full shadow-2xs tracking-wider"
                                                :class="
                                                    getStageTheme(item.status)
                                                        .stageBadge
                                                "
                                            >
                                                {{
                                                    getStageTheme(item.status)
                                                        .label
                                                }}
                                            </span>
                                            <span
                                                class="text-[9px] font-bold uppercase px-1.5 py-0.5 rounded-md"
                                                :class="
                                                    getStageTheme(item.status)
                                                        .statusBadge
                                                "
                                            >
                                                {{ item.status }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Judul Kegiatan: Ditegaskan Ukuran Font Lebih Besar & Word Wrap Penuh -->
                                    <h4
                                        class="text-xs sm:text-sm font-black text-zinc-950 leading-snug break-words whitespace-normal tracking-tight"
                                    >
                                        {{ item.nama_kegiatan }}
                                    </h4>

                                    <!-- Footer: Tim Bertugas Lengkap (Planner, Editor, Admin) & Diskusi -->
                                    <div
                                        class="pt-2 mt-auto border-t border-black/10 space-y-1.5"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-1 text-[10px] font-bold text-zinc-800"
                                        >
                                            <span
                                                class="flex items-center gap-1 uppercase tracking-wider text-[9px] text-zinc-700 font-extrabold"
                                            >
                                                <Users
                                                    class="w-3 h-3 text-zinc-600"
                                                />
                                                Tim Bertugas
                                            </span>
                                            <!-- Angka Diskusi: Background putih, langsung ikon dan angka -->
                                            <span
                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-white border shadow-2xs transition"
                                                :class="
                                                    (item.comments?.length ||
                                                        0) > 0
                                                        ? 'text-indigo-600 border-indigo-200'
                                                        : 'text-zinc-500 border-zinc-200/80'
                                                "
                                                :title="
                                                    (item.comments?.length ||
                                                        0) > 0
                                                        ? `${item.comments.length} Diskusi`
                                                        : '0 Diskusi'
                                                "
                                            >
                                                <MessageSquare
                                                    class="w-3 h-3 shrink-0"
                                                    :class="
                                                        (item.comments
                                                            ?.length || 0) > 0
                                                            ? 'text-indigo-600'
                                                            : 'text-zinc-400'
                                                    "
                                                />
                                                <span>
                                                    {{
                                                        item.comments?.length ||
                                                        0
                                                    }}
                                                </span>
                                            </span>
                                        </div>

                                        <!-- Lengkap Siapa Bertugas: 3 Tahapan -->
                                        <div class="space-y-1 text-[10px]">
                                            <!-- 1. Planner / Instruktur -->
                                            <div
                                                class="flex items-center justify-between gap-1 px-1.5 py-0.5 rounded text-[10px] transition"
                                                :class="
                                                    getStageTheme(item.status)
                                                        .stage === 'Planner'
                                                        ? getStageTheme(
                                                              item.status,
                                                          ).activeRoleClass
                                                        : 'bg-white/75 text-zinc-700 border border-black/5'
                                                "
                                            >
                                                <span
                                                    class="text-[8px] font-extrabold uppercase tracking-wider opacity-85 shrink-0"
                                                >
                                                    1. Planner
                                                </span>
                                                <span
                                                    class="truncate max-w-[110px] text-right font-semibold"
                                                >
                                                    {{
                                                        item.planner?.name ||
                                                        item.instruktur?.name ||
                                                        "-"
                                                    }}
                                                </span>
                                            </div>

                                            <!-- 2. Editor -->
                                            <div
                                                class="flex items-center justify-between gap-1 px-1.5 py-0.5 rounded text-[10px] transition"
                                                :class="
                                                    getStageTheme(item.status)
                                                        .stage === 'Editor'
                                                        ? getStageTheme(
                                                              item.status,
                                                          ).activeRoleClass
                                                        : 'bg-white/75 text-zinc-700 border border-black/5'
                                                "
                                            >
                                                <span
                                                    class="text-[8px] font-extrabold uppercase tracking-wider opacity-85 shrink-0"
                                                >
                                                    2. Editor
                                                </span>
                                                <span
                                                    class="truncate max-w-[110px] text-right font-semibold"
                                                >
                                                    {{
                                                        item.editor?.name || "-"
                                                    }}
                                                </span>
                                            </div>

                                            <!-- 3. Admin Platform -->
                                            <div
                                                class="flex items-center justify-between gap-1 px-1.5 py-0.5 rounded text-[10px] transition"
                                                :class="
                                                    getStageTheme(item.status)
                                                        .stage === 'Admin'
                                                        ? getStageTheme(
                                                              item.status,
                                                          ).activeRoleClass
                                                        : 'bg-white/75 text-zinc-700 border border-black/5'
                                                "
                                            >
                                                <span
                                                    class="text-[8px] font-extrabold uppercase tracking-wider opacity-85 shrink-0"
                                                >
                                                    3. Admin
                                                </span>
                                                <span
                                                    class="truncate max-w-[110px] text-right font-semibold"
                                                >
                                                    {{
                                                        item.admin?.name || "-"
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: DAFTAR KONTEN (TABEL, SEARCH, FILTER, BULK ACTION)-->
            <!-- ======================================================== -->
            <div v-if="currentTab === 'daftar'" class="space-y-4">
                <!-- Search & Filters Toolbar -->
                <div
                    class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-white p-3.5 rounded-2xl border border-zinc-200 shadow-2xs"
                >
                    <div class="relative flex-1">
                        <Search
                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"
                        />
                        <Input
                            v-model="searchQuery"
                            @keyup.enter="refreshData"
                            placeholder="Cari judul kegiatan, konsep brief, atau naskah caption..."
                            class="pl-9 pr-8 h-9 text-xs"
                        />
                        <button
                            v-if="searchQuery"
                            @click="
                                searchQuery = '';
                                refreshData();
                            "
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Filter Stage 3 Bagian -->
                        <select
                            v-model="stageFilter"
                            @change="refreshData"
                            class="h-9 px-3 text-xs font-semibold bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                        >
                            <option value="">Semua Bagian (3 Bagian)</option>
                            <option value="planner">1. Bagian Planner</option>
                            <option value="editor">2. Bagian Editor</option>
                            <option value="admin">
                                3. Bagian Admin Platform
                            </option>
                        </select>

                        <!-- Filter Status -->
                        <select
                            v-model="statusFilter"
                            @change="refreshData"
                            class="h-9 px-3 text-xs font-semibold bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                        >
                            <option value="">Semua Status</option>
                            <option
                                v-for="st in statusOptions"
                                :key="st"
                                :value="st"
                            >
                                {{ st }}
                            </option>
                        </select>

                        <!-- Filter Platform -->
                        <select
                            v-model="platformFilter"
                            @change="refreshData"
                            class="h-9 px-3 text-xs font-semibold bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                        >
                            <option value="">Semua Platform</option>
                            <option
                                v-for="p in platforms"
                                :key="p.id"
                                :value="p.id"
                            >
                                {{ p.name }}
                            </option>
                        </select>

                        <!-- Per Page -->
                        <select
                            v-model="perPage"
                            @change="refreshData"
                            class="h-9 px-2 text-xs font-semibold bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                        >
                            <option :value="10">10 / hal</option>
                            <option :value="25">25 / hal</option>
                            <option :value="50">50 / hal</option>
                        </select>

                        <Button
                            variant="secondary"
                            size="sm"
                            @click="refreshData"
                            class="h-9 px-3 text-xs font-semibold"
                        >
                            Cari
                        </Button>

                        <button
                            v-if="
                                searchQuery ||
                                statusFilter ||
                                stageFilter ||
                                platformFilter ||
                                jenisKontenFilter
                            "
                            @click="resetFilter"
                            class="text-xs text-pink-600 hover:text-pink-700 font-semibold px-2 py-1"
                        >
                            Reset
                        </button>
                    </div>
                </div>

                <!-- Floating Bulk Action Bar -->
                <div
                    v-if="selectedContentIds.length > 0"
                    class="bg-pink-50 border border-pink-200 p-3 rounded-2xl flex flex-wrap items-center justify-between gap-3 animate-in fade-in"
                >
                    <div class="flex items-center gap-2">
                        <Badge class="bg-pink-600 text-white font-bold">
                            {{ selectedContentIds.length }} Terpilih
                        </Badge>
                        <span class="text-xs text-pink-900 font-medium"
                            >Aksi massal konten kegiatan:</span
                        >
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <Button
                            size="sm"
                            variant="outline"
                            @click="executeBulk('status_draft')"
                            class="h-8 text-xs border-blue-300 text-blue-700 hover:bg-blue-100"
                        >
                            Set Draft
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="executeBulk('status_proses_editing')"
                            class="h-8 text-xs border-amber-300 text-amber-700 hover:bg-amber-100"
                        >
                            Set Proses Editing
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="executeBulk('status_review')"
                            class="h-8 text-xs border-purple-300 text-purple-700 hover:bg-purple-100"
                        >
                            Set Menunggu Review
                        </Button>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="executeBulk('status_tayang')"
                            class="h-8 text-xs border-emerald-300 text-emerald-700 hover:bg-emerald-100"
                        >
                            Set Tayang
                        </Button>
                        <Button
                            v-if="isSuperAdmin"
                            size="sm"
                            variant="destructive"
                            @click="executeBulk('delete')"
                            class="h-8 text-xs font-semibold gap-1"
                        >
                            <Trash2 class="w-3.5 h-3.5" /> Hapus Terpilih
                        </Button>
                        <button
                            @click="selectedContentIds = []"
                            class="text-xs text-zinc-500 hover:text-zinc-800 underline ml-1 cursor-pointer"
                        >
                            Batal
                        </button>
                    </div>
                </div>

                <!-- Tabel Konten Lengkap -->
                <div
                    class="rounded-2xl border border-zinc-200 bg-white shadow-2xs overflow-hidden w-full"
                >
                    <div class="overflow-x-auto w-full">
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow class="bg-zinc-50/80">
                                    <TableHead class="w-10">
                                        <button
                                            @click="toggleSelectAll"
                                            type="button"
                                            class="flex items-center justify-center text-zinc-500 hover:text-zinc-800"
                                        >
                                            <CheckSquare
                                                v-if="isAllSelected"
                                                class="w-4 h-4 text-pink-600"
                                            />
                                            <Square v-else class="w-4 h-4" />
                                        </button>
                                    </TableHead>

                                    <TableHead
                                        class="w-12 text-center text-zinc-500 font-bold text-xs"
                                        >No.</TableHead
                                    >

                                    <TableHead
                                        class="w-[300px] min-w-[250px] max-w-[340px]"
                                    >
                                        <DataTableColumnHeader
                                            title="Konten & Format Kegiatan"
                                            column="nama_kegiatan"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            @sort="onSort"
                                        />
                                    </TableHead>

                                    <TableHead class="min-w-[130px]">
                                        <DataTableColumnHeader
                                            title="Tgl Konten"
                                            column="tanggal_kegiatan"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            @sort="onSort"
                                        />
                                    </TableHead>

                                    <TableHead class="min-w-[130px]"
                                        >Platform</TableHead
                                    >

                                    <TableHead class="min-w-[200px]"
                                        >PIC Tim (3 Bagian)</TableHead
                                    >

                                    <TableHead class="min-w-[140px]">
                                        <DataTableColumnHeader
                                            title="Status / Alur"
                                            column="status"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            @sort="onSort"
                                        />
                                    </TableHead>

                                    <TableHead class="min-w-[120px]"
                                        >Link Hasil / Live</TableHead
                                    >

                                    <TableHead class="text-right w-28"
                                        >Aksi</TableHead
                                    >
                                </TableRow>
                            </TableHeader>

                            <TableBody>
                                <TableRow v-if="!contents.data?.length">
                                    <TableCell
                                        colspan="9"
                                        class="h-32 text-center text-zinc-400"
                                    >
                                        Belum ada data konten media sosial
                                        ditemukan.
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="(item, idx) in contents.data"
                                    :key="item.id"
                                    class="hover:bg-zinc-50/60 transition group"
                                >
                                    <TableCell>
                                        <button
                                            @click="
                                                toggleSelectContent(item.id)
                                            "
                                            type="button"
                                            class="flex items-center justify-center text-zinc-400 hover:text-zinc-700"
                                        >
                                            <CheckSquare
                                                v-if="
                                                    selectedContentIds.includes(
                                                        item.id,
                                                    )
                                                "
                                                class="w-4 h-4 text-pink-600"
                                            />
                                            <Square v-else class="w-4 h-4" />
                                        </button>
                                    </TableCell>

                                    <TableCell
                                        class="text-center font-bold text-xs text-zinc-500"
                                    >
                                        {{
                                            (contents.current_page - 1) *
                                                contents.per_page +
                                            idx +
                                            1
                                        }}
                                    </TableCell>

                                    <TableCell
                                        class="w-[300px] min-w-[250px] max-w-[340px] align-top"
                                    >
                                        <div
                                            class="space-y-1.5 max-w-full overflow-hidden"
                                        >
                                            <div
                                                class="flex items-center gap-1.5 flex-wrap"
                                            >
                                                <span
                                                    class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-zinc-100 text-zinc-700 border border-zinc-200 shrink-0"
                                                >
                                                    {{ item.jenis_konten }}
                                                </span>
                                                <span
                                                    v-if="
                                                        item.revisions?.length
                                                    "
                                                    class="px-1.5 py-0.2 rounded text-[10px] font-semibold bg-rose-50 text-rose-700 border border-rose-200 flex items-center gap-0.5 shrink-0"
                                                >
                                                    <Flame class="w-3 h-3" />
                                                    {{ item.revisions.length }}
                                                    Revisi
                                                </span>
                                            </div>
                                            <div
                                                @click="openEdit(item)"
                                                class="font-bold text-zinc-900 group-hover:text-pink-600 cursor-pointer line-clamp-2 text-xs break-words leading-snug"
                                                :title="item.nama_kegiatan"
                                            >
                                                {{ item.nama_kegiatan }}
                                            </div>
                                            <!-- Caption / Brief: Rapi, Disesuaikan Kolom Table, Tidak Membengkak -->
                                            <div
                                                v-if="
                                                    item.caption || item.brief
                                                "
                                                class="text-[11px] text-zinc-500 leading-normal max-w-full break-all"
                                                :title="
                                                    getCleanSnippet(
                                                        item.caption ||
                                                            item.brief,
                                                        300,
                                                    )
                                                "
                                            >
                                                <span
                                                    class="inline-block px-1.5 py-0.2 text-[9px] font-extrabold uppercase rounded bg-zinc-100 text-zinc-600 mr-1.5 border border-zinc-200/80 shrink-0"
                                                >
                                                    {{
                                                        item.caption
                                                            ? "Caption"
                                                            : "Brief"
                                                    }}
                                                </span>
                                                <span
                                                    class="text-zinc-600 line-clamp-2"
                                                >
                                                    {{
                                                        getCleanSnippet(
                                                            item.caption ||
                                                                item.brief,
                                                            80,
                                                        )
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="text-xs font-semibold text-zinc-800"
                                        >
                                            {{
                                                item.tanggal_kegiatan
                                                    ? item.tanggal_kegiatan.substring(
                                                          0,
                                                          10,
                                                      )
                                                    : "-"
                                            }}
                                        </div>
                                        <div
                                            v-if="
                                                item.status === 'Tayang' &&
                                                item.tanggal_posting
                                            "
                                            class="text-[10px] text-emerald-600 font-medium"
                                        >
                                            Tayang:
                                            {{
                                                item.tanggal_posting.substring(
                                                    0,
                                                    10,
                                                )
                                            }}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="flex items-center gap-1 flex-wrap"
                                        >
                                            <span
                                                v-for="p in item.platforms"
                                                :key="p.id"
                                                class="px-1.5 py-0.5 rounded text-[10px] font-semibold border"
                                                :class="
                                                    getPlatformBadgeColor(
                                                        p.slug,
                                                    )
                                                "
                                            >
                                                {{ p.name }}
                                            </span>
                                            <span
                                                v-if="!item.platforms?.length"
                                                class="text-[11px] text-zinc-400 italic"
                                            >
                                                Belum diset
                                            </span>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="text-[11px] space-y-0.5 max-w-[200px]"
                                        >
                                            <div
                                                class="flex items-center gap-1 text-zinc-700"
                                            >
                                                <span
                                                    class="text-zinc-400 font-bold shrink-0"
                                                    >1. Planner:</span
                                                >
                                                <span
                                                    class="font-medium truncate max-w-[130px]"
                                                    :title="
                                                        item.planner?.name ||
                                                        (item.instruktur?.name
                                                            ? 'Menunggu Planner'
                                                            : '-')
                                                    "
                                                    >{{
                                                        item.planner?.name ||
                                                        (item.instruktur?.name
                                                            ? "Menunggu Planner"
                                                            : "-")
                                                    }}</span
                                                >
                                            </div>
                                            <div
                                                v-if="item.instruktur?.name"
                                                class="text-[10px] text-blue-600 font-medium pl-4 truncate max-w-[130px]"
                                                :title="item.instruktur.name"
                                            >
                                                Pengaju:
                                                {{ item.instruktur.name }}
                                            </div>
                                            <div
                                                class="flex items-center gap-1 text-zinc-700"
                                            >
                                                <span
                                                    class="text-zinc-400 font-bold shrink-0"
                                                    >2. Editor:</span
                                                >
                                                <span
                                                    class="font-medium truncate max-w-[130px]"
                                                    :title="
                                                        item.editor?.name || '-'
                                                    "
                                                    >{{
                                                        item.editor?.name || "-"
                                                    }}</span
                                                >
                                            </div>
                                            <div
                                                class="flex items-center gap-1 text-zinc-700"
                                            >
                                                <span
                                                    class="text-zinc-400 font-bold shrink-0"
                                                    >3. Admin:</span
                                                >
                                                <span
                                                    class="font-medium truncate max-w-[130px]"
                                                    :title="
                                                        item.admin?.name || '-'
                                                    "
                                                    >{{
                                                        item.admin?.name || "-"
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div class="space-y-1">
                                            <span
                                                class="inline-block px-2.5 py-1 rounded-lg text-xs font-bold border"
                                                :class="
                                                    getStatusBadge(item.status)
                                                        .color
                                                "
                                            >
                                                {{ item.status }}
                                            </span>
                                            <div
                                                class="text-[10px] text-zinc-500 font-medium"
                                            >
                                                Tahap:
                                                <strong>{{
                                                    getStatusBadge(item.status)
                                                        .stage
                                                }}</strong>
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div class="flex items-center gap-1.5">
                                            <a
                                                v-if="item.link_postingan"
                                                :href="item.link_postingan"
                                                target="_blank"
                                                title="Buka Postingan Live"
                                                class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition border border-emerald-200"
                                            >
                                                <Globe class="w-3.5 h-3.5" />
                                            </a>
                                            <a
                                                v-if="item.link_hasil_edit"
                                                :href="item.link_hasil_edit"
                                                target="_blank"
                                                title="Buka Preview Hasil Render"
                                                class="p-1.5 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition border border-purple-200"
                                            >
                                                <Video class="w-3.5 h-3.5" />
                                            </a>
                                            <a
                                                v-if="item.link_media_mentah"
                                                :href="item.link_media_mentah"
                                                target="_blank"
                                                title="Buka Bahan Mentah Drive"
                                                class="p-1.5 rounded-lg bg-zinc-100 text-zinc-600 hover:bg-zinc-200 transition border border-zinc-200"
                                            >
                                                <FolderGit2
                                                    class="w-3.5 h-3.5"
                                                />
                                            </a>
                                            <span
                                                v-if="
                                                    !item.link_postingan &&
                                                    !item.link_hasil_edit &&
                                                    !item.link_media_mentah
                                                "
                                                class="text-[11px] text-zinc-400"
                                            >
                                                -
                                            </span>
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <div
                                            class="flex items-center justify-end gap-1"
                                        >
                                            <Button
                                                size="sm"
                                                variant="ghost"
                                                @click="openEdit(item)"
                                                class="h-8 w-8 p-0 text-zinc-600 hover:text-pink-600 hover:bg-pink-50"
                                                title="Buka & Edit Detail 3 Bagian"
                                            >
                                                <Pencil class="w-3.5 h-3.5" />
                                            </Button>
                                            <Button
                                                v-if="isSuperAdmin"
                                                size="sm"
                                                variant="ghost"
                                                @click="deleteContent(item)"
                                                class="h-8 w-8 p-0 text-zinc-400 hover:text-rose-600 hover:bg-rose-50"
                                                title="Hapus Konten"
                                            >
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>

                    <!-- Pagination -->
                    <div class="p-3 border-t border-zinc-200">
                        <DataTablePagination :links="contents.links" />
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 3: STATISTIK TIM (EVALUASI BEBAN KERJA & KONTRIBUSI) -->
            <!-- ======================================================== -->
            <div v-if="currentTab === 'statistik_tim'" class="space-y-6">
                <!-- Top KPI Cards -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div
                        class="bg-white p-4 rounded-2xl border border-zinc-200 shadow-2xs"
                    >
                        <div
                            class="flex items-center justify-between text-zinc-500 text-xs mb-2"
                        >
                            <span>Total Konten</span>
                            <Layers class="w-4 h-4 text-blue-600" />
                        </div>
                        <div class="text-2xl font-extrabold text-zinc-900">
                            {{ teamStats?.total_konten || 0 }}
                        </div>
                        <p class="text-[11px] text-zinc-400 mt-1">
                            Kegiatan konten tercatat
                        </p>
                    </div>

                    <div
                        class="bg-white p-4 rounded-2xl border border-zinc-200 shadow-2xs"
                    >
                        <div
                            class="flex items-center justify-between text-zinc-500 text-xs mb-2"
                        >
                            <span>Dalam Produksi</span>
                            <Clock class="w-4 h-4 text-amber-600" />
                        </div>
                        <div class="text-2xl font-extrabold text-amber-600">
                            {{ teamStats?.dalam_proses || 0 }}
                        </div>
                        <p class="text-[11px] text-zinc-400 mt-1">
                            Tahap Planner & Editor
                        </p>
                    </div>

                    <div
                        class="bg-white p-4 rounded-2xl border border-zinc-200 shadow-2xs"
                    >
                        <div
                            class="flex items-center justify-between text-zinc-500 text-xs mb-2"
                        >
                            <span>Sudah Tayang</span>
                            <CheckCircle2 class="w-4 h-4 text-emerald-600" />
                        </div>
                        <div class="text-2xl font-extrabold text-emerald-600">
                            {{ teamStats?.sudah_tayang || 0 }}
                        </div>
                        <p class="text-[11px] text-zinc-400 mt-1">
                            Live di platform media sosial
                        </p>
                    </div>

                    <div
                        class="bg-white p-4 rounded-2xl border border-zinc-200 shadow-2xs"
                    >
                        <div
                            class="flex items-center justify-between text-zinc-500 text-xs mb-2"
                        >
                            <span>Total Revisi & Feedback</span>
                            <Flame class="w-4 h-4 text-rose-600" />
                        </div>
                        <div class="text-2xl font-extrabold text-rose-600">
                            {{ teamStats?.total_revisi || 0 }}
                        </div>
                        <p class="text-[11px] text-zinc-400 mt-1">
                            Penyempurnaan kualitas konten
                        </p>
                    </div>
                </div>

                <!-- 3 Bagian Tim Breakdown -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- 1. Tim Planner -->
                    <div
                        class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-2xs space-y-4"
                    >
                        <div
                            class="flex items-center justify-between pb-3 border-b border-zinc-100"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs"
                                >
                                    1
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-zinc-900">
                                        Kinerja Planner
                                    </h3>
                                    <p class="text-[11px] text-zinc-500">
                                        Perencanaan topik & brief liputan
                                    </p>
                                </div>
                            </div>
                            <Badge
                                class="bg-blue-50 text-blue-700 border-blue-200"
                            >
                                {{ teamStats?.planners?.length || 0 }} Orang
                            </Badge>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="user in teamStats?.planners || []"
                                :key="user.id"
                                class="p-3 rounded-xl bg-zinc-50/80 border border-zinc-200/70 flex items-center justify-between text-xs"
                            >
                                <div>
                                    <div class="font-bold text-zinc-900">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-[10px] text-zinc-500">
                                        {{ user.email }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-extrabold text-zinc-900">
                                        {{ user.total }} Konten
                                    </div>
                                    <div
                                        class="text-[10px] text-emerald-600 font-semibold"
                                    >
                                        {{ user.tayang }} Tayang
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="!teamStats?.planners?.length"
                                class="text-xs text-zinc-400 text-center py-4"
                            >
                                Belum ada riwayat aktivitas planner.
                            </div>
                        </div>
                    </div>

                    <!-- 2. Tim Editor -->
                    <div
                        class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-2xs space-y-4"
                    >
                        <div
                            class="flex items-center justify-between pb-3 border-b border-zinc-100"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-xs"
                                >
                                    2
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-zinc-900">
                                        Kinerja Editor
                                    </h3>
                                    <p class="text-[11px] text-zinc-500">
                                        Editing visual, video render & caption
                                    </p>
                                </div>
                            </div>
                            <Badge
                                class="bg-amber-50 text-amber-700 border-amber-200"
                            >
                                {{ teamStats?.editors?.length || 0 }} Orang
                            </Badge>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="user in teamStats?.editors || []"
                                :key="user.id"
                                class="p-3 rounded-xl bg-zinc-50/80 border border-zinc-200/70 flex items-center justify-between text-xs"
                            >
                                <div>
                                    <div class="font-bold text-zinc-900">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-[10px] text-zinc-500">
                                        {{ user.email }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="font-extrabold text-zinc-900">
                                        {{ user.total }} Ditugaskan
                                    </div>
                                    <div
                                        class="text-[10px] text-amber-600 font-semibold"
                                    >
                                        {{ user.editing }} Proses Editing
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="!teamStats?.editors?.length"
                                class="text-xs text-zinc-400 text-center py-4"
                            >
                                Belum ada riwayat penugasan editor.
                            </div>
                        </div>
                    </div>

                    <!-- 3. Tim Admin Platform -->
                    <div
                        class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-2xs space-y-4"
                    >
                        <div
                            class="flex items-center justify-between pb-3 border-b border-zinc-100"
                        >
                            <div class="flex items-center gap-2">
                                <div
                                    class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs"
                                >
                                    3
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-zinc-900">
                                        Admin Platform
                                    </h3>
                                    <p class="text-[11px] text-zinc-500">
                                        Approval, jadwal & publikasi live
                                    </p>
                                </div>
                            </div>
                            <Badge
                                class="bg-emerald-50 text-emerald-700 border-emerald-200"
                            >
                                {{ teamStats?.admins?.length || 0 }} Orang
                            </Badge>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="user in teamStats?.admins || []"
                                :key="user.id"
                                class="p-3 rounded-xl bg-zinc-50/80 border border-zinc-200/70 flex items-center justify-between text-xs"
                            >
                                <div>
                                    <div class="font-bold text-zinc-900">
                                        {{ user.name }}
                                    </div>
                                    <div class="text-[10px] text-zinc-500">
                                        {{ user.email }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div
                                        class="font-extrabold text-emerald-600"
                                    >
                                        {{ user.tayang }} Tayang
                                    </div>
                                    <div
                                        class="text-[10px] text-zinc-500 font-semibold"
                                    >
                                        {{ user.review || 0 }} Menunggu Review
                                    </div>
                                </div>
                            </div>
                            <div
                                v-if="!teamStats?.admins?.length"
                                class="text-xs text-zinc-400 text-center py-4"
                            >
                                Belum ada riwayat publikasi admin platform.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Riwayat Catatan Revisi Terbaru -->
                <div
                    class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-2xs space-y-4"
                >
                    <div class="flex items-center justify-between">
                        <div>
                            <h3
                                class="text-sm font-bold text-zinc-900 flex items-center gap-2"
                            >
                                <History class="w-4 h-4 text-rose-600" />
                                Riwayat Catatan Revisi & Feedback Tim
                            </h3>
                            <p class="text-xs text-zinc-500">
                                Log perbaikan kualitas visual, narasi, dan video
                                balai
                            </p>
                        </div>
                    </div>

                    <div class="space-y-2.5">
                        <div
                            v-for="rev in teamStats?.recent_revisions || []"
                            :key="rev.id"
                            class="p-3 rounded-xl border border-zinc-200 bg-zinc-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs"
                        >
                            <div class="space-y-1">
                                <div class="flex items-center gap-2">
                                    <Badge
                                        class="bg-rose-100 text-rose-800 border-rose-200 text-[10px]"
                                    >
                                        {{ rev.target_revisi }}
                                    </Badge>
                                    <span class="font-bold text-zinc-900">
                                        {{
                                            rev.content?.nama_kegiatan ||
                                            "Konten"
                                        }}
                                    </span>
                                </div>
                                <p class="text-zinc-600 text-xs pl-0.5">
                                    "{{ rev.catatan }}"
                                </p>
                            </div>
                            <div
                                class="text-right text-[11px] text-zinc-400 shrink-0"
                            >
                                Oleh
                                <strong>{{
                                    rev.user?.name || "Reviewer"
                                }}</strong>
                            </div>
                        </div>
                        <div
                            v-if="!teamStats?.recent_revisions?.length"
                            class="text-xs text-zinc-400 text-center py-4"
                        >
                            Belum ada catatan revisi yang dicatat.
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 4: STATISTIK MEDSOS (BREAKDOWN PLATFORM & FORMAT)     -->
            <!-- ======================================================== -->
            <div v-if="currentTab === 'statistik_medsos'" class="space-y-6">
                <!-- Platform Distribution Grid -->
                <div
                    class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4"
                >
                    <div
                        v-for="p in medsosStats?.platforms || []"
                        :key="p.id"
                        class="bg-white p-4 rounded-2xl border border-zinc-200 shadow-2xs space-y-3"
                    >
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-sm text-zinc-900">{{
                                p.name
                            }}</span>
                            <Badge
                                :class="
                                    p.is_active
                                        ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
                                        : 'bg-zinc-100 text-zinc-500'
                                "
                                class="text-[10px]"
                            >
                                {{ p.is_active ? "Aktif" : "Nonaktif" }}
                            </Badge>
                        </div>

                        <div class="space-y-1 text-xs">
                            <div class="flex justify-between text-zinc-500">
                                <span>Total Sasaran:</span>
                                <strong class="text-zinc-900">{{
                                    p.contents_count || 0
                                }}</strong>
                            </div>
                            <div
                                class="flex justify-between text-emerald-600 font-semibold"
                            >
                                <span>Sudah Tayang:</span>
                                <strong>{{ p.tayang_count || 0 }}</strong>
                            </div>
                            <div class="flex justify-between text-amber-600">
                                <span>Dalam Proses:</span>
                                <strong>{{ p.proses_count || 0 }}</strong>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Format Breakdown & Status Funnel -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Format Konten Terpopuler -->
                    <div
                        class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-2xs space-y-4"
                    >
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900">
                                Distribusi Format Konten
                            </h3>
                            <p class="text-xs text-zinc-500">
                                Perbandingan jenis media sosial yang diproduksi
                            </p>
                        </div>

                        <div class="space-y-3">
                            <div
                                v-for="f in medsosStats?.formats || []"
                                :key="f.jenis_konten"
                                class="space-y-1 text-xs"
                            >
                                <div
                                    class="flex justify-between font-semibold text-zinc-800"
                                >
                                    <span>{{ f.jenis_konten }}</span>
                                    <span>{{ f.total }} Konten</span>
                                </div>
                                <div
                                    class="w-full bg-zinc-100 rounded-full h-2 overflow-hidden"
                                >
                                    <div
                                        class="bg-pink-600 h-2 rounded-full transition-all"
                                        :style="{
                                            width: `${Math.min(100, (f.total / (stats?.total || 1)) * 100)}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                            <div
                                v-if="!medsosStats?.formats?.length"
                                class="text-xs text-zinc-400 text-center py-6"
                            >
                                Belum ada format konten terdata.
                            </div>
                        </div>
                    </div>

                    <!-- Status Funnel Pipeline -->
                    <div
                        class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-2xs space-y-4"
                    >
                        <div>
                            <h3 class="text-sm font-bold text-zinc-900">
                                Alur Funnel Konten
                            </h3>
                            <p class="text-xs text-zinc-500">
                                Progres pergerakan konten dari ideasi hingga
                                tayang
                            </p>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(
                                    count, st
                                ) in medsosStats?.status_funnel || {}"
                                :key="st"
                                class="p-2.5 rounded-xl border flex items-center justify-between text-xs"
                                :class="getStatusBadge(st).color"
                            >
                                <span class="font-bold">{{ st }}</span>
                                <Badge class="font-extrabold text-xs">
                                    {{ count }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 5: PENGATURAN (PLATFORM MEDSOS & API SETTINGS)        -->
            <!-- ======================================================== -->
            <div v-if="currentTab === 'pengaturan'" class="space-y-6">
                <!-- Status Platform Media Sosial -->
                <div
                    class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-2xs space-y-4"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                    >
                        <div>
                            <h3
                                class="text-sm font-bold text-zinc-900 flex items-center gap-2"
                            >
                                <Share2 class="w-4 h-4 text-pink-600" />
                                Kelola Platform Media Sosial Resmi
                            </h3>
                            <p class="text-xs text-zinc-500">
                                Aktifkan atau nonaktifkan platform tujuan
                                publikasi BPVP Pangkep
                            </p>
                        </div>
                        <span
                            class="text-xs font-medium text-zinc-500 bg-zinc-100 px-2.5 py-1 rounded-lg w-fit"
                        >
                            {{ platforms.filter((p) => p.is_active).length }}
                            dari {{ platforms.length }} Aktif
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3.5"
                    >
                        <div
                            v-for="p in platforms"
                            :key="p.id"
                            class="p-4 rounded-2xl border transition-all flex items-center justify-between gap-3 shadow-2xs"
                            :class="
                                p.is_active
                                    ? 'border-zinc-200 bg-white hover:border-zinc-300'
                                    : 'border-zinc-200/70 bg-zinc-50/70 opacity-60'
                            "
                        >
                            <div class="flex items-center gap-3 min-w-0">
                                <!-- Brand Platform SVGs matching Kalender Konten -->
                                <!-- Instagram -->
                                <span
                                    v-if="p.slug === 'instagram'"
                                    class="w-10 h-10 rounded-xl bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] text-white flex items-center justify-center p-2 shadow-2xs shrink-0"
                                    :title="p.name || 'Instagram'"
                                >
                                    <svg
                                        class="w-5 h-5"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2.2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <rect
                                            width="20"
                                            height="20"
                                            x="2"
                                            y="2"
                                            rx="5"
                                            ry="5"
                                        />
                                        <path
                                            d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"
                                        />
                                        <line
                                            x1="17.5"
                                            x2="17.51"
                                            y1="6.5"
                                            y2="6.5"
                                        />
                                    </svg>
                                </span>

                                <!-- TikTok -->
                                <span
                                    v-else-if="p.slug === 'tiktok'"
                                    class="w-10 h-10 rounded-xl bg-black text-white flex items-center justify-center p-2 shadow-2xs shrink-0"
                                    :title="p.name || 'TikTok'"
                                >
                                    <svg
                                        class="w-5 h-5 fill-current"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64c.29 0 .58.04.86.12V9.42a6.34 6.34 0 0 0-.86-.06 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34-6.34V9a7.92 7.92 0 0 0 4.77 1.6V7.15c-.4 0-.8-.16-1.04-.46z"
                                        />
                                    </svg>
                                </span>

                                <!-- YouTube -->
                                <span
                                    v-else-if="p.slug === 'youtube'"
                                    class="w-10 h-10 rounded-xl bg-[#FF0000] text-white flex items-center justify-center p-2 shadow-2xs shrink-0"
                                    :title="p.name || 'YouTube'"
                                >
                                    <svg
                                        class="w-5 h-5 fill-current"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"
                                        />
                                    </svg>
                                </span>

                                <!-- Facebook -->
                                <span
                                    v-else-if="p.slug === 'facebook'"
                                    class="w-10 h-10 rounded-xl bg-[#1877F2] text-white flex items-center justify-center p-2 shadow-2xs shrink-0"
                                    :title="p.name || 'Facebook'"
                                >
                                    <svg
                                        class="w-5 h-5 fill-current"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"
                                        />
                                    </svg>
                                </span>

                                <!-- X / Twitter -->
                                <span
                                    v-else-if="p.slug === 'x'"
                                    class="w-10 h-10 rounded-xl bg-black text-white flex items-center justify-center p-2 shadow-2xs shrink-0"
                                    :title="p.name || 'X (Twitter)'"
                                >
                                    <svg
                                        class="w-5 h-5 fill-current"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                                        />
                                    </svg>
                                </span>

                                <!-- Default -->
                                <span
                                    v-else
                                    class="w-10 h-10 rounded-xl bg-zinc-800 text-white flex items-center justify-center p-2 shadow-2xs shrink-0"
                                    :title="p.name || 'Platform'"
                                >
                                    <Globe class="w-5 h-5" />
                                </span>

                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="font-bold text-xs text-zinc-900 truncate"
                                            >{{ p.name }}</span
                                        >
                                        <span
                                            class="inline-block w-2 h-2 rounded-full shrink-0"
                                            :class="
                                                p.is_active
                                                    ? 'bg-emerald-500'
                                                    : 'bg-zinc-300'
                                            "
                                        ></span>
                                    </div>
                                    <div
                                        class="text-[11px] text-zinc-500 font-mono truncate"
                                    >
                                        slug: {{ p.slug }}
                                    </div>
                                </div>
                            </div>

                            <Button
                                size="sm"
                                :variant="p.is_active ? 'outline' : 'secondary'"
                                @click="togglePlatformActive(p)"
                                class="h-8 px-3 text-xs font-semibold shrink-0 cursor-pointer"
                            >
                                {{ p.is_active ? "Nonaktifkan" : "Aktifkan" }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- FORM META: Kredensial API & Graph Token Media Sosial -->
                <div
                    class="bg-white p-6 rounded-2xl border border-zinc-200 shadow-2xs space-y-6"
                >
                    <!-- Header Form Meta -->
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-4 border-b border-zinc-100"
                    >
                        <div class="flex items-start sm:items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-[#1877F2] via-[#bc1888] to-[#f09433] text-white flex items-center justify-center shadow-xs shrink-0"
                            >
                                <svg
                                    class="w-7 h-7 fill-white"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M12 2C6.477 2 2 6.477 2 12c0 4.991 3.657 9.128 8.438 9.879V14.89h-2.54V12h2.54V9.797c0-2.506 1.492-3.89 3.777-3.89 1.094 0 2.238.195 2.238.195v2.46h-1.26c-1.243 0-1.63.771-1.63 1.562V12h2.773l-.443 2.89h-2.33v6.989C18.343 21.129 22 16.99 22 12c0-5.523-4.477-10-10-10z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h3
                                    class="text-base font-bold text-zinc-900 flex items-center gap-2"
                                >
                                    Form Meta (Instagram Graph API & Facebook
                                    Page)
                                </h3>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    Konfigurasi integrasi resmi Meta for
                                    Developers untuk analitik performa &
                                    publikasi otomatis BPVP Pangkep
                                </p>
                            </div>
                        </div>

                        <!-- Status Koneksi Token -->
                        <div class="shrink-0">
                            <span
                                v-if="socialSettingForm.access_token?.trim()"
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                            >
                                <span
                                    class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"
                                ></span>
                                Token Terpasang & Siap Digunakan
                            </span>
                            <span
                                v-else
                                class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200"
                            >
                                <span
                                    class="w-2 h-2 rounded-full bg-amber-500"
                                ></span>
                                Belum Ada Token Meta
                            </span>
                        </div>
                    </div>

                    <!-- Meta Form Inputs -->
                    <form
                        @submit.prevent="submitSocialSetting"
                        class="space-y-5"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Instagram Business Account ID -->
                            <div class="space-y-1.5">
                                <label
                                    class="flex items-center justify-between text-xs font-bold text-zinc-800"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <span
                                            class="w-3.5 h-3.5 rounded bg-gradient-to-tr from-[#f09433] via-[#dc2743] to-[#bc1888] inline-flex items-center justify-center text-[8px] text-white"
                                            >IG</span
                                        >
                                        Instagram Business Account ID
                                    </span>
                                    <span
                                        class="text-[10px] text-zinc-400 font-normal"
                                        >Wajib untuk Instagram</span
                                    >
                                </label>
                                <Input
                                    v-model="socialSettingForm.ig_user_id"
                                    type="text"
                                    placeholder="Contoh: 17841418026703705"
                                    class="h-10 text-xs font-mono bg-zinc-50/70 border-zinc-200 focus:bg-white"
                                />
                                <p class="text-[11px] text-zinc-500">
                                    ID unik akun Instagram Business yang
                                    terhubung dengan Halaman Facebook resmi BPVP
                                    Pangkep.
                                </p>
                            </div>

                            <!-- Facebook Page ID -->
                            <div class="space-y-1.5">
                                <label
                                    class="flex items-center justify-between text-xs font-bold text-zinc-800"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <span
                                            class="w-3.5 h-3.5 rounded bg-[#1877F2] inline-flex items-center justify-center text-[8px] text-white"
                                            >FB</span
                                        >
                                        Facebook Page ID (ID Halaman)
                                    </span>
                                    <span
                                        class="text-[10px] text-zinc-400 font-normal"
                                        >Wajib untuk Facebook</span
                                    >
                                </label>
                                <Input
                                    v-model="socialSettingForm.page_id"
                                    type="text"
                                    placeholder="Contoh: 109283746501928"
                                    class="h-10 text-xs font-mono bg-zinc-50/70 border-zinc-200 focus:bg-white"
                                />
                                <p class="text-[11px] text-zinc-500">
                                    ID Fanpage Facebook resmi BPVP Pangkep
                                    (dapat dilihat di menu Tentang / About
                                    Halaman FB).
                                </p>
                            </div>

                            <!-- Meta App ID -->
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-zinc-800"
                                >
                                    Meta App ID (Client ID)
                                </label>
                                <Input
                                    v-model="socialSettingForm.app_id"
                                    type="text"
                                    placeholder="Contoh: 182948201948271"
                                    class="h-10 text-xs font-mono bg-zinc-50/70 border-zinc-200 focus:bg-white"
                                />
                                <p class="text-[11px] text-zinc-500">
                                    App ID yang terdaftar pada dashboard Meta
                                    for Developers.
                                </p>
                            </div>

                            <!-- Meta App Secret -->
                            <div class="space-y-1.5">
                                <label
                                    class="block text-xs font-bold text-zinc-800"
                                >
                                    Meta App Secret (Client Secret)
                                </label>
                                <div class="relative">
                                    <Input
                                        v-model="socialSettingForm.app_secret"
                                        :type="
                                            showMetaSecret ? 'text' : 'password'
                                        "
                                        placeholder="Masukkan App Secret Meta..."
                                        class="h-10 text-xs pr-10 font-mono bg-zinc-50/70 border-zinc-200 focus:bg-white"
                                    />
                                    <button
                                        type="button"
                                        @click="
                                            showMetaSecret = !showMetaSecret
                                        "
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 cursor-pointer"
                                        :title="
                                            showMetaSecret
                                                ? 'Sembunyikan Secret'
                                                : 'Lihat Secret'
                                        "
                                    >
                                        <Eye
                                            v-if="!showMetaSecret"
                                            class="w-4 h-4"
                                        />
                                        <EyeOff v-else class="w-4 h-4" />
                                    </button>
                                </div>
                                <p class="text-[11px] text-zinc-500">
                                    Kunci rahasia dari aplikasi Meta for
                                    Developers (Pengaturan Dasar / Basic
                                    Settings).
                                </p>
                            </div>
                        </div>

                        <!-- Meta Graph Access Token -->
                        <div class="space-y-2 pt-1">
                            <div class="flex items-center justify-between">
                                <label
                                    class="flex items-center gap-1.5 text-xs font-bold text-zinc-800"
                                >
                                    <Key class="w-4 h-4 text-pink-600" />
                                    Meta Graph Access Token (User / Page Access
                                    Token)
                                </label>
                                <div class="flex items-center gap-2">
                                    <button
                                        v-if="socialSettingForm.access_token"
                                        type="button"
                                        @click="copyMetaToken"
                                        class="text-xs text-pink-600 hover:text-pink-700 font-semibold flex items-center gap-1 cursor-pointer transition"
                                    >
                                        <Check
                                            v-if="metaTokenCopied"
                                            class="w-3.5 h-3.5 text-emerald-600"
                                        />
                                        <Copy v-else class="w-3.5 h-3.5" />
                                        {{
                                            metaTokenCopied
                                                ? "Tersalin!"
                                                : "Salin Token"
                                        }}
                                    </button>
                                    <button
                                        type="button"
                                        @click="showMetaToken = !showMetaToken"
                                        class="text-xs text-zinc-600 hover:text-zinc-900 font-semibold flex items-center gap-1 cursor-pointer transition pl-2 border-l border-zinc-200"
                                    >
                                        <Eye
                                            v-if="!showMetaToken"
                                            class="w-3.5 h-3.5"
                                        />
                                        <EyeOff v-else class="w-3.5 h-3.5" />
                                        {{
                                            showMetaToken
                                                ? "Sembunyikan"
                                                : "Tampilkan"
                                        }}
                                    </button>
                                </div>
                            </div>

                            <div class="relative">
                                <textarea
                                    v-if="showMetaToken"
                                    v-model="socialSettingForm.access_token"
                                    rows="3"
                                    placeholder="Tempel Meta Graph User Token / Page Access Token (dimulai dengan EAA...)"
                                    class="w-full p-3 text-xs font-mono bg-zinc-50/70 border border-zinc-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-pink-500/20 focus:border-pink-500 focus:bg-white resize-none"
                                ></textarea>
                                <Input
                                    v-else
                                    v-model="socialSettingForm.access_token"
                                    type="password"
                                    placeholder="Tempel Meta Graph User Token / Page Access Token (dimulai dengan EAA...)"
                                    class="h-11 text-xs font-mono bg-zinc-50/70 border-zinc-200 focus:bg-white"
                                />
                            </div>
                            <p
                                class="text-[11px] text-zinc-500 leading-relaxed"
                            >
                                Gunakan
                                <strong
                                    >Long-Lived Page Access Token (Never
                                    Expire)</strong
                                >
                                dengan hak akses:
                                <code
                                    class="px-1 py-0.5 rounded bg-zinc-100 text-zinc-700 text-[10px] font-mono"
                                    >pages_show_list</code
                                >,
                                <code
                                    class="px-1 py-0.5 rounded bg-zinc-100 text-zinc-700 text-[10px] font-mono"
                                    >pages_read_engagement</code
                                >,
                                <code
                                    class="px-1 py-0.5 rounded bg-zinc-100 text-zinc-700 text-[10px] font-mono"
                                    >pages_manage_posts</code
                                >,
                                <code
                                    class="px-1 py-0.5 rounded bg-zinc-100 text-zinc-700 text-[10px] font-mono"
                                    >instagram_basic</code
                                >,
                                <code
                                    class="px-1 py-0.5 rounded bg-zinc-100 text-zinc-700 text-[10px] font-mono"
                                    >instagram_manage_insights</code
                                >.
                            </p>
                        </div>

                        <!-- Buttons & Quick Links -->
                        <div
                            class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 pt-3 border-t border-zinc-100"
                        >
                            <div class="flex items-center gap-2 flex-wrap">
                                <a
                                    href="https://developers.facebook.com/tools/explorer/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-zinc-700 bg-zinc-100 hover:bg-zinc-200 rounded-xl transition cursor-pointer"
                                >
                                    <ExternalLink class="w-3.5 h-3.5" />
                                    Meta Graph API Explorer
                                </a>
                                <a
                                    href="https://developers.facebook.com/apps/"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-zinc-700 bg-zinc-100 hover:bg-zinc-200 rounded-xl transition cursor-pointer"
                                >
                                    <ExternalLink class="w-3.5 h-3.5" />
                                    Meta Apps Dashboard
                                </a>
                            </div>

                            <Button
                                type="submit"
                                :disabled="socialSettingForm.processing"
                                class="h-10 px-5 text-xs bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 hover:opacity-95 text-white font-bold rounded-xl shadow-xs transition cursor-pointer shrink-0"
                            >
                                <ShieldCheck class="w-4 h-4 mr-1.5" />
                                {{
                                    socialSettingForm.processing
                                        ? "Menyimpan Kredensial..."
                                        : "Simpan Kredensial Meta"
                                }}
                            </Button>
                        </div>
                    </form>

                    <!-- Petunjuk Singkat Integrasi Meta -->
                    <div
                        class="bg-blue-50/60 border border-blue-100 rounded-2xl p-4 text-xs text-blue-900 space-y-2"
                    >
                        <div
                            class="font-bold flex items-center gap-2 text-blue-800"
                        >
                            <Info class="w-4 h-4 text-blue-600 shrink-0" />
                            Panduan Cepat Integrasi Meta Graph API:
                        </div>
                        <ol
                            class="list-decimal list-inside space-y-1 text-blue-800/90 pl-1 leading-relaxed"
                        >
                            <li>
                                Buka <strong>Meta for Developers</strong> dan
                                pastikan Anda terdaftar sebagai Admin pada Akun
                                Instagram & Fanpage FB BPVP Pangkep.
                            </li>
                            <li>
                                Buka <strong>Graph API Explorer</strong>, pilih
                                Aplikasi Meta Anda, lalu generate
                                <em>User Token</em> dengan izin halaman dan
                                Instagram.
                            </li>
                            <li>
                                Dapatkan ID Akun Bisnis Instagram lewat
                                endpoint:
                                <code
                                    class="bg-white/80 px-1 py-0.5 rounded text-[10px] font-mono"
                                    >me/accounts?fields=instagram_business_account</code
                                >.
                            </li>
                            <li>
                                Perpanjang token menjadi
                                <em>Never Expiring Page Access Token</em> lalu
                                simpan pada formulir di atas.
                            </li>
                        </ol>
                    </div>
                </div>

                <!-- Panduan SOP 3 Bagian Alur Kerja -->
                <div
                    class="bg-white p-5 rounded-2xl border border-zinc-200 shadow-2xs space-y-3"
                >
                    <h3 class="text-sm font-bold text-zinc-900">
                        Panduan Standar Alur 3 Bagian (SOP Medsos Hub)
                    </h3>
                    <div
                        class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs text-zinc-600"
                    >
                        <div
                            class="p-3.5 rounded-xl bg-blue-50/50 border border-blue-100 space-y-1.5"
                        >
                            <div
                                class="font-extrabold text-blue-900 flex items-center gap-1.5"
                            >
                                <span
                                    class="w-5 h-5 rounded-full bg-blue-600 text-white flex items-center justify-center text-[10px]"
                                    >1</span
                                >
                                Bagian Planner (Perencanaan)
                            </div>
                            <p class="text-[11px] text-blue-800">
                                Planner merancang ide liputan, tanggal kegiatan
                                balai, brief narasumber/instruktur, target
                                platform, dan menugaskan editor terkait.
                            </p>
                        </div>

                        <div
                            class="p-3.5 rounded-xl bg-orange-50/60 border border-orange-100 space-y-1.5"
                        >
                            <div
                                class="font-extrabold text-orange-950 flex items-center gap-1.5"
                            >
                                <span
                                    class="w-5 h-5 rounded-full bg-orange-500 text-white flex items-center justify-center text-[10px]"
                                    >2</span
                                >
                                Bagian Editor (Produksi)
                            </div>
                            <p class="text-[11px] text-orange-900/90">
                                Editor mengakses footage mentah, melakukan
                                rendering video / visual grafis, menyusun draft
                                naskah caption, dan merespon catatan revisi.
                            </p>
                        </div>

                        <div
                            class="p-3.5 rounded-xl bg-emerald-50/60 border border-emerald-100 space-y-1.5"
                        >
                            <div
                                class="font-extrabold text-emerald-950 flex items-center gap-1.5"
                            >
                                <span
                                    class="w-5 h-5 rounded-full bg-emerald-600 text-white flex items-center justify-center text-[10px]"
                                    >3</span
                                >
                                Bagian Admin Platform (Publikasi)
                            </div>
                            <p class="text-[11px] text-emerald-900/90">
                                Admin Platform meninjau hasil akhir, menyetujui
                                jadwal tayang, mengunggah ke medsos resmi, dan
                                menautkan link postingan live.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- MODAL DETAIL / FORM KONTEN: TERBAGI 3 BAGIAN             -->
            <!-- (1. PLANNER, 2. EDITOR, 3. ADMIN PLATFORM, + DISKUSI)    -->
            <!-- ======================================================== -->
            <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
                <DialogContent
                    :class="[
                        editItem
                            ? 'max-w-6xl xl:max-w-7xl w-[96vw]'
                            : 'max-w-4xl w-full',
                        form.status === 'Draft'
                            ? 'border-blue-300'
                            : form.status === 'Proses Editing'
                              ? 'border-amber-300'
                              : form.status === 'Revisi'
                                ? 'border-rose-300'
                                : form.status === 'Menunggu Review'
                                  ? 'border-purple-300'
                                  : form.status === 'Tayang'
                                    ? 'border-emerald-300'
                                    : 'border-zinc-200',
                    ]"
                    class="max-h-[92vh] h-[92vh] flex flex-col p-0 rounded-2xl bg-white shadow-2xl overflow-hidden border transition-colors"
                >
                    <DialogHeader
                        class="p-4 sm:p-5 border-b shrink-0 transition-colors"
                        :class="[
                            form.status === 'Draft' ||
                            (!editItem &&
                                (!form.status || form.status === 'Draft'))
                                ? 'bg-blue-50/70 border-blue-200'
                                : form.status === 'Proses Editing'
                                  ? 'bg-amber-50/70 border-amber-200'
                                  : form.status === 'Revisi'
                                    ? 'bg-rose-50/70 border-rose-200'
                                    : form.status === 'Menunggu Review'
                                      ? 'bg-purple-50/70 border-purple-200'
                                      : form.status === 'Tayang'
                                        ? 'bg-emerald-50/70 border-emerald-200'
                                        : 'bg-zinc-50/90 border-zinc-200',
                        ]"
                    >
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-2"
                        >
                            <div>
                                <DialogTitle
                                    class="text-base font-bold text-zinc-900 flex items-center gap-2"
                                >
                                    <span>{{
                                        editItem
                                            ? "Edit Konten Kegiatan"
                                            : "Buat Rencana Konten Baru"
                                    }}</span>
                                    <Badge
                                        :class="
                                            getStatusBadge(form.status).color
                                        "
                                        class="text-xs font-bold shadow-2xs border"
                                    >
                                        {{ form.status }}
                                    </Badge>
                                </DialogTitle>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    Alur kerja terintegrasi 3 bagian:
                                    Perencanaan, Produksi Editing, dan Review
                                    Publikasi Medsos.
                                </p>
                            </div>

                            <div
                                v-if="editItem"
                                class="flex items-center gap-2"
                            >
                                <span
                                    class="text-[11px] font-semibold text-zinc-400 bg-white/70 px-2 py-0.5 rounded border border-zinc-200/60"
                                    >ID: #{{ editItem.id }}</span
                                >
                            </div>
                        </div>

                        <!-- 3 Bagian Stepper Tabs di dalam Modal (Center Aligned dengan PIC di Bawahnya) -->
                        <div class="mt-3.5 pt-3 border-t border-zinc-200/80">
                            <div
                                class="grid grid-cols-1 sm:grid-cols-3 gap-2.5"
                            >
                                <!-- 1. Bagian Planner -->
                                <button
                                    type="button"
                                    @click="setModalSection('planner')"
                                    :disabled="!canOpenSection('planner')"
                                    class="p-2.5 sm:p-3 rounded-xl transition text-center flex flex-col items-center justify-center border shadow-2xs"
                                    :class="[
                                        !canOpenSection('planner')
                                            ? 'opacity-40 cursor-not-allowed bg-zinc-100/70 text-zinc-400 border-zinc-200'
                                            : 'cursor-pointer',
                                        modalSection === 'planner'
                                            ? 'bg-blue-600 text-white border-blue-600 ring-2 ring-blue-400/20 shadow-xs'
                                            : canOpenSection('planner')
                                              ? 'bg-white hover:bg-blue-50/40 text-zinc-700 hover:text-blue-700 border-zinc-200 hover:border-blue-200'
                                              : '',
                                    ]"
                                    :title="
                                        !canOpenSection('planner')
                                            ? 'Bagian ini khusus untuk role Medsos Planner'
                                            : ''
                                    "
                                >
                                    <div
                                        class="flex items-center gap-1.5 font-bold text-xs"
                                    >
                                        <span
                                            class="w-4 h-4 rounded-full text-[10px] flex items-center justify-center font-bold"
                                            :class="
                                                modalSection === 'planner'
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-blue-50 text-blue-600 border border-blue-200'
                                            "
                                            >1</span
                                        >
                                        <span>Bagian Planner</span>
                                    </div>
                                    <!-- PIC Planner di Bawahnya -->
                                    <div
                                        class="text-[11px] mt-1 line-clamp-1 max-w-full font-medium"
                                        :class="
                                            modalSection === 'planner'
                                                ? 'text-blue-100'
                                                : 'text-zinc-500'
                                        "
                                    >
                                        <template
                                            v-if="editItem?.planner?.name"
                                        >
                                            PIC:
                                            <strong
                                                :class="
                                                    modalSection === 'planner'
                                                        ? 'text-white'
                                                        : 'text-zinc-800'
                                                "
                                                >{{
                                                    editItem.planner.name
                                                }}</strong
                                            >
                                        </template>
                                        <template
                                            v-else-if="
                                                editItem?.instruktur?.name
                                            "
                                        >
                                            Pengaju:
                                            <strong
                                                :class="
                                                    modalSection === 'planner'
                                                        ? 'text-white'
                                                        : 'text-zinc-800'
                                                "
                                                >{{
                                                    editItem.instruktur.name
                                                }}</strong
                                            >
                                        </template>
                                        <template v-else>
                                            <span class="italic text-[10px]"
                                                >PIC: Terekam saat
                                                disimpan</span
                                            >
                                        </template>
                                    </div>
                                    <div
                                        v-if="
                                            editItem?.instruktur?.name &&
                                            editItem?.planner?.name
                                        "
                                        class="text-[10px] line-clamp-1"
                                        :class="
                                            modalSection === 'planner'
                                                ? 'text-blue-200'
                                                : 'text-zinc-400'
                                        "
                                    >
                                        (Inisiator:
                                        {{ editItem.instruktur.name }})
                                    </div>
                                </button>

                                <!-- 2. Bagian Editor -->
                                <button
                                    type="button"
                                    @click="setModalSection('editor')"
                                    :disabled="!canOpenSection('editor')"
                                    class="p-2.5 sm:p-3 rounded-xl transition text-center flex flex-col items-center justify-center border shadow-2xs relative"
                                    :class="[
                                        !canOpenSection('editor')
                                            ? 'opacity-40 cursor-not-allowed bg-zinc-100/70 text-zinc-400 border-zinc-200'
                                            : 'cursor-pointer',
                                        modalSection === 'editor'
                                            ? 'bg-orange-500 text-white border-orange-500 ring-2 ring-orange-400/20 shadow-xs'
                                            : canOpenSection('editor')
                                              ? 'bg-white hover:bg-orange-50/40 text-zinc-700 hover:text-orange-700 border-zinc-200 hover:border-orange-200'
                                              : '',
                                    ]"
                                    :title="
                                        !canOpenSection('editor')
                                            ? 'Bagian ini khusus untuk role Medsos Editor'
                                            : ''
                                    "
                                >
                                    <div
                                        class="flex items-center gap-1.5 font-bold text-xs"
                                    >
                                        <span
                                            class="w-4 h-4 rounded-full text-[10px] flex items-center justify-center font-bold"
                                            :class="
                                                modalSection === 'editor'
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-orange-50 text-orange-600 border border-orange-200'
                                            "
                                            >2</span
                                        >
                                        <span>Bagian Editor</span>
                                        <span
                                            v-if="editItem?.revisions?.length"
                                            class="ml-1 px-1.5 py-0.2 rounded-full text-[9px] font-bold"
                                            :class="
                                                modalSection === 'editor'
                                                    ? 'bg-white/25 text-white'
                                                    : 'bg-rose-500 text-white'
                                            "
                                        >
                                            {{ editItem.revisions.length }} Rev
                                        </span>
                                    </div>
                                    <!-- PIC Editor di Bawahnya -->
                                    <div
                                        class="text-[11px] mt-1 line-clamp-1 max-w-full font-medium"
                                        :class="
                                            modalSection === 'editor'
                                                ? 'text-orange-100'
                                                : 'text-zinc-500'
                                        "
                                    >
                                        <template v-if="editItem?.editor?.name">
                                            PIC:
                                            <strong
                                                :class="
                                                    modalSection === 'editor'
                                                        ? 'text-white'
                                                        : 'text-zinc-800'
                                                "
                                                >{{
                                                    editItem.editor.name
                                                }}</strong
                                            >
                                        </template>
                                        <template v-else>
                                            <span class="italic text-[10px]"
                                                >PIC: Terekam saat
                                                dikerjakan</span
                                            >
                                        </template>
                                    </div>
                                </button>

                                <!-- 3. Bagian Admin Platform -->
                                <button
                                    type="button"
                                    @click="setModalSection('admin')"
                                    :disabled="!canOpenSection('admin')"
                                    class="p-2.5 sm:p-3 rounded-xl transition text-center flex flex-col items-center justify-center border shadow-2xs"
                                    :class="[
                                        !canOpenSection('admin')
                                            ? 'opacity-40 cursor-not-allowed bg-zinc-100/70 text-zinc-400 border-zinc-200'
                                            : 'cursor-pointer',
                                        modalSection === 'admin'
                                            ? 'bg-emerald-600 text-white border-emerald-600 ring-2 ring-emerald-400/20 shadow-xs'
                                            : canOpenSection('admin')
                                              ? 'bg-white hover:bg-emerald-50/40 text-zinc-700 hover:text-emerald-700 border-zinc-200 hover:border-emerald-200'
                                              : '',
                                    ]"
                                    :title="
                                        !canOpenSection('admin')
                                            ? 'Bagian ini khusus untuk role Medsos Admin Platform'
                                            : ''
                                    "
                                >
                                    <div
                                        class="flex items-center gap-1.5 font-bold text-xs"
                                    >
                                        <span
                                            class="w-4 h-4 rounded-full text-[10px] flex items-center justify-center font-bold"
                                            :class="
                                                modalSection === 'admin'
                                                    ? 'bg-white/20 text-white'
                                                    : 'bg-emerald-50 text-emerald-600 border border-emerald-200'
                                            "
                                            >3</span
                                        >
                                        <span>Bagian Admin Platform</span>
                                    </div>
                                    <!-- PIC Admin di Bawahnya -->
                                    <div
                                        class="text-[11px] mt-1 line-clamp-1 max-w-full font-medium"
                                        :class="
                                            modalSection === 'admin'
                                                ? 'text-emerald-100'
                                                : 'text-zinc-500'
                                        "
                                    >
                                        <template v-if="editItem?.admin?.name">
                                            PIC:
                                            <strong
                                                :class="
                                                    modalSection === 'admin'
                                                        ? 'text-white'
                                                        : 'text-zinc-800'
                                                "
                                                >{{
                                                    editItem.admin.name
                                                }}</strong
                                            >
                                        </template>
                                        <template v-else>
                                            <span class="italic text-[10px]"
                                                >PIC: Terekam saat tayang</span
                                            >
                                        </template>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </DialogHeader>

                    <!-- CONTAINER UTAMA MODAL: 2 KOLOM (KIRI: FORM KERJA ALUR KONTEN, KANAN: PANEL DISKUSI TIM) -->
                    <div
                        class="flex-1 flex flex-col lg:flex-row min-h-0 overflow-hidden"
                    >
                        <!-- KOLOM KIRI: FORMULIR KERJA ALUR KONTEN -->
                        <div
                            class="flex-1 min-h-0 flex flex-col overflow-hidden"
                        >
                            <!-- ============================================== -->
                            <!-- TAMPILAN 1: DUA TOMBOL BESAR SAAT MEMBUAT KONTEN -->
                            <!-- (KIRI: PENGAJUAN BAHAN, KANAN: KONTEN LENGKAP/FINAL) -->
                            <!-- ============================================== -->
                            <div
                                v-if="!editItem && !createMode"
                                class="flex-1 min-h-0 flex flex-col overflow-hidden"
                            >
                                <div
                                    class="flex-1 min-h-0 overflow-y-auto p-6 sm:p-10 space-y-6 animate-in fade-in"
                                >
                                    <div
                                        class="text-center max-w-xl mx-auto space-y-2"
                                    >
                                        <Badge
                                            class="bg-pink-100 text-pink-700 border-pink-200 text-xs py-0.5 px-3"
                                        >
                                            Alur Perencanaan Konten Media Sosial
                                        </Badge>
                                        <h3
                                            class="text-lg font-extrabold text-zinc-900"
                                        >
                                            Pilih Model Pembuatan Rencana Konten
                                        </h3>
                                        <p
                                            class="text-xs text-zinc-500 leading-relaxed"
                                        >
                                            Tentukan apakah Anda ingin
                                            mengusulkan bahan mentah ide
                                            kegiatan (misal instruktur
                                            pelatihan) atau langsung merancang
                                            konten final yang siap produksi
                                            (admin planner).
                                        </p>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 md:grid-cols-2 gap-5 max-w-3xl mx-auto pt-2"
                                    >
                                        <!-- TOMBOL BESAR KIRI: PENGAJUAN BAHAN -->
                                        <div
                                            @click="selectCreateMode('bahan')"
                                            class="group p-6 rounded-2xl border-2 border-blue-200 bg-linear-to-b from-blue-50/70 to-white hover:border-blue-600 hover:shadow-xl transition-all duration-200 flex flex-col justify-between cursor-pointer space-y-5"
                                        >
                                            <div class="space-y-4">
                                                <div
                                                    class="w-14 h-14 rounded-2xl bg-blue-600 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform"
                                                >
                                                    <FolderGit2
                                                        class="w-7 h-7"
                                                    />
                                                </div>
                                                <div>
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <h4
                                                            class="text-base font-extrabold text-blue-950 group-hover:text-blue-600 transition-colors"
                                                        >
                                                            1. Pengajuan Bahan
                                                            Konten
                                                        </h4>
                                                    </div>
                                                    <span
                                                        class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-700"
                                                    >
                                                        Instruktur / Pegawai
                                                        Balai
                                                    </span>
                                                    <p
                                                        class="text-xs text-zinc-600 mt-2.5 leading-relaxed"
                                                    >
                                                        Untuk Instruktur
                                                        pelatihan atau Pegawai
                                                        yang mengusulkan
                                                        kegiatan & menyerahkan
                                                        bahan mentah
                                                        (foto/video/link drive).
                                                    </p>
                                                </div>

                                                <div
                                                    class="pt-3 border-t border-blue-100 space-y-2 text-xs"
                                                >
                                                    <div
                                                        class="flex items-start gap-2 text-zinc-700"
                                                    >
                                                        <CheckCircle2
                                                            class="w-4 h-4 text-blue-600 shrink-0 mt-0.5"
                                                        />
                                                        <span
                                                            >Anda otomatis
                                                            tercatat sebagai
                                                            <strong
                                                                >Instruktur /
                                                                Pengaju
                                                                Bahan</strong
                                                            ></span
                                                        >
                                                    </div>
                                                    <div
                                                        class="flex items-start gap-2 text-zinc-700"
                                                    >
                                                        <CheckCircle2
                                                            class="w-4 h-4 text-blue-600 shrink-0 mt-0.5"
                                                        />
                                                        <span
                                                            >Admin Planner yang
                                                            melengkapi brief
                                                            teknis sebelum
                                                            dikirim ke
                                                            Editor</span
                                                        >
                                                    </div>
                                                    <div
                                                        class="flex items-start gap-2 text-zinc-700"
                                                    >
                                                        <CheckCircle2
                                                            class="w-4 h-4 text-blue-600 shrink-0 mt-0.5"
                                                        />
                                                        <span
                                                            >Konten memiliki 2
                                                            PIC:
                                                            <strong
                                                                >Inisiator
                                                                Bahan</strong
                                                            >
                                                            +
                                                            <strong
                                                                >Planner
                                                                Pelaksana</strong
                                                            ></span
                                                        >
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="pt-2">
                                                <Button
                                                    type="button"
                                                    class="w-full h-10 bg-blue-600 group-hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow-xs gap-2 cursor-pointer transition"
                                                >
                                                    <span
                                                        >Pilih: Pengajuan
                                                        Bahan</span
                                                    >
                                                    <ArrowRight
                                                        class="w-4 h-4 group-hover:translate-x-1 transition-transform"
                                                    />
                                                </Button>
                                            </div>
                                        </div>

                                        <!-- TOMBOL BESAR KANAN: KONTEN LENGKAP / FINAL -->
                                        <div
                                            @click="selectCreateMode('final')"
                                            class="group p-6 rounded-2xl border-2 border-purple-200 bg-linear-to-b from-purple-50/70 to-white hover:border-purple-600 hover:shadow-xl transition-all duration-200 flex flex-col justify-between cursor-pointer space-y-5"
                                        >
                                            <div class="space-y-4">
                                                <div
                                                    class="w-14 h-14 rounded-2xl bg-purple-600 text-white flex items-center justify-center shadow-md group-hover:scale-110 transition-transform"
                                                >
                                                    <Sparkles class="w-7 h-7" />
                                                </div>
                                                <div>
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <h4
                                                            class="text-base font-extrabold text-purple-950 group-hover:text-purple-600 transition-colors"
                                                        >
                                                            2. Konten Lengkap /
                                                            Final
                                                        </h4>
                                                    </div>
                                                    <span
                                                        class="inline-block mt-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-purple-100 text-purple-700"
                                                    >
                                                        Admin Planner Utama
                                                    </span>
                                                    <p
                                                        class="text-xs text-zinc-600 mt-2.5 leading-relaxed"
                                                    >
                                                        Untuk Admin Planner yang
                                                        sudah memiliki konsep
                                                        matang. Langsung susun
                                                        brief teknis lengkap &
                                                        upload bahan mentah
                                                        untuk langsung
                                                        dikerjakan Editor.
                                                    </p>
                                                </div>

                                                <div
                                                    class="pt-3 border-t border-purple-100 space-y-2 text-xs"
                                                >
                                                    <div
                                                        class="flex items-start gap-2 text-zinc-700"
                                                    >
                                                        <CheckCircle2
                                                            class="w-4 h-4 text-purple-600 shrink-0 mt-0.5"
                                                        />
                                                        <span
                                                            >Anda otomatis
                                                            tercatat sebagai
                                                            <strong
                                                                >Admin Planner
                                                                Utama</strong
                                                            ></span
                                                        >
                                                    </div>
                                                    <div
                                                        class="flex items-start gap-2 text-zinc-700"
                                                    >
                                                        <CheckCircle2
                                                            class="w-4 h-4 text-purple-600 shrink-0 mt-0.5"
                                                        />
                                                        <span
                                                            >Lengkapi arahan
                                                            naskah narasi
                                                            menggunakan Rich
                                                            Text Editor</span
                                                        >
                                                    </div>
                                                    <div
                                                        class="flex items-start gap-2 text-zinc-700"
                                                    >
                                                        <CheckCircle2
                                                            class="w-4 h-4 text-purple-600 shrink-0 mt-0.5"
                                                        />
                                                        <span
                                                            >Bisa langsung klik
                                                            "Kirim ke Editor"
                                                            untuk mulai produksi
                                                            editing</span
                                                        >
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="pt-2">
                                                <Button
                                                    type="button"
                                                    class="w-full h-10 bg-purple-600 group-hover:bg-purple-700 text-white font-bold text-xs rounded-xl shadow-xs gap-2 cursor-pointer transition"
                                                >
                                                    <span
                                                        >Pilih: Konten
                                                        Lengkap</span
                                                    >
                                                    <ArrowRight
                                                        class="w-4 h-4 group-hover:translate-x-1 transition-transform"
                                                    />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Tampilan Pemilihan Mode -->
                                <div
                                    class="shrink-0 bg-white/95 backdrop-blur-xs border-t border-zinc-200/90 p-3 sm:p-4 px-6 flex justify-end"
                                >
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="isDialogOpen = false"
                                        class="text-xs h-9 px-4 cursor-pointer"
                                    >
                                        Tutup
                                    </Button>
                                </div>
                            </div>

                            <!-- TAMPILAN 2: FORM CONTENT BODY (JIKA SUDAH PILIH MODE ATAU EDIT ITEM) -->
                            <form
                                v-else
                                @submit.prevent="submitForm"
                                class="flex-1 min-h-0 flex flex-col overflow-hidden"
                            >
                                <div
                                    class="flex-1 min-h-0 overflow-y-auto p-5 sm:p-6 space-y-5"
                                >
                                    <!-- Banner Status Mode Pembuatan jika belum tersimpan -->
                                    <div
                                        v-if="!editItem"
                                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-3 rounded-xl border text-xs"
                                        :class="
                                            createMode === 'bahan'
                                                ? 'bg-blue-50/80 border-blue-200 text-blue-950'
                                                : 'bg-purple-50/80 border-purple-200 text-purple-950'
                                        "
                                    >
                                        <div class="flex items-center gap-2">
                                            <Badge
                                                :class="
                                                    createMode === 'bahan'
                                                        ? 'bg-blue-600 text-white'
                                                        : 'bg-purple-600 text-white'
                                                "
                                                class="text-[10px]"
                                            >
                                                {{
                                                    createMode === "bahan"
                                                        ? "Mode: Pengajuan Bahan Mentah"
                                                        : "Mode: Konten Lengkap / Final"
                                                }}
                                            </Badge>
                                            <span
                                                class="font-medium text-[11px]"
                                            >
                                                {{
                                                    createMode === "bahan"
                                                        ? "Diajukan oleh Instruktur/Pegawai. Anda terekam sebagai Inisiator Bahan. Admin Planner yang akan melengkapi brief teknis."
                                                        : "Dirancang oleh Admin Planner. Lengkapi brief & bahan mentah, lalu langsung kirimkan ke Tim Editor."
                                                }}
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            @click="createMode = null"
                                            class="text-[11px] font-bold text-blue-700 hover:text-blue-900 underline shrink-0 cursor-pointer"
                                        >
                                            ← Ganti Model
                                        </button>
                                    </div>

                                    <!-- ============================================== -->
                                    <!-- BAGIAN 1: PLANNER (PERENCANAAN & KONSEP)       -->
                                    <!-- ============================================== -->
                                    <div
                                        v-show="modalSection === 'planner'"
                                        class="space-y-4 animate-in fade-in"
                                    >
                                        <div
                                            class="bg-blue-50/50 p-3 rounded-xl border border-blue-100 text-xs text-blue-900 flex items-center justify-between"
                                        >
                                            <span>
                                                <strong>Fokus Planner:</strong>
                                                {{
                                                    createMode === "bahan"
                                                        ? "Ajukan usulan kegiatan, tanggal pelaksanaan, dan serahkan bahan mentah (foto/video/link drive)."
                                                        : "Rancang judul liputan kegiatan, tanggal pelaksanaan, brief arahan naskah narasi, dan bahan mentah."
                                                }}
                                            </span>
                                            <Badge
                                                class="bg-blue-600 text-white text-[10px]"
                                                >Tahap 1</Badge
                                            >
                                        </div>

                                        <!-- Peringatan Formulir Planner Terkunci Jika Tidak Memiliki Hak Akses / Sudah Dikirim / Tayang -->
                                        <div
                                            v-if="!canEditPlanner"
                                            class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200 text-xs text-amber-900 flex items-center gap-2"
                                        >
                                            <AlertCircle
                                                class="w-4 h-4 text-amber-600 shrink-0"
                                            />
                                            <span v-if="isTayang">
                                                <strong
                                                    >Konten Telah
                                                    Tayang:</strong
                                                >
                                                Seluruh rincian perencanaan
                                                bersifat hanya-baca (read-only).
                                            </span>
                                            <span
                                                v-else-if="isPlannerSubmitted"
                                            >
                                                <strong
                                                    >Formulir Planner
                                                    Terkunci:</strong
                                                >
                                                Konten telah diserahkan ke
                                                <strong>Tim Editor</strong>
                                                (Status:
                                                <strong>{{
                                                    form.status
                                                }}</strong
                                                >). Seluruh rincian perencanaan
                                                bersifat hanya-baca (read-only)
                                                dan tidak dapat diubah lagi.
                                            </span>
                                            <span v-else>
                                                <strong
                                                    >Mode Hanya-Baca:</strong
                                                >
                                                Formulir perencanaan hanya dapat
                                                diedit oleh
                                                <strong
                                                    >Medsos Planner /
                                                    Instruktur</strong
                                                >
                                                atau
                                                <strong>Super Admin</strong>.
                                            </span>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label
                                                class="block text-xs font-bold text-zinc-700"
                                            >
                                                Judul / Nama Kegiatan Liputan
                                                <span class="text-rose-500"
                                                    >*</span
                                                >
                                            </label>
                                            <Input
                                                v-model="form.nama_kegiatan"
                                                placeholder="Contoh: Pembukaan Pelatihan Las & Listrik Angkatan III Tahun 2026"
                                                class="h-9 text-xs"
                                                :disabled="!canEditPlanner"
                                                required
                                            />
                                            <span
                                                v-if="form.errors.nama_kegiatan"
                                                class="text-xs text-rose-500"
                                            >
                                                {{ form.errors.nama_kegiatan }}
                                            </span>
                                        </div>

                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-4"
                                        >
                                            <div class="space-y-1.5">
                                                <label
                                                    class="block text-xs font-bold text-zinc-700"
                                                >
                                                    Tanggal Konten / Kegiatan
                                                    <span class="text-rose-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <Input
                                                    v-model="
                                                        form.tanggal_kegiatan
                                                    "
                                                    type="date"
                                                    class="h-9 text-xs"
                                                    :disabled="!canEditPlanner"
                                                    required
                                                />
                                                <p
                                                    class="text-[11px] text-zinc-400"
                                                >
                                                    Jadwal kegiatan atau
                                                    publikasi konten.
                                                </p>
                                            </div>

                                            <div class="space-y-1.5">
                                                <label
                                                    class="block text-xs font-bold text-zinc-700"
                                                >
                                                    Format / Jenis Konten
                                                    <span class="text-rose-500"
                                                        >*</span
                                                    >
                                                </label>
                                                <select
                                                    v-model="form.jenis_konten"
                                                    class="w-full h-9 px-3 text-xs bg-zinc-50 border border-zinc-200 rounded-xl font-medium"
                                                    :disabled="!canEditPlanner"
                                                    required
                                                >
                                                    <option
                                                        v-for="j in jenisKontenOptions"
                                                        :key="j"
                                                        :value="j"
                                                    >
                                                        {{ j }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Target Platforms Multi-Check -->
                                        <div class="space-y-2">
                                            <label
                                                class="block text-xs font-bold text-zinc-700"
                                                >Target Platform Media
                                                Sosial</label
                                            >
                                            <div class="flex flex-wrap gap-2">
                                                <button
                                                    v-for="p in platforms"
                                                    :key="p.id"
                                                    type="button"
                                                    @click="
                                                        canEditPlanner &&
                                                        toggleFormPlatform(p.id)
                                                    "
                                                    :disabled="!canEditPlanner"
                                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold border transition flex items-center gap-1.5"
                                                    :class="[
                                                        form.platform_ids.includes(
                                                            p.id,
                                                        )
                                                            ? 'bg-blue-600 text-white border-blue-600 shadow-2xs'
                                                            : 'bg-zinc-50 text-zinc-700 border-zinc-200',
                                                        !canEditPlanner
                                                            ? 'cursor-not-allowed opacity-75'
                                                            : 'cursor-pointer hover:bg-zinc-100',
                                                    ]"
                                                >
                                                    <Check
                                                        v-if="
                                                            form.platform_ids.includes(
                                                                p.id,
                                                            )
                                                        "
                                                        class="w-3.5 h-3.5"
                                                    />
                                                    <span>{{ p.name }}</span>
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Arahan Brief Konsep Liputan (Read-Only saat tidak bisa diedit) -->
                                        <div class="space-y-1.5">
                                            <label
                                                class="block text-xs font-bold text-zinc-700"
                                            >
                                                {{
                                                    createMode === "bahan"
                                                        ? "Keterangan Kegiatan / Poin Materi / Catatan Instruktur"
                                                        : "Arahan Brief Konsep Liputan & Naskah Narasi"
                                                }}
                                            </label>
                                            <!-- Tampilan Read-Only Brief jika tidak bisa diedit -->
                                            <div
                                                v-if="!canEditPlanner"
                                                class="rounded-xl border border-zinc-200 bg-zinc-50/80 p-3.5 text-xs text-zinc-800 leading-relaxed shadow-2xs min-h-[90px]"
                                            >
                                                <div
                                                    v-if="form.brief"
                                                    v-html="form.brief"
                                                    class="prose prose-sm max-w-none text-xs"
                                                ></div>
                                                <span
                                                    v-else
                                                    class="text-zinc-400 italic"
                                                    >Tidak ada arahan brief
                                                    tertulis.</span
                                                >
                                            </div>
                                            <!-- RichTextEditor jika masih bisa diedit -->
                                            <div
                                                v-else
                                                class="rounded-xl border border-zinc-200 overflow-hidden bg-white shadow-2xs"
                                            >
                                                <RichTextEditor
                                                    v-model="form.brief"
                                                    :placeholder="
                                                        createMode === 'bahan'
                                                            ? 'Tuliskan rangkuman kegiatan pelatihan, narasumber kejuruan, materi yang dipelajari siswa, atau catatan penting...'
                                                            : 'Tuliskan arahan konsep liputan, angle pengambilan video, shot list, poin narasumber instruktur, dan pesan utama balai...'
                                                    "
                                                />
                                            </div>
                                            <p
                                                v-if="canEditPlanner"
                                                class="text-[10px] text-zinc-400"
                                            >
                                                Gunakan formatting teks, daftar
                                                poin (bullet list), atau heading
                                                untuk merinci pesan.
                                            </p>
                                        </div>

                                        <!-- Bahan Mentah / Footage Kegiatan (Diunggah oleh Planner atau Pengaju Bahan) -->
                                        <div
                                            class="space-y-1.5 p-3 rounded-xl bg-blue-50/40 border border-blue-100"
                                        >
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <label
                                                    class="block text-xs font-bold text-blue-950 flex items-center gap-1.5"
                                                >
                                                    <FolderGit2
                                                        class="w-4 h-4 text-blue-600"
                                                    />
                                                    Bahan Mentah / Footage
                                                    Kegiatan
                                                </label>
                                                <span
                                                    class="text-[10px] text-blue-700 font-semibold"
                                                >
                                                    {{
                                                        createMode === "bahan"
                                                            ? "Wajib Diserahkan oleh Pengaju"
                                                            : "Disiapkan oleh Planner"
                                                    }}
                                                </span>
                                            </div>

                                            <div
                                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2"
                                            >
                                                <!-- Tombol Upload File (Hanya jika berhak edit planner) -->
                                                <label
                                                    v-if="canEditPlanner"
                                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold cursor-pointer shrink-0 transition shadow-2xs"
                                                    :class="{
                                                        'opacity-60 cursor-not-allowed':
                                                            isUploadingMediaMentah,
                                                    }"
                                                >
                                                    <Upload class="w-4 h-4" />
                                                    <span>{{
                                                        isUploadingMediaMentah
                                                            ? "Mengunggah..."
                                                            : "Upload Berkas Mentah"
                                                    }}</span>
                                                    <input
                                                        type="file"
                                                        class="hidden"
                                                        @change="
                                                            handleFileUpload(
                                                                $event,
                                                                'mentah',
                                                            )
                                                        "
                                                        :disabled="
                                                            isUploadingMediaMentah ||
                                                            !canEditPlanner
                                                        "
                                                    />
                                                </label>

                                                <!-- Input URL Drive / Cloud -->
                                                <div
                                                    class="flex items-center gap-1.5 flex-1"
                                                >
                                                    <Input
                                                        v-model="
                                                            form.link_media_mentah
                                                        "
                                                        placeholder="Atau tautan Google Drive / Cloud: https://drive.google.com/..."
                                                        class="h-9 text-xs flex-1 bg-white"
                                                        :disabled="
                                                            !canEditPlanner
                                                        "
                                                    />
                                                    <a
                                                        v-if="
                                                            form.link_media_mentah
                                                        "
                                                        :href="
                                                            form.link_media_mentah
                                                        "
                                                        target="_blank"
                                                        class="p-2 rounded-xl bg-blue-100 hover:bg-blue-200 text-blue-700 transition shrink-0"
                                                        title="Buka Berkas / Tautan Bahan Mentah"
                                                    >
                                                        <ExternalLink
                                                            class="w-4 h-4"
                                                        />
                                                    </a>
                                                </div>
                                            </div>
                                            <p
                                                class="text-[10px] text-blue-800/80"
                                            >
                                                Unggah berkas foto/video
                                                langsung ke server atau
                                                tempelkan link Google
                                                Drive/Cloud.
                                            </p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <label
                                                class="block text-xs font-bold text-zinc-700"
                                            >
                                                Link Referensi Ide (Opsional)
                                            </label>
                                            <Input
                                                v-model="form.link_referensi"
                                                type="text"
                                                placeholder="https://instagram.com/reel/... atau link referensi tren sosmed"
                                                class="h-9 text-xs"
                                                :disabled="!canEditPlanner"
                                            />
                                        </div>

                                        <!-- Tombol Aksi Planner: Simpan Pengajuan Bahan (Hanya mode bahan mentah baru) -->
                                        <div
                                            v-if="
                                                createMode === 'bahan' &&
                                                !editItem &&
                                                canEditPlanner
                                            "
                                            class="p-3.5 rounded-xl bg-blue-50/80 border border-blue-200 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3"
                                        >
                                            <div>
                                                <div
                                                    class="text-xs font-bold text-blue-950 flex items-center gap-1.5"
                                                >
                                                    <FolderGit2
                                                        class="w-4 h-4 text-blue-600"
                                                    />
                                                    Alur Pengajuan Bahan: Simpan
                                                    Usulan Kegiatan
                                                </div>
                                                <p
                                                    class="text-[11px] text-blue-800"
                                                >
                                                    Usulan kegiatan & bahan
                                                    mentah akan masuk ke antrean
                                                    Planner. Anda terekam
                                                    otomatis sebagai
                                                    Inisiator/Pengaju Bahan.
                                                </p>
                                            </div>
                                            <Button
                                                type="submit"
                                                :disabled="form.processing"
                                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs h-9 px-4 shrink-0 shadow-xs gap-1.5 cursor-pointer"
                                            >
                                                <Check class="w-4 h-4" />
                                                Simpan Pengajuan Bahan
                                            </Button>
                                        </div>

                                        <!-- Banner Panduan Alur Kerja ke Tim Editor (Hanya jika bisa edit planner & belum dikirim) -->
                                        <div
                                            v-else-if="canEditPlanner"
                                            class="p-3.5 rounded-xl bg-blue-50/70 border border-blue-200 flex items-center gap-3"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0"
                                            >
                                                <ArrowRight class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <div
                                                    class="text-xs font-bold text-blue-950"
                                                >
                                                    Alur Kerja: Serahkan ke Tim
                                                    Editor
                                                </div>
                                                <p
                                                    class="text-[11px] text-blue-800 mt-0.5"
                                                >
                                                    Brief arahan dan bahan
                                                    mentah sudah disiapkan?
                                                    Silakan gunakan tombol
                                                    <strong
                                                        >"Kirim ke
                                                        Editor"</strong
                                                    >
                                                    di bilah bawah untuk
                                                    menyerahkan tugas ke tim
                                                    editor. Akun Anda akan
                                                    otomatis tercatat sebagai
                                                    PIC Planner.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ============================================== -->
                                    <!-- BAGIAN 2: EDITOR (PRODUKSI & EDITING)          -->
                                    <!-- ============================================== -->
                                    <div
                                        v-show="modalSection === 'editor'"
                                        class="space-y-4 animate-in fade-in"
                                    >
                                        <div
                                            class="bg-orange-50/60 p-3 rounded-xl border border-orange-200 text-xs text-orange-950 flex items-center justify-between"
                                        >
                                            <span
                                                ><strong>Fokus Editor:</strong>
                                                Bekerja setelah dikirim oleh
                                                Planner. Akses brief & bahan
                                                mentah, unggah hasil edit, dan
                                                susun naskah caption.</span
                                            >
                                            <Badge
                                                class="bg-orange-500 text-white text-[10px]"
                                                >Tahap 2</Badge
                                            >
                                        </div>

                                        <!-- Peringatan jika status masih Draft -->
                                        <div
                                            v-if="form.status === 'Draft'"
                                            class="p-3 rounded-xl bg-orange-50/40 border border-orange-200 text-xs text-orange-900 flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-2.5"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <AlertCircle
                                                    class="w-4 h-4 text-orange-600 shrink-0"
                                                />
                                                <span
                                                    >Konten masih berstatus
                                                    <strong>Draft</strong>.
                                                    Planner belum mengirimkan
                                                    tugas ke Editor.</span
                                                >
                                            </div>
                                            <Button
                                                v-if="isSuperAdmin"
                                                type="button"
                                                size="sm"
                                                @click="mulaiEditing"
                                                class="h-8 text-xs bg-orange-500 hover:bg-orange-600 text-white font-semibold shrink-0 shadow-2xs"
                                            >
                                                Mulai Produksi Editing
                                            </Button>
                                        </div>

                                        <!-- Peringatan Formulir Editor Terkunci Jika Tidak Berhak / Sudah Dikirim / Tayang -->
                                        <div
                                            v-if="
                                                !canEditEditor &&
                                                form.status !== 'Draft'
                                            "
                                            class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200 text-xs text-amber-900 flex items-center gap-2"
                                        >
                                            <AlertCircle
                                                class="w-4 h-4 text-amber-600 shrink-0"
                                            />
                                            <span v-if="isTayang">
                                                <strong
                                                    >Konten Telah
                                                    Tayang:</strong
                                                >
                                                Seluruh berkas hasil render dan
                                                draft caption bersifat
                                                hanya-baca (read-only).
                                            </span>
                                            <span v-else-if="isEditorSubmitted">
                                                <strong
                                                    >Formulir Editor
                                                    Terkunci:</strong
                                                >
                                                Konten telah diserahkan ke
                                                <strong>Admin Platform</strong>
                                                (Status:
                                                <strong>{{
                                                    form.status
                                                }}</strong
                                                >). Seluruh hasil render dan
                                                draft caption bersifat
                                                hanya-baca (read-only) dan tidak
                                                dapat diubah lagi.
                                            </span>
                                            <span v-else>
                                                <strong
                                                    >Mode Hanya-Baca:</strong
                                                >
                                                Formulir editor hanya dapat
                                                diedit oleh
                                                <strong>Medsos Editor</strong>
                                                atau
                                                <strong>Super Admin</strong>.
                                            </span>
                                        </div>

                                        <!-- Preview Brief & Bahan Mentah dari Planner -->
                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                        >
                                            <div
                                                class="p-3 rounded-xl bg-orange-50/40 border border-orange-100 space-y-1.5 flex flex-col justify-between"
                                            >
                                                <div>
                                                    <div
                                                        class="text-[11px] font-bold text-orange-950 flex items-center gap-1.5"
                                                    >
                                                        <FileText
                                                            class="w-3.5 h-3.5 text-orange-600"
                                                        />
                                                        Brief dari Planner:
                                                    </div>
                                                    <div
                                                        v-if="form.brief"
                                                        class="prose prose-xs max-w-none text-zinc-700 text-[11px] bg-white p-2.5 rounded-lg border border-orange-100 max-h-32 overflow-y-auto mt-1"
                                                        v-html="form.brief"
                                                    ></div>
                                                    <div
                                                        v-else
                                                        class="text-[11px] text-zinc-400 italic py-2"
                                                    >
                                                        Belum ada arahan brief
                                                        tertulis.
                                                    </div>
                                                </div>
                                                <div
                                                    v-if="form.link_referensi"
                                                    class="pt-1"
                                                >
                                                    <a
                                                        :href="
                                                            form.link_referensi
                                                        "
                                                        target="_blank"
                                                        class="text-[11px] font-semibold text-orange-600 hover:text-orange-700 flex items-center gap-1"
                                                    >
                                                        <ExternalLink
                                                            class="w-3 h-3"
                                                        />
                                                        Lihat Referensi Ide
                                                    </a>
                                                </div>
                                            </div>

                                            <div
                                                class="p-3 rounded-xl bg-orange-50/40 border border-orange-100 space-y-2 flex flex-col justify-between"
                                            >
                                                <div>
                                                    <div
                                                        class="text-[11px] font-bold text-orange-950 flex items-center gap-1.5"
                                                    >
                                                        <FolderGit2
                                                            class="w-3.5 h-3.5 text-orange-600"
                                                        />
                                                        Bahan Mentah / Footage:
                                                    </div>
                                                    <p
                                                        class="text-[11px] text-zinc-600 mt-1"
                                                    >
                                                        {{
                                                            form.link_media_mentah
                                                                ? "Bahan mentah telah disiapkan oleh Planner."
                                                                : "Planner belum mengunggah berkas mentah."
                                                        }}
                                                    </p>
                                                </div>
                                                <div>
                                                    <a
                                                        v-if="
                                                            form.link_media_mentah
                                                        "
                                                        :href="
                                                            form.link_media_mentah
                                                        "
                                                        target="_blank"
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold shadow-2xs transition"
                                                    >
                                                        <Download
                                                            class="w-3.5 h-3.5"
                                                        />
                                                        Akses Bahan Mentah
                                                    </a>
                                                    <span
                                                        v-else
                                                        class="text-[11px] text-zinc-400 italic"
                                                    >
                                                        Menunggu berkas dari
                                                        Planner.
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Upload Hasil Edit (Editor) -->
                                        <div
                                            class="space-y-1.5 p-3 rounded-xl bg-orange-50/50 border border-orange-200"
                                        >
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <label
                                                    class="block text-xs font-bold text-orange-950 flex items-center gap-1.5"
                                                >
                                                    <Video
                                                        class="w-4 h-4 text-orange-600"
                                                    />
                                                    Hasil Render / Desain Akhir
                                                    (Upload Berkas atau Tautan
                                                    URL)
                                                </label>
                                                <span
                                                    class="text-[10px] text-orange-700 font-semibold"
                                                    >Tanggung Jawab Editor</span
                                                >
                                            </div>

                                            <div
                                                class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2"
                                            >
                                                <!-- Tombol Upload File Hasil (Hanya jika berhak edit editor) -->
                                                <label
                                                    v-if="canEditEditor"
                                                    class="inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-xl bg-orange-500 hover:bg-orange-600 text-white text-xs font-semibold cursor-pointer shrink-0 transition shadow-2xs"
                                                    :class="{
                                                        'opacity-60 cursor-not-allowed':
                                                            isUploadingHasilEdit,
                                                    }"
                                                >
                                                    <Upload class="w-4 h-4" />
                                                    <span>{{
                                                        isUploadingHasilEdit
                                                            ? "Mengunggah..."
                                                            : "Upload Berkas Hasil"
                                                    }}</span>
                                                    <input
                                                        type="file"
                                                        class="hidden"
                                                        @change="
                                                            handleFileUpload(
                                                                $event,
                                                                'hasil',
                                                            )
                                                        "
                                                        :disabled="
                                                            isUploadingHasilEdit ||
                                                            !canEditEditor
                                                        "
                                                    />
                                                </label>

                                                <!-- Input URL Hasil -->
                                                <div
                                                    class="flex items-center gap-1.5 flex-1"
                                                >
                                                    <Input
                                                        v-model="
                                                            form.link_hasil_edit
                                                        "
                                                        placeholder="Atau tautan Google Drive / YouTube preview: https://..."
                                                        class="h-9 text-xs flex-1 bg-white border-zinc-200 focus:ring-orange-500"
                                                        :disabled="
                                                            !canEditEditor
                                                        "
                                                    />
                                                    <a
                                                        v-if="
                                                            form.link_hasil_edit
                                                        "
                                                        :href="
                                                            form.link_hasil_edit
                                                        "
                                                        target="_blank"
                                                        class="p-2 rounded-xl bg-orange-100 hover:bg-orange-200 text-orange-700 transition shrink-0"
                                                        title="Buka Preview Hasil Render"
                                                    >
                                                        <ExternalLink
                                                            class="w-4 h-4"
                                                        />
                                                    </a>
                                                </div>
                                            </div>
                                            <p
                                                class="text-[10px] text-orange-900/80"
                                            >
                                                Editor dapat mengunggah berkas
                                                video/gambar hasil render
                                                langsung ke server balai atau
                                                menempelkan tautan Google Drive
                                                / YouTube preview.
                                            </p>
                                        </div>

                                        <div class="space-y-1.5">
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <label
                                                    class="block text-xs font-bold text-zinc-700"
                                                >
                                                    Draft Teks Caption & Hashtag
                                                    Medsos
                                                </label>
                                                <button
                                                    v-if="form.caption"
                                                    type="button"
                                                    @click="
                                                        copyToClipboard(
                                                            form.caption,
                                                            'caption_modal',
                                                        )
                                                    "
                                                    class="text-[11px] text-orange-600 hover:text-orange-700 font-semibold flex items-center gap-1 cursor-pointer"
                                                >
                                                    <Check
                                                        v-if="
                                                            copiedKey ===
                                                            'caption_modal'
                                                        "
                                                        class="w-3 h-3 text-emerald-600"
                                                    />
                                                    <Copy
                                                        v-else
                                                        class="w-3 h-3"
                                                    />
                                                    {{
                                                        copiedKey ===
                                                        "caption_modal"
                                                            ? "Tersalin!"
                                                            : "Salin Teks"
                                                    }}
                                                </button>
                                            </div>
                                            <textarea
                                                v-model="form.caption"
                                                rows="4"
                                                placeholder="Tulis naskah caption lengkap, tagar #bpvppangkep #kemnaker, dan ajakan bertindak (CTA)..."
                                                class="w-full p-3 text-xs bg-zinc-50 border border-zinc-200 rounded-xl focus:outline-none focus:ring-1 focus:ring-orange-500"
                                                :disabled="!canEditEditor"
                                            ></textarea>
                                        </div>

                                        <!-- Panduan Alur Kerja ke Admin Platform (Hanya jika berhak edit editor) -->
                                        <div
                                            v-if="canEditEditor"
                                            class="p-3.5 rounded-xl bg-orange-50/70 border border-orange-200 flex items-center gap-3"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-orange-100 text-orange-700 flex items-center justify-center shrink-0"
                                            >
                                                <Send class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <div
                                                    class="text-xs font-bold text-orange-950"
                                                >
                                                    Alur Kerja: Serahkan ke
                                                    Admin Platform
                                                </div>
                                                <p
                                                    class="text-[11px] text-orange-900/90 mt-0.5"
                                                >
                                                    Hasil edit dan draft caption
                                                    sudah selesai? Silakan klik
                                                    tombol
                                                    <strong
                                                        >"Kirim ke Admin
                                                        Platform"</strong
                                                    >
                                                    di bilah bawah untuk review
                                                    dan persiapan jadwal tayang.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Riwayat Catatan Revisi & Feedback -->
                                        <div
                                            v-if="editItem"
                                            class="pt-3 border-t border-zinc-200 space-y-3"
                                        >
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <h4
                                                    class="text-xs font-bold text-zinc-900 flex items-center gap-1.5"
                                                >
                                                    <Flame
                                                        class="w-4 h-4 text-rose-600"
                                                    />
                                                    Catatan Revisi & Feedback
                                                    ({{
                                                        editItem.revisions
                                                            ?.length || 0
                                                    }})
                                                </h4>
                                                <span
                                                    class="text-[10px] text-zinc-500"
                                                    >Evaluasi dari Admin
                                                    Platform / Planner</span
                                                >
                                            </div>

                                            <div
                                                class="space-y-2 max-h-40 overflow-y-auto pr-1"
                                            >
                                                <div
                                                    v-for="rev in editItem.revisions ||
                                                    []"
                                                    :key="rev.id"
                                                    class="p-2.5 rounded-xl bg-rose-50/60 border border-rose-100 text-xs space-y-1"
                                                >
                                                    <div
                                                        class="flex items-center justify-between"
                                                    >
                                                        <Badge
                                                            class="bg-rose-600 text-white text-[9px] py-0 px-1.5"
                                                        >
                                                            {{
                                                                rev.target_revisi
                                                            }}
                                                        </Badge>
                                                        <span
                                                            class="text-[10px] text-zinc-400"
                                                        >
                                                            {{
                                                                rev.user
                                                                    ?.name ||
                                                                "Reviewer"
                                                            }}
                                                        </span>
                                                    </div>
                                                    <p
                                                        class="text-zinc-800 text-xs"
                                                    >
                                                        {{ rev.catatan }}
                                                    </p>
                                                </div>
                                                <div
                                                    v-if="
                                                        !editItem.revisions
                                                            ?.length
                                                    "
                                                    class="text-center py-2 text-xs text-zinc-400 italic"
                                                >
                                                    Tidak ada catatan revisi.
                                                    Konten siap direview!
                                                </div>
                                            </div>

                                            <!-- Form Tambah Revisi Langsung (Hanya jika belum tayang & user adalah Reviewer / Super Admin / Planner) -->
                                            <div
                                                v-if="
                                                    !isTayang &&
                                                    (isSuperAdmin ||
                                                        isAdminPlatform ||
                                                        isPlanner) &&
                                                    [
                                                        'Menunggu Review',
                                                        'Proses Editing',
                                                        'Revisi',
                                                    ].includes(form.status)
                                                "
                                                class="bg-zinc-50 p-3 rounded-xl border border-zinc-200 space-y-2"
                                            >
                                                <div
                                                    class="text-[11px] font-bold text-zinc-800"
                                                >
                                                    Beri Catatan Revisi Baru:
                                                </div>
                                                <div class="flex gap-2">
                                                    <select
                                                        v-model="
                                                            revisionForm.target_revisi
                                                        "
                                                        class="h-8 px-2 text-xs bg-white border border-zinc-200 rounded-lg shrink-0"
                                                    >
                                                        <option
                                                            value="Visual / Video"
                                                        >
                                                            Visual / Video
                                                        </option>
                                                        <option
                                                            value="Teks Caption"
                                                        >
                                                            Teks Caption
                                                        </option>
                                                        <option
                                                            value="Audio / Musik"
                                                        >
                                                            Audio / Musik
                                                        </option>
                                                        <option
                                                            value="Kesesuaian Brand"
                                                        >
                                                            Kesesuaian Brand
                                                        </option>
                                                    </select>
                                                    <Input
                                                        v-model="
                                                            revisionForm.catatan
                                                        "
                                                        placeholder="Tulis bagian yang perlu diperbaiki editor..."
                                                        class="h-8 text-xs flex-1"
                                                    />
                                                    <Button
                                                        type="button"
                                                        size="sm"
                                                        @click="submitRevision"
                                                        class="h-8 text-xs bg-rose-600 hover:bg-rose-700 text-white shrink-0"
                                                    >
                                                        Kirim Revisi
                                                    </Button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- ============================================== -->
                                    <!-- BAGIAN 3: ADMIN PLATFORM (REVIEW & PUBLIKASI)  -->
                                    <!-- ============================================== -->
                                    <div
                                        v-show="modalSection === 'admin'"
                                        class="space-y-4 animate-in fade-in"
                                    >
                                        <div
                                            class="bg-emerald-50/60 p-3 rounded-xl border border-emerald-200 text-xs text-emerald-950 flex items-center justify-between"
                                        >
                                            <span
                                                ><strong
                                                    >Fokus Admin
                                                    Platform:</strong
                                                >
                                                Meninjau hasil akhir konten,
                                                mengisi tautan link postingan
                                                live resmi di medsos, lalu
                                                menyelesaikan konten menjadi
                                                Tayang.</span
                                            >
                                            <Badge
                                                class="bg-emerald-600 text-white text-[10px]"
                                                >Tahap 3</Badge
                                            >
                                        </div>

                                        <!-- Peringatan Formulir Admin Platform Terkunci -->
                                        <div
                                            v-if="!canEditAdminPlatform"
                                            class="p-3.5 rounded-xl bg-amber-50/80 border border-amber-200 text-xs text-amber-900 flex items-center gap-2"
                                        >
                                            <AlertCircle
                                                class="w-4 h-4 text-amber-600 shrink-0"
                                            />
                                            <span v-if="isTayang">
                                                <strong
                                                    >Konten Telah
                                                    Tayang:</strong
                                                >
                                                Publikasi telah selesai dan
                                                seluruh data bersifat hanya-baca
                                                (read-only).
                                            </span>
                                            <span
                                                v-else-if="
                                                    form.status !==
                                                    'Menunggu Review'
                                                "
                                            >
                                                <strong
                                                    >Belum Siap
                                                    Publikasi:</strong
                                                >
                                                Konten masih dalam proses
                                                (Status:
                                                <strong>{{
                                                    form.status
                                                }}</strong
                                                >). Menunggu Tim Editor
                                                menyerahkan hasil akhir untuk
                                                direview.
                                            </span>
                                            <span v-else>
                                                <strong
                                                    >Mode Hanya-Baca:</strong
                                                >
                                                Hanya
                                                <strong
                                                    >Medsos Admin
                                                    Platform</strong
                                                >
                                                atau
                                                <strong>Super Admin</strong>
                                                yang dapat mengisi link live dan
                                                menyelesaikan publikasi.
                                            </span>
                                        </div>

                                        <!-- Ringkasan Hasil Kerja Tim Editor untuk Admin -->
                                        <div
                                            class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200 space-y-2.5"
                                        >
                                            <div
                                                class="text-xs font-bold text-zinc-900 flex items-center justify-between"
                                            >
                                                <span
                                                    >Tinjauan Konten dari
                                                    Editor</span
                                                >
                                                <Badge
                                                    :class="
                                                        getStatusBadge(
                                                            form.status,
                                                        ).color
                                                    "
                                                    class="text-[10px]"
                                                >
                                                    {{ form.status }}
                                                </Badge>
                                            </div>

                                            <div
                                                class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs"
                                            >
                                                <div
                                                    class="p-2.5 bg-white rounded-lg border border-zinc-200/80"
                                                >
                                                    <span
                                                        class="text-[10px] text-zinc-400 block mb-1"
                                                        >Hasil Render
                                                        Editor:</span
                                                    >
                                                    <div
                                                        v-if="
                                                            form.link_hasil_edit
                                                        "
                                                        class="flex items-center justify-between"
                                                    >
                                                        <span
                                                            class="font-mono text-zinc-700 truncate text-[11px]"
                                                            >{{
                                                                form.link_hasil_edit
                                                            }}</span
                                                        >
                                                        <a
                                                            :href="
                                                                form.link_hasil_edit
                                                            "
                                                            target="_blank"
                                                            class="ml-2 inline-flex items-center gap-1 text-[11px] font-bold text-orange-600 hover:underline shrink-0"
                                                        >
                                                            <ExternalLink
                                                                class="w-3 h-3"
                                                            />
                                                            Buka
                                                        </a>
                                                    </div>
                                                    <span
                                                        v-else
                                                        class="text-zinc-400 italic text-[11px]"
                                                        >Belum ada hasil render
                                                        dari editor.</span
                                                    >
                                                </div>

                                                <div
                                                    class="p-2.5 bg-white rounded-lg border border-zinc-200/80"
                                                >
                                                    <span
                                                        class="text-[10px] text-zinc-400 block mb-1"
                                                        >Naskah Caption:</span
                                                    >
                                                    <p
                                                        v-if="form.caption"
                                                        class="text-zinc-700 text-[11px] line-clamp-2"
                                                    >
                                                        {{ form.caption }}
                                                    </p>
                                                    <span
                                                        v-else
                                                        class="text-zinc-400 italic text-[11px]"
                                                        >Belum ada caption dari
                                                        editor.</span
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                        <!-- INPUT LINK KONTEN UTAMA (INSTRUKSI PENGGUNA) -->
                                        <div
                                            class="space-y-1.5 p-4 rounded-xl bg-emerald-50/50 border border-emerald-200"
                                        >
                                            <label
                                                class="block text-xs font-bold text-emerald-950 flex items-center justify-between"
                                            >
                                                <span
                                                    class="flex items-center gap-1.5"
                                                >
                                                    <Globe
                                                        class="w-4 h-4 text-emerald-600"
                                                    />
                                                    Link Postingan Live Resmi
                                                    (Instagram / TikTok /
                                                    YouTube / FB / X)
                                                </span>
                                                <Badge
                                                    class="bg-emerald-600 text-white text-[9px]"
                                                    >Wajib untuk Selesai</Badge
                                                >
                                            </label>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Input
                                                    v-model="
                                                        form.link_postingan
                                                    "
                                                    type="text"
                                                    placeholder="Tempel link postingan yang sudah tayang, contoh: https://www.instagram.com/p/..."
                                                    class="h-9 text-xs flex-1 font-mono bg-white border-zinc-200 focus:ring-emerald-500"
                                                    :disabled="
                                                        !canEditAdminPlatform
                                                    "
                                                />
                                                <a
                                                    v-if="form.link_postingan"
                                                    :href="form.link_postingan"
                                                    target="_blank"
                                                    class="p-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white transition shadow-2xs"
                                                    title="Buka Postingan Live"
                                                >
                                                    <ExternalLink
                                                        class="w-4 h-4"
                                                    />
                                                </a>
                                            </div>
                                            <p
                                                class="text-[10px] text-emerald-900/80"
                                            >
                                                Admin Platform menempelkan
                                                tautan link konten yang sudah
                                                berhasil dipublikasikan di kanal
                                                media sosial resmi.
                                            </p>
                                        </div>

                                        <!-- Panduan Alur Kerja: Konfirmasi Publikasi Selesai (Hanya jika berhak) -->
                                        <div
                                            v-if="
                                                canEditAdminPlatform &&
                                                form.status !== 'Tayang'
                                            "
                                            class="p-3.5 rounded-xl bg-emerald-50/70 border border-emerald-200 flex items-center gap-3"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0"
                                            >
                                                <CheckCircle2 class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <div
                                                    class="text-xs font-bold text-emerald-950"
                                                >
                                                    Konfirmasi Publikasi Selesai
                                                </div>
                                                <p
                                                    class="text-[11px] text-emerald-900/90 mt-0.5"
                                                >
                                                    Pastikan tautan link live
                                                    postingan sudah diisi di
                                                    atas, lalu klik tombol
                                                    <strong
                                                        >"Selesaikan
                                                        Konten"</strong
                                                    >
                                                    di bilah bawah untuk
                                                    mengubah status menjadi
                                                    Tayang.
                                                </p>
                                            </div>
                                        </div>
                                        <div
                                            v-else-if="isTayang"
                                            class="p-3.5 rounded-xl bg-emerald-100/70 border border-emerald-300 flex items-center gap-3"
                                        >
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-600 text-white flex items-center justify-center shrink-0"
                                            >
                                                <CheckCircle2 class="w-4 h-4" />
                                            </div>
                                            <div>
                                                <div
                                                    class="text-xs font-bold text-emerald-950"
                                                >
                                                    Publikasi Konten Selesai
                                                </div>
                                                <p
                                                    class="text-[11px] text-emerald-800 mt-0.5"
                                                >
                                                    Konten ini telah berstatus
                                                    <strong>Tayang</strong>.
                                                    Tautan live postingan telah
                                                    berhasil dipublikasikan.
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Opsi Ubah Status Manual (Khusus Super Admin Bebas) -->
                                        <div
                                            v-if="isSuperAdmin"
                                            class="pt-2 border-t border-zinc-200/80 flex items-center justify-between"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="text-xs text-zinc-500 font-medium"
                                                    >Status Konten Manual (Super
                                                    Admin):</span
                                                >
                                                <select
                                                    v-model="form.status"
                                                    class="h-8 px-2.5 text-xs bg-zinc-50 border border-zinc-200 rounded-lg font-bold"
                                                    :class="
                                                        getStatusBadge(
                                                            form.status,
                                                        ).color
                                                    "
                                                >
                                                    <option
                                                        v-for="st in statusOptions"
                                                        :key="st"
                                                        :value="st"
                                                    >
                                                        {{ st }}
                                                    </option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Persistent Sticky Actions Footer (Selalu Terlihat Walaupun Form Di-scroll) -->
                                <div
                                    class="shrink-0 bg-white/95 backdrop-blur-xs border-t border-zinc-200/90 p-3 sm:p-4 px-4 sm:px-6 shadow-xs flex flex-wrap items-center justify-between gap-3 z-10"
                                >
                                    <!-- Sisi Kiri: Hapus Konten (Hanya Super Admin) -->
                                    <div class="flex items-center gap-2">
                                        <Button
                                            v-if="editItem && isSuperAdmin"
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteContent(editItem)"
                                            class="text-xs text-rose-600 hover:text-rose-700 hover:bg-rose-50 cursor-pointer h-9 px-3 font-semibold gap-1.5 transition"
                                        >
                                            <Trash2
                                                class="w-3.5 h-3.5 text-rose-500"
                                            />
                                            <span>Hapus Konten</span>
                                        </Button>
                                    </div>

                                    <!-- Sisi Kanan: Tutup, Simpan Perubahan, dan Kirim Alur Kerja Berikutnya -->
                                    <div
                                        class="flex items-center gap-2 flex-wrap justify-end"
                                    >
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            @click="isDialogOpen = false"
                                            class="text-xs h-9 px-3.5 cursor-pointer text-zinc-600 hover:bg-zinc-100 border-zinc-300 font-medium"
                                        >
                                            Tutup
                                        </Button>

                                        <Button
                                            v-if="shouldShowSaveButton"
                                            type="submit"
                                            size="sm"
                                            :disabled="form.processing"
                                            class="text-xs h-9 px-4 font-bold cursor-pointer shadow-xs gap-1.5 transition"
                                            :class="[
                                                editItem
                                                    ? modalSection === 'editor'
                                                        ? 'text-orange-700 bg-white hover:bg-orange-50 border border-orange-300'
                                                        : modalSection ===
                                                            'admin'
                                                          ? 'text-emerald-700 bg-white hover:bg-emerald-50 border border-emerald-300'
                                                          : 'text-blue-700 bg-white hover:bg-blue-50 border border-blue-300'
                                                    : modalSection === 'editor'
                                                      ? 'text-white bg-orange-500 hover:bg-orange-600 shadow-xs'
                                                      : modalSection === 'admin'
                                                        ? 'text-white bg-emerald-600 hover:bg-emerald-700 shadow-xs'
                                                        : 'text-white bg-blue-600 hover:bg-blue-700 shadow-xs',
                                            ]"
                                        >
                                            <Check class="w-3.5 h-3.5" />
                                            <span>{{
                                                editItem
                                                    ? "Simpan Perubahan"
                                                    : "Buat Konten"
                                            }}</span>
                                        </Button>

                                        <!-- Tombol Alur Kerja Berikutnya (Sesuai Tahap / modalSection & Role Permissions) -->
                                        <template v-if="editItem || createMode">
                                            <!-- 1. Tahap Planner: Kirim ke Editor (Tema Biru) -->
                                            <Button
                                                v-if="
                                                    modalSection ===
                                                        'planner' &&
                                                    canEditPlanner
                                                "
                                                type="button"
                                                @click="kirimKeEditor"
                                                :disabled="form.processing"
                                                class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs h-9 px-4 shrink-0 shadow-xs gap-1.5 cursor-pointer transition ring-2 ring-blue-400/20"
                                                title="Serahkan bahan dan brief ke tim editor"
                                            >
                                                <span>Kirim ke Editor</span>
                                                <ArrowRight class="w-4 h-4" />
                                            </Button>

                                            <!-- 2. Tahap Editor: Kirim ke Admin Platform (Tema Oranye) -->
                                            <Button
                                                v-else-if="
                                                    modalSection === 'editor' &&
                                                    canEditEditor
                                                "
                                                type="button"
                                                @click="kirimKeAdminPlatform"
                                                :disabled="form.processing"
                                                class="bg-orange-500 hover:bg-orange-600 text-white font-bold text-xs h-9 px-4 shrink-0 shadow-xs gap-1.5 cursor-pointer transition ring-2 ring-orange-400/20"
                                                title="Serahkan hasil edit ke Admin Platform"
                                            >
                                                <span
                                                    >Kirim ke Admin
                                                    Platform</span
                                                >
                                                <Send class="w-4 h-4" />
                                            </Button>

                                            <!-- 3. Tahap Admin Platform: Selesaikan Konten (Tema Hijau) -->
                                            <Button
                                                v-else-if="
                                                    modalSection === 'admin' &&
                                                    canEditAdminPlatform
                                                "
                                                type="button"
                                                @click="selesaikanKonten"
                                                :disabled="form.processing"
                                                class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs h-9 px-4 shrink-0 shadow-xs gap-1.5 cursor-pointer transition ring-2 ring-emerald-400/20"
                                                title="Publikasi selesai dan ubah status jadi Tayang"
                                            >
                                                <CheckCircle2 class="w-4 h-4" />
                                                <span>Selesaikan Konten</span>
                                            </Button>
                                        </template>
                                    </div>
                                </div>
                            </form>
                        </div>

                        <!-- KOLOM KANAN: PANEL DISKUSI TIM (DALAM BORDER FORM, TETAP MUNCUL SAAT BEKERJA & SCROLL) -->
                        <div
                            v-if="editItem"
                            class="w-full lg:w-[350px] xl:w-[380px] shrink-0 border-t lg:border-t-0 lg:border-l border-zinc-200 bg-zinc-50/50 flex flex-col min-h-0 h-full"
                        >
                            <!-- Header Panel Diskusi -->
                            <div
                                class="p-3.5 px-4 bg-white border-b border-zinc-200 flex items-center justify-between shrink-0 shadow-2xs"
                            >
                                <div class="flex items-center gap-2">
                                    <div
                                        class="w-7 h-7 rounded-lg bg-purple-100 text-purple-700 flex items-center justify-center shrink-0"
                                    >
                                        <MessageSquare class="w-4 h-4" />
                                    </div>
                                    <div>
                                        <h4
                                            class="text-xs font-bold text-zinc-900 flex items-center gap-1.5"
                                        >
                                            Diskusi Tim Konten
                                            <span
                                                class="px-1.5 py-0.2 rounded-full bg-purple-100 text-purple-700 text-[10px] font-extrabold"
                                            >
                                                {{
                                                    editItem.comments?.length ||
                                                    0
                                                }}
                                            </span>
                                        </h4>
                                        <p class="text-[10px] text-zinc-500">
                                            Koordinasi teknis & arahan tim
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <span
                                        class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"
                                    ></span>
                                    <span
                                        class="text-[10px] font-semibold text-emerald-700"
                                        >Aktif</span
                                    >
                                </div>
                            </div>

                            <!-- Chat Stream Komentar (Scrollable sendiri di kolom kanan) -->
                            <div
                                class="flex-1 overflow-y-auto p-3.5 space-y-3 bg-zinc-50/40"
                            >
                                <div
                                    v-for="c in editItem.comments || []"
                                    :key="c.id"
                                    class="p-3 rounded-xl border space-y-1.5 text-xs shadow-2xs transition"
                                    :class="
                                        c.user_id === $page.props.auth?.user?.id
                                            ? 'bg-purple-50/90 border-purple-200 ml-2'
                                            : 'bg-white border-zinc-200/90 mr-2'
                                    "
                                >
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <Avatar
                                                class="w-5 h-5 text-[9px] bg-purple-100 text-purple-700"
                                            >
                                                <AvatarFallback>{{
                                                    c.user?.name?.charAt(0) ||
                                                    "U"
                                                }}</AvatarFallback>
                                            </Avatar>
                                            <span
                                                class="font-bold text-zinc-900 text-[11px]"
                                                >{{ c.user?.name }}</span
                                            >
                                            <span
                                                v-if="
                                                    c.user_id ===
                                                    $page.props.auth?.user?.id
                                                "
                                                class="text-[9px] px-1 rounded bg-purple-200/70 text-purple-800 font-semibold"
                                            >
                                                Anda
                                            </span>
                                        </div>
                                        <span class="text-[10px] text-zinc-400">
                                            {{
                                                new Date(
                                                    c.created_at,
                                                ).toLocaleDateString("id-ID", {
                                                    day: "numeric",
                                                    month: "short",
                                                    hour: "2-digit",
                                                    minute: "2-digit",
                                                })
                                            }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-zinc-800 text-xs leading-relaxed whitespace-pre-line pl-6"
                                    >
                                        {{ c.body }}
                                    </p>
                                </div>

                                <!-- Empty State jika belum ada diskusi -->
                                <div
                                    v-if="!editItem.comments?.length"
                                    class="text-center py-10 px-4 space-y-2.5 text-zinc-400"
                                >
                                    <div
                                        class="w-10 h-10 rounded-2xl bg-purple-100 text-purple-600 mx-auto flex items-center justify-center shadow-xs"
                                    >
                                        <MessageSquare class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <div
                                            class="text-xs font-bold text-zinc-700"
                                        >
                                            Belum Ada Catatan Diskusi
                                        </div>
                                        <p
                                            class="text-[11px] text-zinc-500 leading-normal mt-1 max-w-[220px] mx-auto"
                                        >
                                            Gunakan ruang ini untuk koordinasi
                                            teknis, pertanyaan, atau arahan
                                            antara Planner, Editor, dan Admin
                                            Platform.
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Input Komentar di Bawah Panel Kanan (Pinned, tidak tergeser saat form di-scroll) -->
                            <div
                                class="p-3 bg-white border-t border-zinc-200 shrink-0"
                            >
                                <form
                                    @submit.prevent="submitComment"
                                    class="flex gap-2"
                                >
                                    <Input
                                        v-model="commentForm.body"
                                        placeholder="Tulis pesan diskusi... (Enter)"
                                        class="h-9 text-xs flex-1 bg-zinc-50 border-zinc-200 focus:bg-white"
                                        :disabled="commentForm.processing"
                                        @keyup.enter.prevent="submitComment"
                                    />
                                    <Button
                                        type="submit"
                                        size="sm"
                                        :disabled="
                                            !commentForm.body?.trim() ||
                                            commentForm.processing
                                        "
                                        class="h-9 px-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold shrink-0 cursor-pointer shadow-xs gap-1"
                                    >
                                        <Send class="w-3.5 h-3.5" />
                                        <span class="hidden sm:inline"
                                            >Kirim</span
                                        >
                                    </Button>
                                </form>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </div>
    </DashboardLayout>
</template>
