<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";
import { Head, Link, router, useForm } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import {
    ArrowLeft,
    BookOpen,
    Users,
    Video,
    Award,
    Plus,
    FileSpreadsheet,
    Download,
    Upload,
    Trash2,
    Edit,
    Pencil,
    ChevronLeft,
    MoveUp,
    MoveDown,
    FileText,
    Image as ImageIcon,
    CheckCircle2,
    Clock,
    AlertCircle,
    Eye,
    Save,
    ExternalLink,
    RefreshCw,
    Search,
    ChevronDown,
    ChevronRight,
    Sparkles,
    Calendar,
    Timer,
    StopCircle,
    Lock,
    Unlock,
    Play,
    Check,
    X,
    AlertTriangle,
    HelpCircle,
    Undo2,
    Redo2,
    RotateCcw,
    QrCode,
    Type,
    Loader2,
    Layers,
} from "lucide-vue-next";

const props = defineProps({
    course: Object,
    metrics: Object,
});

// Active Tab: 1: materi (Unit & Jadwal), 2: peserta (& Presensi), 3: sertifikat (Sertifikat A4)
const validTabs = ["materi", "peserta", "sertifikat"];

const getInitialTab = () => {
    if (typeof window === "undefined") return "materi";
    try {
        const params = new URLSearchParams(window.location.search);
        let tabParam = params.get("tab");
        if (tabParam === "zoom") tabParam = "materi";
        if (tabParam && validTabs.includes(tabParam)) {
            return tabParam;
        }
        let hash = window.location.hash.replace("#", "");
        if (hash === "zoom") hash = "materi";
        if (hash && validTabs.includes(hash)) {
            return hash;
        }
        let stored = localStorage.getItem(
            `lms_admin_active_tab_${props.course.id}`,
        );
        if (stored === "zoom") stored = "materi";
        if (stored && validTabs.includes(stored)) {
            return stored;
        }
    } catch (e) {
        // ignore
    }
    return "materi";
};

const activeTab = ref(getInitialTab());

watch(
    activeTab,
    (newTab) => {
        if (typeof window === "undefined") return;
        try {
            localStorage.setItem(
                `lms_admin_active_tab_${props.course.id}`,
                newTab,
            );
            const url = new URL(window.location.href);
            if (url.searchParams.get("tab") !== newTab) {
                url.searchParams.set("tab", newTab);
                window.history.replaceState({}, "", url.toString());
            }
        } catch (e) {
            // ignore
        }
    },
    { immediate: true },
);

onMounted(() => {
    window.addEventListener("popstate", () => {
        const params = new URLSearchParams(window.location.search);
        let tab = params.get("tab");
        if (tab === "zoom") tab = "materi";
        if (tab && validTabs.includes(tab) && tab !== activeTab.value) {
            activeTab.value = tab;
        }
    });
});

// -------------------------------------------------------------
// TAB 1: UNIT KOMPETENSI & PENGATURAN JADWAL
// -------------------------------------------------------------
const showModuleModal = ref(false);
const isEditingModule = ref(false);
const currentModuleId = ref(null);

const moduleForm = useForm({
    title: "",
    description: "",
    delivery_mode: "sinkronus",
    duration_days: 1,
    day_number: 1,
    scheduled_date: "",
    start_time: "08:00",
    end_time: "10:00",
    zoom_link: "",
    zoom_meeting_id: "",
    zoom_passcode: "",
    notes: "",
});

const addDaysToYmd = (ymdStr, daysToAdd = 0) => {
    if (!ymdStr) return "";
    try {
        const clean = String(ymdStr).substring(0, 10);
        const [y, m, d] = clean.split("-").map(Number);
        const dt = new Date(y, m - 1, d + (parseInt(daysToAdd, 10) || 0));
        const pad = (n) => String(n).padStart(2, "0");
        return `${dt.getFullYear()}-${pad(dt.getMonth() + 1)}-${pad(dt.getDate())}`;
    } catch (e) {
        return String(ymdStr).substring(0, 10);
    }
};

const formatToInputDate = (dateStr) => {
    if (!dateStr) return "";
    try {
        const str = String(dateStr).trim();
        if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/.test(str)) {
            return str;
        }
        const d = new Date(str);
        if (isNaN(d.getTime())) return "";

        const parts = new Intl.DateTimeFormat("en-CA", {
            timeZone: "Asia/Makassar",
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
            hour: "2-digit",
            minute: "2-digit",
            hourCycle: "h23",
        }).formatToParts(d);

        const getPart = (type) => parts.find((p) => p.type === type)?.value || "00";
        return `${getPart("year")}-${getPart("month")}-${getPart("day")}T${getPart("hour")}:${getPart("minute")}`;
    } catch (e) {
        return "";
    }
};

const formatDateIndo = (dateStr) => {
    if (!dateStr) return "Belum Ditentukan";
    try {
        const str = String(dateStr).trim();
        const dateMatch = str.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (dateMatch) {
            const [, y, m, d] = dateMatch.map(Number);
            return new Date(y, m - 1, d).toLocaleDateString("id-ID", {
                day: "numeric",
                month: "short",
                year: "numeric",
            });
        }
        const d = new Date(str);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat("id-ID", {
            timeZone: "Asia/Makassar",
            day: "numeric",
            month: "short",
            year: "numeric",
        }).format(d);
    } catch (e) {
        return dateStr;
    }
};

const formatDateIndoWithDay = (dateStr) => {
    if (!dateStr) return "";
    try {
        const str = String(dateStr).trim();
        const dateMatch = str.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (dateMatch) {
            const [, y, m, d] = dateMatch.map(Number);
            return new Date(y, m - 1, d).toLocaleDateString("id-ID", {
                weekday: "long",
                day: "numeric",
                month: "short",
                year: "numeric",
            });
        }
        const d = new Date(str);
        if (isNaN(d.getTime())) return dateStr;
        return new Intl.DateTimeFormat("id-ID", {
            timeZone: "Asia/Makassar",
            weekday: "long",
            day: "numeric",
            month: "short",
            year: "numeric",
        }).format(d);
    } catch (e) {
        return dateStr;
    }
};

const isSingleDayCourse = computed(() => {
    if (props.course.duration_in_days && props.course.duration_in_days === 1)
        return true;
    if (
        props.course.start_date &&
        props.course.end_date &&
        String(props.course.start_date).substring(0, 10) ===
            String(props.course.end_date).substring(0, 10)
    ) {
        return true;
    }
    return false;
});

// Asia/Makassar (WITA, UTC+8) Current Date
const todayWita = computed(() => {
    try {
        return new Intl.DateTimeFormat("en-CA", {
            timeZone: "Asia/Makassar",
            year: "numeric",
            month: "2-digit",
            day: "2-digit",
        }).format(new Date());
    } catch (e) {
        const d = new Date();
        const pad = (n) => String(n).padStart(2, "0");
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
    }
});

// Helper to get scheduled YYYY-MM-DD for a specific day number
const getDateForDayNumber = (dayNum) => {
    const d = parseInt(dayNum, 10) || 1;
    if (props.course?.modules) {
        const found = props.course.modules.find(
            (m) => (m.day_number || 1) === d && m.scheduled_date,
        );
        if (found && found.scheduled_date) {
            return String(found.scheduled_date).substring(0, 10);
        }
    }
    if (props.course?.start_date) {
        return addDaysToYmd(props.course.start_date, d - 1);
    }
    return "";
};

// Calculate which day number today corresponds to relative to course.start_date
// Returns null if today is before course start date or after course end date
const todayDayNumber = computed(() => {
    if (!props.course?.start_date) return null;
    try {
        const startStr = String(props.course.start_date).substring(0, 10);
        const todayStr = String(todayWita.value).substring(0, 10);

        // 1. Check if today matches any module's scheduled_date directly
        if (props.course.modules) {
            const todayMod = props.course.modules.find(
                (m) =>
                    m.scheduled_date &&
                    String(m.scheduled_date).substring(0, 10) === todayStr,
            );
            if (todayMod && todayMod.day_number) {
                return todayMod.day_number;
            }
        }

        // 2. Diff days from start_date
        const [sy, sm, sd] = startStr.split("-").map(Number);
        const [ty, tm, td] = todayStr.split("-").map(Number);
        const startDate = new Date(sy, sm - 1, sd);
        const todayDate = new Date(ty, tm - 1, td);
        const diffTime = todayDate.getTime() - startDate.getTime();
        const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24)) + 1;

        if (diffDays < 1) {
            return null;
        }

        const maxDays =
            props.course.duration_in_days || distinctDays.value.length || 1;
        if (diffDays > maxDays) {
            return null;
        }

        return diffDays;
    } catch (e) {
        return null;
    }
});

// Relative course schedule status when today is not an active training day
const courseStatusRelative = computed(() => {
    if (!props.course?.start_date) return "";
    try {
        const startStr = String(props.course.start_date).substring(0, 10);
        const todayStr = String(todayWita.value).substring(0, 10);
        if (todayStr === startStr) return "Hari Pertama (Hari ke-1)";

        const [sy, sm, sd] = startStr.split("-").map(Number);
        const [ty, tm, td] = todayStr.split("-").map(Number);
        const startDate = new Date(sy, sm - 1, sd);
        const todayDate = new Date(ty, tm - 1, td);
        const diffDays = Math.round(
            (startDate.getTime() - todayDate.getTime()) / (1000 * 60 * 60 * 24),
        );

        if (diffDays === 1) {
            return `Mulai Besok (${formatDateIndo(startStr)})`;
        }
        if (diffDays > 1) {
            return `${diffDays} hari lagi (${formatDateIndo(startStr)})`;
        }

        const endStr = props.course.end_date
            ? String(props.course.end_date).substring(0, 10)
            : startStr;
        if (todayStr > endStr) {
            return "Pelatihan Selesai";
        }
        return "";
    } catch (e) {
        return "";
    }
});

const calculateDateForDay = (dayNum) => {
    if (!dayNum) return "";
    try {
        const d = parseInt(dayNum, 10) || 1;
        const targetDateYmd = getDateForDayNumber(d);
        if (!targetDateYmd) return "";

        const formatted = formatDateIndoWithDay(targetDateYmd);

        const [ty, tm, td] = todayWita.value.split("-").map(Number);
        const [sy, sm, sd] = targetDateYmd.split("-").map(Number);
        const targetDate = new Date(sy, sm - 1, sd);
        const todayDate = new Date(ty, tm - 1, td);
        const diffDays = Math.round(
            (targetDate.getTime() - todayDate.getTime()) /
                (1000 * 60 * 60 * 24),
        );
        let relative = "";
        if (diffDays === 0) relative = " (Hari Ini)";
        else if (diffDays === 1) relative = " (Besok)";
        else if (diffDays === -1) relative = " (Kemarin)";
        else if (diffDays > 1) relative = ` (${diffDays} hari lagi)`;
        else relative = ` (${Math.abs(diffDays)} hari lalu)`;

        return `${formatted}${relative}`;
    } catch (e) {
        return "";
    }
};

watch(
    () => moduleForm.day_number,
    (newDay) => {
        if (newDay && !isEditingModule.value) {
            moduleForm.scheduled_date =
                getDateForDayNumber(newDay) || moduleForm.scheduled_date;
        }
    },
);

const openAddModuleModal = () => {
    isEditingModule.value = false;
    currentModuleId.value = null;
    moduleForm.reset();
    moduleForm.delivery_mode = "sinkronus";
    moduleForm.duration_days = 1;

    let defaultDay = 1;
    if (isSingleDayCourse.value) {
        defaultDay = 1;
    } else if (
        selectedScheduleFilter.value &&
        selectedScheduleFilter.value.startsWith("day_")
    ) {
        defaultDay =
            parseInt(selectedScheduleFilter.value.replace("day_", ""), 10) || 1;
    } else if (selectedScheduleFilter.value === "today" && todayDayNumber.value) {
        defaultDay = todayDayNumber.value;
    } else {
        const maxDay =
            props.course.modules?.reduce(
                (max, m) => Math.max(max, m.day_number || 1),
                0,
            ) || 0;
        defaultDay = maxDay > 0 ? maxDay : 1;
    }

    moduleForm.day_number = defaultDay;
    moduleForm.scheduled_date =
        getDateForDayNumber(defaultDay) || todayWita.value;

    moduleForm.start_time = "08:00";
    moduleForm.end_time = "10:00";
    moduleForm.zoom_link = props.course.zoom_link || "";
    moduleForm.zoom_meeting_id = props.course.zoom_meeting_id || "";
    moduleForm.zoom_passcode = props.course.zoom_passcode || "";
    moduleForm.notes = "";
    showModuleModal.value = true;
};

const openEditModuleModal = (module) => {
    isEditingModule.value = true;
    currentModuleId.value = module.id;
    moduleForm.title = module.title;
    moduleForm.description = module.description || "";
    moduleForm.delivery_mode = module.delivery_mode || "sinkronus";
    moduleForm.duration_days = module.duration_days || 1;
    moduleForm.day_number = module.day_number || 1;

    let sched = module.scheduled_date
        ? String(module.scheduled_date).substring(0, 10)
        : "";
    if (!sched && module.zoom_start_at) {
        sched = String(module.zoom_start_at).substring(0, 10);
    }
    if (!sched) {
        sched = getDateForDayNumber(module.day_number || 1);
    }
    moduleForm.scheduled_date = sched || todayWita.value;

    let st = module.start_time || "";
    if (!st && module.zoom_start_at) {
        st = String(module.zoom_start_at).substring(11, 16);
    }
    moduleForm.start_time = st || "08:00";

    let et = module.end_time || "";
    if (!et && module.zoom_end_at) {
        et = String(module.zoom_end_at).substring(11, 16);
    }
    moduleForm.end_time = et || "10:00";

    const unitForm = unitZoomForms.value[module.id];
    moduleForm.zoom_link =
        unitForm?.zoom_link !== undefined && unitForm.zoom_link !== null
            ? unitForm.zoom_link
            : module.zoom_link || props.course.zoom_link || "";
    moduleForm.zoom_meeting_id =
        unitForm?.zoom_meeting_id !== undefined &&
        unitForm.zoom_meeting_id !== null
            ? unitForm.zoom_meeting_id
            : module.zoom_meeting_id || props.course.zoom_meeting_id || "";
    moduleForm.zoom_passcode =
        unitForm?.zoom_passcode !== undefined && unitForm.zoom_passcode !== null
            ? unitForm.zoom_passcode
            : module.zoom_passcode || props.course.zoom_passcode || "";

    moduleForm.notes = module.notes || "";
    showModuleModal.value = true;
};

// Dropdown Schedule Filter for Tab 1 (Default: "all" so all units are immediately visible upon refresh)
const selectedScheduleFilter = ref("all");

// Sorted Unique Day Numbers present in course modules
const distinctDays = computed(() => {
    if (!props.course.modules || props.course.modules.length === 0) return [1];
    const days = props.course.modules.map((m) => m.day_number || 1);
    return Array.from(new Set(days)).sort((a, b) => a - b);
});

// Count how many units on a given day
const countUnitsForDay = (day) => {
    if (!props.course.modules) return 0;
    return props.course.modules.filter((m) => (m.day_number || 1) === day)
        .length;
};

// Filtered modules displayed in Tab 1
const filteredModules = computed(() => {
    if (!props.course.modules) return [];
    if (selectedScheduleFilter.value === "all") {
        return props.course.modules;
    }
    if (selectedScheduleFilter.value === "today") {
        const matched = props.course.modules.filter(
            (m) =>
                (m.day_number && m.day_number === todayDayNumber.value) ||
                (m.scheduled_date &&
                    String(m.scheduled_date).substring(0, 10) ===
                        todayWita.value),
        );
        return matched;
    }
    if (selectedScheduleFilter.value.startsWith("day_")) {
        const day = parseInt(
            selectedScheduleFilter.value.replace("day_", ""),
            10,
        );
        return props.course.modules.filter((m) => (m.day_number || 1) === day);
    }
    return props.course.modules;
});

// Grouped modules by day so Online Meeting & Attendance can be managed once per day
const groupedModulesByDay = computed(() => {
    const list = filteredModules.value;
    if (!list || list.length === 0) return [];

    const map = new Map();
    list.forEach((m) => {
        const day = m.day_number || 1;
        if (!map.has(day)) {
            map.set(day, {
                day_number: day,
                scheduled_date: m.scheduled_date || getDateForDayNumber(day),
                modules: [],
                hasSinkronus: false,
                primaryModule: m,
            });
        }
        const group = map.get(day);
        group.modules.push(m);
        if (m.delivery_mode === "sinkronus") {
            group.hasSinkronus = true;
            if (group.primaryModule.delivery_mode !== "sinkronus") {
                group.primaryModule = m;
            }
        }
        if (!group.scheduled_date && m.scheduled_date) {
            group.scheduled_date = m.scheduled_date;
        }
    });

    return Array.from(map.values()).sort((a, b) => a.day_number - b.day_number);
});

const getOverallModuleIndex = (mod) => {
    if (!props.course?.modules || !mod) return 1;
    const idx = props.course.modules.findIndex((m) => m.id === mod.id);
    return idx !== -1 ? idx + 1 : 1;
};

// Per-Unit Online Meeting & Attendance State & Timers
const unitZoomForms = ref({});
const unitAttendanceTimers = ref({});
const unitZoomTimers = ref({});
const isStartingUnitZoom = ref({});
const isEndingUnitZoom = ref({});
const isOpeningUnitAttendance = ref({});
const unitCustomDuration = ref({});
const unitSelectedDuration = ref({});
const unitScheduledAttendanceForm = ref({});
const isEditingZoomEnded = ref({});
const isEditingZoomLive = ref({});
const isOpeningAttendanceSusulan = ref({});

const getModuleZoomStartEnd = (mod) => {
    if (!mod) return { startMs: null, endMs: null };
    let startMs = null;
    let endMs = null;

    const normalizeTime = (t) => {
        if (!t) return "00:00";
        const clean = String(t).trim();
        if (clean.length === 5) return clean;
        if (clean.length >= 8) return clean.substring(0, 5);
        return clean.padStart(5, "0");
    };

    if (mod.zoom_start_at) {
        startMs = new Date(mod.zoom_start_at).getTime();
    } else if (mod.scheduled_date && mod.start_time) {
        startMs = new Date(
            `${String(mod.scheduled_date).substring(0, 10)}T${normalizeTime(mod.start_time)}:00`,
        ).getTime();
    }

    if (mod.zoom_end_at) {
        endMs = new Date(mod.zoom_end_at).getTime();
    } else if (mod.scheduled_date && mod.end_time) {
        endMs = new Date(
            `${String(mod.scheduled_date).substring(0, 10)}T${normalizeTime(mod.end_time)}:00`,
        ).getTime();
    }

    return { startMs, endMs };
};

const isUnitZoomLive = (mod) => {
    if (!mod) return false;
    if (mod.zoom_status === "ended") return false;
    if (mod.zoom_status === "live") return true;
    if ((unitZoomTimers.value[mod.id] || 0) > 0) return true;

    const { startMs, endMs } = getModuleZoomStartEnd(mod);
    const now = Date.now();
    if (startMs && endMs && now >= startMs && now <= endMs) {
        return true;
    }
    return false;
};

const initUnitState = (mod) => {
    if (!mod || !mod.id) return;

    const dayNum = mod.day_number || 1;
    const initialDate = mod.scheduled_date
        ? String(mod.scheduled_date).substring(0, 10)
        : mod.zoom_start_at
          ? String(mod.zoom_start_at).substring(0, 10)
          : (getDateForDayNumber(dayNum) || todayWita.value);

    const initialStart = mod.start_time
        ? String(mod.start_time).substring(0, 5)
        : mod.zoom_start_at
          ? String(mod.zoom_start_at).substring(11, 16)
          : "08:00";

    const initialEnd = mod.end_time
        ? String(mod.end_time).substring(0, 5)
        : mod.zoom_end_at
          ? String(mod.zoom_end_at).substring(11, 16)
          : "10:00";

    if (!unitZoomForms.value[mod.id]) {
        unitZoomForms.value[mod.id] = {
            scheduled_date: initialDate,
            start_time: initialStart,
            end_time: initialEnd,
            zoom_link: mod.zoom_link || props.course.zoom_link || "",
            zoom_meeting_id:
                mod.zoom_meeting_id || props.course.zoom_meeting_id || "",
            zoom_passcode:
                mod.zoom_passcode || props.course.zoom_passcode || "",
            isSaving: false,
        };
    } else {
        if (
            !unitZoomForms.value[mod.id].scheduled_date ||
            (mod.scheduled_date &&
                unitZoomForms.value[mod.id].scheduled_date !==
                    String(mod.scheduled_date).substring(0, 10))
        ) {
            unitZoomForms.value[mod.id].scheduled_date = initialDate;
        }
        if (!unitZoomForms.value[mod.id].start_time) {
            unitZoomForms.value[mod.id].start_time = initialStart;
        }
        if (!unitZoomForms.value[mod.id].end_time) {
            unitZoomForms.value[mod.id].end_time = initialEnd;
        }
    }

    if (unitAttendanceTimers.value[mod.id] === undefined) {
        unitAttendanceTimers.value[mod.id] =
            mod.attendance_remaining_seconds || 0;
    }
    if (unitZoomTimers.value[mod.id] === undefined) {
        unitZoomTimers.value[mod.id] = mod.zoom_remaining_seconds || 0;
    }
    if (unitSelectedDuration.value[mod.id] === undefined) {
        unitSelectedDuration.value[mod.id] =
            mod.zoom_attendance_duration_minutes || 30;
    }
    if (unitCustomDuration.value[mod.id] === undefined) {
        unitCustomDuration.value[mod.id] = 30;
    }
    if (!unitScheduledAttendanceForm.value[mod.id]) {
        unitScheduledAttendanceForm.value[mod.id] = {
            scheduled_at:
                formatToInputDate(mod.zoom_attendance_scheduled_at) ||
                (initialDate && initialStart
                    ? `${initialDate}T${initialStart}`
                    : formatToInputDate(mod.zoom_start_at)),
            duration_minutes: mod.zoom_attendance_duration_minutes || 30,
        };
    } else if (!unitScheduledAttendanceForm.value[mod.id].scheduled_at) {
        unitScheduledAttendanceForm.value[mod.id].scheduled_at =
            formatToInputDate(mod.zoom_attendance_scheduled_at) ||
            (initialDate && initialStart
                ? `${initialDate}T${initialStart}`
                : formatToInputDate(mod.zoom_start_at));
    }
};

watch(
    () => props.course.modules,
    (modules) => {
        if (modules) {
            modules.forEach((m) => {
                initUnitState(m);
                unitAttendanceTimers.value[m.id] =
                    m.attendance_remaining_seconds || 0;
                unitZoomTimers.value[m.id] = m.zoom_remaining_seconds || 0;
            });
        }
    },
    { immediate: true, deep: true },
);

onMounted(() => {
    if (props.course?.modules) {
        props.course.modules.forEach((m) => {
            initUnitState(m);
        });
    }
});

const submitUnitZoomSchedule = (mod) => {
    const form = unitZoomForms.value[mod.id];
    if (!form) return;
    form.isSaving = true;
    const dayNum = mod.day_number || 1;
    router.post(
        `/admin/lms/modules/${mod.id}/zoom/schedule`,
        {
            scheduled_date: form.scheduled_date,
            start_time: form.start_time,
            end_time: form.end_time,
            zoom_link: form.zoom_link,
            zoom_meeting_id: form.zoom_meeting_id,
            zoom_passcode: form.zoom_passcode,
            zoom_status: "upcoming",
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (props.course?.modules) {
                    props.course.modules.forEach((m) => {
                        if ((m.day_number || 1) === dayNum) {
                            m.scheduled_date = form.scheduled_date;
                            m.start_time = form.start_time;
                            m.end_time = form.end_time;
                            m.zoom_link = form.zoom_link;
                            m.zoom_meeting_id = form.zoom_meeting_id;
                            m.zoom_passcode = form.zoom_passcode;
                            m.zoom_status = "upcoming";
                            m.zoom_attendance_closed_at = null;
                            isEditingZoomEnded.value[m.id] = false;
                            isEditingZoomLive.value[m.id] = false;
                            if (unitZoomForms.value[m.id]) {
                                unitZoomForms.value[m.id].scheduled_date = form.scheduled_date;
                                unitZoomForms.value[m.id].start_time = form.start_time;
                                unitZoomForms.value[m.id].end_time = form.end_time;
                                unitZoomForms.value[m.id].zoom_link = form.zoom_link;
                                unitZoomForms.value[m.id].zoom_meeting_id = form.zoom_meeting_id;
                                unitZoomForms.value[m.id].zoom_passcode = form.zoom_passcode;
                            }
                        }
                    });
                }
            },
            onFinish: () => {
                form.isSaving = false;
            },
        },
    );
};

const startUnitZoomNow = (mod) => {
    const dayNum = mod.day_number || 1;
    if (
        confirm(
            `Mulai sesi Online Meeting untuk Hari ke-${dayNum} sekarang? Status kelas akan LIVE dan peserta dapat bergabung ke Online Meeting.`,
        )
    ) {
        isStartingUnitZoom.value[mod.id] = true;
        router.post(
            `/admin/lms/modules/${mod.id}/zoom/start`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    const nowIso = new Date().toISOString();
                    const endIso =
                        !mod.zoom_end_at ||
                        new Date(mod.zoom_end_at).getTime() <= Date.now()
                            ? new Date(
                                  Date.now() + 2 * 60 * 60 * 1000,
                              ).toISOString()
                            : mod.zoom_end_at;
                    const endMs = new Date(endIso).getTime();
                    const remSec = Math.max(
                        0,
                        Math.floor((endMs - Date.now()) / 1000),
                    );

                    if (props.course?.modules) {
                        props.course.modules.forEach((m) => {
                            if ((m.day_number || 1) === dayNum) {
                                m.zoom_status = "live";
                                m.zoom_start_at = nowIso;
                                m.zoom_end_at = endIso;
                                unitZoomTimers.value[m.id] = remSec;
                                isEditingZoomEnded.value[m.id] = false;
                            }
                        });
                    }
                },
                onFinish: () => {
                    isStartingUnitZoom.value[mod.id] = false;
                },
            },
        );
    }
};

const endUnitZoomNow = (mod) => {
    const dayNum = mod.day_number || 1;
    if (
        confirm(
            `Akhiri sesi Online Meeting untuk Hari ke-${dayNum} sekarang? Sesi Online Meeting hari ini akan ditutup dan peserta terlambat dapat mengakses materi mandiri.`,
        )
    ) {
        isEndingUnitZoom.value[mod.id] = true;
        router.post(
            `/admin/lms/modules/${mod.id}/zoom/end`,
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    const nowIso = new Date().toISOString();
                    if (props.course?.modules) {
                        props.course.modules.forEach((m) => {
                            if ((m.day_number || 1) === dayNum) {
                                m.zoom_status = "ended";
                                m.zoom_end_at = nowIso;
                                unitZoomTimers.value[m.id] = 0;
                                isEditingZoomEnded.value[m.id] = false;
                                isEditingZoomLive.value[m.id] = false;
                            }
                        });
                    }
                },
                onFinish: () => {
                    isEndingUnitZoom.value[mod.id] = false;
                },
            },
        );
    }
};

const openUnitAttendanceWithDuration = (mod, durationMinutes) => {
    const dayNum = mod.day_number || 1;
    isOpeningUnitAttendance.value[mod.id] = true;
    router.post(
        `/admin/lms/modules/${mod.id}/zoom/attendance/open`,
        { duration_minutes: durationMinutes },
        {
            preserveScroll: true,
            onSuccess: () => {
                const closedIso = new Date(
                    Date.now() + durationMinutes * 60 * 1000,
                ).toISOString();
                const remSec = durationMinutes * 60;
                if (props.course?.modules) {
                    props.course.modules.forEach((m) => {
                        if ((m.day_number || 1) === dayNum) {
                            m.is_attendance_open_now = true;
                            m.zoom_attendance_closed_at = closedIso;
                            unitAttendanceTimers.value[m.id] = remSec;
                            isOpeningAttendanceSusulan.value[m.id] = false;
                        }
                    });
                }
            },
            onFinish: () => {
                isOpeningUnitAttendance.value[mod.id] = false;
            },
        },
    );
};

const closeUnitAttendanceNow = (mod) => {
    const dayNum = mod.day_number || 1;
    if (
        !confirm(
            `Yakin ingin menutup sesi absensi untuk Hari ke-${dayNum} sekarang? Tombol absen di kelas peserta untuk hari ini akan dinonaktifkan.`,
        )
    ) {
        return;
    }
    isOpeningUnitAttendance.value[mod.id] = true;
    router.post(
        `/admin/lms/modules/${mod.id}/zoom/attendance/close`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                const nowIso = new Date().toISOString();
                if (props.course?.modules) {
                    props.course.modules.forEach((m) => {
                        if ((m.day_number || 1) === dayNum) {
                            unitAttendanceTimers.value[m.id] = 0;
                            m.is_attendance_open_now = false;
                            m.zoom_attendance_closed_at = nowIso;
                            isOpeningAttendanceSusulan.value[m.id] = false;
                        }
                    });
                }
            },
            onFinish: () => {
                isOpeningUnitAttendance.value[mod.id] = false;
            },
        },
    );
};

const extendUnitAttendance = (mod, extraMinutes = 15) => {
    const curSec = unitAttendanceTimers.value[mod.id] || 0;
    const curMin = Math.ceil(curSec / 60);
    const newTotal = Math.max(1, curMin + extraMinutes);
    openUnitAttendanceWithDuration(mod, newTotal);
};

const selectUnitAttendanceDuration = (mod, mins) => {
    const val = parseInt(mins, 10) || 30;
    const dayNum = mod.day_number || 1;
    if (props.course?.modules) {
        props.course.modules.forEach((m) => {
            if ((m.day_number || 1) === dayNum) {
                unitSelectedDuration.value[m.id] = val;
                if (unitScheduledAttendanceForm.value[m.id]) {
                    unitScheduledAttendanceForm.value[m.id].duration_minutes = val;
                }
            }
        });
    }
};

const submitUnitScheduleAttendance = (mod) => {
    const f = unitScheduledAttendanceForm.value[mod.id];
    if (!f) return;
    const dur = parseInt(
        unitSelectedDuration.value[mod.id] || f.duration_minutes || 30,
        10,
    );
    f.duration_minutes = dur;
    const dayNum = mod.day_number || 1;

    router.post(
        `/admin/lms/modules/${mod.id}/zoom/attendance/schedule`,
        {
            scheduled_at: f.scheduled_at,
            duration_minutes: dur,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                const schedTime = new Date(f.scheduled_at).getTime();
                const durationMs = dur * 60 * 1000;
                const now = Date.now();
                const isActive = now >= schedTime && now <= schedTime + durationMs;
                const remSec = isActive
                    ? Math.max(0, Math.floor((schedTime + durationMs - now) / 1000))
                    : 0;

                if (props.course?.modules) {
                    props.course.modules.forEach((m) => {
                        if ((m.day_number || 1) === dayNum) {
                            isOpeningAttendanceSusulan.value[m.id] = false;
                            m.zoom_attendance_scheduled_at = f.scheduled_at;
                            m.zoom_attendance_duration_minutes = dur;
                            m.zoom_attendance_closed_at = null;
                            if (m.zoom_status === "ended") {
                                m.zoom_status = "upcoming";
                            }
                            unitSelectedDuration.value[m.id] = dur;
                            if (unitScheduledAttendanceForm.value[m.id]) {
                                unitScheduledAttendanceForm.value[m.id].scheduled_at =
                                    f.scheduled_at;
                                unitScheduledAttendanceForm.value[m.id].duration_minutes =
                                    dur;
                            }
                            if (isActive) {
                                unitAttendanceTimers.value[m.id] = remSec;
                                m.is_attendance_open_now = true;
                            }
                        }
                    });
                }
            },
        },
    );
};

// Tab 2 Matrix Attendance Helpers
const togglingAttendance = ref({});

const getModuleAttendanceStatus = (enrollment, module, modIdx = 0) => {
    const list =
        enrollment.module_attendances || enrollment.moduleAttendances || [];
    const found = list.find((a) => a.module_id === module.id);
    if (found && found.attendance_path) {
        return found.attendance_path;
    }
    if (
        ((props.course.modules && props.course.modules.length === 1) ||
            modIdx === 0) &&
        enrollment.attendance_path &&
        enrollment.attendance_path !== "none"
    ) {
        return enrollment.attendance_path;
    }
    return "none";
};

const isModuleAttended = (enrollment, moduleId, modIdx = 0) => {
    const list =
        enrollment.module_attendances || enrollment.moduleAttendances || [];
    const hasMod = list.some((a) => a.module_id === moduleId);
    if (hasMod) return true;
    if (
        ((props.course.modules && props.course.modules.length === 1) ||
            modIdx === 0) &&
        enrollment.attendance_path &&
        enrollment.attendance_path !== "none"
    ) {
        return true;
    }
    return false;
};

const getAttendedCount = (enrollment) => {
    if (
        enrollment.attended_days_count !== undefined &&
        enrollment.attended_days_count !== null &&
        enrollment.attended_days_count > 0
    ) {
        return enrollment.attended_days_count;
    }
    const list =
        enrollment.module_attendances || enrollment.moduleAttendances || [];
    if (list.length > 0) {
        return list.length;
    }
    if (enrollment.attendance_path && enrollment.attendance_path !== "none") {
        return 1;
    }
    return 0;
};

const getAttendancePercentage = (enrollment) => {
    const total = props.course.modules?.length || 0;
    if (total === 0) return 0;
    const attended = getAttendedCount(enrollment);
    return Math.min(100, Math.round((attended / total) * 100));
};

const toggleStudentModuleAttendance = (enrollment, module) => {
    const key = `${enrollment.id}_${module.id}`;
    if (togglingAttendance.value[key]) return;
    togglingAttendance.value[key] = true;

    router.post(
        `/admin/lms/${props.course.id}/enrollments/${enrollment.id}/modules/${module.id}/toggle-attendance`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                togglingAttendance.value[key] = false;
            },
        },
    );
};

const submitModule = () => {
    if (isEditingModule.value) {
        const modId = currentModuleId.value;
        moduleForm.put(`/admin/lms/modules/${modId}`, {
            onSuccess: () => {
                showModuleModal.value = false;
                if (unitZoomForms.value[modId]) {
                    unitZoomForms.value[modId].scheduled_date =
                        moduleForm.scheduled_date;
                    unitZoomForms.value[modId].start_time =
                        moduleForm.start_time;
                    unitZoomForms.value[modId].end_time = moduleForm.end_time;
                    unitZoomForms.value[modId].zoom_link = moduleForm.zoom_link;
                    unitZoomForms.value[modId].zoom_meeting_id =
                        moduleForm.zoom_meeting_id;
                    unitZoomForms.value[modId].zoom_passcode =
                        moduleForm.zoom_passcode;
                }
            },
        });
    } else {
        moduleForm.post(`/admin/lms/${props.course.id}/modules`, {
            onSuccess: () => {
                showModuleModal.value = false;
            },
        });
    }
};

const deletingModuleId = ref(null);
const deleteModule = (module) => {
    if (
        confirm(
            `Hapus Unit Kompetensi "${module.title}" beserta seluruh Elemen Kompetensinya?`,
        )
    ) {
        deletingModuleId.value = module.id;
        router.delete(`/admin/lms/modules/${module.id}`, {
            preserveScroll: true,
            onFinish: () => {
                deletingModuleId.value = null;
            },
        });
    }
};

// Elemen Kompetensi (Lessons)
const showLessonModal = ref(false);
const isEditingLesson = ref(false);
const currentLessonId = ref(null);
const currentLessonMediaPath = ref(null);

const lessonForm = useForm({
    module_id: "",
    title: "",
    content_type: "article",
    estimated_duration_minutes: 10,
    video_url: "",
    content_text: "",
    media_file: null,
});

const openAddLessonModal = (moduleId) => {
    isEditingLesson.value = false;
    currentLessonId.value = null;
    currentLessonMediaPath.value = null;
    lessonForm.reset();
    lessonForm.module_id = moduleId;
    lessonForm.content_type = "article";
    lessonForm.estimated_duration_minutes = 10;
    showLessonModal.value = true;
};

const openEditLessonModal = (lesson) => {
    isEditingLesson.value = true;
    currentLessonId.value = lesson.id;
    currentLessonMediaPath.value = lesson.media_path || null;
    lessonForm.module_id = lesson.module_id;
    lessonForm.title = lesson.title;
    lessonForm.content_type = lesson.content_type;
    lessonForm.estimated_duration_minutes = lesson.estimated_duration_minutes;
    lessonForm.video_url = lesson.video_url || "";
    lessonForm.content_text = lesson.content_text || "";
    lessonForm.media_file = null;
    showLessonModal.value = true;
};

const submitLesson = () => {
    if (isEditingLesson.value) {
        lessonForm.post(`/admin/lms/lessons/${currentLessonId.value}/update`, {
            onSuccess: () => {
                showLessonModal.value = false;
            },
        });
    } else {
        lessonForm.post(`/admin/lms/${props.course.id}/lessons`, {
            onSuccess: () => {
                showLessonModal.value = false;
            },
        });
    }
};

const deletingLessonId = ref(null);
const deleteLesson = (lesson) => {
    if (confirm(`Hapus Elemen Kompetensi "${lesson.title}"?`)) {
        deletingLessonId.value = lesson.id;
        router.delete(`/admin/lms/lessons/${lesson.id}`, {
            preserveScroll: true,
            onFinish: () => {
                deletingLessonId.value = null;
            },
        });
    }
};

const handleLessonMedia = (e) => {
    const file = e.target.files[0] || null;
    if (file) {
        const sizeMb = file.size / (1024 * 1024);
        if (sizeMb > 50) {
            alert(
                `Ukuran file "${file.name}" adalah ${sizeMb.toFixed(1)} MB, melebihi batas maksimal upload 50 MB.`
            );
            e.target.value = "";
            lessonForm.media_file = null;
            return;
        }
    }
    lessonForm.media_file = file;
};

// Curriculum Import (Unit & Elemen Kompetensi via Excel/CSV)
const showCurriculumImportModal = ref(false);
const curriculumImportForm = useForm({
    file: null,
});

const submitCurriculumImport = () => {
    if (!curriculumImportForm.file) return;
    curriculumImportForm.post(
        `/admin/lms/${props.course.id}/curriculum/import`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showCurriculumImportModal.value = false;
                curriculumImportForm.reset();
            },
        },
    );
};

// -------------------------------------------------------------
// TAB 1: KUIS FLEKSIBEL / OPSIONAL PER UNIT KOMPETENSI
// -------------------------------------------------------------
const showQuizModal = ref(false);
const activeQuizModule = ref(null);
const activeQuiz = ref(null);
const quizForm = useForm({
    quiz_id: null,
    title: "",
    passing_score: 70,
    description: "",
    time_limit_minutes: null,
});

const openQuizModal = (module, quiz = null) => {
    activeQuizModule.value = module;
    activeQuiz.value = quiz || null;
    if (activeQuiz.value) {
        quizForm.quiz_id = activeQuiz.value.id;
        quizForm.title = activeQuiz.value.title;
        quizForm.passing_score = activeQuiz.value.passing_score || 70;
        quizForm.description = activeQuiz.value.description || "";
        quizForm.time_limit_minutes =
            activeQuiz.value.time_limit_minutes || null;
    } else {
        quizForm.quiz_id = null;
        const count = (module.quizzes?.length || (module.quiz ? 1 : 0)) + 1;
        quizForm.title = `Kuis ${count}: ${module.title}`;
        quizForm.passing_score = 70;
        quizForm.description =
            "Jawab seluruh pertanyaan pilihan ganda berikut untuk memverifikasi penguasaan unit kompetensi.";
        quizForm.time_limit_minutes = null;
    }
    resetQuestionForm();
    showQuizModal.value = true;
};

const submitQuizForm = () => {
    if (!activeQuizModule.value) return;
    quizForm.post(`/admin/lms/modules/${activeQuizModule.value.id}/quiz`, {
        preserveScroll: true,
        onSuccess: () => {
            const updated = props.course.modules?.find(
                (m) => m.id === activeQuizModule.value.id,
            );
            if (updated) {
                activeQuizModule.value = updated;
                if (quizForm.quiz_id) {
                    activeQuiz.value =
                        (updated.quizzes || []).find(
                            (q) => q.id === quizForm.quiz_id,
                        ) || updated.quiz;
                } else {
                    const list =
                        updated.quizzes || (updated.quiz ? [updated.quiz] : []);
                    activeQuiz.value = list[list.length - 1] || null;
                    if (activeQuiz.value) {
                        quizForm.quiz_id = activeQuiz.value.id;
                    }
                }
            }
        },
    });
};

const deletingQuizId = ref(null);
const deleteQuiz = (quiz) => {
    if (!quiz) return;
    if (!confirm(`Hapus kuis "${quiz.title}" dari Unit Kompetensi ini?`))
        return;
    deletingQuizId.value = quiz.id;
    router.delete(`/admin/lms/quizzes/${quiz.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            showQuizModal.value = false;
            activeQuiz.value = null;
        },
        onFinish: () => {
            deletingQuizId.value = null;
        },
    });
};

// Question form inside quiz
const isEditingQuestion = ref(false);
const currentEditingQuestionId = ref(null);
const questionForm = useForm({
    question_text: "",
    options: [
        { key: "A", text: "" },
        { key: "B", text: "" },
        { key: "C", text: "" },
        { key: "D", text: "" },
    ],
    correct_answer: "A",
    explanation: "",
    points: 10,
});

const resetQuestionForm = () => {
    isEditingQuestion.value = false;
    currentEditingQuestionId.value = null;
    questionForm.question_text = "";
    questionForm.options = [
        { key: "A", text: "" },
        { key: "B", text: "" },
        { key: "C", text: "" },
        { key: "D", text: "" },
    ];
    questionForm.correct_answer = "A";
    questionForm.explanation = "";
    questionForm.points = 10;
};

const editQuestion = (question) => {
    isEditingQuestion.value = true;
    currentEditingQuestionId.value = question.id;
    questionForm.question_text = question.question_text;
    questionForm.options = Array.isArray(question.options)
        ? JSON.parse(JSON.stringify(question.options))
        : [
              { key: "A", text: "" },
              { key: "B", text: "" },
              { key: "C", text: "" },
              { key: "D", text: "" },
          ];
    questionForm.correct_answer = question.correct_answer || "A";
    questionForm.explanation = question.explanation || "";
    questionForm.points = question.points || 10;
};

const submitQuestionForm = () => {
    const targetQuiz = activeQuiz.value || activeQuizModule.value?.quiz;
    if (!targetQuiz) {
        alert(
            "Silakan klik 'Simpan Pengaturan Kuis' terlebih dahulu sebelum menambahkan butir soal.",
        );
        return;
    }
    const quizId = targetQuiz.id;
    if (isEditingQuestion.value) {
        questionForm.put(
            `/admin/lms/quiz-questions/${currentEditingQuestionId.value}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    resetQuestionForm();
                    const updated = props.course.modules?.find(
                        (m) => m.id === activeQuizModule.value.id,
                    );
                    if (updated) {
                        activeQuizModule.value = updated;
                        activeQuiz.value =
                            (updated.quizzes || []).find(
                                (q) => q.id === quizId,
                            ) || updated.quiz;
                    }
                },
            },
        );
    } else {
        questionForm.post(`/admin/lms/quizzes/${quizId}/questions`, {
            preserveScroll: true,
            onSuccess: () => {
                resetQuestionForm();
                const updated = props.course.modules?.find(
                    (m) => m.id === activeQuizModule.value.id,
                );
                if (updated) {
                    activeQuizModule.value = updated;
                    activeQuiz.value =
                        (updated.quizzes || []).find((q) => q.id === quizId) ||
                        updated.quiz;
                }
            },
        });
    }
};

const deletingQuestionId = ref(null);
const deleteQuestion = (question) => {
    if (!confirm("Hapus butir soal kuis ini?")) return;
    const targetQuizId =
        activeQuiz.value?.id || activeQuizModule.value?.quiz?.id;
    deletingQuestionId.value = question.id;
    router.delete(`/admin/lms/quiz-questions/${question.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            const updated = props.course.modules?.find(
                (m) => m.id === activeQuizModule.value.id,
            );
            if (updated) {
                activeQuizModule.value = updated;
                if (targetQuizId) {
                    activeQuiz.value =
                        (updated.quizzes || []).find(
                            (q) => q.id === targetQuizId,
                        ) || updated.quiz;
                }
            }
        },
        onFinish: () => {
            deletingQuestionId.value = null;
        },
    });
};

// -------------------------------------------------------------
// TAB 2: PESERTA & IMPORT
// -------------------------------------------------------------
const showParticipantModal = ref(false);
const showImportModal = ref(false);
const participantSearch = ref("");

const participantForm = useForm({
    name: "",
    email: "",
    training_transaction_code: "",
    nik: "",
    phone: "",
    gender: "L",
    address: "",
});

const importForm = useForm({
    file: null,
});

const submitParticipant = () => {
    participantForm.post(`/admin/lms/${props.course.id}/participants`, {
        onSuccess: () => {
            showParticipantModal.value = false;
            participantForm.reset();
        },
    });
};

const submitImport = () => {
    importForm.post(`/admin/lms/${props.course.id}/participants/import`, {
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const deletingEnrollmentId = ref(null);
const deleteEnrollment = (enrollment) => {
    if (
        confirm(
            `Keluarkan peserta "${enrollment.participant?.name}" dari kelas ini?`,
        )
    ) {
        deletingEnrollmentId.value = enrollment.id;
        router.delete(
            `/admin/lms/${props.course.id}/enrollments/${enrollment.id}`,
            {
                preserveScroll: true,
                onSuccess: () => {
                    selectedEnrollmentIds.value =
                        selectedEnrollmentIds.value.filter(
                            (id) => id !== enrollment.id,
                        );
                },
                onFinish: () => {
                    deletingEnrollmentId.value = null;
                },
            },
        );
    }
};

// Selection & Bulk Action
const selectedEnrollmentIds = ref([]);

const isAllSelected = computed(() => {
    if (paginatedEnrollments.value.length === 0) return false;
    return paginatedEnrollments.value.every((e) =>
        selectedEnrollmentIds.value.includes(e.id),
    );
});

const toggleSelectAll = () => {
    if (isAllSelected.value) {
        const currentIds = paginatedEnrollments.value.map((e) => e.id);
        selectedEnrollmentIds.value = selectedEnrollmentIds.value.filter(
            (id) => !currentIds.includes(id),
        );
    } else {
        const currentIds = paginatedEnrollments.value.map((e) => e.id);
        const combined = new Set([
            ...selectedEnrollmentIds.value,
            ...currentIds,
        ]);
        selectedEnrollmentIds.value = Array.from(combined);
    }
};

const isBulkDeleting = ref(false);
const deleteSelectedEnrollments = () => {
    if (selectedEnrollmentIds.value.length === 0) return;
    if (
        confirm(
            `Yakin ingin mengeluarkan ${selectedEnrollmentIds.value.length} peserta terpilih dari kelas ini?`,
        )
    ) {
        isBulkDeleting.value = true;
        router.post(
            `/admin/lms/${props.course.id}/enrollments/bulk-delete`,
            {
                enrollment_ids: selectedEnrollmentIds.value,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    selectedEnrollmentIds.value = [];
                },
                onFinish: () => {
                    isBulkDeleting.value = false;
                },
            },
        );
    }
};

// Edit Participant Modal
const showEditModal = ref(false);
const editingEnrollment = ref(null);
const editParticipantForm = useForm({
    name: "",
    email: "",
    training_transaction_code: "",
    nik: "",
    phone: "",
    gender: "L",
    address: "",
    status: "enrolled",
    attendance_path: "none",
    progress_percentage: 0,
    certificate_number: "",
    daily_attendances: {},
});

const openEditModal = (enrollment) => {
    editingEnrollment.value = enrollment;
    const p = enrollment.participant || {};
    editParticipantForm.name = p.name || "";
    editParticipantForm.email = p.email || "";
    editParticipantForm.training_transaction_code =
        enrollment.training_transaction_code ||
        p.training_transaction_code ||
        "";
    editParticipantForm.nik = p.nik && p.nik !== "0" ? p.nik : "";
    editParticipantForm.phone = p.phone && p.phone !== "0" ? p.phone : "";
    editParticipantForm.gender = p.gender || "L";
    editParticipantForm.address =
        p.address && p.address !== "0" ? p.address : "";
    editParticipantForm.status = enrollment.status || "enrolled";
    editParticipantForm.attendance_path = enrollment.attendance_path || "none";
    editParticipantForm.progress_percentage =
        enrollment.progress_percentage !== undefined
            ? enrollment.progress_percentage
            : 0;
    editParticipantForm.certificate_number =
        enrollment.certificate_number || "";

    // Populate daily_attendances map for each unit / day
    const daily = {};
    const attendances =
        enrollment.module_attendances || enrollment.moduleAttendances || [];
    if (props.course.modules) {
        props.course.modules.forEach((mod, idx) => {
            const found = attendances.find((a) => a.module_id === mod.id);
            if (found && found.attendance_path) {
                daily[mod.id] = found.attendance_path;
            } else if (
                ((props.course.modules && props.course.modules.length === 1) ||
                    idx === 0) &&
                enrollment.attendance_path &&
                enrollment.attendance_path !== "none"
            ) {
                daily[mod.id] = enrollment.attendance_path;
            } else {
                daily[mod.id] = "none";
            }
        });
    }
    editParticipantForm.daily_attendances = daily;

    showEditModal.value = true;
};

const submitEditParticipant = () => {
    if (!editingEnrollment.value) return;
    editParticipantForm.put(
        `/admin/lms/${props.course.id}/enrollments/${editingEnrollment.value.id}`,
        {
            preserveScroll: true,
            onSuccess: () => {
                showEditModal.value = false;
                editParticipantForm.reset();
                editingEnrollment.value = null;
            },
        },
    );
};

const attendanceStatusFilter = ref("all"); // 'all', 'live_zoom', 'self_study', 'unattended'

watch(attendanceStatusFilter, () => {
    currentPage.value = 1;
});

const filteredEnrollments = computed(() => {
    if (!props.course.enrollments) return [];
    let list = props.course.enrollments;

    if (attendanceStatusFilter.value !== "all") {
        if (attendanceStatusFilter.value === "live_zoom") {
            list = list.filter((e) => e.attendance_path === "live_zoom");
        } else if (attendanceStatusFilter.value === "self_study") {
            list = list.filter((e) => e.attendance_path === "self_study");
        } else if (attendanceStatusFilter.value === "unattended") {
            list = list.filter((e) => e.status !== "completed");
        }
    }

    if (!participantSearch.value) return list;
    const term = participantSearch.value.toLowerCase();
    return list.filter((e) => {
        const p = e.participant || {};
        return (
            (p.name && p.name.toLowerCase().includes(term)) ||
            (p.email && p.email.toLowerCase().includes(term)) ||
            (p.nik && p.nik.includes(term)) ||
            (p.phone && p.phone.includes(term)) ||
            (p.address && p.address.toLowerCase().includes(term)) ||
            (p.training_transaction_code &&
                p.training_transaction_code.toLowerCase().includes(term)) ||
            (e.training_transaction_code &&
                e.training_transaction_code.toLowerCase().includes(term)) ||
            (p.agency_or_institution &&
                p.agency_or_institution.toLowerCase().includes(term))
        );
    });
});

// Pagination & Counters
const itemsPerPage = ref(10);
const currentPage = ref(1);

const totalPages = computed(() => {
    if (itemsPerPage.value === 0) return 1;
    return (
        Math.ceil(filteredEnrollments.value.length / itemsPerPage.value) || 1
    );
});

const paginatedEnrollments = computed(() => {
    if (itemsPerPage.value === 0) return filteredEnrollments.value;
    const start = (currentPage.value - 1) * itemsPerPage.value;
    return filteredEnrollments.value.slice(start, start + itemsPerPage.value);
});

const showingStart = computed(() => {
    if (filteredEnrollments.value.length === 0) return 0;
    if (itemsPerPage.value === 0) return 1;
    return (currentPage.value - 1) * itemsPerPage.value + 1;
});

const showingEnd = computed(() => {
    if (itemsPerPage.value === 0) return filteredEnrollments.value.length;
    return Math.min(
        currentPage.value * itemsPerPage.value,
        filteredEnrollments.value.length,
    );
});

watch(participantSearch, () => {
    currentPage.value = 1;
});

watch(itemsPerPage, () => {
    currentPage.value = 1;
});

// -------------------------------------------------------------
// TAB 3: ONLINE MEETING & MONITORING KEHADIRAN
// -------------------------------------------------------------

// Attendance Window Controls
const selectedDuration = ref(
    props.course.zoom_attendance_duration_minutes || 30,
);
const customDurationInput = ref(30);
const isCustomDuration = ref(false);
const isOpeningAttendance = ref(false);

const openAttendanceWithDuration = (durationMinutes) => {
    isOpeningAttendance.value = true;
    router.post(
        `/admin/lms/${props.course.id}/zoom/attendance/open`,
        { duration_minutes: durationMinutes },
        {
            preserveScroll: true,
            onFinish: () => {
                isOpeningAttendance.value = false;
            },
        },
    );
};

const closeAttendanceNow = () => {
    if (
        !confirm(
            "Yakin ingin menutup sesi absensi sekarang? Tombol absen online di ruang kelas siswa akan dinonaktifkan.",
        )
    ) {
        return;
    }
    isOpeningAttendance.value = true;
    router.post(
        `/admin/lms/${props.course.id}/zoom/attendance/close`,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                remainingAttendanceSeconds.value = 0;
            },
            onFinish: () => {
                isOpeningAttendance.value = false;
            },
        },
    );
};

const extendAttendance = (extraMinutes = 15) => {
    const currentRemaining = props.course.attendance_remaining_seconds
        ? Math.ceil(props.course.attendance_remaining_seconds / 60)
        : 0;
    const newTotal = Math.max(1, currentRemaining + extraMinutes);
    openAttendanceWithDuration(newTotal);
};

// Scheduled Attendance Form
const scheduleAttendanceForm = useForm({
    scheduled_at: formatToInputDate(props.course.zoom_attendance_scheduled_at),
    duration_minutes: props.course.zoom_attendance_duration_minutes || 30,
});

const submitScheduleAttendance = () => {
    scheduleAttendanceForm.post(
        `/admin/lms/${props.course.id}/zoom/attendance/schedule`,
        {
            preserveScroll: true,
        },
    );
};

// Real-time Countdown Timer for Attendance
const remainingAttendanceSeconds = ref(
    props.course.attendance_remaining_seconds || 0,
);

watch(
    () => props.course.attendance_remaining_seconds,
    (val) => {
        remainingAttendanceSeconds.value = val || 0;
    },
    { immediate: true },
);

const isUnitAttendanceActive = (mod) => {
    if (!mod) return false;
    if (mod.zoom_attendance_closed_at) {
        const closedTime = new Date(mod.zoom_attendance_closed_at).getTime();
        if (mod.zoom_attendance_scheduled_at) {
            const schedTime = new Date(
                mod.zoom_attendance_scheduled_at,
            ).getTime();
            if (
                closedTime >= schedTime &&
                !isOpeningAttendanceSusulan.value[mod.id]
            ) {
                if ((unitAttendanceTimers.value[mod.id] || 0) <= 0) {
                    return false;
                }
            }
        } else if (
            (unitAttendanceTimers.value[mod.id] || 0) <= 0 &&
            !mod.is_attendance_open_now
        ) {
            return false;
        }
    }

    if ((unitAttendanceTimers.value[mod.id] || 0) > 0) return true;
    if (mod.zoom_attendance_scheduled_at) {
        const schedTime = new Date(mod.zoom_attendance_scheduled_at).getTime();
        const durationMs =
            (mod.zoom_attendance_duration_minutes || 30) * 60 * 1000;
        const now = Date.now();
        if (now >= schedTime && now <= schedTime + durationMs) return true;
    }
    return !!mod.is_attendance_open_now;
};

const isUnitAttendanceEnded = (mod) => {
    if (!mod) return false;
    if (isUnitAttendanceActive(mod)) return false;
    if (mod.zoom_attendance_scheduled_at) {
        const schedTime = new Date(mod.zoom_attendance_scheduled_at).getTime();
        const durationMs =
            (mod.zoom_attendance_duration_minutes || 30) * 60 * 1000;
        if (Date.now() < schedTime + durationMs) return false;
    }
    if (mod.zoom_attendance_closed_at) return true;
    if (mod.zoom_status === "ended") return true;
    if (mod.zoom_attendance_opened_at && !mod.is_attendance_open_now)
        return true;
    if (mod.zoom_attendance_scheduled_at) {
        const schedTime = new Date(mod.zoom_attendance_scheduled_at).getTime();
        const durationMs =
            (mod.zoom_attendance_duration_minutes || 30) * 60 * 1000;
        if (Date.now() >= schedTime + durationMs) return true;
    }
    return false;
};

let attendanceTimer = null;
onMounted(() => {
    attendanceTimer = setInterval(() => {
        const now = Date.now();

        // 1. Check unit-level Online Meeting real-time countdown & auto-status
        if (props.course?.modules) {
            props.course.modules.forEach((mod) => {
                if (mod.zoom_status === "ended") {
                    unitZoomTimers.value[mod.id] = 0;
                    return;
                }

                const { startMs, endMs } = getModuleZoomStartEnd(mod);

                if (mod.zoom_status === "live") {
                    if (endMs) {
                        if (now >= endMs) {
                            mod.zoom_status = "ended";
                            unitZoomTimers.value[mod.id] = 0;
                        } else {
                            unitZoomTimers.value[mod.id] = Math.max(
                                0,
                                Math.floor((endMs - now) / 1000),
                            );
                        }
                    } else if (unitZoomTimers.value[mod.id] > 0) {
                        unitZoomTimers.value[mod.id]--;
                    }
                } else if (startMs && endMs) {
                    if (now >= startMs && now <= endMs) {
                        mod.zoom_status = "live";
                        unitZoomTimers.value[mod.id] = Math.max(
                            0,
                            Math.floor((endMs - now) / 1000),
                        );
                    } else if (now > endMs) {
                        mod.zoom_status = "ended";
                        unitZoomTimers.value[mod.id] = 0;
                    } else {
                        mod.zoom_status = "upcoming";
                    }
                }
            });
        }

        // 2. Check unit-level scheduled attendance in real-time
        if (props.course?.modules) {
            props.course.modules.forEach((mod) => {
                if (mod.zoom_attendance_scheduled_at) {
                    const schedTime = new Date(
                        mod.zoom_attendance_scheduled_at,
                    ).getTime();
                    const durationMs =
                        (mod.zoom_attendance_duration_minutes || 30) *
                        60 *
                        1000;
                    const closeTime = schedTime + durationMs;

                    if (now >= schedTime && now <= closeTime) {
                        const remaining = Math.max(
                            0,
                            Math.floor((closeTime - now) / 1000),
                        );
                        unitAttendanceTimers.value[mod.id] = remaining;
                        mod.is_attendance_open_now = true;
                    } else if (
                        now > closeTime &&
                        mod.is_attendance_open_now &&
                        (!unitAttendanceTimers.value[mod.id] ||
                            unitAttendanceTimers.value[mod.id] <= 0)
                    ) {
                        unitAttendanceTimers.value[mod.id] = 0;
                        mod.is_attendance_open_now = false;
                    }
                }
            });
        }

        // 2. Decrement unit timers
        if (unitAttendanceTimers.value) {
            Object.keys(unitAttendanceTimers.value).forEach((id) => {
                if (unitAttendanceTimers.value[id] > 0) {
                    unitAttendanceTimers.value[id]--;
                }
            });
        }

        // 3. Course-level scheduled attendance check
        if (props.course?.zoom_attendance_scheduled_at) {
            const schedTime = new Date(
                props.course.zoom_attendance_scheduled_at,
            ).getTime();
            const durationMs =
                (props.course.zoom_attendance_duration_minutes || 30) *
                60 *
                1000;
            const closeTime = schedTime + durationMs;

            if (now >= schedTime && now <= closeTime) {
                remainingAttendanceSeconds.value = Math.max(
                    0,
                    Math.floor((closeTime - now) / 1000),
                );
                props.course.is_attendance_open_now = true;
            } else if (
                now > closeTime &&
                props.course.is_attendance_open_now &&
                remainingAttendanceSeconds.value <= 0
            ) {
                remainingAttendanceSeconds.value = 0;
                props.course.is_attendance_open_now = false;
            }
        } else if (remainingAttendanceSeconds.value > 0) {
            remainingAttendanceSeconds.value--;
        }
    }, 1000);
});

onUnmounted(() => {
    if (attendanceTimer) clearInterval(attendanceTimer);
});

const formatTimeRemaining = (seconds) => {
    if (seconds <= 0) return "00:00";
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${String(m).padStart(2, "0")}:${String(s).padStart(2, "0")}`;
};

const formatReadableDate = (dateStr) => {
    if (!dateStr) return "-";
    const d = new Date(dateStr);
    return d.toLocaleString("id-ID", {
        weekday: "short",
        day: "numeric",
        month: "short",
        year: "numeric",
        hour: "2-digit",
        minute: "2-digit",
    });
};


const calculateDurationDays = (startDate, endDate) => {
    if (!startDate || !endDate) return null;
    try {
        const start = new Date(startDate);
        const end = new Date(endDate);
        if (isNaN(start.getTime()) || isNaN(end.getTime())) return null;
        const utcStart = Date.UTC(
            start.getFullYear(),
            start.getMonth(),
            start.getDate(),
        );
        const utcEnd = Date.UTC(
            end.getFullYear(),
            end.getMonth(),
            end.getDate(),
        );
        const diffDays =
            Math.round((utcEnd - utcStart) / (1000 * 60 * 60 * 24)) + 1;
        return diffDays > 0 ? diffDays : 1;
    } catch (e) {
        return null;
    }
};

// -------------------------------------------------------------
// PRE-FLIGHT INSPECTION CHECKLIST & COURSE STATUS (MULAI PELATIHAN)
// -------------------------------------------------------------
const showPreflightModal = ref(false);
const isPublishing = ref(false);
const editDurationInline = ref(false);

const durationForm = useForm({
    start_date: props.course.start_date
        ? props.course.start_date.substring(0, 10)
        : "",
    end_date: props.course.end_date
        ? props.course.end_date.substring(0, 10)
        : "",
});

const saveDuration = () => {
    durationForm.patch(`/admin/lms/${props.course.id}/dates`, {
        preserveScroll: true,
        onSuccess: () => {
            editDurationInline.value = false;
        },
    });
};

const openPreflightChecklist = () => {
    durationForm.start_date = props.course.start_date
        ? props.course.start_date.substring(0, 10)
        : "";
    durationForm.end_date = props.course.end_date
        ? props.course.end_date.substring(0, 10)
        : "";
    editDurationInline.value = false;
    showPreflightModal.value = true;
};

const confirmPublishCourse = () => {
    isPublishing.value = true;
    router.patch(
        `/admin/lms/${props.course.id}/status`,
        { status: "published" },
        {
            preserveScroll: true,
            onSuccess: () => {
                showPreflightModal.value = false;
            },
            onFinish: () => {
                isPublishing.value = false;
            },
        },
    );
};

const unpublishToDraft = () => {
    if (
        confirm(
            `Ubah status kelas "${props.course.title}" kembali menjadi DRAFT? Siswa sementara tidak dapat mengakses ruang kelas hingga kelas ditayangkan kembali.`,
        )
    ) {
        router.patch(
            `/admin/lms/${props.course.id}/status`,
            { status: "draft" },
            { preserveScroll: true },
        );
    }
};

const courseChecklist = computed(() => {
    const list = [];

    // 1. Durasi Pelatihan (start_date s/d end_date)
    const hasDates = Boolean(props.course.start_date && props.course.end_date);
    const days = calculateDurationDays(
        props.course.start_date,
        props.course.end_date,
    );
    list.push({
        id: "duration",
        title: "Durasi & Jadwal Pelatihan",
        status: hasDates ? "ready" : "warning",
        value: hasDates
            ? `${formatDateIndo(props.course.start_date)} s/d ${formatDateIndo(props.course.end_date)} (${days} Hari Pelatihan)`
            : "Tanggal mulai dan selesai belum diatur",
        description: hasDates
            ? "Durasi tanggal pelaksanaan kelas sudah valid."
            : "Disarankan mengatur tanggal mulai dan selesai kelas agar terstruktur.",
        actionTab: null,
        canEditInline: true,
    });

    // 2. Jadwal Mulai & Selesai Online Meeting
    const hasZoomStart = Boolean(props.course.zoom_start_at);
    const hasZoomEnd = Boolean(props.course.zoom_end_at);
    const zoomReady = hasZoomStart && hasZoomEnd;
    list.push({
        id: "zoom_schedule",
        title: "Jadwal Sesi Online Meeting",
        status: zoomReady ? "ready" : hasZoomStart ? "partial" : "warning",
        value: zoomReady
            ? `${formatReadableDate(props.course.zoom_start_at)} s/d ${formatReadableDate(props.course.zoom_end_at)}`
            : hasZoomStart
              ? `Mulai: ${formatReadableDate(props.course.zoom_start_at)} (Jam selesai belum diatur)`
              : "Jadwal Online Meeting belum diatur",
        description: zoomReady
            ? "Jadwal tatap maya Online Meeting sudah lengkap dan akan ditampilkan kepada siswa."
            : "Peserta tidak dapat melihat estimasi waktu Online Meeting di ruang kelas siswa jika belum dijadwalkan.",
        actionTab: "materi",
    });

    // 3. Tautan / Link & Akses Online Meeting
    const hasZoomLink = Boolean(
        props.course.zoom_link || props.course.zoom_meeting_id,
    );
    list.push({
        id: "zoom_access",
        title: "Tautan & Akses Masuk Online Meeting",
        status: hasZoomLink ? "ready" : "warning",
        value: hasZoomLink
            ? props.course.zoom_link
                ? "Tautan Online Meeting Siap Digunakan"
                : `Meeting ID: ${props.course.zoom_meeting_id}`
            : "Tautan Online Meeting masih kosong",
        description: hasZoomLink
            ? props.course.zoom_passcode
                ? `Dilengkapi Passcode: ${props.course.zoom_passcode}`
                : "Tautan Online Meeting siap diklik oleh peserta."
            : "Peserta tidak akan bisa bergabung ke Online Meeting tanpa tautan yang valid.",
        actionTab: "materi",
    });

    // 4. Jadwal & Jendela Presensi / Absen Online
    const hasScheduledAttendance = Boolean(
        props.course.zoom_attendance_scheduled_at,
    );
    const attendanceDuration =
        props.course.zoom_attendance_duration_minutes || 15;
    list.push({
        id: "attendance",
        title: "Pengaturan Presensi / Absen Online Siswa",
        status: "ready",
        value: hasScheduledAttendance
            ? `Otomatis dijadwalkan pada: ${formatReadableDate(props.course.zoom_attendance_scheduled_at)} (Durasi: ${attendanceDuration} Menit)`
            : `Dibuka Manual saat Live (Durasi default: ${attendanceDuration} Menit)`,
        description: hasScheduledAttendance
            ? "Tombol absen akan otomatis menyala di kelas siswa pada waktu yang dijadwalkan."
            : "Instruktur/Admin dapat membuka sesi absen kapan saja menggunakan tombol kontrol di Tab 3 atau Header.",
        actionTab: "zoom",
    });

    // 5. Ketersediaan Unit & Elemen Kompetensi (Untuk Jalur 2: Belajar Mandiri)
    const totalModules = props.metrics?.total_modules || 0;
    const totalLessons = props.metrics?.total_lessons || 0;
    const lessonsReady = totalLessons > 0;
    list.push({
        id: "curriculum",
        title: "Unit & Elemen Kompetensi (Jalur 2)",
        status: lessonsReady ? "ready" : "warning",
        value: lessonsReady
            ? `${totalModules} Unit & ${totalLessons} Elemen Kompetensi siap dipelajari`
            : "Belum ada unit & elemen kompetensi yang diunggah",
        description: lessonsReady
            ? "Peserta yang terlambat/susulan dapat mempelajari unit & elemen kompetensi mandiri untuk mencapai progres 100%."
            : "Jika materi kosong, peserta Jalur 2 tidak dapat menyelesaikan progres belajar untuk klaim sertifikat.",
        actionTab: "materi",
    });

    // 6. Data Peserta Kelas
    const totalStudents = props.metrics?.total_students || 0;
    const studentsReady = totalStudents > 0;
    list.push({
        id: "participants",
        title: "Peserta Terdaftar di Kelas",
        status: studentsReady ? "ready" : "warning",
        value: studentsReady
            ? `${totalStudents} Peserta telah terdaftar di kelas ini`
            : "Belum ada peserta terdaftar",
        description: studentsReady
            ? "Data email dan NIK peserta siap untuk login dan verifikasi kelas."
            : "Import data peserta dari file Excel laporan transaksi di TAB 2 agar peserta dapat login.",
        actionTab: "peserta",
    });

    // 7. Pengaturan Template Sertifikat & TTE
    const hasCert = Boolean(props.course.certificate_template);
    list.push({
        id: "certificate",
        title: "Template Sertifikat & QR TTE",
        status: hasCert ? "ready" : "info",
        value: hasCert
            ? "Template Sertifikat A4 & Koordinat QR TTE Siap"
            : "Menggunakan template standar (atau belum diunggah)",
        description: hasCert
            ? "Sertifikat otomatis dapat dicetak siswa begitu status kelulusan terpenuhi."
            : "Dapat diatur di TAB 4 sewaktu-waktu sebelum kelas berakhir.",
        actionTab: "sertifikat",
    });

    return list;
});

const warningCount = computed(() => {
    return courseChecklist.value.filter((item) => item.status === "warning")
        .length;
});

const markingAttendanceEnrollmentId = ref(null);
const markManualAttendance = (enrollment, path = "live_zoom") => {
    if (
        confirm(
            `Tandai peserta "${enrollment.participant?.name}" sebagai SUDAH HADIR (${path === "live_zoom" ? "Online Meeting Live" : "Belajar Mandiri"}) dan terbitkan sertifikat?`,
        )
    ) {
        markingAttendanceEnrollmentId.value = `${enrollment.id}_${path}`;
        router.post(
            `/admin/lms/${props.course.id}/enrollments/${enrollment.id}/manual-attendance`,
            { attendance_path: path },
            {
                preserveScroll: true,
                onFinish: () => {
                    markingAttendanceEnrollmentId.value = null;
                },
            },
        );
    }
};

// -------------------------------------------------------------
// TAB 3: PENGATURAN SERTIFIKAT A4 & TTE
// -------------------------------------------------------------
const defaultCertConfig = {
    show_grid: false,
    font_family: "'Plus Jakarta Sans', Arial, sans-serif",
    header_kop: {
        show: true,
        x: 50,
        y: 9,
        line1: "KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA",
        line2: "BALAI PELATIHAN VOKASI DAN PRODUKTIVITAS (BPVP) PANGKAJENE DAN KEPULAUAN",
        font_size_line1: 10.5,
        font_size_line2: 8.5,
        color_line1: "#0f2b48",
        color_line2: "#b38b25",
        align: "center",
    },
    certificate_title: {
        show: true,
        x: 50,
        y: 18,
        text: "SERTIFIKAT PELATIHAN",
        font_size: 24,
        color: "#0f2b48",
        align: "center",
    },
    certificate_number: {
        x: 50,
        y: 27,
        font_size: 12,
        color: "#475569",
        align: "center",
    },
    recipient_name: {
        x: 50,
        y: 37,
        font_size: 26,
        color: "#0f2b48",
        align: "center",
    },
    course_title: {
        x: 50,
        y: 49,
        font_size: 15,
        color: "#1e293b",
        align: "center",
    },
    issue_date: {
        x: 50,
        y: 67,
        font_size: 12,
        color: "#64748b",
        align: "center",
    },
    qr_code: {
        x: 50,
        y: 77,
        size: 80,
        align: "center",
    },
};

const certFontFamilies = [
    {
        label: "Plus Jakarta Sans (Modern & Bersih - Standar)",
        value: "'Plus Jakarta Sans', Arial, sans-serif",
    },
    {
        label: "Playfair Display (Elegan & Formal)",
        value: "'Playfair Display', Georgia, serif",
    },
    {
        label: "Cinzel (Megah & Klasik)",
        value: "'Cinzel', Georgia, serif",
    },
    {
        label: "Arial (Standar Sans-Serif)",
        value: "Arial, sans-serif",
    },
    {
        label: "Times New Roman (Standar Serif)",
        value: "'Times New Roman', serif",
    },
];

const initCertConfig = () => {
    const userConfig = props.course.certificate_config || {};
    return {
        show_grid: userConfig.show_grid ?? defaultCertConfig.show_grid,
        font_family: userConfig.font_family || defaultCertConfig.font_family,
        header_kop: {
            ...defaultCertConfig.header_kop,
            ...(userConfig.header_kop || {}),
        },
        certificate_title: {
            ...defaultCertConfig.certificate_title,
            ...(userConfig.certificate_title || {}),
        },
        certificate_number: {
            ...defaultCertConfig.certificate_number,
            ...(userConfig.certificate_number || {}),
        },
        recipient_name: {
            ...defaultCertConfig.recipient_name,
            ...(userConfig.recipient_name || {}),
        },
        course_title: {
            ...defaultCertConfig.course_title,
            ...(userConfig.course_title || {}),
        },
        issue_date: {
            ...defaultCertConfig.issue_date,
            ...(userConfig.issue_date || {}),
        },
        qr_code: {
            ...defaultCertConfig.qr_code,
            ...(userConfig.qr_code || {}),
        },
    };
};

const certConfig = ref(initCertConfig());
const selectedCertKey = ref("recipient_name");
const draggingCertKey = ref(null);
const canvasWrapperRef = ref(null);
const canvasBoxRef = ref(null);
const scaleFactor = ref(1);
let certResizeObserver = null;
const templateImageFile = ref(null);
const isSavingCert = ref(false);

const sampleIssueDateFormatted = computed(() => {
    try {
        return new Date().toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric",
        });
    } catch (e) {
        return "30 September 2026";
    }
});

const updateCanvasScale = () => {
    if (canvasWrapperRef.value) {
        const width = canvasWrapperRef.value.clientWidth;
        if (width > 0) {
            scaleFactor.value = width / 1123;
        }
    }
};

// Undo / Redo History Stack
const certHistoryStack = ref([JSON.parse(JSON.stringify(certConfig.value))]);
const certHistoryIndex = ref(0);
let isApplyingHistory = false;
let dragStartSnapshot = null;

const canCertUndo = computed(() => certHistoryIndex.value > 0);
const canCertRedo = computed(
    () => certHistoryIndex.value < certHistoryStack.value.length - 1,
);

const recordCertHistory = () => {
    if (isApplyingHistory) return;
    const currentState = JSON.parse(JSON.stringify(certConfig.value));
    const lastState = certHistoryStack.value[certHistoryIndex.value];
    if (JSON.stringify(currentState) === JSON.stringify(lastState)) {
        return;
    }
    // Truncate future redo entries
    certHistoryStack.value = certHistoryStack.value.slice(
        0,
        certHistoryIndex.value + 1,
    );
    certHistoryStack.value.push(currentState);
    // Limit stack size to 50
    if (certHistoryStack.value.length > 50) {
        certHistoryStack.value.shift();
    }
    certHistoryIndex.value = certHistoryStack.value.length - 1;
};

const certUndo = () => {
    if (!canCertUndo.value) return;
    isApplyingHistory = true;
    certHistoryIndex.value--;
    certConfig.value = JSON.parse(
        JSON.stringify(certHistoryStack.value[certHistoryIndex.value]),
    );
    setTimeout(() => {
        isApplyingHistory = false;
    }, 50);
};

const certRedo = () => {
    if (!canCertRedo.value) return;
    isApplyingHistory = true;
    certHistoryIndex.value++;
    certConfig.value = JSON.parse(
        JSON.stringify(certHistoryStack.value[certHistoryIndex.value]),
    );
    setTimeout(() => {
        isApplyingHistory = false;
    }, 50);
};

const resetCertToDefaultLayout = () => {
    certConfig.value = {
        show_grid: certConfig.value.show_grid,
        font_family: defaultCertConfig.font_family,
        header_kop: JSON.parse(JSON.stringify(defaultCertConfig.header_kop)),
        certificate_title: JSON.parse(
            JSON.stringify(defaultCertConfig.certificate_title),
        ),
        certificate_number: JSON.parse(
            JSON.stringify(defaultCertConfig.certificate_number),
        ),
        recipient_name: JSON.parse(
            JSON.stringify(defaultCertConfig.recipient_name),
        ),
        course_title: JSON.parse(
            JSON.stringify(defaultCertConfig.course_title),
        ),
        issue_date: JSON.parse(JSON.stringify(defaultCertConfig.issue_date)),
        qr_code: JSON.parse(JSON.stringify(defaultCertConfig.qr_code)),
    };
    recordCertHistory();
};

const selectCertElement = (key) => {
    selectedCertKey.value = key;
};

const centerCertElement = (key) => {
    if (certConfig.value[key]) {
        certConfig.value[key].x = 50;
        recordCertHistory();
    }
};

const onCertPointerDown = (key, event) => {
    event.preventDefault();
    event.stopPropagation();
    selectedCertKey.value = key;
    draggingCertKey.value = key;
    dragStartSnapshot = JSON.parse(JSON.stringify(certConfig.value));

    const onPointerMove = (e) => {
        if (!draggingCertKey.value || !canvasBoxRef.value) return;
        const rect = canvasBoxRef.value.getBoundingClientRect();
        if (rect.width <= 0 || rect.height <= 0) return;

        let pctX = ((e.clientX - rect.left) / rect.width) * 100;
        let pctY = ((e.clientY - rect.top) / rect.height) * 100;

        // Clamp between 0% and 100% with 1 decimal precision
        pctX = Math.max(0, Math.min(100, Math.round(pctX * 10) / 10));
        pctY = Math.max(0, Math.min(100, Math.round(pctY * 10) / 10));

        if (certConfig.value[draggingCertKey.value]) {
            certConfig.value[draggingCertKey.value].x = pctX;
            certConfig.value[draggingCertKey.value].y = pctY;
        }
    };

    const onPointerUp = () => {
        draggingCertKey.value = null;
        window.removeEventListener("pointermove", onPointerMove);
        window.removeEventListener("pointerup", onPointerUp);
        if (
            dragStartSnapshot &&
            JSON.stringify(certConfig.value) !==
                JSON.stringify(dragStartSnapshot)
        ) {
            recordCertHistory();
        }
        dragStartSnapshot = null;
    };

    window.addEventListener("pointermove", onPointerMove);
    window.addEventListener("pointerup", onPointerUp);
};

const handleCertKeyDown = (e) => {
    if (activeTab.value !== "sertifikat") return;
    const isInput = ["INPUT", "TEXTAREA", "SELECT"].includes(
        document.activeElement?.tagName,
    );
    if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "z") {
        if (!isInput) {
            e.preventDefault();
            if (e.shiftKey) {
                certRedo();
            } else {
                certUndo();
            }
        }
    } else if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === "y") {
        if (!isInput) {
            e.preventDefault();
            certRedo();
        }
    }
};

watch(
    activeTab,
    (newTab) => {
        if (newTab === "sertifikat") {
            nextTick(() => {
                updateCanvasScale();
                if (
                    canvasWrapperRef.value &&
                    !certResizeObserver &&
                    typeof ResizeObserver !== "undefined"
                ) {
                    certResizeObserver = new ResizeObserver(() => {
                        updateCanvasScale();
                    });
                    certResizeObserver.observe(canvasWrapperRef.value);
                }
            });
        }
    },
    { immediate: true },
);

onMounted(() => {
    window.addEventListener("keydown", handleCertKeyDown);
    window.addEventListener("resize", updateCanvasScale);
    nextTick(() => {
        updateCanvasScale();
        if (canvasWrapperRef.value && typeof ResizeObserver !== "undefined") {
            certResizeObserver = new ResizeObserver(() => {
                updateCanvasScale();
            });
            certResizeObserver.observe(canvasWrapperRef.value);
        }
    });
});

onUnmounted(() => {
    window.removeEventListener("keydown", handleCertKeyDown);
    window.removeEventListener("resize", updateCanvasScale);
    if (certResizeObserver) {
        certResizeObserver.disconnect();
    }
});

const saveCertificateConfig = (callback = null) => {
    isSavingCert.value = true;
    router.post(
        `/admin/lms/${props.course.id}/certificate/config`,
        {
            certificate_config: certConfig.value,
            certificate_number_format: "BPVP-PANGKEP/LMS/{YEAR}/{ID}",
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (callback) callback();
            },
            onFinish: () => {
                isSavingCert.value = false;
            },
        },
    );
};

const openPdfPreview = () => {
    isSavingCert.value = true;

    // Buka window preview segera untuk mencegah browser memblokir popup
    const previewWindow = window.open("about:blank", "_blank");
    if (previewWindow) {
        previewWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head><title>Memuat Preview PDF...</title></head>
            <body style="font-family: system-ui, -apple-system, sans-serif; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; margin: 0; background: #0f172a; color: #f8fafc;">
                <div style="font-size: 16px; font-weight: 600; margin-bottom: 8px;">Menyinkronkan Tata Letak & Merender PDF...</div>
                <div style="font-size: 13px; color: #94a3b8;">Mohon tunggu sebentar, dokumen preview sedang disiapkan sesuai posisi canvas...</div>
            </body>
            </html>
        `);
    }

    router.post(
        `/admin/lms/${props.course.id}/certificate/config`,
        {
            certificate_config: certConfig.value,
            certificate_number_format: "BPVP-PANGKEP/LMS/{YEAR}/{ID}",
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                isSavingCert.value = false;
                if (previewWindow) {
                    previewWindow.location.href = `/admin/lms/${props.course.id}/certificate/preview?t=${Date.now()}`;
                }
            },
            onError: () => {
                isSavingCert.value = false;
                if (previewWindow) {
                    previewWindow.close();
                }
            },
        },
    );
};

const uploadTemplateImage = (e) => {
    const file = e.target.files[0];
    if (!file) return;

    const data = new FormData();
    data.append("template_image", file);

    router.post(`/admin/lms/${props.course.id}/certificate/template`, data, {
        preserveScroll: true,
    });
};
</script>

<template>
    <DashboardLayout>
        <Head :title="`Kelola: ${course.title} - LMS BPVP Pangkep`" />

        <div class="space-y-6 pb-16">
            <!-- Workspace Sticky Top Header -->
            <div
                class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm"
            >
                <div
                    class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4"
                >
                    <div class="flex items-start gap-3">
                        <Link
                            href="/admin/lms"
                            class="p-2 text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors mt-0.5"
                            title="Kembali ke Dashboard LMS"
                        >
                            <ArrowLeft class="w-5 h-5" />
                        </Link>
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <span
                                    v-if="course.batch_name"
                                    class="px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300"
                                >
                                    {{ course.batch_name }}
                                </span>
                                <span
                                    v-if="course.category"
                                    class="px-2 py-0.5 rounded text-xs font-semibold bg-indigo-50 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300"
                                >
                                    {{ course.category }}
                                </span>
                            </div>
                            <h1
                                class="text-xl md:text-2xl font-black text-slate-900 dark:text-white mt-1"
                            >
                                {{ course.title }}
                            </h1>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap items-center gap-1.5"
                            >
                                <span
                                    >Instruktur:
                                    <strong
                                        class="text-slate-700 dark:text-slate-200"
                                        >{{
                                            course.instructor_name || "-"
                                        }}</strong
                                    ></span
                                >
                                <span class="text-slate-300 dark:text-slate-600"
                                    >&bull;</span
                                >
                                <span class="flex items-center gap-1">
                                    <Calendar
                                        class="w-3.5 h-3.5 text-indigo-500"
                                    />
                                    <span>Durasi:</span>
                                    <strong
                                        class="text-slate-700 dark:text-slate-200 font-semibold"
                                    >
                                        {{ formatDateIndo(course.start_date) }}
                                        s/d
                                        {{ formatDateIndo(course.end_date) }}
                                    </strong>
                                </span>
                                <span
                                    v-if="
                                        calculateDurationDays(
                                            course.start_date,
                                            course.end_date,
                                        )
                                    "
                                    class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-indigo-50 dark:bg-indigo-950/70 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800"
                                >
                                    {{
                                        calculateDurationDays(
                                            course.start_date,
                                            course.end_date,
                                        )
                                    }}
                                    Hari Pelatihan
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Header Action Bar (Paling Kanan: Status Kelas, Mulai Pelatihan & Buka Absen) -->
                    <div class="flex flex-wrap items-center gap-2.5">
                        <!-- Status Kelas Badge -->
                        <div
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border text-xs font-black shadow-sm tracking-wide"
                            :class="
                                course.status === 'published'
                                    ? 'bg-emerald-50 dark:bg-emerald-950/60 border-emerald-300 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300'
                                    : course.status === 'draft'
                                      ? 'bg-amber-50 dark:bg-amber-950/60 border-amber-300 dark:border-amber-800 text-amber-700 dark:text-amber-300'
                                      : 'bg-slate-100 dark:bg-slate-800 border-slate-300 dark:border-slate-700 text-slate-600 dark:text-slate-400'
                            "
                        >
                            <span
                                class="w-2.5 h-2.5 rounded-full"
                                :class="
                                    course.status === 'published'
                                        ? 'bg-emerald-500 animate-pulse'
                                        : course.status === 'draft'
                                          ? 'bg-amber-500'
                                          : 'bg-slate-400'
                                "
                            ></span>
                            <span
                                >STATUS:
                                {{
                                    course.status === "published"
                                        ? "TAYANG"
                                        : course.status === "draft"
                                          ? "DRAFT"
                                          : "ARSIP"
                                }}</span
                            >
                        </div>

                        <!-- Tombol Mulai Pelatihan (Jika Posisi Draft) -->
                        <button
                            v-if="course.status === 'draft'"
                            type="button"
                            @click="openPreflightChecklist"
                            class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-xs sm:text-sm font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/30 transition transform active:scale-95"
                            title="Periksa seluruh jadwal dan aktifkan/tayangkan pelatihan untuk siswa"
                        >
                            <Play class="w-4 h-4 fill-white" />
                            <span>Mulai Pelatihan</span>
                        </button>

                        <!-- Tombol Ubah ke Draft (Jika Admin ingin menghentikan tayang) -->
                        <button
                            v-else-if="course.status === 'published'"
                            type="button"
                            @click="unpublishToDraft"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/40 border border-slate-200 dark:border-slate-700 transition"
                            title="Tarik kembali kelas ke DRAFT jika perlu revisi (siswa tidak dapat mengakses)"
                        >
                            <span>Ubah ke Draft</span>
                        </button>
                    </div>
                </div>

                <!-- 3 Tabs Navigation Bar -->
                <div
                    class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 mt-6 -mb-5 overflow-x-auto"
                >
                    <button
                        @click="activeTab = 'materi'"
                        class="flex items-center gap-2 py-3 px-4 font-bold text-sm border-b-2 transition-all whitespace-nowrap"
                        :class="
                            activeTab === 'materi'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
                        "
                    >
                        <BookOpen class="w-4 h-4" />
                        TAB 1: Unit Kompetensi & Pengaturan Jadwal
                        <span
                            class="ml-1 text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 font-semibold"
                        >
                            {{ metrics.total_modules }} Unit /
                            {{ metrics.total_lessons }} Elemen
                        </span>
                        <span
                            v-if="course.is_zoom_attendance_open"
                            class="ml-1 text-xs px-2 py-0.5 rounded-full bg-rose-500 text-white font-bold animate-pulse"
                        >
                            ABSEN ONLINE MEETING ON
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'peserta'"
                        class="flex items-center gap-2 py-3 px-4 font-bold text-sm border-b-2 transition-all whitespace-nowrap"
                        :class="
                            activeTab === 'peserta'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
                        "
                    >
                        <Users class="w-4 h-4" />
                        TAB 2: Peserta & Monitoring Presensi
                        <span
                            class="ml-1 text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 font-semibold"
                        >
                            {{ metrics.total_students }} Orang
                        </span>
                    </button>

                    <button
                        @click="activeTab = 'sertifikat'"
                        class="flex items-center gap-2 py-3 px-4 font-bold text-sm border-b-2 transition-all whitespace-nowrap"
                        :class="
                            activeTab === 'sertifikat'
                                ? 'border-indigo-600 text-indigo-600 dark:text-indigo-400'
                                : 'border-transparent text-slate-500 hover:text-slate-800 dark:hover:text-slate-300'
                        "
                    >
                        <Award class="w-4 h-4" />
                        TAB 3: Pengaturan Sertifikat A4 & TTE
                    </button>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 1: UNIT KOMPETENSI & PENGATURAN JADWAL               -->
            <!-- ======================================================== -->
            <div v-if="activeTab === 'materi'" class="space-y-6">
                <!-- Dropdown Filter & Kontrol Jadwal Harian Bar -->
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl p-5 shadow-sm"
                >
                    <div
                        class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="p-2.5 bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 rounded-xl"
                            >
                                <Calendar class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <span
                                        class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400"
                                    >
                                        Jadwal Harian Pelatihan
                                    </span>
                                    <span
                                        class="text-xs px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold"
                                    >
                                        WITA Hari Ini: {{ formatDateIndo(todayWita) }}
                                        <template v-if="todayDayNumber"> (Hari ke-{{ todayDayNumber }})</template>
                                        <template v-else-if="courseStatusRelative"> &bull; {{ courseStatusRelative }}</template>
                                    </span>
                                    <span
                                        v-if="
                                            calculateDurationDays(
                                                course.start_date,
                                                course.end_date,
                                            )
                                        "
                                        class="text-xs px-2 py-0.5 rounded-full bg-indigo-100 dark:bg-indigo-950/70 text-indigo-800 dark:text-indigo-300 font-bold"
                                    >
                                        {{
                                            calculateDurationDays(
                                                course.start_date,
                                                course.end_date,
                                            )
                                        }}
                                        Hari Pelatihan
                                    </span>
                                </div>
                                <h3
                                    class="text-base font-bold text-slate-900 dark:text-white mt-0.5"
                                >
                                    Pilih Jadwal Unit Kompetensi Berdasarkan
                                    Hari
                                </h3>
                            </div>
                        </div>

                        <!-- Dropdown Filter Selector & Quick Actions -->
                        <div class="flex flex-wrap items-center gap-2.5">
                            <div
                                class="relative min-w-[260px] sm:min-w-[310px]"
                            >
                                <select
                                    v-model="selectedScheduleFilter"
                                    class="w-full text-xs font-bold py-2.5 pl-3 pr-8 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-800 dark:text-slate-200 shadow-sm focus:ring-2 focus:ring-indigo-500 cursor-pointer"
                                >
                                    <option value="all">
                                        📅 Tampilkan Semua Hari ({{ distinctDays.length }} Hari &bull; {{ course.modules?.length || 0 }} Unit)
                                    </option>
                                    <option v-if="todayDayNumber" value="today">
                                        🌟 Hari Ini (Hari ke-{{
                                            todayDayNumber
                                        }}
                                        &bull; {{ formatDateIndo(todayWita) }})
                                    </option>
                                    <optgroup label="Pilih Hari Pelatihan:">
                                        <option
                                            v-for="d in distinctDays"
                                            :key="d"
                                            :value="'day_' + d"
                                        >
                                            Hari ke-{{ d }} &bull; {{ formatDateIndo(getDateForDayNumber(d)) }} ({{
                                                countUnitsForDay(d)
                                            }}
                                            Unit Kompetensi)
                                        </option>
                                    </optgroup>
                                </select>
                            </div>

                            <button
                                @click="openAddModuleModal"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
                                title="Tambah Unit Kompetensi Baru"
                            >
                                <Plus class="w-4 h-4" />
                                <span>+ Unit Baru</span>
                            </button>
                            <button
                                @click="showCurriculumImportModal = true"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
                                title="Import Kurikulum dari Excel / CSV"
                            >
                                <FileSpreadsheet class="w-4 h-4" />
                                <span>Import Excel</span>
                            </button>
                            <a
                                :href="`/admin/lms/${course.id}/curriculum/template`"
                                class="inline-flex items-center gap-1.5 px-3.5 py-2.5 bg-slate-800 hover:bg-slate-900 text-white dark:bg-slate-700 dark:hover:bg-slate-600 rounded-xl text-xs font-bold shadow-sm transition"
                                title="Download template import file kurikulum Excel/CSV"
                            >
                                <Download class="w-4 h-4" />
                                <span>Template</span>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Notice: When Today is Selected but no modules match today -->
                <div
                    v-if="
                        selectedScheduleFilter === 'today' &&
                        groupedModulesByDay.length === 0
                    "
                    class="bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-900/60 rounded-2xl p-6 text-center space-y-3"
                >
                    <Clock class="w-10 h-10 mx-auto text-amber-500" />
                    <div>
                        <h4
                            class="text-sm font-bold text-amber-900 dark:text-amber-200"
                        >
                            Tidak Ada Unit Kompetensi Terjadwal Hari Ini
                            <template v-if="todayDayNumber"> (Hari ke-{{ todayDayNumber }})</template>
                        </h4>
                        <p
                            class="text-xs text-amber-700 dark:text-amber-300 mt-1 max-w-lg mx-auto"
                        >
                            Hari ini adalah {{ formatDateIndo(todayWita) }}.
                            <template v-if="todayDayNumber">
                                Belum ada unit kompetensi yang diset untuk Hari ke-{{ todayDayNumber }}.
                            </template>
                            <template v-else-if="courseStatusRelative">
                                Status kelas: {{ courseStatusRelative }}.
                            </template>
                            Anda dapat melihat unit di hari lain melalui dropdown di atas atau klik tombol berikut untuk melihat seluruh unit.
                        </p>
                    </div>
                    <div class="flex items-center justify-center gap-2 pt-1">
                        <button
                            type="button"
                            @click="selectedScheduleFilter = 'all'"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-bold shadow-sm transition"
                        >
                            Tampilkan Semua Hari / Semua Unit
                        </button>
                        <button
                            type="button"
                            @click="openAddModuleModal"
                            class="px-4 py-2 bg-white dark:bg-slate-800 border border-amber-300 dark:border-amber-700 text-amber-800 dark:text-amber-200 rounded-xl text-xs font-bold hover:bg-amber-100 transition"
                        >
                            + Tambah Unit untuk Hari ke-{{ todayDayNumber || 1 }}
                        </button>
                    </div>
                </div>

            <!-- Grouped by Day (Filtered by Dropdown) -->
                <div v-if="groupedModulesByDay.length > 0" class="space-y-8">
                    <div
                        v-for="dayGroup in groupedModulesByDay"
                        :key="dayGroup.day_number"
                        class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-2xl shadow-sm overflow-hidden"
                    >
                        <!-- Day Group Header -->
                        <div
                            class="bg-slate-50/90 dark:bg-slate-800/60 px-5 md:px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex flex-col md:flex-row md:items-center justify-between gap-3"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    class="px-3.5 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-black tracking-wider shadow-sm flex items-center gap-1.5"
                                >
                                    <Calendar class="w-3.5 h-3.5" />
                                    <span>HARI KE-{{ dayGroup.day_number }}</span>
                                </span>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3
                                            class="text-sm font-bold text-slate-900 dark:text-white"
                                        >
                                            Kegiatan Pelatihan Hari Ke-{{ dayGroup.day_number }}
                                        </h3>
                                        <span
                                            v-if="dayGroup.scheduled_date || getDateForDayNumber(dayGroup.day_number)"
                                            class="text-xs text-slate-500 dark:text-slate-400 font-medium"
                                        >
                                            &bull; {{ formatDateIndo(dayGroup.scheduled_date || getDateForDayNumber(dayGroup.day_number)) }}
                                        </span>
                                    </div>
                                    <p
                                        class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5"
                                    >
                                        Memuat {{ dayGroup.modules.length }} Unit Kompetensi &bull;
                                        <span
                                            :class="
                                                dayGroup.hasSinkronus
                                                    ? 'text-blue-600 dark:text-blue-400 font-bold'
                                                    : 'text-emerald-600 dark:text-emerald-400 font-bold'
                                            "
                                        >
                                            {{
                                                dayGroup.hasSinkronus
                                                    ? 'Tatap Muka Online (Sinkronus)'
                                                    : 'Belajar Mandiri (Asinkronus)'
                                            }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- One Shared Zoom & Attendance Setting Panel per Day (if day has Sinkronus) -->
                        <div
                            v-if="dayGroup.hasSinkronus"
                            class="p-5 md:p-6 border-b border-slate-200 dark:border-slate-800 bg-slate-50/40 dark:bg-slate-900/40"
                        >
                            <template
                                v-for="mod in [dayGroup.primaryModule]"
                                :key="mod.id"
                            >
                                <!-- Per-Unit Zoom & Attendance Panel (For Sinkronus Units) -->
                        <div
                            v-if="mod.delivery_mode === 'sinkronus'"
                            class="grid grid-cols-1 lg:grid-cols-2 gap-5 pt-1"
                        >
                            <!-- Left: Jadwal & Akses Zoom Sesi Unit Ini -->
                            <div
                                class="rounded-xl p-4 border transition-all space-y-3 flex flex-col justify-between"
                                :class="
                                    isUnitZoomLive(mod)
                                        ? 'bg-rose-50 border-rose-300 dark:bg-rose-950/30 dark:border-rose-900/60'
                                        : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700/60'
                                "
                            >
                                <div
                                    class="flex items-center justify-between border-b pb-2.5"
                                    :class="
                                        isUnitZoomLive(mod)
                                            ? 'border-rose-200 dark:border-rose-900/60'
                                            : 'border-slate-200 dark:border-slate-700/60'
                                    "
                                >
                                    <div class="flex items-center gap-2">
                                        <Video
                                            class="w-4 h-4"
                                            :class="
                                                isUnitZoomLive(mod)
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-blue-600 dark:text-blue-400'
                                            "
                                        />
                                        <h4
                                            class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Jadwal & Tautan Online Meeting Hari Ke-{{ dayGroup.day_number }}
                                        </h4>
                                    </div>
                                    <!-- Zoom Status Badge -->
                                    <span
                                        v-if="isUnitZoomLive(mod)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white animate-pulse shadow-sm"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-white animate-ping"
                                        ></span>
                                        LIVE SEKARANG &bull; Sisa:
                                        {{
                                            formatTimeRemaining(
                                                unitZoomTimers[mod.id] || 0,
                                            )
                                        }}
                                    </span>
                                    <span
                                        v-else-if="
                                            mod.zoom_status === 'upcoming'
                                        "
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                    >
                                        DIJADWALKAN
                                    </span>
                                    <span
                                        v-else-if="mod.zoom_status === 'ended'"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        BERAKHIR
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                    >
                                        Belum Dijadwalkan
                                    </span>
                                </div>

                                <!-- IF ONLINE MEETING IS CURRENTLY LIVE FOR THIS UNIT -->
                                <div
                                    v-if="isUnitZoomLive(mod)"
                                    class="space-y-3 bg-white dark:bg-slate-900/80 p-3.5 rounded-lg border border-rose-200 dark:border-rose-900/40"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-black text-rose-700 dark:text-rose-400 flex items-center gap-1.5"
                                            >
                                                <span
                                                    class="w-2 h-2 rounded-full bg-rose-600 animate-ping"
                                                ></span>
                                                Sesi Online Meeting Sedang
                                                Berjalan
                                            </p>
                                            <p
                                                class="text-[11px] text-slate-500"
                                            >
                                                Tautan tatap muka online aktif
                                                dan peserta dapat bergabung ke
                                                ruang meeting.
                                            </p>
                                        </div>
                                        <span
                                            class="text-lg font-black font-mono text-rose-600"
                                        >
                                            {{
                                                formatTimeRemaining(
                                                    unitZoomTimers[mod.id] || 0,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-rose-100 dark:border-rose-900/30"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <a
                                                v-if="
                                                    unitZoomForms[mod.id]
                                                        ?.zoom_link ||
                                                    mod.zoom_link
                                                "
                                                :href="
                                                    unitZoomForms[mod.id]
                                                        ?.zoom_link ||
                                                    mod.zoom_link
                                                "
                                                target="_blank"
                                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-sm transition inline-flex items-center gap-1.5"
                                            >
                                                <ExternalLink
                                                    class="w-3.5 h-3.5"
                                                />
                                                <span
                                                    >Masuk / Buka Meeting</span
                                                >
                                            </a>
                                            <button
                                                type="button"
                                                @click="
                                                    isEditingZoomLive[mod.id] =
                                                        !isEditingZoomLive[
                                                            mod.id
                                                        ]
                                                "
                                                class="px-2.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg text-xs font-semibold text-slate-600 dark:text-slate-300 transition inline-flex items-center gap-1"
                                            >
                                                <Pencil class="w-3 h-3" />
                                                <span>{{
                                                    isEditingZoomLive[mod.id]
                                                        ? "Tutup Edit"
                                                        : "Ubah Tautan / Jam"
                                                }}</span>
                                            </button>
                                        </div>

                                        <button
                                            type="button"
                                            @click="endUnitZoomNow(mod)"
                                            :disabled="isEndingUnitZoom[mod.id]"
                                            class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-black transition disabled:opacity-50 flex items-center gap-1.5 shadow-sm"
                                            title="Akhiri sesi Online Meeting unit ini"
                                        >
                                            <StopCircle class="w-3.5 h-3.5" />
                                            <span>AKHIRI MEETING SEKARANG</span>
                                        </button>
                                    </div>

                                    <!-- Collapsible Edit Form during Live if instructor needs to change link -->
                                    <div
                                        v-if="isEditingZoomLive[mod.id]"
                                        class="pt-2 border-t border-slate-200 dark:border-slate-700/60 space-y-2 text-xs"
                                    >
                                        <div
                                            class="grid grid-cols-1 sm:grid-cols-2 gap-2"
                                        >
                                            <div>
                                                <label
                                                    class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                    >Tanggal</label
                                                >
                                                <input
                                                    v-if="unitZoomForms[mod.id]"
                                                    v-model="
                                                        unitZoomForms[mod.id]
                                                            .scheduled_date
                                                    "
                                                    type="date"
                                                    class="w-full text-xs px-2 py-1 border rounded bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                                />
                                            </div>
                                            <div class="grid grid-cols-2 gap-1">
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                        >Mulai</label
                                                    >
                                                    <input
                                                        v-if="
                                                            unitZoomForms[
                                                                mod.id
                                                            ]
                                                        "
                                                        v-model="
                                                            unitZoomForms[
                                                                mod.id
                                                            ].start_time
                                                        "
                                                        type="text"
                                                        class="w-full text-xs px-2 py-1 border rounded bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                                                    />
                                                </div>
                                                <div>
                                                    <label
                                                        class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                        >Selesai</label
                                                    >
                                                    <input
                                                        v-if="
                                                            unitZoomForms[
                                                                mod.id
                                                            ]
                                                        "
                                                        v-model="
                                                            unitZoomForms[
                                                                mod.id
                                                            ].end_time
                                                        "
                                                        type="text"
                                                        class="w-full text-xs px-2 py-1 border rounded bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                >Link Meeting</label
                                            >
                                            <input
                                                v-if="unitZoomForms[mod.id]"
                                                v-model="
                                                    unitZoomForms[mod.id]
                                                        .zoom_link
                                                "
                                                type="text"
                                                class="w-full text-xs px-2 py-1 border rounded bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                            />
                                        </div>
                                        <button
                                            type="button"
                                            @click="submitUnitZoomSchedule(mod)"
                                            :disabled="
                                                unitZoomForms[mod.id]?.isSaving
                                            "
                                            class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded text-xs font-bold transition flex items-center gap-1"
                                        >
                                            <Save class="w-3 h-3" />
                                            <span>Simpan Perubahan</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Centered Closed State when Ended -->
                                <div
                                    v-else-if="
                                        mod.zoom_status === 'ended' &&
                                        !isEditingZoomEnded[mod.id]
                                    "
                                    @click="isEditingZoomEnded[mod.id] = true"
                                    class="my-auto py-8 px-4 flex flex-col items-center justify-center text-center rounded-xl bg-slate-100/70 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700 cursor-pointer hover:bg-blue-50/50 dark:hover:bg-blue-950/20 hover:border-blue-400 dark:hover:border-blue-500 transition group select-none"
                                    title="Klik untuk membuka sesi susulan / ubah jadwal"
                                >
                                    <div
                                        class="w-12 h-12 rounded-full bg-slate-200/80 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 mb-2 group-hover:scale-110 group-hover:bg-blue-100 dark:group-hover:bg-blue-900/40 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition shadow-sm"
                                    >
                                        <Video class="w-6 h-6" />
                                    </div>
                                    <h5
                                        class="text-xs font-black text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition"
                                    >
                                        Sesi Online Meeting Sudah Berakhir
                                    </h5>
                                    <p
                                        class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 max-w-xs leading-relaxed"
                                    >
                                        Sesi tatap muka online untuk unit ini
                                        telah diselesaikan. Peserta yang belum
                                        hadir diarahkan ke belajar mandiri.
                                    </p>
                                    <div
                                        class="mt-3.5 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-blue-600 dark:text-blue-400 shadow-sm group-hover:bg-blue-600 group-hover:text-white group-hover:border-blue-600 transition"
                                    >
                                        <RefreshCw
                                            class="w-3.5 h-3.5 group-hover:rotate-180 transition duration-500"
                                        />
                                        <span
                                            >Klik di Sini Jika Ingin Sesi
                                            Susulan / Ubah Jadwal</span
                                        >
                                    </div>
                                </div>

                                <!-- Form when Active / Upcoming / Editing Susulan -->
                                <div v-else class="space-y-2.5 text-xs">
                                    <!-- Banner if in Susulan Mode -->
                                    <div
                                        v-if="
                                            mod.zoom_status === 'ended' &&
                                            isEditingZoomEnded[mod.id]
                                        "
                                        class="flex items-center justify-between px-3 py-1.5 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900 rounded-lg text-xs"
                                    >
                                        <span
                                            class="font-bold text-blue-700 dark:text-blue-300 flex items-center gap-1.5 text-[11px]"
                                        >
                                            <RefreshCw class="w-3.5 h-3.5" />
                                            Mode Sesi Susulan / Ubah Jadwal
                                        </span>
                                        <button
                                            type="button"
                                            @click.stop="
                                                isEditingZoomEnded[mod.id] =
                                                    false
                                            "
                                            class="px-2 py-0.5 text-[11px] font-bold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800 rounded transition"
                                        >
                                            &times; Tutup
                                        </button>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-2.5"
                                    >
                                        <div>
                                            <label
                                                class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                >Tanggal Online Meeting</label
                                            >
                                            <input
                                                v-if="unitZoomForms[mod.id]"
                                                v-model="
                                                    unitZoomForms[mod.id]
                                                        .scheduled_date
                                                "
                                                type="date"
                                                class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-medium"
                                            />
                                        </div>
                                        <div class="grid grid-cols-2 gap-1.5">
                                            <div>
                                                <label
                                                    class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                    >Jam Mulai</label
                                                >
                                                <input
                                                    v-if="unitZoomForms[mod.id]"
                                                    v-model="
                                                        unitZoomForms[mod.id]
                                                            .start_time
                                                    "
                                                    type="text"
                                                    placeholder="22:50"
                                                    class="w-full text-xs px-2 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                    >Jam Selesai</label
                                                >
                                                <input
                                                    v-if="unitZoomForms[mod.id]"
                                                    v-model="
                                                        unitZoomForms[mod.id]
                                                            .end_time
                                                    "
                                                    type="text"
                                                    placeholder="22:55"
                                                    class="w-full text-xs px-2 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <label
                                            class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                            >Link Online Meeting Tatap
                                            Muka</label
                                        >
                                        <input
                                            v-if="unitZoomForms[mod.id]"
                                            v-model="
                                                unitZoomForms[mod.id].zoom_link
                                            "
                                            type="text"
                                            placeholder="Contoh: https://meet.google.com/... atau https://zoom.us/j/..."
                                            class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                        />
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label
                                                class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                >Meeting ID (Opsional)</label
                                            >
                                            <input
                                                v-if="unitZoomForms[mod.id]"
                                                v-model="
                                                    unitZoomForms[mod.id]
                                                        .zoom_meeting_id
                                                "
                                                type="text"
                                                placeholder="Contoh: 832 9481 0291"
                                                class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="block text-[10px] font-semibold text-slate-500 mb-0.5"
                                                >Passcode (Opsional)</label
                                            >
                                            <input
                                                v-if="unitZoomForms[mod.id]"
                                                v-model="
                                                    unitZoomForms[mod.id]
                                                        .zoom_passcode
                                                "
                                                type="text"
                                                placeholder="Contoh: 123456"
                                                class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                                            />
                                        </div>
                                    </div>

                                    <div
                                        class="flex flex-wrap items-center justify-between gap-2 pt-1 border-t border-slate-200 dark:border-slate-700/60"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="
                                                    submitUnitZoomSchedule(mod)
                                                "
                                                :disabled="
                                                    unitZoomForms[mod.id]
                                                        ?.isSaving
                                                "
                                                class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 flex items-center gap-1"
                                            >
                                                <Save class="w-3.5 h-3.5" />
                                                <span>{{
                                                    unitZoomForms[mod.id]
                                                        ?.isSaving
                                                        ? "Menyimpan..."
                                                        : "Simpan Link & Jam"
                                                }}</span>
                                            </button>
                                            <a
                                                v-if="
                                                    unitZoomForms[mod.id]
                                                        ?.zoom_link ||
                                                    mod.zoom_link
                                                "
                                                :href="
                                                    unitZoomForms[mod.id]
                                                        ?.zoom_link ||
                                                    mod.zoom_link
                                                "
                                                target="_blank"
                                                class="px-2.5 py-1.5 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 rounded-lg text-xs font-medium text-slate-600 dark:text-slate-300 transition inline-flex items-center gap-1"
                                            >
                                                <ExternalLink class="w-3 h-3" />
                                                <span>Uji</span>
                                            </a>
                                        </div>

                                        <div class="flex items-center gap-1.5">
                                            <button
                                                type="button"
                                                @click="startUnitZoomNow(mod)"
                                                :disabled="
                                                    isStartingUnitZoom[mod.id]
                                                "
                                                class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-bold shadow-sm transition disabled:opacity-50 flex items-center gap-1"
                                                title="Langsung mulai sesi Online Meeting unit ini (status LIVE)"
                                            >
                                                <Play class="w-3 h-3" />
                                                <span
                                                    >Mulai Online Meeting</span
                                                >
                                            </button>
                                            <button
                                                type="button"
                                                @click="endUnitZoomNow(mod)"
                                                :disabled="
                                                    isEndingUnitZoom[mod.id]
                                                "
                                                class="px-2.5 py-1.5 bg-slate-700 hover:bg-slate-800 text-white rounded-lg text-xs font-bold transition disabled:opacity-50"
                                                title="Akhiri sesi Online Meeting unit ini"
                                            >
                                                <StopCircle class="w-3 h-3" />
                                                <span>Akhiri Meeting</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right: Kontrol Jendela Presensi / Absensi Siswa Unit Ini -->
                            <div
                                class="rounded-xl p-4 border transition-all space-y-3 flex flex-col justify-between"
                                :class="
                                    isUnitAttendanceActive(mod)
                                        ? 'bg-rose-50 border-rose-300 dark:bg-rose-950/30 dark:border-rose-900/60'
                                        : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700/60'
                                "
                            >
                                <div
                                    class="flex items-center justify-between border-b pb-2.5"
                                    :class="
                                        isUnitAttendanceActive(mod)
                                            ? 'border-rose-200 dark:border-rose-900/60'
                                            : 'border-slate-200 dark:border-slate-700/60'
                                    "
                                >
                                    <div class="flex items-center gap-2">
                                        <CheckCircle2
                                            class="w-4 h-4"
                                            :class="
                                                isUnitAttendanceActive(mod)
                                                    ? 'text-rose-600 dark:text-rose-400'
                                                    : 'text-emerald-600 dark:text-emerald-400'
                                            "
                                        />
                                        <h4
                                            class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Kontrol Presensi / Absen Online Hari Ke-{{ dayGroup.day_number }}
                                        </h4>
                                    </div>

                                    <!-- Attendance Live Status Badge -->
                                    <span
                                        v-if="isUnitAttendanceActive(mod)"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black bg-rose-600 text-white animate-pulse shadow-sm"
                                    >
                                        <span
                                            class="w-1.5 h-1.5 rounded-full bg-white animate-ping"
                                        ></span>
                                        ABSEN DIBUKA &bull; Sisa:
                                        {{
                                            formatTimeRemaining(
                                                unitAttendanceTimers[mod.id] ||
                                                    0,
                                            )
                                        }}
                                    </span>
                                    <span
                                        v-else-if="isUnitAttendanceEnded(mod)"
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-200 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                    >
                                        ABSEN DITUTUP
                                    </span>
                                    <span
                                        v-else-if="
                                            mod.zoom_attendance_scheduled_at
                                        "
                                        class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                                    >
                                        DIJADWALKAN
                                    </span>
                                    <span
                                        v-else
                                        class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                                    >
                                        Belum Dibuka
                                    </span>
                                </div>

                                <!-- IF ATTENDANCE IS CURRENTLY OPEN FOR THIS UNIT -->
                                <div
                                    v-if="isUnitAttendanceActive(mod)"
                                    class="space-y-3 bg-white dark:bg-slate-900/80 p-3.5 rounded-lg border border-rose-200 dark:border-rose-900/40"
                                >
                                    <div
                                        class="flex items-center justify-between gap-2"
                                    >
                                        <div>
                                            <p
                                                class="text-xs font-black text-rose-700 dark:text-rose-400 flex items-center gap-1.5"
                                            >
                                                <span
                                                    class="w-2 h-2 rounded-full bg-rose-600 animate-ping"
                                                ></span>
                                                Sesi Absen Unit Sedang Berjalan
                                            </p>
                                            <p
                                                class="text-[11px] text-slate-500"
                                            >
                                                Tombol presensi aktif di ruang
                                                kelas seluruh siswa untuk unit
                                                ini.
                                            </p>
                                        </div>
                                        <span
                                            class="text-lg font-black font-mono text-rose-600"
                                        >
                                            {{
                                                formatTimeRemaining(
                                                    unitAttendanceTimers[
                                                        mod.id
                                                    ] || 0,
                                                )
                                            }}
                                        </span>
                                    </div>

                                    <div
                                        class="flex items-center gap-2 pt-1 border-t border-rose-100 dark:border-rose-900/30"
                                    >
                                        <button
                                            type="button"
                                            @click="
                                                extendUnitAttendance(mod, 15)
                                            "
                                            :disabled="
                                                isOpeningUnitAttendance[mod.id]
                                            "
                                            class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 flex items-center gap-1 shadow-sm"
                                        >
                                            <Plus class="w-3.5 h-3.5" />
                                            <span>+15 Mnt</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="closeUnitAttendanceNow(mod)"
                                            :disabled="
                                                isOpeningUnitAttendance[mod.id]
                                            "
                                            class="flex-1 px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-xs font-black transition disabled:opacity-50 flex items-center justify-center gap-1.5 shadow-sm"
                                        >
                                            <StopCircle class="w-3.5 h-3.5" />
                                            <span
                                                >TUTUP SESI ABSEN SEKARANG</span
                                            >
                                        </button>
                                    </div>
                                </div>

                                <!-- Centered Closed State when Attendance Ended -->
                                <div
                                    v-else-if="
                                        isUnitAttendanceEnded(mod) &&
                                        !isOpeningAttendanceSusulan[mod.id]
                                    "
                                    @click="
                                        isOpeningAttendanceSusulan[mod.id] =
                                            true
                                    "
                                    class="my-auto py-8 px-4 flex flex-col items-center justify-center text-center rounded-xl bg-slate-100/70 dark:bg-slate-900/40 border border-dashed border-slate-300 dark:border-slate-700 cursor-pointer hover:bg-emerald-50/50 dark:hover:bg-emerald-950/20 hover:border-emerald-400 dark:hover:border-emerald-500 transition group select-none"
                                    title="Klik untuk membuka sesi presensi susulan"
                                >
                                    <div
                                        class="w-12 h-12 rounded-full bg-slate-200/80 dark:bg-slate-800 flex items-center justify-center text-slate-500 dark:text-slate-400 mb-2 group-hover:scale-110 group-hover:bg-emerald-100 dark:group-hover:bg-emerald-900/40 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition shadow-sm"
                                    >
                                        <CheckCircle2 class="w-6 h-6" />
                                    </div>
                                    <h5
                                        class="text-xs font-black text-slate-800 dark:text-slate-200 group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition"
                                    >
                                        Sesi Presensi Online Sudah Berakhir /
                                        Ditutup
                                    </h5>
                                    <p
                                        class="text-[11px] text-slate-500 dark:text-slate-400 mt-1 max-w-xs leading-relaxed"
                                    >
                                        Jendela presensi online untuk unit ini
                                        telah ditutup. Peserta terlambat yang
                                        belum presensi dapat diarahkan presensi
                                        susulan atau belajar mandiri.
                                    </p>
                                    <div
                                        class="mt-3.5 inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-xs font-bold text-emerald-600 dark:text-emerald-400 shadow-sm group-hover:bg-emerald-600 group-hover:text-white group-hover:border-emerald-600 transition"
                                    >
                                        <RefreshCw
                                            class="w-3.5 h-3.5 group-hover:rotate-180 transition duration-500"
                                        />
                                        <span
                                            >Klik di Sini Jika Ingin Buka
                                            Presensi Susulan</span
                                        >
                                    </div>
                                </div>

                                <!-- IF ATTENDANCE IS CLOSED: PRESETS & SCHEDULE -->
                                <div v-else class="space-y-2.5 text-xs">
                                    <!-- Banner if in Susulan Mode -->
                                    <div
                                        v-if="
                                            isUnitAttendanceEnded(mod) &&
                                            isOpeningAttendanceSusulan[mod.id]
                                        "
                                        class="flex items-center justify-between px-3 py-1.5 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-900 rounded-lg text-xs"
                                    >
                                        <span
                                            class="font-bold text-emerald-700 dark:text-emerald-300 flex items-center gap-1.5 text-[11px]"
                                        >
                                            <RefreshCw class="w-3.5 h-3.5" />
                                            Mode Presensi Susulan / Buka Ulang
                                        </span>
                                        <button
                                            type="button"
                                            @click.stop="
                                                isOpeningAttendanceSusulan[
                                                    mod.id
                                                ] = false
                                            "
                                            class="px-2 py-0.5 text-[11px] font-bold text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200 hover:bg-white dark:hover:bg-slate-800 rounded transition"
                                        >
                                            &times; Tutup
                                        </button>
                                    </div>

                                    <span
                                        class="text-[11px] font-bold text-slate-700 dark:text-slate-300 block"
                                    >
                                        ⚡ Pilih Durasi Sesi Absen Unit:
                                    </span>

                                    <div
                                        class="flex flex-wrap items-center gap-1.5"
                                    >
                                        <button
                                            v-for="mins in [15, 30, 45, 60]"
                                            :key="mins"
                                            type="button"
                                            @click="
                                                selectUnitAttendanceDuration(
                                                    mod,
                                                    mins,
                                                )
                                            "
                                            class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition border"
                                            :class="
                                                (unitSelectedDuration[mod.id] ||
                                                    30) === mins
                                                    ? 'bg-emerald-600 text-white border-emerald-600 shadow-sm ring-2 ring-emerald-500/20'
                                                    : 'bg-white dark:bg-slate-900 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-slate-100'
                                            "
                                        >
                                            {{ mins }} Mnt
                                        </button>

                                        <button
                                            type="button"
                                            @click="
                                                openUnitAttendanceWithDuration(
                                                    mod,
                                                    unitSelectedDuration[
                                                        mod.id
                                                    ] || 30,
                                                )
                                            "
                                            :disabled="
                                                isOpeningUnitAttendance[mod.id]
                                            "
                                            class="ml-auto px-4 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-black shadow-md shadow-emerald-600/30 transition transform active:scale-95 disabled:opacity-50 flex items-center gap-1.5"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" />
                                            <span
                                                >BUKA SEKARANG ({{
                                                    unitSelectedDuration[
                                                        mod.id
                                                    ] || 30
                                                }}
                                                MENIT)</span
                                            >
                                        </button>
                                    </div>

                                    <!-- Jadwalkan Buka Absen Otomatis Form -->
                                    <div
                                        class="pt-2 border-t border-slate-200 dark:border-slate-700/60"
                                    >
                                        <div
                                            class="flex items-center justify-between mb-1"
                                        >
                                            <label
                                                class="block text-[10px] font-semibold text-slate-500"
                                            >
                                                Atau Jadwalkan Jam Buka Absen
                                                Otomatis:
                                            </label>
                                            <span
                                                class="text-[10px] font-bold text-emerald-600 dark:text-emerald-400"
                                            >
                                                Durasi:
                                                {{
                                                    unitSelectedDuration[
                                                        mod.id
                                                    ] || 30
                                                }}
                                                Menit
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <input
                                                v-if="
                                                    unitScheduledAttendanceForm[
                                                        mod.id
                                                    ]
                                                "
                                                v-model="
                                                    unitScheduledAttendanceForm[
                                                        mod.id
                                                    ].scheduled_at
                                                "
                                                type="datetime-local"
                                                class="flex-1 text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                            />
                                            <button
                                                type="button"
                                                @click="
                                                    submitUnitScheduleAttendance(
                                                        mod,
                                                    )
                                                "
                                                class="px-3 py-1.5 bg-slate-800 hover:bg-slate-900 text-white dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm shrink-0"
                                                title="Jadwalkan jam buka absen otomatis"
                                            >
                                                <Clock class="w-3.5 h-3.5" />
                                                <span
                                                    >Jadwalkan ({{
                                                        unitSelectedDuration[
                                                            mod.id
                                                        ] || 30
                                                    }}
                                                    Mnt)</span
                                                >
                                            </button>
                                        </div>
                                        <p
                                            v-if="
                                                mod.zoom_attendance_scheduled_at
                                            "
                                            class="text-[10px] text-indigo-600 dark:text-indigo-400 font-semibold mt-1"
                                        >
                                            Tersimpan: Absen unit ini otomatis
                                            dibuka pada
                                            {{
                                                formatReadableDate(
                                                    mod.zoom_attendance_scheduled_at,
                                                )
                                            }}
                                            selama
                                            {{
                                                mod.zoom_attendance_duration_minutes ||
                                                30
                                            }}
                                            menit.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                            </template>
                        </div>

                        <!-- Info Banner when entire Day is Asinkronus -->
                        <div
                            v-else
                            class="p-4 mx-5 md:mx-6 my-4 bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200/80 dark:border-emerald-900/40 rounded-xl flex items-center gap-3"
                        >
                            <BookOpen class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0" />
                            <div class="text-xs text-emerald-800 dark:text-emerald-300">
                                <span class="font-bold">Hari Pembelajaran Mandiri (Asinkronus):</span>
                                Seluruh unit kompetensi pada hari ini diselesaikan oleh peserta secara mandiri melalui materi dan evaluasi di bawah.
                            </div>
                        </div>

                        <!-- Unit Cards belonging to this Day -->
                        <div class="p-5 md:p-6 space-y-6 bg-white dark:bg-slate-900">
                            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800/80 pb-2.5">
                                <h4 class="text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-400 flex items-center gap-2">
                                    <Layers class="w-4 h-4 text-indigo-500" />
                                    <span>Daftar Unit Kompetensi Hari Ke-{{ dayGroup.day_number }} ({{ dayGroup.modules.length }} Unit)</span>
                                </h4>
                            </div>

                            <div
                                v-for="(mod, modIdx) in dayGroup.modules"
                                :key="mod.id"
                                class="bg-slate-50/60 dark:bg-slate-800/30 border border-slate-200 dark:border-slate-700/80 rounded-2xl p-5 md:p-6 space-y-5 transition shadow-xs hover:border-indigo-300 dark:hover:border-indigo-700"
                            >
                                <!-- Unit Header Bar -->
                        <div
                            class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 border-b border-slate-100 dark:border-slate-800/80 pb-4"
                        >
                            <div class="flex items-start gap-3">
                                <span
                                    class="px-3 py-1.5 rounded-xl bg-indigo-600 text-white text-xs font-black shrink-0 tracking-wider shadow-sm"
                                >
                                    Unit {{ getOverallModuleIndex(mod) }}
                                </span>
                                <div>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <span
                                            class="text-[10px] font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/60 px-2 py-0.5 rounded"
                                        >
                                            Unit Kompetensi {{ getOverallModuleIndex(mod) }}
                                        </span>
                                        <span
                                            v-if="
                                                mod.delivery_mode ===
                                                'sinkronus'
                                            "
                                            class="inline-flex items-center gap-1 text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800 px-2 py-0.5 rounded-full"
                                        >
                                            <Video
                                                class="w-3 h-3 text-blue-500"
                                            />
                                            <span
                                                >Sinkronus (Live Online
                                                Meeting)</span
                                            >
                                            <span v-if="mod.scheduled_date"
                                                >&bull;
                                                {{
                                                    formatDateIndo(
                                                        mod.scheduled_date,
                                                    )
                                                }}</span
                                            >
                                            <span
                                                v-if="
                                                    mod.start_time &&
                                                    mod.end_time
                                                "
                                                >({{ mod.start_time }} -
                                                {{ mod.end_time }})</span
                                            >
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 border border-emerald-200 dark:border-emerald-800 px-2 py-0.5 rounded-full"
                                        >
                                            <BookOpen
                                                class="w-3 h-3 text-emerald-500"
                                            />
                                            <span
                                                >Asinkronus &bull;
                                                {{ mod.duration_days || 1 }}
                                                Hari</span
                                            >
                                        </span>
                                        <span
                                            v-if="
                                                (mod.quizzes?.length ||
                                                    (mod.quiz ? 1 : 0)) > 0
                                            "
                                            class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 border border-amber-200 dark:border-amber-800 px-2 py-0.5 rounded-full"
                                        >
                                            <HelpCircle
                                                class="w-3 h-3 text-amber-500"
                                            />
                                            <span
                                                >{{
                                                    mod.quizzes?.length || 1
                                                }}
                                                Kuis</span
                                            >
                                        </span>
                                        <span
                                            v-else
                                            class="text-[10px] font-medium text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full"
                                        >
                                            Tanpa Kuis
                                        </span>
                                    </div>
                                    <h3
                                        class="text-base font-bold text-slate-900 dark:text-white mt-1"
                                    >
                                        {{ mod.title }}
                                    </h3>
                                    <p
                                        v-if="mod.description"
                                        class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                                    >
                                        {{ mod.description }}
                                    </p>
                                </div>
                            </div>

                            <!-- Unit Actions: + Lesson, Quiz, Edit, Delete -->
                            <div
                                class="flex items-center gap-2 self-start lg:self-center flex-wrap"
                            >
                                <button
                                    type="button"
                                    @click="openAddLessonModal(mod.id)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 rounded-lg text-xs font-bold transition"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    <span>+ Elemen</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openQuizModal(mod, null)"
                                    class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-semibold transition bg-amber-50 hover:bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-200 dark:border-amber-800"
                                >
                                    <HelpCircle
                                        class="w-3.5 h-3.5 text-amber-500"
                                    />
                                    <span>+ Kuis</span>
                                </button>
                                <button
                                    type="button"
                                    @click="openEditModuleModal(mod)"
                                    class="p-1.5 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"
                                    title="Edit Unit Kompetensi"
                                >
                                    <Edit class="w-4 h-4" />
                                </button>
                                <button
                                    type="button"
                                    @click="deleteModule(mod)"
                                    :disabled="deletingModuleId === mod.id"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 rounded-lg transition disabled:opacity-50"
                                    title="Hapus Unit Kompetensi"
                                >
                                    <Loader2
                                        v-if="deletingModuleId === mod.id"
                                        class="w-4 h-4 animate-spin text-rose-600"
                                    />
                                    <Trash2 v-else class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                                <!-- Elemen Kompetensi (Lessons) List under this Unit -->
                        <div
                            class="border-t border-slate-100 dark:border-slate-800 pt-3 space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <h5
                                    class="text-xs font-bold uppercase tracking-wider text-slate-500"
                                >
                                    Elemen Kompetensi (Materi) &bull;
                                    {{ mod.lessons?.length || 0 }} Elemen
                                </h5>
                                <button
                                    type="button"
                                    @click="openAddLessonModal(mod.id)"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 hover:underline"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    <span>Tambah Elemen Kompetensi</span>
                                </button>
                            </div>

                            <div
                                v-if="mod.lessons && mod.lessons.length > 0"
                                class="divide-y divide-slate-100 dark:divide-slate-800/60 border border-slate-100 dark:border-slate-800 rounded-xl overflow-hidden bg-slate-50/50 dark:bg-slate-800/20"
                            >
                                <div
                                    v-for="(lesson, lesIdx) in mod.lessons"
                                    :key="lesson.id"
                                    class="px-4 py-2.5 flex items-center justify-between hover:bg-white dark:hover:bg-slate-800/60 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="text-xs font-bold text-slate-400 w-6 text-right shrink-0"
                                        >
                                            {{ getOverallModuleIndex(mod) }}.{{ lesIdx + 1 }}
                                        </span>
                                        <span
                                            class="p-1.5 rounded-lg shrink-0"
                                            :class="{
                                                'bg-rose-50 text-rose-600 dark:bg-rose-950 dark:text-rose-400':
                                                    lesson.content_type ===
                                                    'video',
                                                'bg-blue-50 text-blue-600 dark:bg-blue-950 dark:text-blue-400':
                                                    lesson.content_type ===
                                                    'article',
                                                'bg-emerald-50 text-emerald-600 dark:bg-emerald-950 dark:text-emerald-400':
                                                    lesson.content_type ===
                                                    'image',
                                                'bg-amber-50 text-amber-600 dark:bg-amber-950 dark:text-amber-400':
                                                    lesson.content_type ===
                                                    'pdf',
                                            }"
                                        >
                                            <Video
                                                v-if="
                                                    lesson.content_type ===
                                                    'video'
                                                "
                                                class="w-3.5 h-3.5"
                                            />
                                            <FileText
                                                v-else-if="
                                                    lesson.content_type ===
                                                    'article'
                                                "
                                                class="w-3.5 h-3.5"
                                            />
                                            <ImageIcon
                                                v-else-if="
                                                    lesson.content_type ===
                                                    'image'
                                                "
                                                class="w-3.5 h-3.5"
                                            />
                                            <BookOpen
                                                v-else
                                                class="w-3.5 h-3.5"
                                            />
                                        </span>
                                        <div>
                                            <p
                                                class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                            >
                                                {{ lesson.title }}
                                            </p>
                                            <span
                                                class="text-[10px] text-slate-400"
                                            >
                                                {{
                                                    lesson.estimated_duration_minutes
                                                }}
                                                menit &bull; Tipe:
                                                {{
                                                    lesson.content_type ===
                                                    "article"
                                                        ? "Artikel Teks"
                                                        : lesson.content_type ===
                                                            "video"
                                                          ? "Video"
                                                          : lesson.content_type ===
                                                              "pdf"
                                                            ? "Dokumen PDF / Slide"
                                                            : "Gambar"
                                                }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-1.5">
                                        <button
                                            type="button"
                                            @click="openEditLessonModal(lesson)"
                                            class="p-1 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded"
                                            title="Edit Elemen Kompetensi"
                                        >
                                            <Edit class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteLesson(lesson)"
                                            :disabled="deletingLessonId === lesson.id"
                                            class="p-1 text-slate-300 hover:text-rose-600 rounded disabled:opacity-50"
                                            title="Hapus Elemen Kompetensi"
                                        >
                                            <Loader2
                                                v-if="deletingLessonId === lesson.id"
                                                class="w-3.5 h-3.5 animate-spin text-rose-600"
                                            />
                                            <Trash2 v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="p-4 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-800/20 text-xs text-slate-400"
                            >
                                Belum ada elemen kompetensi di unit ini.
                                <button
                                    type="button"
                                    @click="openAddLessonModal(mod.id)"
                                    class="text-indigo-600 dark:text-indigo-400 font-bold hover:underline ml-1"
                                >
                                    + Tambah Sekarang
                                </button>
                            </div>
                        </div>

                        <!-- Evaluasi / Kuis Pemahaman Unit Kompetensi (Bisa Lebih dari 1 Kuis) -->
                        <div
                            class="border-t border-slate-100 dark:border-slate-800 pt-3 space-y-2"
                        >
                            <div class="flex items-center justify-between">
                                <h5
                                    class="text-xs font-bold uppercase tracking-wider text-slate-500 flex items-center gap-1.5"
                                >
                                    <HelpCircle
                                        class="w-3.5 h-3.5 text-amber-500"
                                    />
                                    <span>
                                        Kuis Pemahaman Unit &bull;
                                        {{
                                            mod.quizzes?.length ||
                                            (mod.quiz ? 1 : 0)
                                        }}
                                        Kuis
                                    </span>
                                </h5>
                                <button
                                    type="button"
                                    @click="openQuizModal(mod, null)"
                                    class="inline-flex items-center gap-1 text-xs font-bold text-amber-600 hover:text-amber-800 dark:text-amber-400 hover:underline"
                                >
                                    <Plus class="w-3.5 h-3.5" />
                                    <span>Tambah Kuis Baru</span>
                                </button>
                            </div>

                            <!-- List of Quizzes in this Unit -->
                            <div
                                v-if="
                                    (mod.quizzes && mod.quizzes.length > 0) ||
                                    mod.quiz
                                "
                                class="divide-y divide-slate-100 dark:divide-slate-800/60 border border-amber-200/60 dark:border-amber-900/40 rounded-xl overflow-hidden bg-amber-50/20 dark:bg-amber-950/10"
                            >
                                <div
                                    v-for="(qz, qzIdx) in mod.quizzes &&
                                    mod.quizzes.length > 0
                                        ? mod.quizzes
                                        : [mod.quiz]"
                                    :key="qz.id"
                                    class="px-4 py-3 flex flex-wrap items-center justify-between gap-3 hover:bg-white dark:hover:bg-slate-800/60 transition-colors"
                                >
                                    <div class="flex items-center gap-3">
                                        <span
                                            class="w-7 h-7 rounded-lg bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300 text-xs font-bold flex items-center justify-center shrink-0"
                                        >
                                            Q{{ qzIdx + 1 }}
                                        </span>
                                        <div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <p
                                                    class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                                >
                                                    {{ qz.title }}
                                                </p>
                                                <span
                                                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800"
                                                >
                                                    KKM: {{ qz.passing_score }}%
                                                </span>
                                            </div>
                                            <p
                                                class="text-[11px] text-slate-400 mt-0.5"
                                            >
                                                {{ qz.questions?.length || 0 }}
                                                Butir Soal &bull;
                                                {{
                                                    qz.time_limit_minutes
                                                        ? qz.time_limit_minutes +
                                                          " Menit"
                                                        : "Tanpa Batas Waktu"
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            type="button"
                                            @click="openQuizModal(mod, qz)"
                                            class="px-3 py-1.5 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-xs font-bold transition flex items-center gap-1 shadow-sm"
                                        >
                                            <Edit class="w-3.5 h-3.5" />
                                            <span>Kelola Soal & Kuis</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteQuiz(qz)"
                                            :disabled="deletingQuizId === qz.id"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 rounded-lg transition disabled:opacity-50"
                                            title="Hapus Kuis Ini"
                                        >
                                            <Loader2
                                                v-if="deletingQuizId === qz.id"
                                                class="w-4 h-4 animate-spin text-rose-600"
                                            />
                                            <Trash2 v-else class="w-4 h-4" />
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="p-3.5 text-center border border-dashed border-slate-200 dark:border-slate-800 rounded-xl bg-slate-50/50 dark:bg-slate-800/20 text-xs text-slate-400"
                            >
                                Belum ada kuis di unit kompetensi ini
                                (opsional).
                                <button
                                    type="button"
                                    @click="openQuizModal(mod, null)"
                                    class="text-amber-600 dark:text-amber-400 font-bold hover:underline ml-1"
                                >
                                    + Tambah Kuis
                                </button>
                            </div>
                        </div>
                            </div>
                        </div>
                    </div>
                </div>

                    <!-- Empty State if No Modules in course at all -->
                <div
                    v-else-if="!course.modules || course.modules.length === 0"
                    class="bg-white dark:bg-slate-900 border border-dashed border-slate-300 dark:border-slate-800 rounded-2xl p-10 text-center"
                >
                    <BookOpen class="w-10 h-10 mx-auto text-slate-400 mb-2" />
                    <h3
                        class="text-sm font-bold text-slate-800 dark:text-slate-200"
                    >
                        Belum Ada Unit Kompetensi
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Buat unit kompetensi pertama atau import langsung
                        seluruh kurikulum dari file Excel / CSV.
                    </p>
                    <div
                        class="mt-4 flex flex-wrap items-center justify-center gap-2.5"
                    >
                        <button
                            type="button"
                            @click="openAddModuleModal"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm"
                        >
                            + Tambah Unit Kompetensi Pertama
                        </button>
                        <button
                            type="button"
                            @click="showCurriculumImportModal = true"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm flex items-center gap-1.5"
                        >
                            <FileSpreadsheet class="w-3.5 h-3.5" />
                            <span>Import Excel / CSV</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 2: PESERTA & IMPORT                                  -->
            <!-- ======================================================== -->
            <div v-if="activeTab === 'peserta'" class="space-y-6">
                <div
                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                >
                    <div>
                        <h2
                            class="text-lg font-bold text-slate-900 dark:text-white"
                        >
                            Data Peserta Kelas Pelatihan
                        </h2>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Peserta tersinkronisasi otomatis via email. Import
                            file Excel atau CSV untuk memasukkan peserta secara
                            massal.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-3">
                        <button
                            @click="showParticipantModal = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors"
                        >
                            <Plus class="w-4 h-4" />
                            + Tambah Peserta
                        </button>
                        <button
                            @click="showImportModal = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors"
                        >
                            <FileSpreadsheet class="w-4 h-4" />
                            Import Excel / CSV
                        </button>
                        <a
                            :href="`/admin/lms/${course.id}/participants/export`"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white dark:bg-slate-700 dark:hover:bg-slate-600 rounded-lg text-xs font-semibold shadow-sm transition-colors"
                        >
                            <Download class="w-4 h-4" />
                            Export CSV
                        </a>
                    </div>
                </div>

                <!-- Real-time Attendance Metrics Grid -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-slate-500"
                                >Total Peserta</span
                            >
                            <span
                                class="p-1.5 bg-slate-100 dark:bg-slate-800 rounded-lg text-slate-600 dark:text-slate-300"
                            >
                                <Users class="w-4 h-4" />
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-slate-900 dark:text-white mt-2"
                        >
                            {{
                                course.enrollments
                                    ? course.enrollments.length
                                    : 0
                            }}
                        </p>
                    </div>

                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold text-rose-600 dark:text-rose-400"
                                >Hadir Online Meeting</span
                            >
                            <span
                                class="p-1.5 bg-rose-50 dark:bg-rose-950/60 rounded-lg text-rose-600"
                            >
                                <Video class="w-4 h-4" />
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-rose-600 dark:text-rose-400 mt-2"
                        >
                            {{ metrics.live_zoom_attendees || 0 }}
                        </p>
                    </div>

                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold text-blue-600 dark:text-blue-400"
                                >Hadir Mandiri (100%)</span
                            >
                            <span
                                class="p-1.5 bg-blue-50 dark:bg-blue-950/60 rounded-lg text-blue-600"
                            >
                                <BookOpen class="w-4 h-4" />
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-blue-600 dark:text-blue-400 mt-2"
                        >
                            {{ metrics.self_study_attendees || 0 }}
                        </p>
                    </div>

                    <div
                        class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-xs font-semibold text-amber-600 dark:text-amber-400"
                                >Belum Presensi</span
                            >
                            <span
                                class="p-1.5 bg-amber-50 dark:bg-amber-950/60 rounded-lg text-amber-600"
                            >
                                <Clock class="w-4 h-4" />
                            </span>
                        </div>
                        <p
                            class="text-2xl font-black text-amber-600 dark:text-amber-400 mt-2"
                        >
                            {{
                                (course.enrollments
                                    ? course.enrollments.length
                                    : 0) - (metrics.completed_students || 0)
                            }}
                        </p>
                    </div>
                </div>

                <!-- Status Filter Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1">
                    <button
                        type="button"
                        @click="attendanceStatusFilter = 'all'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap"
                        :class="
                            attendanceStatusFilter === 'all'
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-200'
                        "
                    >
                        Semua ({{
                            course.enrollments ? course.enrollments.length : 0
                        }})
                    </button>
                    <button
                        type="button"
                        @click="attendanceStatusFilter = 'live_zoom'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1.5"
                        :class="
                            attendanceStatusFilter === 'live_zoom'
                                ? 'bg-rose-600 text-white shadow-sm'
                                : 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300 hover:bg-rose-100'
                        "
                    >
                        <Video class="w-3.5 h-3.5" />
                        <span
                            >Hadir Online Meeting ({{
                                metrics.live_zoom_attendees || 0
                            }})</span
                        >
                    </button>
                    <button
                        type="button"
                        @click="attendanceStatusFilter = 'self_study'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1.5"
                        :class="
                            attendanceStatusFilter === 'self_study'
                                ? 'bg-blue-600 text-white shadow-sm'
                                : 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 hover:bg-blue-100'
                        "
                    >
                        <BookOpen class="w-3.5 h-3.5" />
                        <span
                            >Hadir Mandiri ({{
                                metrics.self_study_attendees || 0
                            }})</span
                        >
                    </button>
                    <button
                        type="button"
                        @click="attendanceStatusFilter = 'unattended'"
                        class="px-3.5 py-1.5 rounded-lg text-xs font-bold transition whitespace-nowrap flex items-center gap-1.5"
                        :class="
                            attendanceStatusFilter === 'unattended'
                                ? 'bg-amber-600 text-white shadow-sm'
                                : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 hover:bg-amber-100'
                        "
                    >
                        <Clock class="w-3.5 h-3.5" />
                        <span
                            >Belum Presensi ({{
                                (course.enrollments
                                    ? course.enrollments.length
                                    : 0) - (metrics.completed_students || 0)
                            }})</span
                        >
                    </button>
                </div>

                <!-- Participant Search & Actions Bar -->
                <div
                    class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3"
                >
                    <div class="flex items-center gap-3 flex-1 flex-wrap">
                        <div class="relative w-full sm:w-80">
                            <Search
                                class="w-4 h-4 absolute left-3 top-2.5 text-slate-400"
                            />
                            <input
                                v-model="participantSearch"
                                type="text"
                                placeholder="Cari nama, email, NIK, alamat..."
                                class="w-full pl-9 pr-4 py-2 text-xs bg-slate-50 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>

                        <!-- Bulk Action Controls -->
                        <div
                            v-if="selectedEnrollmentIds.length > 0"
                            class="flex items-center gap-2 bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-900/60 px-3 py-1.5 rounded-lg text-xs"
                        >
                            <span
                                class="font-bold text-rose-700 dark:text-rose-300"
                            >
                                {{ selectedEnrollmentIds.length }} peserta
                                dipilih
                            </span>
                            <button
                                @click="deleteSelectedEnrollments"
                                :disabled="isBulkDeleting"
                                class="inline-flex items-center gap-1.5 px-3 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded text-xs font-bold transition disabled:opacity-50"
                            >
                                <Loader2
                                    v-if="isBulkDeleting"
                                    class="w-3.5 h-3.5 animate-spin"
                                />
                                <Trash2 v-else class="w-3.5 h-3.5" />
                                {{
                                    isBulkDeleting
                                        ? "Menghapus..."
                                        : "Hapus Terpilih"
                                }}
                            </button>
                            <button
                                @click="selectedEnrollmentIds = []"
                                class="text-slate-500 hover:text-slate-700 dark:text-slate-400 text-xs underline ml-1"
                            >
                                Batal
                            </button>
                        </div>
                    </div>

                    <span class="text-xs text-slate-500 shrink-0">
                        Total:
                        <strong>{{ filteredEnrollments.length }}</strong>
                        Peserta
                    </span>
                </div>

                <!-- Participants Table -->
                <div
                    class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm"
                >
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="bg-slate-50 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-800 text-slate-600 dark:text-slate-300 font-bold uppercase tracking-wider"
                            >
                                <tr>
                                    <th class="w-10 px-3 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="isAllSelected"
                                            @change="toggleSelectAll"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                            title="Pilih Semua di Halaman Ini"
                                        />
                                    </th>
                                    <th class="w-12 px-2 py-3 text-center">
                                        No.
                                    </th>
                                    <th class="px-4 py-3">
                                        Nama & Email Peserta
                                    </th>
                                    <th class="px-4 py-3">NIK / Identitas</th>
                                    <th class="px-4 py-3">Alamat Domisili</th>
                                    <th class="px-4 py-3">Status Kelas</th>
                                    <th class="px-4 py-3">Progress Belajar</th>
                                    <th
                                        class="px-4 py-3 whitespace-nowrap text-center bg-indigo-50/70 dark:bg-indigo-950/50 text-indigo-900 dark:text-indigo-200"
                                    >
                                        Jumlah Kehadiran
                                    </th>
                                    <th
                                        v-for="(mod, modIdx) in course.modules"
                                        :key="mod.id"
                                        class="px-3 py-3 text-center whitespace-nowrap border-l border-slate-200 dark:border-slate-700 bg-slate-100/80 dark:bg-slate-800"
                                    >
                                        <div
                                            class="text-[11px] font-black text-indigo-700 dark:text-indigo-300"
                                        >
                                            Hari
                                            {{ mod.day_number || modIdx + 1 }}
                                        </div>
                                        <div
                                            class="text-[9px] font-medium text-slate-500 max-w-[120px] truncate"
                                            :title="mod.title"
                                        >
                                            {{ mod.title }}
                                        </div>
                                        <div
                                            class="text-[9px] font-bold text-indigo-600 dark:text-indigo-400 mt-0.5"
                                        >
                                            (Jalur Presensi)
                                        </div>
                                    </th>
                                    <th class="px-4 py-3">Sertifikat</th>
                                    <th class="px-4 py-3 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody
                                class="divide-y divide-slate-100 dark:divide-slate-800/60"
                            >
                                <tr
                                    v-for="(e, index) in paginatedEnrollments"
                                    :key="e.id"
                                    class="hover:bg-slate-50/50 dark:hover:bg-slate-800/40 transition"
                                    :class="
                                        selectedEnrollmentIds.includes(e.id)
                                            ? 'bg-indigo-50/50 dark:bg-indigo-950/20'
                                            : ''
                                    "
                                >
                                    <td class="w-10 px-3 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            :value="e.id"
                                            v-model="selectedEnrollmentIds"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                        />
                                    </td>
                                    <td
                                        class="w-12 px-2 py-3 text-center font-mono font-medium text-slate-400"
                                    >
                                        {{
                                            (currentPage - 1) *
                                                (itemsPerPage ||
                                                    filteredEnrollments.length) +
                                            index +
                                            1
                                        }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-1.5">
                                            <p
                                                class="font-bold text-slate-900 dark:text-white"
                                            >
                                                {{ e.participant?.name }}
                                            </p>
                                            <span
                                                v-if="e.participant?.gender"
                                                class="px-1.5 py-0.2 rounded text-[9px] font-bold"
                                                :class="
                                                    e.participant.gender === 'L'
                                                        ? 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300'
                                                        : 'bg-pink-100 text-pink-700 dark:bg-pink-950 dark:text-pink-300'
                                                "
                                            >
                                                {{
                                                    e.participant.gender === "L"
                                                        ? "L"
                                                        : "P"
                                                }}
                                            </span>
                                        </div>
                                        <p class="text-slate-400 text-[11px]">
                                            {{ e.participant?.email }}
                                        </p>
                                        <p
                                            v-if="
                                                e.participant?.phone &&
                                                e.participant.phone !== '0'
                                            "
                                            class="text-slate-500 text-[10px]"
                                        >
                                            Telp: {{ e.participant.phone }}
                                        </p>
                                    </td>
                                    <td
                                        class="px-4 py-3 font-mono text-slate-600 dark:text-slate-300"
                                    >
                                        <div>
                                            {{
                                                e.participant?.nik &&
                                                e.participant.nik !== "0"
                                                    ? e.participant.nik
                                                    : "-"
                                            }}
                                        </div>
                                        <div
                                            v-if="
                                                (e.training_transaction_code &&
                                                    e.training_transaction_code !==
                                                        '0') ||
                                                (e.participant
                                                    ?.training_transaction_code &&
                                                    e.participant
                                                        .training_transaction_code !==
                                                        '0')
                                            "
                                            class="text-[10px] text-indigo-500 font-sans mt-0.5"
                                        >
                                            Kode:
                                            {{
                                                e.training_transaction_code ||
                                                e.participant
                                                    ?.training_transaction_code
                                            }}
                                        </div>
                                    </td>
                                    <td
                                        class="px-4 py-3 text-slate-600 dark:text-slate-300 max-w-xs"
                                    >
                                        <p
                                            v-if="
                                                e.participant?.address &&
                                                e.participant.address !== '0'
                                            "
                                            class="text-xs text-slate-700 dark:text-slate-300 line-clamp-2"
                                            :title="e.participant.address"
                                        >
                                            {{ e.participant.address }}
                                        </p>
                                        <span v-else class="text-slate-400"
                                            >-</span
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <span
                                            v-if="e.status === 'completed'"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                                        >
                                            <CheckCircle2 class="w-3 h-3" />
                                            SUDAH MENGIKUTI
                                        </span>
                                        <span
                                            v-else-if="
                                                e.status === 'in_progress'
                                            "
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300"
                                        >
                                            SEDANG BELAJAR
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                        >
                                            TERDAFTAR
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="w-24">
                                            <div
                                                class="flex justify-between text-[10px] mb-0.5 font-bold"
                                            >
                                                <span
                                                    >{{
                                                        e.progress_percentage ||
                                                        0
                                                    }}%</span
                                                >
                                            </div>
                                            <div
                                                class="w-full bg-slate-200 dark:bg-slate-700 h-1.5 rounded-full overflow-hidden"
                                            >
                                                <div
                                                    class="h-full rounded-full transition-all"
                                                    :class="
                                                        e.progress_percentage >=
                                                        100
                                                            ? 'bg-emerald-500'
                                                            : 'bg-indigo-600'
                                                    "
                                                    :style="{
                                                        width: `${e.progress_percentage || 0}%`,
                                                    }"
                                                ></div>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Jumlah Kehadiran -->
                                    <td
                                        class="px-4 py-3 whitespace-nowrap text-center bg-indigo-50/20 dark:bg-indigo-950/10"
                                    >
                                        <div
                                            class="inline-flex flex-col items-center"
                                        >
                                            <span
                                                class="font-black text-xs"
                                                :class="
                                                    getAttendancePercentage(
                                                        e,
                                                    ) === 100
                                                        ? 'text-emerald-600 dark:text-emerald-400'
                                                        : 'text-slate-800 dark:text-slate-200'
                                                "
                                            >
                                                {{ getAttendedCount(e) }} /
                                                {{
                                                    course.modules?.length || 1
                                                }}
                                                Sesi
                                            </span>
                                            <span
                                                class="text-[10px] px-1.5 py-0.2 rounded-full font-bold mt-0.5"
                                                :class="
                                                    getAttendancePercentage(
                                                        e,
                                                    ) === 100
                                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300'
                                                        : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400'
                                                "
                                            >
                                                {{
                                                    getAttendancePercentage(e)
                                                }}%
                                            </span>
                                        </div>
                                    </td>

                                    <!-- Dynamic Checklist Columns per Day / Unit (Jalur Presensi) -->
                                    <td
                                        v-for="(mod, modIdx) in course.modules"
                                        :key="mod.id"
                                        class="px-3 py-3 text-center whitespace-nowrap border-l border-slate-100 dark:border-slate-800"
                                    >
                                        <button
                                            type="button"
                                            @click="
                                                toggleStudentModuleAttendance(
                                                    e,
                                                    mod,
                                                )
                                            "
                                            :disabled="
                                                togglingAttendance[
                                                    `${e.id}_${mod.id}`
                                                ]
                                            "
                                            class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-bold transition shadow-sm"
                                            :class="
                                                getModuleAttendanceStatus(
                                                    e,
                                                    mod,
                                                    modIdx,
                                                ) === 'live_zoom'
                                                    ? 'bg-rose-50 text-rose-700 border border-rose-200 hover:bg-rose-100 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-800 shadow-rose-500/10'
                                                    : getModuleAttendanceStatus(
                                                            e,
                                                            mod,
                                                            modIdx,
                                                        ) === 'self_study'
                                                      ? 'bg-blue-50 text-blue-700 border border-blue-200 hover:bg-blue-100 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-800 shadow-blue-500/10'
                                                      : 'bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-400 hover:text-rose-600 hover:border-rose-300'
                                            "
                                            :title="
                                                getModuleAttendanceStatus(
                                                    e,
                                                    mod,
                                                    modIdx,
                                                ) === 'live_zoom'
                                                    ? `Hadir Online Meeting Hari ke-${mod.day_number || modIdx + 1}. Klik untuk ubah menjadi Belajar Mandiri.`
                                                    : getModuleAttendanceStatus(
                                                            e,
                                                            mod,
                                                            modIdx,
                                                        ) === 'self_study'
                                                      ? `Hadir Belajar Mandiri Hari ke-${mod.day_number || modIdx + 1} (Terlambat/Susulan). Klik untuk batalkan presensi (Belum Hadir).`
                                                      : `Belum hadir Hari ke-${mod.day_number || modIdx + 1}. Klik untuk tandai Hadir Online Meeting.`
                                            "
                                        >
                                            <span
                                                v-if="
                                                    togglingAttendance[
                                                        `${e.id}_${mod.id}`
                                                    ]
                                                "
                                                class="w-3.5 h-3.5 border-2 border-current border-t-transparent rounded-full animate-spin"
                                            ></span>
                                            <template v-else>
                                                <template
                                                    v-if="
                                                        getModuleAttendanceStatus(
                                                            e,
                                                            mod,
                                                            modIdx,
                                                        ) === 'live_zoom'
                                                    "
                                                >
                                                    <Video
                                                        class="w-3.5 h-3.5 text-rose-600 dark:text-rose-400 shrink-0"
                                                    />
                                                    <span>Online Meeting</span>
                                                </template>
                                                <template
                                                    v-else-if="
                                                        getModuleAttendanceStatus(
                                                            e,
                                                            mod,
                                                            modIdx,
                                                        ) === 'self_study'
                                                    "
                                                >
                                                    <BookOpen
                                                        class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400 shrink-0"
                                                    />
                                                    <span>Belajar Mandiri</span>
                                                </template>
                                                <template v-else>
                                                    <Clock
                                                        class="w-3.5 h-3.5 text-slate-400 shrink-0"
                                                    />
                                                    <span class="text-[11px]"
                                                        >Belum Hadir</span
                                                    >
                                                </template>
                                            </template>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div v-if="e.certificate_hash">
                                            <a
                                                :href="`/lms/certificates/${e.id}/download`"
                                                target="_blank"
                                                class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 font-semibold inline-flex items-center gap-1 text-[11px]"
                                            >
                                                <Award class="w-3.5 h-3.5" />
                                                Unduh PDF
                                            </a>
                                            <p
                                                class="text-[10px] font-mono text-slate-400 truncate max-w-[120px]"
                                            >
                                                {{ e.certificate_number }}
                                            </p>
                                        </div>
                                        <span
                                            v-else
                                            class="text-slate-400 text-[11px]"
                                            >-</span
                                        >
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div
                                            class="flex items-center justify-center gap-1.5 flex-wrap"
                                        >
                                            <div
                                                v-if="e.status !== 'completed'"
                                                class="flex items-center gap-1"
                                            >
                                                <button
                                                    @click="
                                                        markManualAttendance(
                                                            e,
                                                            'live_zoom',
                                                        )
                                                    "
                                                    :disabled="markingAttendanceEnrollmentId === `${e.id}_live_zoom`"
                                                    class="px-2 py-1 bg-rose-50 hover:bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300 rounded text-[10px] font-bold whitespace-nowrap inline-flex items-center gap-1 disabled:opacity-50"
                                                    title="Tandai Hadir Online Meeting & Terbitkan Sertifikat"
                                                >
                                                    <Loader2
                                                        v-if="markingAttendanceEnrollmentId === `${e.id}_live_zoom`"
                                                        class="w-3 h-3 animate-spin shrink-0"
                                                    />
                                                    <span>+ Hadir Online Meeting</span>
                                                </button>
                                                <button
                                                    @click="
                                                        markManualAttendance(
                                                            e,
                                                            'self_study',
                                                        )
                                                    "
                                                    :disabled="markingAttendanceEnrollmentId === `${e.id}_self_study`"
                                                    class="px-2 py-1 bg-blue-50 hover:bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300 rounded text-[10px] font-bold whitespace-nowrap inline-flex items-center gap-1 disabled:opacity-50"
                                                    title="Tandai Lulus Mandiri & Terbitkan Sertifikat"
                                                >
                                                    <Loader2
                                                        v-if="markingAttendanceEnrollmentId === `${e.id}_self_study`"
                                                        class="w-3 h-3 animate-spin shrink-0"
                                                    />
                                                    <span>+ Lulus Mandiri</span>
                                                </button>
                                            </div>
                                            <span
                                                v-else
                                                class="text-emerald-600 text-[11px] font-bold"
                                                >&check; Hadir</span
                                            >

                                            <button
                                                @click="openEditModal(e)"
                                                class="p-1.5 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-slate-800 rounded-md transition"
                                                title="Edit Data Peserta"
                                            >
                                                <Pencil class="w-3.5 h-3.5" />
                                            </button>
                                            <button
                                                @click="deleteEnrollment(e)"
                                                :disabled="deletingEnrollmentId === e.id"
                                                class="p-1.5 text-slate-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-slate-800 rounded-md transition disabled:opacity-50"
                                                title="Keluarkan Peserta"
                                            >
                                                <Loader2
                                                    v-if="deletingEnrollmentId === e.id"
                                                    class="w-3.5 h-3.5 animate-spin text-rose-600"
                                                />
                                                <Trash2 v-else class="w-3.5 h-3.5" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>

                                <tr v-if="filteredEnrollments.length === 0">
                                    <td
                                        :colspan="
                                            11 + (course.modules?.length || 0)
                                        "
                                        class="px-4 py-8 text-center text-slate-400"
                                    >
                                        Tidak ada peserta yang cocok dengan
                                        kriteria.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Table Footer / Pagination -->
                    <div
                        class="px-4 py-3 bg-slate-50 dark:bg-slate-800/50 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs"
                    >
                        <div class="flex items-center gap-2 text-slate-500">
                            <span>
                                Menampilkan
                                <strong>{{ showingStart }}</strong> -
                                <strong>{{ showingEnd }}</strong> dari
                                <strong>{{
                                    filteredEnrollments.length
                                }}</strong>
                                peserta
                            </span>
                            <span
                                class="mx-1 text-slate-300 dark:text-slate-700"
                                >|</span
                            >
                            <label class="flex items-center gap-1.5">
                                <span>Per halaman:</span>
                                <select
                                    v-model.number="itemsPerPage"
                                    class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded px-2 py-1 text-xs focus:ring-1 focus:ring-indigo-500"
                                >
                                    <option :value="10">10</option>
                                    <option :value="25">25</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                    <option :value="0">Semua</option>
                                </select>
                            </label>
                        </div>

                        <!-- Right Side: Number Pagination ("angka paling kanan") -->
                        <div
                            v-if="totalPages > 1 && itemsPerPage > 0"
                            class="flex items-center gap-1"
                        >
                            <button
                                @click="
                                    currentPage = Math.max(1, currentPage - 1)
                                "
                                :disabled="currentPage === 1"
                                class="px-2 py-1 rounded border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                                title="Halaman Sebelumnya"
                            >
                                <ChevronLeft class="w-3.5 h-3.5" />
                            </button>

                            <template v-for="page in totalPages" :key="page">
                                <button
                                    v-if="
                                        page === 1 ||
                                        page === totalPages ||
                                        (page >= currentPage - 2 &&
                                            page <= currentPage + 2)
                                    "
                                    @click="currentPage = page"
                                    class="min-w-[28px] h-7 px-2 flex items-center justify-center rounded text-xs font-bold transition"
                                    :class="
                                        currentPage === page
                                            ? 'bg-indigo-600 text-white shadow-sm'
                                            : 'border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800'
                                    "
                                >
                                    {{ page }}
                                </button>
                                <span
                                    v-else-if="
                                        page === currentPage - 3 ||
                                        page === currentPage + 3
                                    "
                                    class="px-1 text-slate-400 select-none"
                                >
                                    ...
                                </span>
                            </template>

                            <button
                                @click="
                                    currentPage = Math.min(
                                        totalPages,
                                        currentPage + 1,
                                    )
                                "
                                :disabled="currentPage === totalPages"
                                class="px-2 py-1 rounded border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700 disabled:opacity-40 disabled:cursor-not-allowed transition"
                                title="Halaman Berikutnya"
                            >
                                <ChevronRight class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- TAB 3: PENGATURAN SERTIFIKAT A4 & TTE                    -->
            <!-- ======================================================== -->
            <div v-if="activeTab === 'sertifikat'" class="space-y-6">
                <div
                    class="flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                >
                    <div>
                        <h2
                            class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2"
                        >
                            <Award
                                class="w-5 h-5 text-indigo-600 dark:text-indigo-400"
                            />
                            <span>Canvas Builder Sertifikat A4 Landscape</span>
                        </h2>
                        <p
                            class="text-xs text-slate-500 dark:text-slate-400 mt-0.5"
                        >
                            Klik & geser (drag) teks/QR langsung pada canvas
                            dengan mouse, atau atur angka koordinat dan warna
                            pada panel samping.
                        </p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2">
                        <!-- Undo Button -->
                        <button
                            type="button"
                            @click="certUndo"
                            :disabled="!canCertUndo"
                            title="Urungkan / Undo (Ctrl+Z)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold shadow-sm transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <Undo2 class="w-4 h-4" />
                            <span class="hidden sm:inline">Undo</span>
                        </button>
                        <!-- Redo Button -->
                        <button
                            type="button"
                            @click="certRedo"
                            :disabled="!canCertRedo"
                            title="Ulangi / Redo (Ctrl+Y)"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-xs font-semibold shadow-sm transition-colors disabled:opacity-40 disabled:cursor-not-allowed cursor-pointer"
                        >
                            <Redo2 class="w-4 h-4" />
                            <span class="hidden sm:inline">Redo</span>
                        </button>
                        <!-- Reset Layout Button -->
                        <button
                            type="button"
                            @click="resetCertToDefaultLayout"
                            title="Reset Posisi ke Tata Letak Standar / Baku BPVP"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/40 dark:hover:bg-amber-900/50 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800/60 rounded-lg text-xs font-semibold shadow-sm transition-colors cursor-pointer"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            <span>Reset Tata Letak Baku</span>
                        </button>
                        <!-- Preview PDF Button (Auto-saves canvas first so PDF matches 100%) -->
                        <button
                            type="button"
                            @click="openPdfPreview"
                            :disabled="isSavingCert"
                            title="Simpan otomatis tata letak dan buka preview PDF sertifikat"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="isSavingCert" class="w-4 h-4 animate-spin shrink-0" />
                            <Eye v-else class="w-4 h-4" />
                            <span>{{
                                isSavingCert
                                    ? "Menyinkronkan..."
                                    : "Buka Preview PDF"
                            }}</span>
                        </button>
                        <!-- Save Button -->
                        <button
                            @click="saveCertificateConfig"
                            :disabled="isSavingCert"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold shadow-sm transition-colors cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="isSavingCert" class="w-4 h-4 animate-spin shrink-0" />
                            <Save v-else class="w-4 h-4" />
                            <span>{{
                                isSavingCert
                                    ? "Menyimpan..."
                                    : "Simpan Konfigurasi"
                            }}</span>
                        </button>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                    <!-- Left: Interactive Visual Canvas Preview (7 cols) -->
                    <div class="lg:col-span-7 space-y-4">
                        <div class="flex items-center justify-between text-xs">
                            <div class="flex items-center gap-2">
                                <span
                                    class="font-bold text-slate-700 dark:text-slate-300"
                                >
                                    Canvas A4 Landscape (297mm &times; 210mm)
                                </span>
                                <span
                                    class="text-[10px] px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300 font-bold"
                                >
                                    Interactive Drag & Drop
                                </span>
                            </div>
                            <label
                                class="flex items-center gap-2 cursor-pointer text-slate-600 dark:text-slate-400"
                            >
                                <input
                                    type="checkbox"
                                    v-model="certConfig.show_grid"
                                    class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                />
                                <span>Garis Bantu Grid (10%)</span>
                            </label>
                        </div>

                        <!-- Canvas Box Container with Responsive Vector Scaling (1123px x 794px base) -->
                        <div
                            ref="canvasWrapperRef"
                            class="relative w-full overflow-hidden rounded-2xl shadow-xl border-2 border-slate-300 dark:border-slate-700 bg-slate-900/5 select-none"
                            :style="{ height: `${794 * scaleFactor}px` }"
                        >
                            <div
                                ref="canvasBoxRef"
                                class="absolute top-0 left-0 bg-white select-none overflow-hidden"
                                :style="{
                                    width: '1123px',
                                    height: '794px',
                                    transform: `scale(${scaleFactor})`,
                                    transformOrigin: 'top left',
                                    fontFamily:
                                        certConfig.font_family ||
                                        `'Plus Jakarta Sans', Arial, sans-serif`,
                                }"
                            >
                                <!-- Background Image: Custom if uploaded, or standard default luxury background -->
                                <img
                                    :src="
                                        course.certificate_template_path
                                            ? '/storage/' +
                                              course.certificate_template_path +
                                              '?v=' +
                                              (course.updated_at || Date.now())
                                            : '/images/lms/certificate_default_bg.jpg?v=' +
                                              (course.updated_at || Date.now())
                                    "
                                    class="absolute inset-0 w-full h-full object-cover pointer-events-none"
                                    alt="Certificate Template Background"
                                />

                                <!-- Grid Overlay Lines -->
                                <div
                                    v-if="certConfig.show_grid"
                                    class="absolute inset-0 pointer-events-none z-30"
                                >
                                    <div
                                        v-for="y in [
                                            10, 20, 30, 40, 50, 60, 70, 80, 90,
                                        ]"
                                        :key="'y' + y"
                                        class="absolute left-0 w-full border-t border-dashed border-rose-400/50 text-[10px] text-rose-500 pl-2 font-mono"
                                        :style="{ top: `${y}%` }"
                                    >
                                        Y: {{ y }}%
                                    </div>
                                    <div
                                        v-for="x in [
                                            10, 20, 30, 40, 50, 60, 70, 80, 90,
                                        ]"
                                        :key="'x' + x"
                                        class="absolute top-0 h-full border-l border-dashed border-blue-400/50 text-[10px] text-blue-500 pt-2 font-mono"
                                        :style="{ left: `${x}%` }"
                                    >
                                        X: {{ x }}%
                                    </div>
                                </div>

                                <!-- DRAGGABLE ELEMENTS ON CANVAS -->

                                <!-- 1. Kop Instansi (Configurable & Draggable) -->
                                <div
                                    v-if="
                                        certConfig.header_kop &&
                                        certConfig.header_kop.show !== false
                                    "
                                    class="absolute transform -translate-x-1/2 whitespace-nowrap text-center select-none transition-[box-shadow,transform]"
                                    :class="[
                                        selectedCertKey === 'header_kop'
                                            ? 'ring-2 ring-indigo-500 ring-offset-2 z-40 bg-indigo-50/50 dark:bg-indigo-950/60 rounded p-1.5'
                                            : 'hover:ring-1 hover:ring-indigo-300 z-10',
                                        draggingCertKey === 'header_kop'
                                            ? 'cursor-grabbing opacity-90 scale-105 z-50'
                                            : 'cursor-grab',
                                    ]"
                                    :style="{
                                        top: `${certConfig.header_kop.y}%`,
                                        left: `${certConfig.header_kop.x}%`,
                                        lineHeight: 1.35,
                                    }"
                                    @pointerdown="
                                        onCertPointerDown('header_kop', $event)
                                    "
                                >
                                    <div
                                        v-if="
                                            selectedCertKey === 'header_kop' ||
                                            draggingCertKey === 'header_kop'
                                        "
                                        class="absolute -top-7 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-mono font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap flex items-center gap-1 z-50"
                                    >
                                        <MoveUp class="w-3 h-3 rotate-45" />
                                        <span
                                            >Kop: X
                                            {{ certConfig.header_kop.x }}% | Y
                                            {{ certConfig.header_kop.y }}%</span
                                        >
                                    </div>
                                    <div
                                        :style="{
                                            fontSize: `${certConfig.header_kop.font_size_line1 || 10.5}pt`,
                                            fontWeight: 800,
                                            letterSpacing: '2px',
                                            color:
                                                certConfig.header_kop
                                                    .color_line1 || '#0f2b48',
                                            textTransform: 'uppercase',
                                        }"
                                    >
                                        {{
                                            certConfig.header_kop.line1 ||
                                            "KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA"
                                        }}
                                    </div>
                                    <div
                                        :style="{
                                            fontSize: `${certConfig.header_kop.font_size_line2 || 8.5}pt`,
                                            fontWeight: 700,
                                            letterSpacing: '1.5px',
                                            color:
                                                certConfig.header_kop
                                                    .color_line2 || '#b38b25',
                                            textTransform: 'uppercase',
                                            marginTop: '4px',
                                        }"
                                    >
                                        {{
                                            certConfig.header_kop.line2 ||
                                            "BALAI PELATIHAN VOKASI DAN PRODUKTIVITAS (BPVP) PANGKAJENE DAN KEPULAUAN"
                                        }}
                                    </div>
                                </div>

                                <!-- 2. Judul Sertifikat (Configurable & Draggable) -->
                                <div
                                    v-if="
                                        certConfig.certificate_title &&
                                        certConfig.certificate_title.show !==
                                            false
                                    "
                                    class="absolute transform -translate-x-1/2 whitespace-nowrap text-center select-none transition-[box-shadow,transform]"
                                    :class="[
                                        selectedCertKey === 'certificate_title'
                                            ? 'ring-2 ring-indigo-500 ring-offset-2 z-40 bg-indigo-50/50 dark:bg-indigo-950/60 rounded p-1.5'
                                            : 'hover:ring-1 hover:ring-indigo-300 z-10',
                                        draggingCertKey === 'certificate_title'
                                            ? 'cursor-grabbing opacity-90 scale-105 z-50'
                                            : 'cursor-grab',
                                    ]"
                                    :style="{
                                        top: `${certConfig.certificate_title.y}%`,
                                        left: `${certConfig.certificate_title.x}%`,
                                        fontSize: `${certConfig.certificate_title.font_size || 24}pt`,
                                        fontWeight: 900,
                                        letterSpacing: '4px',
                                        color:
                                            certConfig.certificate_title
                                                .color || '#0f2b48',
                                        textTransform: 'uppercase',
                                    }"
                                    @pointerdown="
                                        onCertPointerDown(
                                            'certificate_title',
                                            $event,
                                        )
                                    "
                                >
                                    <div
                                        v-if="
                                            selectedCertKey ===
                                                'certificate_title' ||
                                            draggingCertKey ===
                                                'certificate_title'
                                        "
                                        class="absolute -top-7 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-mono font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap flex items-center gap-1 z-50"
                                    >
                                        <MoveUp class="w-3 h-3 rotate-45" />
                                        <span
                                            >Judul: X
                                            {{
                                                certConfig.certificate_title.x
                                            }}% | Y
                                            {{
                                                certConfig.certificate_title.y
                                            }}%</span
                                        >
                                    </div>
                                    {{
                                        certConfig.certificate_title.text ||
                                        "SERTIFIKAT PELATIHAN"
                                    }}
                                </div>

                                <!-- 3. Nomor Sertifikat -->
                                <div
                                    class="absolute transform -translate-x-1/2 whitespace-nowrap select-none transition-[box-shadow,transform]"
                                    :class="[
                                        selectedCertKey === 'certificate_number'
                                            ? 'ring-2 ring-indigo-500 ring-offset-2 z-40 bg-indigo-50/50 dark:bg-indigo-950/60 rounded px-2 py-0.5'
                                            : 'hover:ring-1 hover:ring-indigo-300 z-10',
                                        draggingCertKey === 'certificate_number'
                                            ? 'cursor-grabbing opacity-90 scale-105 z-50'
                                            : 'cursor-grab',
                                    ]"
                                    :style="{
                                        top: `${certConfig.certificate_number.y}%`,
                                        left: `${certConfig.certificate_number.x}%`,
                                        fontSize: `${certConfig.certificate_number.font_size || 12}pt`,
                                        color:
                                            certConfig.certificate_number
                                                .color || '#475569',
                                        fontFamily: `'Courier New', Courier, monospace`,
                                    }"
                                    @pointerdown="
                                        onCertPointerDown(
                                            'certificate_number',
                                            $event,
                                        )
                                    "
                                >
                                    <div
                                        v-if="
                                            selectedCertKey ===
                                                'certificate_number' ||
                                            draggingCertKey ===
                                                'certificate_number'
                                        "
                                        class="absolute -top-7 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-mono font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap flex items-center gap-1 z-50"
                                    >
                                        <MoveUp class="w-3 h-3 rotate-45" />
                                        <span
                                            >Nomor: X
                                            {{
                                                certConfig.certificate_number.x
                                            }}% | Y
                                            {{
                                                certConfig.certificate_number.y
                                            }}%</span
                                        >
                                    </div>
                                    Nomor: BPVP-PANGKEP/LMS/{{
                                        new Date().getFullYear()
                                    }}/1
                                </div>

                                <!-- 4. Nama Peserta -->
                                <div
                                    class="absolute transform -translate-x-1/2 whitespace-nowrap select-none tracking-wide transition-[box-shadow,transform]"
                                    :class="[
                                        selectedCertKey === 'recipient_name'
                                            ? 'ring-2 ring-indigo-500 ring-offset-2 z-40 bg-indigo-50/50 dark:bg-indigo-950/60 rounded px-2 py-1'
                                            : 'hover:ring-1 hover:ring-indigo-300 z-10',
                                        draggingCertKey === 'recipient_name'
                                            ? 'cursor-grabbing opacity-90 scale-105 z-50'
                                            : 'cursor-grab',
                                    ]"
                                    :style="{
                                        top: `${certConfig.recipient_name.y}%`,
                                        left: `${certConfig.recipient_name.x}%`,
                                        fontSize: `${certConfig.recipient_name.font_size || 26}pt`,
                                        fontWeight: 800,
                                        color:
                                            certConfig.recipient_name.color ||
                                            '#0f2b48',
                                        letterSpacing: '0.5px',
                                    }"
                                    @pointerdown="
                                        onCertPointerDown(
                                            'recipient_name',
                                            $event,
                                        )
                                    "
                                >
                                    <div
                                        v-if="
                                            selectedCertKey ===
                                                'recipient_name' ||
                                            draggingCertKey === 'recipient_name'
                                        "
                                        class="absolute -top-7 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-mono font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap flex items-center gap-1 z-50"
                                    >
                                        <MoveUp class="w-3 h-3 rotate-45" />
                                        <span
                                            >Nama: X
                                            {{ certConfig.recipient_name.x }}% |
                                            Y
                                            {{
                                                certConfig.recipient_name.y
                                            }}%</span
                                        >
                                    </div>
                                    MUHAMMAD IKHLAS, S.T.
                                </div>

                                <!-- 5. Judul Pelatihan & Keterangan Durasi -->
                                <div
                                    class="absolute transform -translate-x-1/2 whitespace-nowrap text-center select-none transition-[box-shadow,transform]"
                                    :class="[
                                        selectedCertKey === 'course_title'
                                            ? 'ring-2 ring-indigo-500 ring-offset-2 z-40 bg-indigo-50/50 dark:bg-indigo-950/60 rounded p-1.5'
                                            : 'hover:ring-1 hover:ring-indigo-300 z-10',
                                        draggingCertKey === 'course_title'
                                            ? 'cursor-grabbing opacity-90 scale-105 z-50'
                                            : 'cursor-grab',
                                    ]"
                                    :style="{
                                        top: `${certConfig.course_title.y}%`,
                                        left: `${certConfig.course_title.x}%`,
                                        fontSize: `${certConfig.course_title.font_size || 15}pt`,
                                        fontWeight: 'bold',
                                        color:
                                            certConfig.course_title.color ||
                                            '#1e293b',
                                        lineHeight: 1.45,
                                    }"
                                    @pointerdown="
                                        onCertPointerDown(
                                            'course_title',
                                            $event,
                                        )
                                    "
                                >
                                    <div
                                        v-if="
                                            selectedCertKey ===
                                                'course_title' ||
                                            draggingCertKey === 'course_title'
                                        "
                                        class="absolute -top-7 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-mono font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap flex items-center gap-1 z-50"
                                    >
                                        <MoveUp class="w-3 h-3 rotate-45" />
                                        <span
                                            >Pelatihan: X
                                            {{ certConfig.course_title.x }}% | Y
                                            {{
                                                certConfig.course_title.y
                                            }}%</span
                                        >
                                    </div>
                                    <div
                                        style="
                                            font-size: 0.9em;
                                            font-weight: normal;
                                            color: #334155;
                                        "
                                    >
                                        Telah menyelesaikan pelatihan:
                                    </div>
                                    <div
                                        style="
                                            font-size: 1.15em;
                                            font-weight: 800;
                                            color: #0f2b48;
                                            margin: 3px 0;
                                        "
                                    >
                                        "{{ course.title }}"
                                    </div>
                                    <div
                                        style="
                                            font-size: 0.85em;
                                            font-weight: normal;
                                            color: #475569;
                                        "
                                    >
                                        selama
                                        {{ course.duration_in_days || 1 }} hari
                                    </div>
                                </div>

                                <!-- 6. Tanggal Terbit -->
                                <div
                                    class="absolute transform -translate-x-1/2 whitespace-nowrap select-none transition-[box-shadow,transform]"
                                    :class="[
                                        selectedCertKey === 'issue_date'
                                            ? 'ring-2 ring-indigo-500 ring-offset-2 z-40 bg-indigo-50/50 dark:bg-indigo-950/60 rounded px-2 py-1'
                                            : 'hover:ring-1 hover:ring-indigo-300 z-10',
                                        draggingCertKey === 'issue_date'
                                            ? 'cursor-grabbing opacity-90 scale-105 z-50'
                                            : 'cursor-grab',
                                    ]"
                                    :style="{
                                        top: `${certConfig.issue_date.y}%`,
                                        left: `${certConfig.issue_date.x}%`,
                                        fontSize: `${certConfig.issue_date.font_size || 12}pt`,
                                        fontWeight: 'normal',
                                        color:
                                            certConfig.issue_date.color ||
                                            '#475569',
                                    }"
                                    @pointerdown="
                                        onCertPointerDown('issue_date', $event)
                                    "
                                >
                                    <div
                                        v-if="
                                            selectedCertKey === 'issue_date' ||
                                            draggingCertKey === 'issue_date'
                                        "
                                        class="absolute -top-7 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-mono font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap flex items-center gap-1 z-50"
                                    >
                                        <MoveUp class="w-3 h-3 rotate-45" />
                                        <span
                                            >Tanggal: X
                                            {{ certConfig.issue_date.x }}% | Y
                                            {{ certConfig.issue_date.y }}%</span
                                        >
                                    </div>
                                    Pangkajene dan Kepulauan,
                                    {{ sampleIssueDateFormatted }}
                                </div>

                                <!-- 7. QR Code TTE Keabsahan -->
                                <div
                                    class="absolute transform -translate-x-1/2 text-center select-none bg-white p-2 rounded-lg shadow-md border border-slate-200 transition-[box-shadow,transform]"
                                    :class="[
                                        selectedCertKey === 'qr_code'
                                            ? 'ring-2 ring-indigo-500 ring-offset-2 z-40'
                                            : 'hover:ring-1 hover:ring-indigo-300 z-10',
                                        draggingCertKey === 'qr_code'
                                            ? 'cursor-grabbing opacity-90 scale-105 z-50'
                                            : 'cursor-grab',
                                    ]"
                                    :style="{
                                        top: `${certConfig.qr_code.y}%`,
                                        left: `${certConfig.qr_code.x}%`,
                                    }"
                                    @pointerdown="
                                        onCertPointerDown('qr_code', $event)
                                    "
                                >
                                    <div
                                        v-if="
                                            selectedCertKey === 'qr_code' ||
                                            draggingCertKey === 'qr_code'
                                        "
                                        class="absolute -top-7 left-1/2 -translate-x-1/2 bg-indigo-600 text-white text-[10px] font-mono font-bold px-2 py-0.5 rounded shadow pointer-events-none whitespace-nowrap flex items-center gap-1 z-50"
                                    >
                                        <MoveUp class="w-3 h-3 rotate-45" />
                                        <span
                                            >QR TTE: X
                                            {{ certConfig.qr_code.x }}% | Y
                                            {{ certConfig.qr_code.y }}%</span
                                        >
                                    </div>
                                    <div
                                        class="bg-slate-900 flex flex-col items-center justify-center text-white font-mono rounded"
                                        :style="{
                                            width: `${certConfig.qr_code.size || 80}px`,
                                            height: `${certConfig.qr_code.size || 80}px`,
                                        }"
                                    >
                                        <QrCode class="w-8 h-8 text-white/90" />
                                        <span
                                            class="text-[8px] font-bold mt-1 tracking-wider"
                                            >QR TTE</span
                                        >
                                    </div>
                                    <div
                                        class="text-[6.5pt] text-slate-500 font-bold block mt-1 tracking-wider"
                                    >
                                        VERIFIKASI TTE RESMI
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Canvas Helper Instruction -->
                        <div
                            class="p-3 bg-indigo-50/60 dark:bg-indigo-950/30 rounded-xl border border-indigo-200 dark:border-indigo-800/60 text-xs text-indigo-900 dark:text-indigo-200 flex items-center gap-2"
                        >
                            <Sparkles
                                class="w-4 h-4 text-indigo-600 shrink-0"
                            />
                            <span>
                                <strong>Tip Interaktif:</strong> Anda bisa klik
                                dan geser langsung elemen Kop, Judul Sertifikat,
                                Nama, Nomor, Pelatihan, Tanggal, dan QR Code
                                dengan mouse pada canvas di atas, atau masukkan
                                nilai presisi pada panel di sebelah kanan.
                            </span>
                        </div>

                        <!-- Template Background Upload -->
                        <div
                            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-800 flex items-center justify-between"
                        >
                            <div>
                                <h4
                                    class="text-xs font-bold text-slate-900 dark:text-white"
                                >
                                    Ganti Background Desain Canvas
                                </h4>
                                <p class="text-[11px] text-slate-500">
                                    Rekomendasi ukuran: 297mm x 210mm (3508 x
                                    2480 px, format JPG atau PNG).
                                </p>
                            </div>
                            <label
                                class="cursor-pointer px-3.5 py-2 bg-white dark:bg-slate-700 border border-slate-300 dark:border-slate-600 rounded-lg text-xs font-bold text-slate-700 dark:text-slate-200 hover:bg-slate-50 flex items-center gap-2"
                            >
                                <Upload class="w-3.5 h-3.5" />
                                <span>Pilih Gambar</span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    @change="uploadTemplateImage"
                                    class="hidden"
                                />
                            </label>
                        </div>
                    </div>

                    <!-- Right: Precision Controls & Color Customization (5 cols) -->
                    <div
                        class="lg:col-span-5 bg-white dark:bg-slate-900 p-5 rounded-2xl border border-slate-200 dark:border-slate-800 space-y-5 shadow-sm"
                    >
                        <div class="border-b pb-3">
                            <h3
                                class="text-sm font-black text-slate-900 dark:text-white"
                            >
                                Presisi Tata Letak, Font & Warna
                            </h3>
                            <p class="text-[11px] text-slate-500">
                                Ubah posisi (X & Y), jenis font, teks, dan warna
                                untuk setiap elemen sertifikat.
                            </p>
                        </div>

                        <!-- 1. Pilihan Font Family -->
                        <div
                            class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/80 space-y-1.5"
                        >
                            <label
                                class="block text-[11px] font-bold text-slate-700 dark:text-slate-300 flex items-center gap-1.5"
                            >
                                <Type class="w-3.5 h-3.5 text-indigo-600" />
                                <span>Jenis Huruf / Typography Sertifikat</span>
                            </label>
                            <select
                                v-model="certConfig.font_family"
                                @change="recordCertHistory"
                                class="w-full px-3 py-2 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800 text-slate-900 dark:text-white font-medium focus:ring-2 focus:ring-indigo-500"
                            >
                                <option
                                    v-for="f in certFontFamilies"
                                    :key="f.value"
                                    :value="f.value"
                                >
                                    {{ f.label }}
                                </option>
                            </select>
                            <p class="text-[10px] text-slate-500">
                                Font akan diterapkan seragam baik pada tampilan
                                canvas maupun hasil cetak PDF.
                            </p>
                        </div>

                        <!-- 2. Format Nomor Sertifikat (Standar Baku) -->
                        <div
                            class="p-3.5 bg-gradient-to-br from-indigo-50 to-blue-50 dark:from-indigo-950/40 dark:to-slate-800/60 rounded-xl border border-indigo-200 dark:border-indigo-800/60 space-y-1.5"
                        >
                            <div
                                class="flex items-center gap-1.5 text-xs font-bold text-indigo-900 dark:text-indigo-200"
                            >
                                <Award class="w-4 h-4 text-indigo-600" />
                                <span>Format Nomor Sertifikat (Baku)</span>
                            </div>
                            <div
                                class="font-mono text-xs font-black text-indigo-700 dark:text-indigo-300 bg-white dark:bg-slate-900 px-2.5 py-1.5 rounded-lg border border-indigo-200 dark:border-indigo-800 flex items-center justify-between"
                            >
                                <span>BPVP-PANGKEP/LMS/(TAHUN)/(ID)</span>
                                <span
                                    class="text-[10px] text-emerald-600 font-sans font-bold flex items-center gap-1"
                                >
                                    <Check class="w-3 h-3" /> Standar
                                </span>
                            </div>
                            <p
                                class="text-[11px] text-slate-500 dark:text-slate-400"
                            >
                                Contoh hasil penerbitan:
                                <span
                                    class="font-mono font-bold text-slate-700 dark:text-slate-300"
                                    >BPVP-PANGKEP/LMS/{{
                                        new Date().getFullYear()
                                    }}/1</span
                                >
                            </p>
                        </div>

                        <!-- 3. Quick Element Switcher Tabs -->
                        <div class="space-y-1">
                            <label
                                class="block text-[11px] font-bold text-slate-600 dark:text-slate-400"
                            >
                                Pilih Elemen yang Diedit:
                            </label>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    type="button"
                                    v-for="item in [
                                        {
                                            key: 'header_kop',
                                            label: 'Kop Instansi',
                                        },
                                        {
                                            key: 'certificate_title',
                                            label: 'Judul Sertifikat',
                                        },
                                        {
                                            key: 'recipient_name',
                                            label: 'Nama Peserta',
                                        },
                                        {
                                            key: 'course_title',
                                            label: 'Judul Pelatihan',
                                        },
                                        {
                                            key: 'certificate_number',
                                            label: 'Nomor Sertifikat',
                                        },
                                        { key: 'issue_date', label: 'Tanggal' },
                                        { key: 'qr_code', label: 'QR TTE' },
                                    ]"
                                    :key="item.key"
                                    @click="selectCertElement(item.key)"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-all cursor-pointer"
                                    :class="
                                        selectedCertKey === item.key
                                            ? 'bg-indigo-600 text-white shadow-sm'
                                            : 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200'
                                    "
                                >
                                    {{ item.label }}
                                </button>
                            </div>
                        </div>

                        <!-- CONTROLS FOR EACH ELEMENT -->

                        <!-- A. Kop Instansi Controls -->
                        <div
                            v-show="selectedCertKey === 'header_kop'"
                            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b pb-2"
                            >
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-indigo-600"
                                    ></span>
                                    Kop Instansi (Atas)
                                </span>
                                <div class="flex items-center gap-2">
                                    <label
                                        class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-400 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="certConfig.header_kop.show"
                                            @change="recordCertHistory"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span>Tampilkan</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="centerCertElement('header_kop')"
                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                    >
                                        Tengahkan (X: 50%)
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi X (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="certConfig.header_kop.x"
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="certConfig.header_kop.x"
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi Y (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="certConfig.header_kop.y"
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="certConfig.header_kop.y"
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                            </div>

                            <!-- Baris 1: Teks Kementerian -->
                            <div
                                class="space-y-2 pt-1 border-t border-slate-200 dark:border-slate-700"
                            >
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Teks Baris 1 (Kementerian)
                                    </label>
                                    <input
                                        type="text"
                                        v-model="certConfig.header_kop.line1"
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                        placeholder="KEMENTERIAN KETENAGAKERJAAN REPUBLIK INDONESIA"
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <label
                                            class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                        >
                                            Font Size Baris 1 (pt)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            min="6"
                                            max="20"
                                            v-model.number="
                                                certConfig.header_kop
                                                    .font_size_line1
                                            "
                                            @change="recordCertHistory"
                                            class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                        >
                                            Warna Baris 1
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="color"
                                                v-model="
                                                    certConfig.header_kop
                                                        .color_line1
                                                "
                                                @change="recordCertHistory"
                                                class="w-8 h-8 rounded border border-slate-300 dark:border-slate-700 p-0 cursor-pointer shrink-0"
                                            />
                                            <input
                                                type="text"
                                                v-model="
                                                    certConfig.header_kop
                                                        .color_line1
                                                "
                                                @change="recordCertHistory"
                                                class="w-full px-2 py-1.5 font-mono text-xs border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Baris 2: Teks Balai / BPVP -->
                            <div
                                class="space-y-2 pt-1 border-t border-slate-200 dark:border-slate-700"
                            >
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Teks Baris 2 (Balai / BPVP)
                                    </label>
                                    <input
                                        type="text"
                                        v-model="certConfig.header_kop.line2"
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                        placeholder="BALAI PELATIHAN VOKASI DAN PRODUKTIVITAS (BPVP) PANGKAJENE DAN KEPULAUAN"
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-3 text-xs">
                                    <div>
                                        <label
                                            class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                        >
                                            Font Size Baris 2 (pt)
                                        </label>
                                        <input
                                            type="number"
                                            step="0.5"
                                            min="6"
                                            max="20"
                                            v-model.number="
                                                certConfig.header_kop
                                                    .font_size_line2
                                            "
                                            @change="recordCertHistory"
                                            class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                        >
                                            Warna Baris 2
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <input
                                                type="color"
                                                v-model="
                                                    certConfig.header_kop
                                                        .color_line2
                                                "
                                                @change="recordCertHistory"
                                                class="w-8 h-8 rounded border border-slate-300 dark:border-slate-700 p-0 cursor-pointer shrink-0"
                                            />
                                            <input
                                                type="text"
                                                v-model="
                                                    certConfig.header_kop
                                                        .color_line2
                                                "
                                                @change="recordCertHistory"
                                                class="w-full px-2 py-1.5 font-mono text-xs border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- B. Judul Sertifikat Controls -->
                        <div
                            v-show="selectedCertKey === 'certificate_title'"
                            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b pb-2"
                            >
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-indigo-600"
                                    ></span>
                                    Judul Sertifikat ("SERTIFIKAT PELATIHAN")
                                </span>
                                <div class="flex items-center gap-2">
                                    <label
                                        class="flex items-center gap-1.5 text-[11px] font-semibold text-slate-600 dark:text-slate-400 cursor-pointer"
                                    >
                                        <input
                                            type="checkbox"
                                            v-model="
                                                certConfig.certificate_title
                                                    .show
                                            "
                                            @change="recordCertHistory"
                                            class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <span>Tampilkan</span>
                                    </label>
                                    <button
                                        type="button"
                                        @click="
                                            centerCertElement(
                                                'certificate_title',
                                            )
                                        "
                                        class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                    >
                                        Tengahkan (X: 50%)
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi X (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="
                                            certConfig.certificate_title.x
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="
                                            certConfig.certificate_title.x
                                        "
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi Y (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="
                                            certConfig.certificate_title.y
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="
                                            certConfig.certificate_title.y
                                        "
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                >
                                    Teks Judul
                                </label>
                                <input
                                    type="text"
                                    v-model="certConfig.certificate_title.text"
                                    @change="recordCertHistory"
                                    class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800 uppercase font-black tracking-wider"
                                    placeholder="SERTIFIKAT PELATIHAN"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Font Size (pt)
                                    </label>
                                    <input
                                        type="number"
                                        min="12"
                                        max="48"
                                        v-model.number="
                                            certConfig.certificate_title
                                                .font_size
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Warna Teks
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            v-model="
                                                certConfig.certificate_title
                                                    .color
                                            "
                                            @change="recordCertHistory"
                                            class="w-8 h-8 rounded border border-slate-300 dark:border-slate-700 p-0 cursor-pointer shrink-0"
                                        />
                                        <input
                                            type="text"
                                            v-model="
                                                certConfig.certificate_title
                                                    .color
                                            "
                                            @change="recordCertHistory"
                                            class="w-full px-2 py-1.5 font-mono text-xs border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800"
                                            placeholder="#0f2b48"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 1. Nama Peserta Controls -->
                        <div
                            v-show="selectedCertKey === 'recipient_name'"
                            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b pb-2"
                            >
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-indigo-600"
                                    ></span>
                                    Nama Peserta
                                </span>
                                <button
                                    type="button"
                                    @click="centerCertElement('recipient_name')"
                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                >
                                    Tengahkan (X: 50%)
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi X (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="
                                            certConfig.recipient_name.x
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="
                                            certConfig.recipient_name.x
                                        "
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi Y (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="
                                            certConfig.recipient_name.y
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="
                                            certConfig.recipient_name.y
                                        "
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Font Size (pt)
                                    </label>
                                    <input
                                        type="number"
                                        min="10"
                                        max="60"
                                        v-model.number="
                                            certConfig.recipient_name.font_size
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Warna Teks
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            v-model="
                                                certConfig.recipient_name.color
                                            "
                                            @change="recordCertHistory"
                                            class="w-8 h-8 rounded border border-slate-300 dark:border-slate-700 p-0 cursor-pointer shrink-0"
                                        />
                                        <input
                                            type="text"
                                            v-model="
                                                certConfig.recipient_name.color
                                            "
                                            @change="recordCertHistory"
                                            class="w-full px-2 py-1.5 font-mono text-xs border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800"
                                            placeholder="#0f2b48"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Judul Pelatihan Controls -->
                        <div
                            v-show="selectedCertKey === 'course_title'"
                            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b pb-2"
                            >
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-indigo-600"
                                    ></span>
                                    Judul Pelatihan & Keterangan
                                </span>
                                <button
                                    type="button"
                                    @click="centerCertElement('course_title')"
                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                >
                                    Tengahkan (X: 50%)
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi X (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="
                                            certConfig.course_title.x
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="
                                            certConfig.course_title.x
                                        "
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi Y (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="
                                            certConfig.course_title.y
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="
                                            certConfig.course_title.y
                                        "
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Font Size (pt)
                                    </label>
                                    <input
                                        type="number"
                                        min="10"
                                        max="40"
                                        v-model.number="
                                            certConfig.course_title.font_size
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Warna Teks
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            v-model="
                                                certConfig.course_title.color
                                            "
                                            @change="recordCertHistory"
                                            class="w-8 h-8 rounded border border-slate-300 dark:border-slate-700 p-0 cursor-pointer shrink-0"
                                        />
                                        <input
                                            type="text"
                                            v-model="
                                                certConfig.course_title.color
                                            "
                                            @change="recordCertHistory"
                                            class="w-full px-2 py-1.5 font-mono text-xs border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800"
                                            placeholder="#1e293b"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Nomor Sertifikat Controls -->
                        <div
                            v-show="selectedCertKey === 'certificate_number'"
                            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b pb-2"
                            >
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-indigo-600"
                                    ></span>
                                    Nomor Sertifikat
                                </span>
                                <button
                                    type="button"
                                    @click="
                                        centerCertElement('certificate_number')
                                    "
                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                >
                                    Tengahkan (X: 50%)
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi X (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="
                                            certConfig.certificate_number.x
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="
                                            certConfig.certificate_number.x
                                        "
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi Y (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="
                                            certConfig.certificate_number.y
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="
                                            certConfig.certificate_number.y
                                        "
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Font Size (pt)
                                    </label>
                                    <input
                                        type="number"
                                        min="8"
                                        max="24"
                                        v-model.number="
                                            certConfig.certificate_number
                                                .font_size
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Warna Teks
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            v-model="
                                                certConfig.certificate_number
                                                    .color
                                            "
                                            @change="recordCertHistory"
                                            class="w-8 h-8 rounded border border-slate-300 dark:border-slate-700 p-0 cursor-pointer shrink-0"
                                        />
                                        <input
                                            type="text"
                                            v-model="
                                                certConfig.certificate_number
                                                    .color
                                            "
                                            @change="recordCertHistory"
                                            class="w-full px-2 py-1.5 font-mono text-xs border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800"
                                            placeholder="#475569"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 4. Tanggal Terbit Controls -->
                        <div
                            v-show="selectedCertKey === 'issue_date'"
                            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b pb-2"
                            >
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-indigo-600"
                                    ></span>
                                    Tanggal Terbit
                                </span>
                                <button
                                    type="button"
                                    @click="centerCertElement('issue_date')"
                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                >
                                    Tengahkan (X: 50%)
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi X (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="certConfig.issue_date.x"
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="certConfig.issue_date.x"
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi Y (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="certConfig.issue_date.y"
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="certConfig.issue_date.y"
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Font Size (pt)
                                    </label>
                                    <input
                                        type="number"
                                        min="8"
                                        max="24"
                                        v-model.number="
                                            certConfig.issue_date.font_size
                                        "
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Warna Teks
                                    </label>
                                    <div class="flex items-center gap-2">
                                        <input
                                            type="color"
                                            v-model="
                                                certConfig.issue_date.color
                                            "
                                            @change="recordCertHistory"
                                            class="w-8 h-8 rounded border border-slate-300 dark:border-slate-700 p-0 cursor-pointer shrink-0"
                                        />
                                        <input
                                            type="text"
                                            v-model="
                                                certConfig.issue_date.color
                                            "
                                            @change="recordCertHistory"
                                            class="w-full px-2 py-1.5 font-mono text-xs border border-slate-300 dark:border-slate-700 rounded-lg bg-white dark:bg-slate-800"
                                            placeholder="#64748b"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5. QR Code Controls -->
                        <div
                            v-show="selectedCertKey === 'qr_code'"
                            class="p-4 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b pb-2"
                            >
                                <span
                                    class="text-xs font-bold text-slate-900 dark:text-white flex items-center gap-1.5"
                                >
                                    <span
                                        class="w-2 h-2 rounded-full bg-indigo-600"
                                    ></span>
                                    QR Code TTE Keabsahan
                                </span>
                                <button
                                    type="button"
                                    @click="centerCertElement('qr_code')"
                                    class="text-[10px] text-indigo-600 dark:text-indigo-400 font-bold hover:underline cursor-pointer"
                                >
                                    Tengahkan (X: 50%)
                                </button>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-xs">
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi X (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="certConfig.qr_code.x"
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="certConfig.qr_code.x"
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                    >
                                        Posisi Y (%)
                                    </label>
                                    <input
                                        type="number"
                                        step="0.5"
                                        min="0"
                                        max="100"
                                        v-model.number="certConfig.qr_code.y"
                                        @change="recordCertHistory"
                                        class="w-full px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="0"
                                        max="100"
                                        step="0.5"
                                        v-model.number="certConfig.qr_code.y"
                                        @change="recordCertHistory"
                                        class="w-full mt-1 accent-indigo-600"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="text-[11px] font-semibold text-slate-600 dark:text-slate-400 block mb-1"
                                >
                                    Ukuran QR Code (px)
                                </label>
                                <div class="flex items-center gap-3">
                                    <input
                                        type="number"
                                        min="40"
                                        max="180"
                                        v-model.number="certConfig.qr_code.size"
                                        @change="recordCertHistory"
                                        class="w-24 px-2.5 py-1.5 border border-slate-300 dark:border-slate-700 rounded-lg text-xs bg-white dark:bg-slate-800"
                                    />
                                    <input
                                        type="range"
                                        min="40"
                                        max="180"
                                        v-model.number="certConfig.qr_code.size"
                                        @change="recordCertHistory"
                                        class="w-full accent-indigo-600"
                                    />
                                </div>
                            </div>
                        </div>

                        <button
                            @click="saveCertificateConfig"
                            :disabled="isSavingCert"
                            class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition-all shadow-md shadow-indigo-600/25 flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                        >
                            <Loader2 v-if="isSavingCert" class="w-4 h-4 animate-spin shrink-0" />
                            <Save v-else class="w-4 h-4" />
                            <span>{{
                                isSavingCert
                                    ? "Menyimpan Konfigurasi..."
                                    : "Simpan Konfigurasi Tata Letak"
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ======================================================== -->
        <!-- MODALS                                                   -->
        <!-- ======================================================== -->

        <!-- Modal Tambah / Edit Unit Kompetensi -->
        <div
            v-if="showModuleModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl border border-slate-200 dark:border-slate-800"
            >
                <div class="flex items-center justify-between border-b pb-3">
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        {{
                            isEditingModule
                                ? "Edit Unit Kompetensi"
                                : "Tambah Unit Kompetensi Baru"
                        }}
                    </h3>
                    <button
                        @click="showModuleModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold"
                    >
                        &times;
                    </button>
                </div>
                <form @submit.prevent="submitModule" class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Judul / Nama Unit Kompetensi
                            <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="moduleForm.title"
                            type="text"
                            required
                            placeholder="Contoh: Mengoperasikan Sistem Irigasi Otomatis (atau Kode Unit)"
                            class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                        />
                    </div>
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Deskripsi / Rangkuman Unit Kompetensi
                        </label>
                        <textarea
                            v-model="moduleForm.description"
                            rows="2"
                            placeholder="Penjelasan ringkas mengenai unit kompetensi ini..."
                            class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                        ></textarea>
                    </div>

                    <!-- Pelaksanaan Pada Hari ke- -->
                    <div
                        v-if="isSingleDayCourse"
                        class="p-3 bg-emerald-50 dark:bg-emerald-950/40 rounded-xl border border-emerald-200 dark:border-emerald-800/60 flex items-center justify-between"
                    >
                        <div>
                            <div
                                class="text-xs font-bold text-emerald-900 dark:text-emerald-200 flex items-center gap-1.5"
                            >
                                <span>📅 Pelatihan 1 Hari Penuh</span>
                                <span
                                    class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-200 dark:bg-emerald-800 text-emerald-800 dark:text-emerald-100 font-black"
                                    >Hari ke-1 (Hari Ini)</span
                                >
                            </div>
                            <div
                                class="text-[11px] text-emerald-700 dark:text-emerald-300 mt-0.5"
                            >
                                Tanggal Pelaksanaan:
                                <span class="font-bold">{{
                                    formatDateIndo(course.start_date)
                                }}</span>
                            </div>
                        </div>
                    </div>
                    <div
                        v-else
                        class="grid grid-cols-1 sm:grid-cols-2 gap-3 p-3 bg-indigo-50/50 dark:bg-indigo-950/30 rounded-xl border border-indigo-100 dark:border-indigo-900/50"
                    >
                        <div>
                            <label
                                class="block text-xs font-bold text-indigo-900 dark:text-indigo-200 mb-1"
                            >
                                Pelaksanaan Pada Hari ke-
                                <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold text-slate-500"
                                    >Hari ke-</span
                                >
                                <input
                                    v-model.number="moduleForm.day_number"
                                    type="number"
                                    min="1"
                                    :max="course.duration_in_days || 365"
                                    required
                                    class="w-20 text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-black text-center"
                                />
                            </div>
                        </div>
                        <div class="flex flex-col justify-center">
                            <span
                                class="text-[11px] font-semibold text-slate-500"
                            >
                                Perkiraan Tanggal Pelaksanaan:
                            </span>
                            <span
                                class="text-xs font-bold text-indigo-700 dark:text-indigo-300"
                            >
                                {{
                                    calculateDateForDay(
                                        moduleForm.day_number,
                                    ) || "Menyesuaikan tanggal mulai kelas"
                                }}
                            </span>
                        </div>
                    </div>

                    <!-- Mode Pembelajaran: Sinkronus vs Asinkronus -->
                    <div
                        class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/80 space-y-3"
                    >
                        <label
                            class="block text-xs font-bold text-slate-800 dark:text-slate-200"
                        >
                            Pengaturan Jadwal & Mode Pembelajaran:
                        </label>

                        <div class="grid grid-cols-2 gap-2">
                            <button
                                type="button"
                                @click="moduleForm.delivery_mode = 'sinkronus'"
                                class="p-2.5 rounded-lg border text-left text-xs transition flex items-center gap-2"
                                :class="
                                    moduleForm.delivery_mode === 'sinkronus'
                                        ? 'bg-blue-50 border-blue-500 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 dark:border-blue-700 ring-2 ring-blue-500/30'
                                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100'
                                "
                            >
                                <Video
                                    class="w-4 h-4 shrink-0 text-blue-600 dark:text-blue-400"
                                />
                                <div>
                                    <div class="font-bold">Sinkronus</div>
                                    <div class="text-[10px] text-slate-500">
                                        Online Meeting Tatap Muka
                                    </div>
                                </div>
                            </button>

                            <button
                                type="button"
                                @click="moduleForm.delivery_mode = 'asinkronus'"
                                class="p-2.5 rounded-lg border text-left text-xs transition flex items-center gap-2"
                                :class="
                                    moduleForm.delivery_mode === 'asinkronus'
                                        ? 'bg-emerald-50 border-emerald-500 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border-emerald-700 ring-2 ring-emerald-500/30'
                                        : 'bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100'
                                "
                            >
                                <BookOpen
                                    class="w-4 h-4 shrink-0 text-emerald-600 dark:text-emerald-400"
                                />
                                <div>
                                    <div class="font-bold">Asinkronus</div>
                                    <div class="text-[10px] text-slate-500">
                                        Belajar Mandiri
                                    </div>
                                </div>
                            </button>
                        </div>

                        <!-- Form Sinkronus -->
                        <div
                            v-if="moduleForm.delivery_mode === 'sinkronus'"
                            class="space-y-3 pt-2 border-t border-slate-200 dark:border-slate-700"
                        >
                            <div
                                class="text-[11px] font-bold text-blue-700 dark:text-blue-300 flex items-center gap-1.5"
                            >
                                <Video class="w-3.5 h-3.5" />
                                <span
                                    >Jadwal & Tautan Online Meeting Tatap
                                    Muka</span
                                >
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1"
                                    >
                                        Tanggal Online Meeting
                                    </label>
                                    <input
                                        v-model="moduleForm.scheduled_date"
                                        type="date"
                                        class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                    />
                                </div>
                                <div class="grid grid-cols-2 gap-1.5">
                                    <div>
                                        <label
                                            class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1"
                                        >
                                            Jam Mulai
                                        </label>
                                        <input
                                            v-model="moduleForm.start_time"
                                            type="text"
                                            placeholder="08:00"
                                            class="w-full text-xs px-2 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1"
                                        >
                                            Jam Selesai
                                        </label>
                                        <input
                                            v-model="moduleForm.end_time"
                                            type="text"
                                            placeholder="10:00"
                                            class="w-full text-xs px-2 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1"
                                >
                                    Link Online Meeting Tatap Muka
                                </label>
                                <input
                                    v-model="moduleForm.zoom_link"
                                    type="text"
                                    placeholder="Contoh: https://meet.google.com/... atau https://zoom.us/j/..."
                                    class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                />
                            </div>

                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1"
                                    >
                                        Meeting ID (Opsional)
                                    </label>
                                    <input
                                        v-model="moduleForm.zoom_meeting_id"
                                        type="text"
                                        placeholder="Contoh: 832 9481 0291"
                                        class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1"
                                    >
                                        Passcode (Opsional)
                                    </label>
                                    <input
                                        v-model="moduleForm.zoom_passcode"
                                        type="text"
                                        placeholder="Contoh: 123456"
                                        class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Form Asinkronus -->
                        <div
                            v-else
                            class="space-y-3 pt-2 border-t border-slate-200 dark:border-slate-700"
                        >
                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1"
                                >
                                    Durasi Pengerjaan / Belajar Mandiri (Hari)
                                </label>
                                <div class="flex items-center gap-2">
                                    <input
                                        v-model.number="
                                            moduleForm.duration_days
                                        "
                                        type="number"
                                        min="1"
                                        max="365"
                                        class="w-24 text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                    />
                                    <span
                                        class="text-xs text-slate-500 font-medium"
                                        >Hari</span
                                    >
                                </div>
                            </div>
                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300 mb-1"
                                >
                                    Keterangan / Panduan Belajar Mandiri
                                </label>
                                <textarea
                                    v-model="moduleForm.notes"
                                    rows="2"
                                    placeholder="Contoh: Selesaikan membaca materi dan video elemen kompetensi ini secara mandiri dalam waktu 1 hari."
                                    class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                ></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button
                            type="button"
                            @click="showModuleModal = false"
                            class="px-3 py-2 text-xs font-medium text-slate-500 hover:text-slate-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="moduleForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-2"
                        >
                            <Loader2
                                v-if="moduleForm.processing"
                                class="w-3.5 h-3.5 animate-spin"
                            />
                            <span>{{
                                moduleForm.processing
                                    ? "Menyimpan..."
                                    : "Simpan Unit Kompetensi"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah / Edit Elemen Kompetensi -->
        <div
            v-if="showLessonModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between border-b pb-3">
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        {{
                            isEditingLesson
                                ? "Edit Elemen Kompetensi"
                                : "Tambah Elemen Kompetensi Baru"
                        }}
                    </h3>
                    <button
                        @click="showLessonModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold"
                    >
                        &times;
                    </button>
                </div>
                <form @submit.prevent="submitLesson" class="space-y-4">
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Judul Elemen Kompetensi
                            <span class="text-rose-500">*</span>
                        </label>
                        <input
                            v-model="lessonForm.title"
                            type="text"
                            required
                            placeholder="Contoh: Mengoperasikan Pompa & Valve Kontrol Tekanan"
                            class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                        />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Tipe Konten Materi
                            </label>
                            <select
                                v-model="lessonForm.content_type"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                            >
                                <option value="article">
                                    Artikel / Modul Bacaan
                                </option>
                                <option value="video">
                                    Video Praktik / Tutorial
                                </option>
                                <option value="image">
                                    Gambar Kerja / Infografis
                                </option>
                                <option value="pdf">
                                    Dokumen PDF / Slide Modul
                                </option>
                            </select>
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Estimasi Waktu (Menit)
                            </label>
                            <input
                                v-model.number="
                                    lessonForm.estimated_duration_minutes
                                "
                                type="number"
                                min="1"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                            />
                        </div>
                    </div>

                    <!-- Video Fields -->
                    <div
                        v-if="lessonForm.content_type === 'video'"
                        class="space-y-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg"
                    >
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                URL Video (YouTube / Link Stream)
                            </label>
                            <input
                                v-model="lessonForm.video_url"
                                type="url"
                                placeholder="https://www.youtube.com/watch?v=..."
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                            />
                        </div>
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Atau Upload File Video (MP4)
                            </label>
                            <input
                                type="file"
                                accept="video/mp4,video/*"
                                @change="handleLessonMedia"
                                class="w-full text-xs"
                            />
                        </div>
                    </div>

                    <!-- Image Fields -->
                    <div
                        v-if="lessonForm.content_type === 'image'"
                        class="space-y-3 p-3 bg-slate-50 dark:bg-slate-800 rounded-lg"
                    >
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Upload File Gambar / Slide / Infografis
                        </label>
                        <input
                            type="file"
                            accept="image/*"
                            @change="handleLessonMedia"
                            class="w-full text-xs"
                        />
                    </div>

                    <!-- PDF Fields -->
                    <div
                        v-if="lessonForm.content_type === 'pdf'"
                        class="space-y-3 p-3 bg-amber-50/60 dark:bg-amber-950/20 border border-amber-200/80 dark:border-amber-800/50 rounded-lg"
                    >
                        <div>
                            <label
                                class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                            >
                                Upload File Dokumen PDF (.pdf)
                            </label>
                            <input
                                type="file"
                                accept=".pdf,application/pdf"
                                @change="handleLessonMedia"
                                class="w-full text-xs file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-100 file:text-amber-800 hover:file:bg-amber-200 dark:file:bg-amber-900/60 dark:file:text-amber-300"
                            />
                            <p class="text-[11px] text-slate-500 dark:text-slate-400 mt-1.5">
                                Format wajib PDF (maks. 50MB). Materi ini akan disajikan ke siswa dalam mode pembaca mirip buku/slide presentasi dengan proteksi wajib tuntas membaca hingga halaman terakhir serta fitur Full Screen.
                            </p>
                            <div
                                v-if="currentLessonMediaPath && isEditingLesson"
                                class="mt-2 p-2 bg-white dark:bg-slate-900 rounded border border-amber-200 dark:border-amber-800 text-xs flex items-center justify-between"
                            >
                                <span class="text-slate-600 dark:text-slate-400 flex items-center gap-1.5 truncate">
                                    <FileText class="w-3.5 h-3.5 text-amber-600 shrink-0" />
                                    <span class="truncate">File PDF saat ini tersimpan di server</span>
                                </span>
                                <a
                                    :href="'/storage/' + currentLessonMediaPath"
                                    target="_blank"
                                    class="px-2 py-0.5 bg-amber-100 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 rounded font-semibold text-[11px] shrink-0 hover:underline"
                                >
                                    Buka Dokumen &nearr;
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Article / Text Fields -->
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Isi Materi / Teks Penjelasan
                        </label>
                        <textarea
                            v-model="lessonForm.content_text"
                            rows="6"
                            placeholder="Tuliskan uraian materi, langkah-langkah kerja, instruksi kerja (SOP), atau panduan K3..."
                            class="w-full text-xs px-3 py-2 border rounded-lg font-mono bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                        ></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button
                            type="button"
                            @click="showLessonModal = false"
                            class="px-3 py-2 text-xs font-medium text-slate-500 hover:text-slate-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="lessonForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-2"
                        >
                            <Loader2
                                v-if="lessonForm.processing"
                                class="w-3.5 h-3.5 animate-spin"
                            />
                            <span>{{
                                lessonForm.processing
                                    ? "Menyimpan..."
                                    : "Simpan Elemen Kompetensi"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Tambah Peserta Manual -->
        <div
            v-if="showParticipantModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-md w-full p-6 space-y-4 shadow-xl border border-slate-200 dark:border-slate-800"
            >
                <div class="flex items-center justify-between border-b pb-3">
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        Daftarkan Peserta Manual
                    </h3>
                    <button
                        @click="showParticipantModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold"
                    >
                        &times;
                    </button>
                </div>
                <form @submit.prevent="submitParticipant" class="space-y-3">
                    <div>
                        <label class="block text-xs font-bold mb-1"
                            >Nama Lengkap
                            <span class="text-rose-500">*</span></label
                        >
                        <input
                            v-model="participantForm.name"
                            required
                            type="text"
                            placeholder="Nama Peserta"
                            class="w-full text-xs px-3 py-2 border rounded-lg"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1"
                            >Email <span class="text-rose-500">*</span></label
                        >
                        <input
                            v-model="participantForm.email"
                            required
                            type="email"
                            placeholder="peserta@email.com"
                            class="w-full text-xs px-3 py-2 border rounded-lg"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1"
                            >NIK (Nomor Induk Kependudukan)</label
                        >
                        <input
                            v-model="participantForm.nik"
                            type="text"
                            placeholder="16 digit NIK"
                            class="w-full text-xs px-3 py-2 border rounded-lg"
                        />
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1"
                            >No. Handphone / WhatsApp</label
                        >
                        <input
                            v-model="participantForm.phone"
                            type="text"
                            placeholder="08xxxxxxxxxx"
                            class="w-full text-xs px-3 py-2 border rounded-lg"
                        />
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >Kode Transaksi</label
                            >
                            <input
                                v-model="
                                    participantForm.training_transaction_code
                                "
                                type="text"
                                placeholder="Kode Transaksi / Registrasi"
                                class="w-full text-xs px-3 py-2 border rounded-lg"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >Jenis Kelamin</label
                            >
                            <select
                                v-model="participantForm.gender"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800"
                            >
                                <option value="L">Laki-Laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold mb-1"
                            >Alamat Lengkap / Domisili</label
                        >
                        <textarea
                            v-model="participantForm.address"
                            rows="2"
                            placeholder="Alamat tempat tinggal / KTP"
                            class="w-full text-xs px-3 py-2 border rounded-lg resize-none"
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button
                            type="button"
                            @click="showParticipantModal = false"
                            class="px-3 py-2 text-xs"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="participantForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-2"
                        >
                            <Loader2
                                v-if="participantForm.processing"
                                class="w-3.5 h-3.5 animate-spin"
                            />
                            <span>{{
                                participantForm.processing
                                    ? "Mendaftarkan..."
                                    : "Daftarkan"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Edit Peserta -->
        <div
            v-if="showEditModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-xl border border-slate-200 dark:border-slate-800 max-h-[90vh] overflow-y-auto"
            >
                <div class="flex items-center justify-between border-b pb-3">
                    <div>
                        <h3
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Edit Data & Status Peserta
                        </h3>
                        <p class="text-xs text-slate-500">
                            Perbarui identitas, status kelas, jalur presensi,
                            dan riwayat kehadiran harian.
                        </p>
                    </div>
                    <button
                        @click="showEditModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold"
                    >
                        &times;
                    </button>
                </div>
                <form
                    @submit.prevent="submitEditParticipant"
                    class="space-y-3.5"
                >
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >Nama Lengkap
                                <span class="text-rose-500">*</span></label
                            >
                            <input
                                v-model="editParticipantForm.name"
                                required
                                type="text"
                                placeholder="Nama Peserta"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >Email
                                <span class="text-rose-500">*</span></label
                            >
                            <input
                                v-model="editParticipantForm.email"
                                required
                                type="email"
                                placeholder="peserta@email.com"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >NIK (Nomor Induk Kependudukan)</label
                            >
                            <input
                                v-model="editParticipantForm.nik"
                                type="text"
                                placeholder="16 digit NIK"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >No. Handphone / WhatsApp</label
                            >
                            <input
                                v-model="editParticipantForm.phone"
                                type="text"
                                placeholder="08xxxxxxxxxx"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                            />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >Kode Transaksi</label
                            >
                            <input
                                v-model="
                                    editParticipantForm.training_transaction_code
                                "
                                type="text"
                                placeholder="Kode Transaksi / Registrasi"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700 font-mono"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >Jenis Kelamin</label
                            >
                            <select
                                v-model="editParticipantForm.gender"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                            >
                                <option value="L">Laki-Laki (L)</option>
                                <option value="P">Perempuan (P)</option>
                            </select>
                        </div>
                    </div>

                    <!-- Status Kelas, Jalur Presensi, Progress -->
                    <div
                        class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/80 space-y-3"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-bold mb-1"
                                    >Status Kelas</label
                                >
                                <select
                                    v-model="editParticipantForm.status"
                                    class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-semibold"
                                >
                                    <option value="enrolled">Terdaftar</option>
                                    <option value="in_progress">
                                        Sedang Belajar
                                    </option>
                                    <option value="completed">
                                        Sudah Mengikuti (Lulus)
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1"
                                    >Jalur Presensi Utama</label
                                >
                                <select
                                    v-model="
                                        editParticipantForm.attendance_path
                                    "
                                    class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-semibold"
                                    :class="
                                        editParticipantForm.attendance_path ===
                                        'live_zoom'
                                            ? 'text-rose-600 dark:text-rose-400'
                                            : editParticipantForm.attendance_path ===
                                                'self_study'
                                              ? 'text-blue-600 dark:text-blue-400'
                                              : 'text-slate-500'
                                    "
                                >
                                    <option value="none">Belum Presensi</option>
                                    <option value="live_zoom">
                                        Online Meeting Tatap Muka
                                    </option>
                                    <option value="self_study">
                                        Belajar Mandiri Susulan
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-bold mb-1"
                                    >Progress (%)</label
                                >
                                <input
                                    v-model.number="
                                        editParticipantForm.progress_percentage
                                    "
                                    type="number"
                                    min="0"
                                    max="100"
                                    placeholder="0"
                                    class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono font-bold"
                                />
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-bold mb-1"
                                >Nomor Sertifikat (Opsional)</label
                            >
                            <input
                                v-model="editParticipantForm.certificate_number"
                                type="text"
                                placeholder="Contoh: BPVP-PKP/TIK/2026/001"
                                class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 font-mono"
                            />
                        </div>
                    </div>

                    <!-- Presensi Per Hari / Unit Kompetensi -->
                    <div
                        v-if="course.modules && course.modules.length > 0"
                        class="p-3 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700/80 space-y-2.5"
                    >
                        <div class="flex items-center justify-between">
                            <label
                                class="block text-xs font-bold text-slate-800 dark:text-slate-200"
                            >
                                Presensi Tiap Hari / Unit Kompetensi:
                            </label>
                            <span class="text-[10px] text-slate-500">
                                {{ course.modules.length }} Unit
                            </span>
                        </div>
                        <div class="space-y-2 max-h-48 overflow-y-auto pr-1">
                            <div
                                v-for="(mod, modIdx) in course.modules"
                                :key="mod.id"
                                class="flex items-center justify-between gap-3 p-2 bg-white dark:bg-slate-800 rounded-lg border border-slate-200 dark:border-slate-700 text-xs"
                            >
                                <div class="min-w-0 pr-2">
                                    <span
                                        class="font-bold text-indigo-600 dark:text-indigo-400"
                                    >
                                        Hari {{ mod.day_number || modIdx + 1 }}:
                                    </span>
                                    <span
                                        class="text-slate-700 dark:text-slate-300 ml-1 truncate"
                                    >
                                        {{ mod.title }}
                                    </span>
                                </div>
                                <select
                                    v-model="
                                        editParticipantForm.daily_attendances[
                                            mod.id
                                        ]
                                    "
                                    class="shrink-0 text-xs px-2.5 py-1.5 rounded-md border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-900 font-semibold cursor-pointer"
                                    :class="
                                        editParticipantForm.daily_attendances[
                                            mod.id
                                        ] === 'live_zoom'
                                            ? 'text-rose-600 dark:text-rose-400 border-rose-300'
                                            : editParticipantForm
                                                    .daily_attendances[
                                                    mod.id
                                                ] === 'self_study'
                                              ? 'text-blue-600 dark:text-blue-400 border-blue-300'
                                              : 'text-slate-500'
                                    "
                                >
                                    <option value="none">Belum Hadir</option>
                                    <option value="live_zoom">
                                        Online Meeting
                                    </option>
                                    <option value="self_study">
                                        Belajar Mandiri
                                    </option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold mb-1"
                            >Alamat Lengkap / Domisili</label
                        >
                        <textarea
                            v-model="editParticipantForm.address"
                            rows="2"
                            placeholder="Alamat tempat tinggal / KTP"
                            class="w-full text-xs px-3 py-2 border rounded-lg resize-none bg-white dark:bg-slate-800 border-slate-200 dark:border-slate-700"
                        ></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button
                            type="button"
                            @click="showEditModal = false"
                            class="px-3 py-2 text-xs text-slate-500 hover:text-slate-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="editParticipantForm.processing"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-2"
                        >
                            <Loader2
                                v-if="editParticipantForm.processing"
                                class="w-3.5 h-3.5 animate-spin"
                            />
                            <span>{{
                                editParticipantForm.processing
                                    ? "Menyimpan..."
                                    : "Simpan Perubahan"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Import Excel / CSV -->
        <div
            v-if="showImportModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-lg w-full p-6 space-y-4 shadow-xl border border-slate-200 dark:border-slate-800"
            >
                <div class="flex items-center justify-between border-b pb-3">
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        Import File Peserta (Excel / CSV)
                    </h3>
                    <button
                        @click="showImportModal = false"
                        class="text-slate-400 hover:text-slate-600 text-xl font-bold"
                    >
                        &times;
                    </button>
                </div>
                <div
                    class="p-3 bg-blue-50 dark:bg-blue-950/40 rounded-lg text-xs text-blue-800 dark:text-blue-300 space-y-1"
                >
                    <p class="font-bold">Format Kolom Header yang Dikenali:</p>
                    <p class="font-mono text-[11px]">
                        Nama Lengkap, Email, NIK, No HP, Asal Instansi, Jenis
                        Kelamin (L/P)
                    </p>
                    <p
                        class="text-[10px] text-blue-600 dark:text-blue-400 mt-1"
                    >
                        * Email digunakan sebagai identitas unik peserta. Jika
                        peserta sudah ada, sistem akan langsung menyinkronkan
                        data kelasnya.
                    </p>
                </div>
                <form @submit.prevent="submitImport" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold mb-1"
                            >Pilih File (.xlsx atau .csv)</label
                        >
                        <input
                            type="file"
                            accept=".xlsx,.csv,.txt"
                            required
                            @change="
                                (e) => (importForm.file = e.target.files[0])
                            "
                            class="w-full text-xs"
                        />
                    </div>
                    <div class="flex justify-end gap-2 pt-2 border-t">
                        <button
                            type="button"
                            @click="showImportModal = false"
                            class="px-3 py-2 text-xs"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="importForm.processing || !importForm.file"
                            class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-2"
                        >
                            <Loader2
                                v-if="importForm.processing"
                                class="w-3.5 h-3.5 animate-spin"
                            />
                            <span>{{
                                importForm.processing
                                    ? "Mengimpor..."
                                    : "Mulai Import"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Import Unit & Elemen Kompetensi (Excel / CSV) -->
        <div
            v-if="showCurriculumImportModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-xl w-full p-6 space-y-4 shadow-xl border border-slate-200 dark:border-slate-800"
            >
                <div
                    class="flex items-center justify-between border-b pb-3 border-slate-200 dark:border-slate-800"
                >
                    <div>
                        <h3
                            class="text-base font-bold text-slate-900 dark:text-white"
                        >
                            Import Unit & Elemen Kompetensi
                        </h3>
                        <p class="text-xs text-slate-500 dark:text-slate-400">
                            Unggah silabus kurikulum PBK secara massal
                            menggunakan file Excel (.xlsx) atau CSV
                        </p>
                    </div>
                    <button
                        @click="showCurriculumImportModal = false"
                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xl font-bold"
                    >
                        &times;
                    </button>
                </div>

                <div
                    class="p-3.5 bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 rounded-xl text-xs text-indigo-900 dark:text-indigo-200 space-y-2"
                >
                    <div class="flex items-center justify-between">
                        <span class="font-bold"
                            >Format Kolom Template Import:</span
                        >
                        <a
                            :href="`/admin/lms/${course.id}/curriculum/template`"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-2.5 py-1 bg-white dark:bg-slate-800 text-indigo-600 dark:text-indigo-400 border border-indigo-200 dark:border-indigo-800 rounded-lg text-[11px] font-bold shadow-sm hover:bg-indigo-50 transition"
                        >
                            <Download class="w-3.5 h-3.5" />
                            <span>Download Template CSV</span>
                        </a>
                    </div>
                    <p
                        class="font-mono text-[11px] bg-white/70 dark:bg-slate-900/60 p-2 rounded border border-indigo-100 dark:border-indigo-900/40"
                    >
                        Kode Unit, Judul Unit Kompetensi, Deskripsi Unit, Judul
                        Elemen Kompetensi, Tipe Konten, Durasi, Isi Materi, URL
                        Video
                    </p>
                    <p class="text-[11px] text-slate-600 dark:text-slate-300">
                        * Sistem akan otomatis mengelompokkan baris berdasarkan
                        <strong>Kode Unit</strong> atau
                        <strong>Judul Unit Kompetensi</strong>, dan membuat
                        <strong>Elemen Kompetensi</strong> di dalamnya.
                    </p>
                </div>

                <form
                    @submit.prevent="submitCurriculumImport"
                    class="space-y-4"
                >
                    <div>
                        <label
                            class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                        >
                            Pilih File Excel / CSV Silabus (.xlsx, .csv)
                        </label>
                        <input
                            type="file"
                            accept=".xlsx,.csv,.txt"
                            required
                            @change="
                                (e) =>
                                    (curriculumImportForm.file =
                                        e.target.files[0])
                            "
                            class="w-full text-xs file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-slate-800 dark:file:text-slate-200"
                        />
                    </div>

                    <div
                        class="flex justify-end gap-2 pt-3 border-t border-slate-200 dark:border-slate-800"
                    >
                        <button
                            type="button"
                            @click="showCurriculumImportModal = false"
                            class="px-3 py-2 text-xs font-medium text-slate-500 hover:text-slate-700"
                        >
                            Batal
                        </button>
                        <button
                            type="submit"
                            :disabled="
                                curriculumImportForm.processing ||
                                !curriculumImportForm.file
                            "
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-2"
                        >
                            <Loader2
                                v-if="curriculumImportForm.processing"
                                class="w-3.5 h-3.5 animate-spin"
                            />
                            <span>{{
                                curriculumImportForm.processing
                                    ? "Mengunggah & Memproses..."
                                    : "Mulai Import Kurikulum"
                            }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Modal Kelola Kuis Unit Kompetensi -->
        <div
            v-if="showQuizModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-3xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl flex flex-col max-h-[90vh] overflow-hidden"
            >
                <!-- Modal Header -->
                <div
                    class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/60 dark:bg-slate-800/50"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-amber-100 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-sm"
                        >
                            <HelpCircle class="w-5 h-5" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                {{
                                    activeQuiz
                                        ? "Kelola: " + activeQuiz.title
                                        : "Tambah Kuis Baru"
                                }}
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400 line-clamp-1"
                            >
                                Unit: {{ activeQuizModule?.title }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showQuizModal = false"
                        class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="p-6 overflow-y-auto space-y-6">
                    <!-- SECTION 1: Pengaturan Kuis -->
                    <div
                        class="bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 p-4 rounded-xl space-y-3"
                    >
                        <div
                            class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-2"
                        >
                            <h4
                                class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300"
                            >
                                1. Pengaturan Kuis Unit
                            </h4>
                            <span
                                v-if="activeQuiz || activeQuizModule?.quiz"
                                class="text-[11px] font-bold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/60 px-2 py-0.5 rounded-full border border-emerald-200 dark:border-emerald-800"
                            >
                                Kuis Aktif (Tersimpan)
                            </span>
                            <span
                                v-else
                                class="text-[11px] font-medium text-slate-400"
                            >
                                Kuis Baru (Belum Disimpan)
                            </span>
                        </div>

                        <form
                            @submit.prevent="submitQuizForm"
                            class="space-y-3"
                        >
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                <div class="sm:col-span-2">
                                    <label
                                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                                    >
                                        Judul Kuis
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model="quizForm.title"
                                        type="text"
                                        required
                                        placeholder="Contoh: Kuis Pemahaman Unit 1"
                                        class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                                    >
                                        Nilai Kelulusan (KKM %)
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <input
                                        v-model.number="quizForm.passing_score"
                                        type="number"
                                        min="10"
                                        max="100"
                                        required
                                        placeholder="70"
                                        class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                                >
                                    Petunjuk Pengerjaan / Deskripsi
                                </label>
                                <textarea
                                    v-model="quizForm.description"
                                    rows="2"
                                    placeholder="Tuliskan petunjuk pengerjaan kuis untuk peserta..."
                                    class="w-full text-xs px-3 py-2 border rounded-lg resize-none bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-between pt-2">
                                <button
                                    v-if="activeQuiz || activeQuizModule?.quiz"
                                    type="button"
                                    :disabled="deletingQuizId === (activeQuiz?.id || activeQuizModule?.quiz?.id)"
                                    @click="
                                        deleteQuiz(
                                            activeQuiz ||
                                                activeQuizModule?.quiz,
                                        )
                                    "
                                    class="text-xs font-semibold text-rose-600 hover:text-rose-700 underline inline-flex items-center gap-1 disabled:opacity-50"
                                >
                                    <Loader2
                                        v-if="deletingQuizId === (activeQuiz?.id || activeQuizModule?.quiz?.id)"
                                        class="w-3.5 h-3.5 animate-spin"
                                    />
                                    <span>Hapus Kuis Ini</span>
                                </button>
                                <div v-else></div>

                                <button
                                    type="submit"
                                    :disabled="quizForm.processing"
                                    class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-2"
                                >
                                    <Loader2
                                        v-if="quizForm.processing"
                                        class="w-3.5 h-3.5 animate-spin"
                                    />
                                    <span>{{
                                        quizForm.processing
                                            ? "Menyimpan..."
                                            : activeQuiz ||
                                                activeQuizModule?.quiz
                                              ? "Simpan Pengaturan Kuis"
                                              : "Buat & Simpan Kuis Baru"
                                    }}</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- SECTION 2: Daftar Butir Soal -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4
                                class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300"
                            >
                                2. Butir Soal Kuis ({{
                                    (activeQuiz || activeQuizModule?.quiz)
                                        ?.questions?.length || 0
                                }}
                                Butir)
                            </h4>
                            <span
                                v-if="!(activeQuiz || activeQuizModule?.quiz)"
                                class="text-[11px] text-amber-600 dark:text-amber-400 font-medium"
                            >
                                * Buat dan simpan kuis di atas terlebih dahulu
                                untuk menambah butir soal.
                            </span>
                        </div>

                        <!-- Questions List -->
                        <div
                            v-if="
                                (activeQuiz || activeQuizModule?.quiz)
                                    ?.questions?.length > 0
                            "
                            class="space-y-3"
                        >
                            <div
                                v-for="(q, qIdx) in (
                                    activeQuiz || activeQuizModule?.quiz
                                ).questions"
                                :key="q.id"
                                class="p-3.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl space-y-2.5 shadow-sm"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <div class="flex items-start gap-2.5">
                                        <span
                                            class="w-5 h-5 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-[10px] font-bold flex items-center justify-center shrink-0 mt-0.5"
                                        >
                                            {{ qIdx + 1 }}
                                        </span>
                                        <div>
                                            <p
                                                class="text-xs font-bold text-slate-900 dark:text-white leading-relaxed"
                                            >
                                                {{ q.question_text }}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex items-center gap-1 shrink-0"
                                    >
                                        <button
                                            type="button"
                                            @click="editQuestion(q)"
                                            class="p-1 text-slate-400 hover:text-indigo-600 rounded"
                                            title="Edit Soal"
                                        >
                                            <Edit class="w-3.5 h-3.5" />
                                        </button>
                                        <button
                                            type="button"
                                            @click="deleteQuestion(q)"
                                            :disabled="deletingQuestionId === q.id"
                                            class="p-1 text-slate-400 hover:text-rose-600 rounded disabled:opacity-50"
                                            title="Hapus Soal"
                                        >
                                            <Loader2
                                                v-if="deletingQuestionId === q.id"
                                                class="w-3.5 h-3.5 animate-spin text-rose-600"
                                            />
                                            <Trash2 v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </div>

                                <!-- Options Display -->
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-1.5 pl-7"
                                >
                                    <div
                                        v-for="opt in q.options"
                                        :key="opt.key"
                                        class="px-2.5 py-1.5 rounded-lg text-xs flex items-center gap-2 border"
                                        :class="
                                            opt.key === q.correct_answer
                                                ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200 font-bold'
                                                : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300'
                                        "
                                    >
                                        <span
                                            class="w-4 h-4 rounded text-[10px] flex items-center justify-center font-bold"
                                            :class="
                                                opt.key === q.correct_answer
                                                    ? 'bg-emerald-600 text-white'
                                                    : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'
                                            "
                                        >
                                            {{ opt.key }}
                                        </span>
                                        <span class="truncate">{{
                                            opt.text
                                        }}</span>
                                        <Check
                                            v-if="opt.key === q.correct_answer"
                                            class="w-3.5 h-3.5 ml-auto text-emerald-600 shrink-0"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-if="q.explanation"
                                    class="pl-7 text-[11px] text-slate-500 italic"
                                >
                                    Pembahasan: {{ q.explanation }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="activeQuizModule?.quiz"
                            class="p-6 text-center border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-xl text-xs text-slate-400"
                        >
                            Belum ada butir soal. Silakan gunakan form di bawah
                            untuk membuat butir soal pertama kuis ini.
                        </div>

                        <!-- Form Tambah / Edit Butir Soal -->
                        <div
                            v-if="activeQuizModule?.quiz"
                            class="p-4 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-xl space-y-3"
                        >
                            <div
                                class="flex items-center justify-between border-b border-slate-200 dark:border-slate-700 pb-2"
                            >
                                <span
                                    class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                >
                                    {{
                                        isEditingQuestion
                                            ? "Edit Butir Soal"
                                            : "+ Tambah Butir Soal Pilihan Ganda"
                                    }}
                                </span>
                                <button
                                    v-if="isEditingQuestion"
                                    type="button"
                                    @click="resetQuestionForm"
                                    class="text-[11px] font-semibold text-slate-500 hover:text-slate-700"
                                >
                                    Batal Edit
                                </button>
                            </div>

                            <form
                                @submit.prevent="submitQuestionForm"
                                class="space-y-3"
                            >
                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                                    >
                                        Pertanyaan
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <textarea
                                        v-model="questionForm.question_text"
                                        rows="2"
                                        required
                                        placeholder="Tuliskan pertanyaan kuis di sini..."
                                        class="w-full text-xs px-3 py-2 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                    ></textarea>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                                    >
                                        Pilihan Jawaban & Kunci Jawaban Benar
                                        <span class="text-rose-500">*</span>
                                    </label>
                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-2"
                                    >
                                        <div
                                            v-for="(
                                                opt, idx
                                            ) in questionForm.options"
                                            :key="opt.key"
                                            class="flex items-center gap-2 p-2 rounded-lg border bg-white dark:bg-slate-900"
                                            :class="
                                                questionForm.correct_answer ===
                                                opt.key
                                                    ? 'border-emerald-500 ring-1 ring-emerald-500'
                                                    : 'border-slate-200 dark:border-slate-700'
                                            "
                                        >
                                            <input
                                                type="radio"
                                                :name="'correct_answer_choice'"
                                                :value="opt.key"
                                                v-model="
                                                    questionForm.correct_answer
                                                "
                                                class="w-3.5 h-3.5 text-emerald-600 focus:ring-emerald-500"
                                                :title="
                                                    'Pilih ' +
                                                    opt.key +
                                                    ' sebagai kunci jawaban benar'
                                                "
                                            />
                                            <span
                                                class="w-5 font-bold text-xs text-slate-700 dark:text-slate-300"
                                                >{{ opt.key }}.</span
                                            >
                                            <input
                                                v-model="opt.text"
                                                type="text"
                                                required
                                                :placeholder="
                                                    'Teks pilihan ' + opt.key
                                                "
                                                class="flex-1 text-xs px-2 py-1 border-0 focus:ring-0 bg-transparent text-slate-800 dark:text-slate-200"
                                            />
                                        </div>
                                    </div>
                                    <p class="text-[10px] text-slate-500 mt-1">
                                        * Pilih radio button di sebelah kiri
                                        opsi untuk menentukan kunci jawaban yang
                                        benar.
                                    </p>
                                </div>

                                <div>
                                    <label
                                        class="block text-xs font-bold text-slate-700 dark:text-slate-300 mb-1"
                                    >
                                        Penjelasan / Pembahasan (Opsional)
                                    </label>
                                    <input
                                        v-model="questionForm.explanation"
                                        type="text"
                                        placeholder="Penjelasan yang tampil setelah siswa menyelesaikan kuis..."
                                        class="w-full text-xs px-3 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                    />
                                </div>

                                <div class="flex justify-end gap-2 pt-2">
                                    <button
                                        type="submit"
                                        :disabled="questionForm.processing"
                                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-2"
                                    >
                                        <Loader2
                                            v-if="questionForm.processing"
                                            class="w-3.5 h-3.5 animate-spin"
                                        />
                                        <span>{{
                                            questionForm.processing
                                                ? "Menyimpan..."
                                                : isEditingQuestion
                                                  ? "Perbarui Butir Soal"
                                                  : "+ Simpan Butir Soal"
                                        }}</span>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="p-4 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex justify-end"
                >
                    <button
                        type="button"
                        @click="showQuizModal = false"
                        class="px-4 py-2 bg-slate-800 text-white dark:bg-slate-700 hover:bg-slate-900 rounded-lg text-xs font-bold transition"
                    >
                        Selesai & Tutup
                    </button>
                </div>
            </div>
        </div>

        <!-- Pre-Flight Inspection Checklist Modal (Pemeriksaan Kesiapan Mulai Pelatihan) -->
        <div
            v-if="showPreflightModal"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm animate-fade-in"
        >
            <div
                class="bg-white dark:bg-slate-900 rounded-2xl max-w-2xl w-full border border-slate-200 dark:border-slate-800 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
            >
                <!-- Modal Header -->
                <div
                    class="p-5 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between bg-slate-50/50 dark:bg-slate-800/40"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-emerald-100 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-sm"
                        >
                            <Play class="w-5 h-5 fill-current" />
                        </div>
                        <div>
                            <h3
                                class="text-base font-bold text-slate-900 dark:text-white"
                            >
                                Pemeriksaan Jadwal & Kesiapan Pelatihan
                            </h3>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                Pastikan seluruh jadwal dan konfigurasi telah
                                siap sebelum kelas resmi dibuka untuk siswa.
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="showPreflightModal = false"
                        class="p-2 text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 transition"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body (Scrollable Checklist) -->
                <div class="p-6 overflow-y-auto space-y-4">
                    <!-- Status Banner -->
                    <div
                        class="p-4 rounded-xl border flex items-start gap-3"
                        :class="
                            warningCount === 0
                                ? 'bg-emerald-50 dark:bg-emerald-950/30 border-emerald-200 dark:border-emerald-800/50 text-emerald-900 dark:text-emerald-200'
                                : 'bg-amber-50 dark:bg-amber-950/30 border-amber-200 dark:border-amber-800/50 text-amber-900 dark:text-amber-200'
                        "
                    >
                        <component
                            :is="
                                warningCount === 0 ? CheckCircle2 : AlertCircle
                            "
                            class="w-5 h-5 shrink-0 mt-0.5"
                            :class="
                                warningCount === 0
                                    ? 'text-emerald-600 dark:text-emerald-400'
                                    : 'text-amber-600 dark:text-amber-400'
                            "
                        />
                        <div class="text-xs">
                            <p class="font-bold">
                                {{
                                    warningCount === 0
                                        ? "Semua Jadwal & Data Siap!"
                                        : `Terdapat ${warningCount} Item Yang Perlu Diperhatikan`
                                }}
                            </p>
                            <p class="mt-0.5 opacity-90">
                                {{
                                    warningCount === 0
                                        ? "Kelas ini siap dimulai. Setelah Anda konfirmasi, peserta dapat masuk ke ruang kelas dan mengakses sesi webinar sesuai jadwal."
                                        : "Beberapa jadwal atau data belum lengkap. Anda dapat melengkapinya sekarang atau tetap melanjutkan jika kelas memang sudah ingin dibuka."
                                }}
                            </p>
                        </div>
                    </div>

                    <!-- Checklist Cards List -->
                    <div class="space-y-3">
                        <div
                            v-for="item in courseChecklist"
                            :key="item.id"
                            class="p-3.5 rounded-xl border transition-all"
                            :class="
                                item.status === 'ready'
                                    ? 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800'
                                    : item.status === 'warning'
                                      ? 'bg-amber-50/40 dark:bg-amber-950/20 border-amber-200 dark:border-amber-900/50'
                                      : 'bg-slate-50 dark:bg-slate-800/40 border-slate-200 dark:border-slate-700'
                            "
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <!-- Indicator Icon -->
                                    <span
                                        class="mt-0.5 p-1 rounded-full shrink-0"
                                        :class="
                                            item.status === 'ready'
                                                ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-600 dark:text-emerald-400'
                                                : item.status === 'warning'
                                                  ? 'bg-amber-100 dark:bg-amber-950 text-amber-600 dark:text-amber-400'
                                                  : 'bg-slate-100 dark:bg-slate-800 text-slate-500'
                                        "
                                    >
                                        <CheckCircle2
                                            v-if="item.status === 'ready'"
                                            class="w-4 h-4"
                                        />
                                        <AlertCircle
                                            v-else-if="
                                                item.status === 'warning'
                                            "
                                            class="w-4 h-4"
                                        />
                                        <Clock v-else class="w-4 h-4" />
                                    </span>

                                    <!-- Content -->
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <h4
                                                class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                            >
                                                {{ item.title }}
                                            </h4>
                                            <span
                                                class="text-[10px] px-2 py-0.5 rounded-full font-semibold"
                                                :class="
                                                    item.status === 'ready'
                                                        ? 'bg-emerald-100 dark:bg-emerald-950 text-emerald-700 dark:text-emerald-300'
                                                        : item.status ===
                                                            'warning'
                                                          ? 'bg-amber-100 dark:bg-amber-950 text-amber-700 dark:text-amber-300'
                                                          : 'bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400'
                                                "
                                            >
                                                {{
                                                    item.status === "ready"
                                                        ? "Siap"
                                                        : item.status ===
                                                            "warning"
                                                          ? "Perlu Dilengkapi"
                                                          : "Opsional"
                                                }}
                                            </span>
                                        </div>

                                        <p
                                            class="text-xs font-semibold text-slate-900 dark:text-white"
                                        >
                                            {{ item.value }}
                                        </p>

                                        <p
                                            class="text-[11px] text-slate-500 dark:text-slate-400"
                                        >
                                            {{ item.description }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Action Quick Jump -->
                                <div class="shrink-0 flex items-center gap-1.5">
                                    <button
                                        v-if="
                                            item.canEditInline &&
                                            !editDurationInline
                                        "
                                        type="button"
                                        @click="editDurationInline = true"
                                        class="px-2.5 py-1 text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 rounded-lg transition"
                                    >
                                        Edit Tanggal
                                    </button>
                                    <button
                                        v-if="item.actionTab"
                                        type="button"
                                        @click="
                                            activeTab = item.actionTab;
                                            showPreflightModal = false;
                                        "
                                        class="px-2.5 py-1 text-[11px] font-semibold text-slate-600 dark:text-slate-400 hover:text-indigo-600 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition"
                                    >
                                        Buka Tab &rarr;
                                    </button>
                                </div>
                            </div>

                            <!-- Inline Form for Duration (If toggled) -->
                            <div
                                v-if="
                                    item.id === 'duration' && editDurationInline
                                "
                                class="mt-3 pt-3 border-t border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 p-3 rounded-lg space-y-2"
                            >
                                <form
                                    @submit.prevent="saveDuration"
                                    class="flex flex-wrap items-center gap-2"
                                >
                                    <div class="flex-1 min-w-[130px]">
                                        <label
                                            class="block text-[10px] text-slate-500 font-semibold mb-1"
                                            >Mulai Pelatihan</label
                                        >
                                        <input
                                            v-model="durationForm.start_date"
                                            type="date"
                                            required
                                            class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                        />
                                    </div>
                                    <div class="flex-1 min-w-[130px]">
                                        <label
                                            class="block text-[10px] text-slate-500 font-semibold mb-1"
                                            >Selesai Pelatihan</label
                                        >
                                        <input
                                            v-model="durationForm.end_date"
                                            type="date"
                                            required
                                            class="w-full text-xs px-2.5 py-1.5 border rounded-lg bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700"
                                        />
                                    </div>
                                    <div class="pt-4 flex items-center gap-1.5">
                                        <button
                                            type="submit"
                                            :disabled="durationForm.processing"
                                            class="px-3 py-1.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition disabled:opacity-50 inline-flex items-center gap-1.5"
                                        >
                                            <Loader2
                                                v-if="durationForm.processing"
                                                class="w-3.5 h-3.5 animate-spin"
                                            />
                                            <span>Simpan</span>
                                        </button>
                                        <button
                                            type="button"
                                            @click="editDurationInline = false"
                                            class="px-2.5 py-1.5 text-xs text-slate-500 hover:text-slate-700"
                                        >
                                            Batal
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div
                    class="p-5 border-t border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-800/40 flex flex-wrap items-center justify-between gap-3"
                >
                    <button
                        type="button"
                        @click="showPreflightModal = false"
                        class="px-4 py-2 text-xs font-bold text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 rounded-xl transition"
                    >
                        Tutup & Cek Nanti
                    </button>

                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="confirmPublishCourse"
                            :disabled="isPublishing"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black bg-emerald-600 hover:bg-emerald-700 text-white shadow-lg shadow-emerald-600/30 transition transform active:scale-95 disabled:opacity-50"
                        >
                            <Play class="w-3.5 h-3.5 fill-white" />
                            <span>{{
                                isPublishing
                                    ? "Memproses Mulai Pelatihan..."
                                    : "Ya, Konfirmasi & Mulai Pelatihan Sekarang"
                            }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
