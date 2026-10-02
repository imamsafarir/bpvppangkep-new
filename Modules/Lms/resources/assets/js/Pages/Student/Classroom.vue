<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Head, Link, router } from "@inertiajs/vue3";
import axios from "axios";
import PdfBookViewer from "../../Components/PdfBookViewer.vue";
import {
    ArrowLeft,
    Video,
    BookOpen,
    CheckCircle2,
    Clock,
    Award,
    Play,
    FileText,
    Image as ImageIcon,
    ExternalLink,
    Lock,
    Unlock,
    AlertCircle,
    ChevronDown,
    ChevronRight,
    ArrowRight,
    Calendar,
    Timer,
    HelpCircle,
    Check,
    X,
    RotateCcw,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    course: Object,
    enrollment: Object,
    participant: Object,
    completedLessonIds: Array,
    quizAttempts: Object,
    progressPercentage: Number,
    dailySchedule: {
        type: Object,
        default: () => ({}),
    },
});

// Selected Path Tab: Always default to Tab 1 ('zoom')
const selectedPath = ref("zoom");

// Multi-day & Path Gating Rules
const isMultiDay = computed(() => {
    return (
        !props.dailySchedule?.is_single_day &&
        (props.dailySchedule?.duration_days > 1 ||
            (props.course.duration_in_days &&
                props.course.duration_in_days > 1))
    );
});

const hasAttendedLiveZoom = computed(() => {
    return (
        Boolean(props.dailySchedule?.has_attended_zoom) ||
        props.enrollment?.attendance_path === "live_zoom" ||
        Boolean(
            props.enrollment?.module_attendances?.some(
                (a) => a.attendance_path === "live_zoom",
            ),
        )
    );
});

const hasAttendedSelfStudy = computed(() => {
    return (
        Boolean(props.dailySchedule?.has_attended_self_study) ||
        props.enrollment?.attendance_path === "self_study" ||
        Boolean(
            props.enrollment?.module_attendances?.some(
                (a) => a.attendance_path === "self_study",
            ),
        )
    );
});

const hasAttendedAny = computed(() => {
    return (
        hasAttendedLiveZoom.value ||
        hasAttendedSelfStudy.value ||
        Boolean(props.dailySchedule?.has_attended_today) ||
        Boolean(props.enrollment?.attendance_at) ||
        (props.enrollment?.module_attendances &&
            props.enrollment.module_attendances.length > 0) ||
        (props.dailySchedule?.attended_module_ids &&
            props.dailySchedule.attended_module_ids.length > 0) ||
        props.enrollment?.status === "completed"
    );
});

const hasAttendedZoom = hasAttendedAny;

const isTodayAttendanceCompleted = computed(() => {
    if (props.enrollment?.status === "completed") return true;
    if (isMultiDay.value) {
        const todayModId = props.dailySchedule?.today_module?.id;
        const curDay = currentDayNumber.value;
        if (todayModId && isModuleAttended(todayModId, curDay)) {
            return true;
        }
        return Boolean(props.dailySchedule?.has_attended_today);
    }
    return (
        Boolean(props.dailySchedule?.has_attended_today) || hasAttendedAny.value
    );
});

const isModuleAttended = (moduleId, dayNum) => {
    if (props.dailySchedule?.attended_module_ids && moduleId) {
        return props.dailySchedule.attended_module_ids.includes(moduleId);
    }
    if (props.dailySchedule?.attended_day_numbers && dayNum) {
        return props.dailySchedule.attended_day_numbers.includes(dayNum);
    }
    if (props.enrollment?.module_attendances) {
        return props.enrollment.module_attendances.some(
            (a) =>
                a.module_id === moduleId || (dayNum && a.day_number === dayNum),
        );
    }
    return false;
};

// Tab 2 visibility rule:
// 1. If multi-day: Tab 2 is immediately visible/open from the beginning.
// 2. If single-day: Tab 2 MUST NOT be visible initially. It ONLY becomes visible after student attends in Tab 1, or if scheduled session has ended / self-study unlocked.
const isJalur2Visible = computed(() => {
    if (isMultiDay.value) return true;
    return (
        hasAttendedAny.value ||
        Boolean(props.course.is_self_study_unlocked) ||
        isSchedulePassed.value
    );
});

const currentDayNumber = computed(() => {
    if (!isMultiDay.value) return 1;
    return props.dailySchedule?.current_day_number || 1;
});

const getModuleDayNumber = (mod, index) => {
    if (mod.day_number && Number(mod.day_number) > 0) {
        return Number(mod.day_number);
    }
    if (mod.scheduled_date && props.course.start_date) {
        try {
            const startStr = String(props.course.start_date).substring(0, 10);
            const schedStr = String(mod.scheduled_date).substring(0, 10);
            const [sy, sm, sd] = startStr.split("-").map(Number);
            const [my, mm, md] = schedStr.split("-").map(Number);
            const sDate = new Date(sy, sm - 1, sd);
            const mDate = new Date(my, mm - 1, md);
            const diff =
                Math.round((mDate - sDate) / (1000 * 60 * 60 * 24)) + 1;
            if (diff >= 1) return diff;
        } catch (e) {}
    }
    return (index || 0) + 1;
};

const isModuleLocked = (mod, index) => {
    if (!isMultiDay.value) return false;
    const modDay = getModuleDayNumber(mod, index);
    return modDay > currentDayNumber.value;
};

// Real-time ticking clock for exact reactive schedule evaluation
const currentTime = ref(Date.now());

const normalizeTime = (t) => {
    if (!t) return "00:00";
    const clean = String(t).trim();
    if (clean.length === 5) return clean;
    if (clean.length >= 8) return clean.substring(0, 5);
    return clean.padStart(5, "0");
};

// Calculate startMs and endMs for today's meeting schedule
const todayZoomSchedule = computed(() => {
    let startMs = null;
    let endMs = null;

    // 1. Check today's module first (for multi-day or unit-scheduled courses)
    const mod = props.dailySchedule?.today_module;
    if (mod) {
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
    }

    // 2. Check course-level schedule
    if (!startMs && props.course?.zoom_start_at) {
        startMs = new Date(props.course.zoom_start_at).getTime();
    }
    if (!endMs && props.course?.zoom_end_at) {
        endMs = new Date(props.course.zoom_end_at).getTime();
    }

    return {
        startMs,
        endMs,
        hasSchedule: Boolean(startMs),
    };
});

// Real-time reactive status: 'upcoming', 'live', 'ended', 'unscheduled'
const effectiveZoomStatus = computed(() => {
    if (props.course?.zoom_status === "ended") return "ended";
    if (props.dailySchedule?.today_module?.zoom_status === "ended")
        return "ended";

    const { startMs, endMs, hasSchedule } = todayZoomSchedule.value;
    if (!hasSchedule) {
        return props.course?.zoom_status || "unscheduled";
    }

    const now = currentTime.value;

    if (endMs && now > endMs) {
        return "ended";
    }

    if (startMs && now < startMs) {
        // If not reached startMs yet, check if instructor manually marked as live
        if (
            props.course?.zoom_status === "live" ||
            props.dailySchedule?.today_module?.zoom_status === "live"
        ) {
            return "live";
        }
        return "upcoming";
    }

    return "live";
});

// Has the scheduled meeting start time arrived?
// Meeting ID & Passcode are ONLY revealed when this is TRUE!
const isZoomTimeStarted = computed(() => {
    const { startMs, hasSchedule } = todayZoomSchedule.value;
    if (!hasSchedule) {
        return true;
    }
    return currentTime.value >= startMs || effectiveZoomStatus.value === "live";
});

// Check if attendance schedule is upcoming in the future
const isAttendanceScheduleUpcoming = computed(() => {
    const schedAt =
        props.course?.zoom_attendance_scheduled_at ||
        dailySchedule.value?.today_module?.zoom_attendance_scheduled_at;
    if (!schedAt) return false;
    const schedTime = new Date(schedAt).getTime();
    return currentTime.value < schedTime;
});

// Has the scheduled meeting time passed?
// "Terlambat / Berhalangan Hadir Online?" is ONLY shown when this is TRUE!
const isSchedulePassed = computed(() => {
    const { startMs, endMs, hasSchedule } = todayZoomSchedule.value;

    if (effectiveZoomStatus.value === "ended") {
        return true;
    }

    if (!hasSchedule) {
        // If no schedule exists at all, allow self-study option
        return true;
    }

    const now = currentTime.value;

    if (endMs) {
        return now >= endMs;
    }

    // If only start time was provided without end time, consider passed after 2 hours
    return now >= startMs + 2 * 60 * 60 * 1000;
});

// Real-time Attendance Countdown Timer
const remainingAttendanceSeconds = ref(
    props.course.attendance_remaining_seconds || 0,
);

const isAttendanceOpenNow = computed(() => {
    if (remainingAttendanceSeconds.value > 0) return true;
    if (props.course?.is_attendance_open_now) return true;
    const now = Date.now();
    if (props.course?.zoom_attendance_scheduled_at) {
        const schedTime = new Date(
            props.course.zoom_attendance_scheduled_at,
        ).getTime();
        const durationMs =
            (props.course.zoom_attendance_duration_minutes || 30) * 60 * 1000;
        if (now >= schedTime && now <= schedTime + durationMs) return true;
    }
    if (props.course?.modules) {
        const hasOpen = props.course.modules.some((m) => {
            if (m.is_attendance_open_now) return true;
            if (m.zoom_attendance_scheduled_at) {
                const schedTime = new Date(
                    m.zoom_attendance_scheduled_at,
                ).getTime();
                const durationMs =
                    (m.zoom_attendance_duration_minutes || 30) * 60 * 1000;
                return now >= schedTime && now <= schedTime + durationMs;
            }
            return false;
        });
        if (hasOpen) return true;
    }
    return false;
});

watch(
    () => props.course.attendance_remaining_seconds,
    (val) => {
        remainingAttendanceSeconds.value = val || 0;
    },
    { immediate: true },
);

let attendanceTimer = null;
onMounted(() => {
    attendanceTimer = setInterval(() => {
        const now = Date.now();
        currentTime.value = now;
        let scheduledCloseTime = null;

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
                scheduledCloseTime = closeTime;
            }
        }

        if (!scheduledCloseTime && props.course?.modules) {
            props.course.modules.forEach((m) => {
                if (m.zoom_attendance_scheduled_at) {
                    const schedTime = new Date(
                        m.zoom_attendance_scheduled_at,
                    ).getTime();
                    const durationMs =
                        (m.zoom_attendance_duration_minutes || 30) * 60 * 1000;
                    const closeTime = schedTime + durationMs;
                    if (now >= schedTime && now <= closeTime) {
                        scheduledCloseTime = closeTime;
                    }
                }
            });
        }

        if (scheduledCloseTime) {
            remainingAttendanceSeconds.value = Math.max(
                0,
                Math.floor((scheduledCloseTime - now) / 1000),
            );
            props.course.is_attendance_open_now = true;
        } else if (remainingAttendanceSeconds.value > 0) {
            remainingAttendanceSeconds.value--;
            if (remainingAttendanceSeconds.value <= 0) {
                props.course.is_attendance_open_now = false;
            }
        } else if (props.course.is_attendance_open_now) {
            let isStillOpen = false;
            if (props.course.zoom_attendance_closed_at) {
                isStillOpen = now <= new Date(props.course.zoom_attendance_closed_at).getTime();
            }
            if (!isStillOpen) {
                props.course.is_attendance_open_now = false;
            }
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

// Completed lessons reactive array
const completedIds = ref([...props.completedLessonIds]);
const currentProgress = ref(props.progressPercentage || 0);

// PDF reading completion tracking (lessonId => boolean)
const completedPdfLessons = ref({});

const onPdfCompleted = (lessonId) => {
    completedPdfLessons.value[lessonId] = true;
};

const canCompleteActiveLesson = computed(() => {
    if (!activeLesson.value) return false;
    if (activeLesson.value.content_type === "pdf") {
        if (completedIds.value.includes(activeLesson.value.id)) return true;
        return Boolean(completedPdfLessons.value[activeLesson.value.id]);
    }
    return true;
});

// Active selected lesson
const allLessons = computed(() => {
    const list = [];
    if (!props.course.modules) return list;
    props.course.modules.forEach((mod, mIdx) => {
        if (mod.lessons) {
            mod.lessons.forEach((les) => {
                list.push({
                    ...les,
                    module_title: mod.title,
                    module_id: mod.id,
                    module_index: mIdx,
                    module_day_number: getModuleDayNumber(mod, mIdx),
                });
            });
        }
    });
    return list;
});

const findFirstUnlockedLesson = () => {
    if (!props.course.modules) return null;
    for (let i = 0; i < props.course.modules.length; i++) {
        const mod = props.course.modules[i];
        if (!isModuleLocked(mod, i) && mod.lessons && mod.lessons.length > 0) {
            return {
                ...mod.lessons[0],
                module_title: mod.title,
                module_id: mod.id,
                module_index: i,
                module_day_number: getModuleDayNumber(mod, i),
            };
        }
    }
    return allLessons.value[0] || null;
};

const activeItemType = ref("lesson"); // 'lesson' | 'quiz'
const activeLesson = ref(findFirstUnlockedLesson());
const activeQuiz = ref(null);
const studentQuizAttempts = ref({ ...(props.quizAttempts || {}) });
const quizAnswers = ref({});
const isSubmittingQuiz = ref(false);
const quizResult = ref(null);

const getPdfUrl = (path) => {
    if (!path) return "";
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    if (path.startsWith("/storage/")) return path;
    if (path.startsWith("storage/")) return "/" + path;
    return "/storage/" + path;
};

const selectLesson = (lesson, mod, mIdx) => {
    if (mod && isModuleLocked(mod, mIdx)) {
        alert(
            `Materi pada unit ini baru akan dibuka pada Hari ke-${getModuleDayNumber(mod, mIdx)}.`,
        );
        return;
    }
    activeItemType.value = "lesson";
    activeLesson.value = {
        ...lesson,
        module_title: mod ? mod.title : lesson.module_title || "",
    };
    activeQuiz.value = null;
    quizResult.value = null;
};

const selectQuiz = (quiz, mod, mIdx) => {
    if (mod && isModuleLocked(mod, mIdx)) {
        alert(
            `Kuis pada unit ini baru akan dibuka pada Hari ke-${getModuleDayNumber(mod, mIdx)}.`,
        );
        return;
    }
    activeItemType.value = "quiz";
    activeQuiz.value = { ...quiz, module_title: mod.title };
    activeLesson.value = null;
    quizResult.value = null;

    const existingAttempt = studentQuizAttempts.value[quiz.id];
    if (existingAttempt && existingAttempt.answers) {
        quizAnswers.value = { ...existingAttempt.answers };
    } else {
        quizAnswers.value = {};
    }
};

const submitStudentQuiz = async (quiz) => {
    if (!quiz) return;
    const questions = quiz.questions || [];
    const answeredCount = Object.keys(quizAnswers.value).filter(
        (k) =>
            quizAnswers.value[k] !== undefined && quizAnswers.value[k] !== "",
    ).length;

    if (answeredCount < questions.length) {
        if (
            !confirm(
                `Anda baru menjawab ${answeredCount} dari ${questions.length} butir soal. Yakin ingin mengirim jawaban sekarang?`,
            )
        ) {
            return;
        }
    }

    isSubmittingQuiz.value = true;
    try {
        const response = await axios.post(`/lms/quizzes/${quiz.id}/submit`, {
            answers: quizAnswers.value,
        });
        const data = response.data;
        if (data.success) {
            quizResult.value = data;
            studentQuizAttempts.value[quiz.id] = data.attempt;
            if (data.progress_percentage !== undefined) {
                currentProgress.value = data.progress_percentage;
            }
        }
    } catch (err) {
        alert(
            err.response?.data?.error ||
                "Terjadi kesalahan saat mengirim jawaban kuis. Silakan coba lagi.",
        );
    } finally {
        isSubmittingQuiz.value = false;
    }
};

const retakeQuiz = () => {
    quizResult.value = null;
    quizAnswers.value = {};
};

// Mark Lesson Complete
const isSubmittingLesson = ref(false);
const markComplete = async (lesson) => {
    if (!lesson) return;
    if (lesson.content_type === "pdf" && !canCompleteActiveLesson.value) {
        alert(
            "Anda harus membaca materi PDF ini hingga slide terakhir terlebih dahulu untuk menyelesaikan materi.",
        );
        return;
    }
    isSubmittingLesson.value = true;

    try {
        const response = await axios.post(`/lms/lessons/${lesson.id}/complete`);
        const data = response.data;
        if (data.success) {
            if (!completedIds.value.includes(lesson.id)) {
                completedIds.value.push(lesson.id);
            }
            currentProgress.value = data.progress_percentage;

            // Automatically advance to next lesson if available and unlocked
            const currentIndex = allLessons.value.findIndex(
                (l) => l.id === lesson.id,
            );
            if (
                currentIndex !== -1 &&
                currentIndex + 1 < allLessons.value.length
            ) {
                const nextLes = allLessons.value[currentIndex + 1];
                const nextMod = props.course.modules?.find(
                    (m) => m.id === nextLes.module_id,
                );
                const nextModIdx = props.course.modules?.findIndex(
                    (m) => m.id === nextLes.module_id,
                );
                if (!nextMod || !isModuleLocked(nextMod, nextModIdx)) {
                    activeLesson.value = nextLes;
                }
            }
        }
    } catch (err) {
        console.error(err);
    } finally {
        isSubmittingLesson.value = false;
    }
};

// Attendance Submissions
const isAttending = ref(false);
const isAttendingSelfStudy = ref(false);

const submitZoomAttendance = () => {
    isAttending.value = true;
    router.post(
        `/lms/courses/${props.course.id}/attend`,
        { path: "live_zoom" },
        {
            preserveScroll: true,
            onFinish: () => {
                isAttending.value = false;
            },
        },
    );
};

const submitSelfStudyCheckin = () => {
    isAttendingSelfStudy.value = true;
    router.post(
        `/lms/courses/${props.course.id}/attend`,
        { path: "self_study_checkin" },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedPath.value = "mandiri";
            },
            onFinish: () => {
                isAttendingSelfStudy.value = false;
            },
        },
    );
};

const submitCompleteCourse = () => {
    if (allLessons.value.length === 0) {
        alert("Belum ada unit & elemen kompetensi yang tersedia di kelas ini.");
        return;
    }
    if (currentProgress.value < 100) {
        alert(
            "Anda harus menyelesaikan seluruh unit & elemen kompetensi (100%) terlebih dahulu.",
        );
        return;
    }
    isAttending.value = true;

    router.post(
        `/lms/courses/${props.course.id}/attend`,
        {
            action: "complete_course",
            path: "complete_course",
        },
        {
            preserveScroll: true,
            onFinish: () => {
                isAttending.value = false;
            },
        },
    );
};

const submitSelfStudyAttendance = submitCompleteCourse;

// Helper YouTube Embed
const getYoutubeEmbedUrl = (url) => {
    if (!url) return "";
    let videoId = "";
    if (url.includes("youtu.be/")) {
        videoId = url.split("youtu.be/")[1]?.split("?")[0];
    } else if (url.includes("watch?v=")) {
        videoId = url.split("watch?v=")[1]?.split("&")[0];
    } else if (url.includes("embed/")) {
        return url;
    }
    return videoId ? `https://www.youtube.com/embed/${videoId}` : url;
};
</script>

<template>
    <div
        class="min-h-screen bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col"
    >
        <Head :title="`${course.title} - Ruang Belajar LMS`" />

        <!-- Top Navigation Bar -->
        <header
            class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 sticky top-0 z-30 shadow-sm"
        >
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <Link
                        href="/lms/dashboard"
                        class="p-2 text-slate-500 hover:text-slate-800 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 rounded-lg transition-colors"
                        title="Kembali ke Dashboard Siswa"
                    >
                        <ArrowLeft class="w-5 h-5" />
                    </Link>
                    <div class="truncate max-w-md sm:max-w-xl">
                        <span
                            v-if="course.batch_name"
                            class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider block"
                        >
                            {{ course.batch_name }} &bull;
                            {{ course.category || "Pelatihan Vokasi" }}
                        </span>
                        <h1
                            class="text-sm sm:text-base font-black text-slate-900 dark:text-white truncate"
                        >
                            {{ course.title }}
                        </h1>
                    </div>
                </div>

                <!-- Top Progress Capsule -->
                <div class="flex items-center gap-3">
                    <div class="hidden sm:flex items-center gap-2">
                        <div
                            class="w-28 bg-slate-200 dark:bg-slate-700 h-2 rounded-full overflow-hidden"
                        >
                            <div
                                class="h-full rounded-full transition-all duration-500"
                                :class="
                                    currentProgress >= 100
                                        ? 'bg-emerald-500'
                                        : 'bg-indigo-600'
                                "
                                :style="{ width: `${currentProgress}%` }"
                            ></div>
                        </div>
                        <span class="text-xs font-bold font-mono"
                            >{{ currentProgress }}%</span
                        >
                    </div>

                    <!-- Certificate Quick Download if completed -->
                    <a
                        v-if="
                            enrollment.status === 'completed' &&
                            enrollment.certificate_hash
                        "
                        :href="`/lms/certificates/${enrollment.id}/download`"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-amber-900 bg-amber-400 hover:bg-amber-300 rounded-lg shadow-sm transition-colors"
                    >
                        <Award class="w-4 h-4" />
                        <span class="hidden md:inline"
                            >Unduh Sertifikat PDF</span
                        >
                        <span class="md:hidden">Sertifikat</span>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Body -->
        <main
            class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 space-y-6"
        >
            <!-- Completed Certificate Alert Banner if participant attended -->
            <div
                v-if="enrollment.status === 'completed'"
                class="bg-gradient-to-r from-emerald-900 via-teal-950 to-slate-900 border border-emerald-500/30 rounded-2xl p-5 text-white shadow-lg flex flex-col md:flex-row items-center justify-between gap-4"
            >
                <div class="flex items-center gap-3.5">
                    <span
                        class="p-2.5 bg-emerald-500/20 text-emerald-300 rounded-xl ring-1 ring-emerald-400/40"
                    >
                        <Award class="w-7 h-7" />
                    </span>
                    <div>
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2 py-0.5 rounded text-[10px] font-black bg-emerald-500 text-white uppercase tracking-wider"
                            >
                                RESMI &bull; SUDAH MENGIKUTI
                            </span>
                            <span class="text-xs text-emerald-200">
                                Diselesaikan via
                                {{
                                    enrollment.attendance_path === "live_zoom"
                                        ? "Sesi Online Meeting Tatap Muka"
                                        : "Pembelajaran Mandiri"
                                }}
                            </span>
                        </div>
                        <p class="text-sm font-bold text-white mt-1">
                            Selamat! Sertifikat Kelulusan Anda telah diterbitkan
                            dengan Nomor:
                            <span class="font-mono text-amber-300">{{
                                enrollment.certificate_number
                            }}</span>
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-2.5 w-full md:w-auto">
                    <a
                        :href="`/lms/certificates/${enrollment.id}/download`"
                        target="_blank"
                        class="flex-1 md:flex-initial inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-amber-400 hover:bg-amber-300 text-amber-950 rounded-xl text-xs font-black shadow-md transition-colors"
                    >
                        <Award class="w-4 h-4" />
                        <span>Unduh Sertifikat PDF (A4)</span>
                    </a>
                    <a
                        :href="`/lms/verify/${enrollment.certificate_hash}`"
                        target="_blank"
                        class="inline-flex items-center justify-center gap-1.5 px-3 py-2.5 bg-white/10 hover:bg-white/20 text-white rounded-xl text-xs font-semibold transition-colors"
                        title="Periksa Keabsahan QR TTE"
                    >
                        <ExternalLink class="w-3.5 h-3.5" />
                        <span>Verifikasi TTE</span>
                    </a>
                </div>
            </div>

            <!-- Dual-Path Selection Tabs -->
            <div
                class="flex flex-wrap items-center gap-3 border-b border-slate-200 dark:border-slate-800 pb-3"
            >
                <button
                    @click="selectedPath = 'zoom'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all"
                    :class="
                        selectedPath === 'zoom'
                            ? 'bg-indigo-600 text-white shadow-md'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50'
                    "
                >
                    <Video class="w-4 h-4" />
                    <span>Tab 1: Status Pembelajaran & Absensi</span>
                    <span
                        v-if="enrollment.status === 'completed'"
                        class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        SELESAI
                    </span>
                    <span
                        v-else-if="isTodayAttendanceCompleted"
                        class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                    >
                        SUDAH ABSEN
                    </span>
                    <span
                        v-else-if="effectiveZoomStatus === 'live'"
                        class="px-1.5 py-0.5 rounded text-[9px] font-black bg-rose-500 text-white animate-pulse"
                    >
                        LIVE
                    </span>
                    <span
                        v-else-if="effectiveZoomStatus === 'upcoming'"
                        class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300"
                    >
                        TERJADWAL
                    </span>
                    <span
                        v-else-if="effectiveZoomStatus === 'ended'"
                        class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-slate-200 dark:bg-slate-800 text-slate-600 dark:text-slate-400"
                    >
                        BERAKHIR
                    </span>
                    <span
                        v-if="
                            isAttendanceOpenNow && !isTodayAttendanceCompleted
                        "
                        class="w-2 h-2 rounded-full bg-emerald-400 animate-ping ml-1"
                        title="Absensi Sedang Dibuka"
                    ></span>
                </button>

                <!-- Tab 2 Tab: For 1-day course, it is NOT visible until student attends Tab 1. For multi-day, it is visible from day 1 -->
                <button
                    v-if="isJalur2Visible"
                    @click="selectedPath = 'mandiri'"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs font-bold transition-all"
                    :class="
                        selectedPath === 'mandiri'
                            ? 'bg-indigo-600 text-white shadow-md'
                            : 'bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-800 hover:bg-slate-50'
                    "
                >
                    <BookOpen class="w-4 h-4" />
                    <span>Tab 2: Materi & Evaluasi Pembelajaran</span>
                    <span
                        class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-black/20 ml-1"
                    >
                        {{ currentProgress }}%
                    </span>
                </button>
            </div>

            <!-- ======================================================== -->
            <!-- Tab 1: SESI LIVE ZOOM                                  -->
            <!-- ======================================================== -->
            <div v-if="selectedPath === 'zoom'" class="space-y-6">
                <!-- Mandatory Tab 1 Guideline / Daily Schedule Banner -->
                <!-- Case A: Pelatihan 1 Hari -->
                <div
                    v-if="dailySchedule?.is_single_day"
                    class="p-4 bg-indigo-50/90 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-900/60 rounded-2xl text-xs text-indigo-950 dark:text-indigo-200 flex items-center gap-3 shadow-sm"
                >
                    <div
                        class="p-2.5 bg-indigo-600 text-white rounded-xl shrink-0 shadow-sm"
                    >
                        <Video class="w-4 h-4" />
                    </div>
                    <div class="leading-relaxed">
                        <strong class="font-bold"
                            >Pelatihan 1 Hari (Wajib Tab 1 - Tatap Muka Online
                            Meeting):</strong
                        >
                        Pelatihan ini berdurasi 1 hari. Seluruh peserta wajib
                        mengikuti sesi tatap muka online (Live Online Meeting)
                        bersama instruktur dan melakukan presensi online saat
                        sesi dibuka untuk memenuhi kelulusan dan menerbitkan
                        sertifikat.
                    </div>
                </div>

                <!-- Case B: Pelatihan Multi-Hari (> 1 Hari) -->
                <div
                    v-else-if="dailySchedule?.duration_days > 1"
                    class="p-5 bg-gradient-to-r from-blue-50 to-indigo-50 dark:from-slate-800 dark:to-slate-800/80 border border-blue-200 dark:border-slate-700 rounded-2xl shadow-sm space-y-3"
                >
                    <div
                        class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 border-b border-blue-100 dark:border-slate-700/60 pb-3"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="px-2.5 py-1 bg-indigo-600 text-white font-bold text-[11px] rounded-lg"
                            >
                                Pelatihan {{ dailySchedule.duration_days }} Hari
                            </span>
                            <span
                                class="text-xs text-slate-500 dark:text-slate-400 font-medium"
                            >
                                Agenda & Jadwal Sesi Hari Ini
                            </span>
                        </div>
                        <span
                            v-if="dailySchedule.today_mode === 'sinkronus'"
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300"
                        >
                            <Video class="w-3.5 h-3.5 text-blue-600" />
                            Mode Hari Ini: Sinkronus (Tatap Muka / Online
                            Meeting)
                        </span>
                        <span
                            v-else
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300"
                        >
                            <BookOpen class="w-3.5 h-3.5 text-emerald-600" />
                            Mode Hari Ini: Asinkronus (Belajar Mandiri)
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs">
                        <div
                            class="bg-white dark:bg-slate-900/80 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700"
                        >
                            <span
                                class="text-[10px] uppercase font-bold text-slate-400 block mb-1"
                                >Agenda Hari Ini</span
                            >
                            <p
                                v-if="dailySchedule.today_module"
                                class="font-bold text-slate-900 dark:text-white"
                            >
                                {{ dailySchedule.today_module.title }}
                            </p>
                            <p
                                v-else
                                class="font-bold text-slate-900 dark:text-white"
                            >
                                Sesi Pelatihan
                            </p>
                            <p
                                v-if="dailySchedule.today_zoom_time"
                                class="text-indigo-600 dark:text-indigo-400 font-semibold mt-1 flex items-center gap-1"
                            >
                                <Clock class="w-3.5 h-3.5" />
                                Waktu Online Meeting:
                                {{ dailySchedule.today_zoom_time }}
                            </p>
                            <p
                                v-if="dailySchedule.today_notes"
                                class="text-slate-600 dark:text-slate-300 mt-1 italic"
                            >
                                Keterangan: {{ dailySchedule.today_notes }}
                            </p>
                        </div>

                        <div
                            class="bg-white dark:bg-slate-900/80 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700"
                        >
                            <span
                                class="text-[10px] uppercase font-bold text-slate-400 block mb-1"
                                >Pengingat Jadwal Besok / Selanjutnya</span
                            >
                            <div
                                v-if="
                                    dailySchedule.tomorrow_module ||
                                    dailySchedule.tomorrow_zoom_time ||
                                    dailySchedule.tomorrow_notes
                                "
                            >
                                <p
                                    v-if="dailySchedule.tomorrow_module"
                                    class="font-bold text-slate-900 dark:text-white"
                                >
                                    {{ dailySchedule.tomorrow_module.title }}
                                </p>
                                <p
                                    v-if="dailySchedule.tomorrow_zoom_time"
                                    class="text-amber-600 dark:text-amber-400 font-semibold mt-1 flex items-center gap-1"
                                >
                                    <Clock class="w-3.5 h-3.5" />
                                    Mulai Online Meeting Besok:
                                    {{ dailySchedule.tomorrow_zoom_time }}
                                </p>
                                <p
                                    v-else-if="
                                        dailySchedule.tomorrow_mode ===
                                        'asinkronus'
                                    "
                                    class="text-emerald-600 dark:text-emerald-400 font-semibold mt-1 flex items-center gap-1"
                                >
                                    <BookOpen class="w-3.5 h-3.5" />
                                    Jadwal Besok: Asinkronus (Belajar Mandiri)
                                </p>
                                <p
                                    v-if="dailySchedule.tomorrow_notes"
                                    class="text-slate-600 dark:text-slate-300 mt-1 italic"
                                >
                                    Keterangan:
                                    {{ dailySchedule.tomorrow_notes }}
                                </p>
                            </div>
                            <div v-else class="text-slate-500 italic py-1">
                                Silakan lanjutkan mempelajari unit & elemen
                                kompetensi secara mandiri.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zoom Meeting Box -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-8 shadow-sm"
                >
                    <div class="max-w-2xl mx-auto space-y-6 text-center">
                        <div
                            class="w-16 h-16 mx-auto rounded-2xl flex items-center justify-center shadow-inner"
                            :class="
                                effectiveZoomStatus === 'live'
                                    ? 'bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 animate-pulse'
                                    : 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400'
                            "
                        >
                            <Video class="w-8 h-8" />
                        </div>

                        <div>
                            <span
                                class="text-xs font-bold text-indigo-600 dark:text-indigo-400 uppercase tracking-widest"
                            >
                                Tatap Muka Virtual Online
                            </span>
                            <h2
                                class="text-2xl font-black text-slate-900 dark:text-white mt-1"
                            >
                                Sesi Video Conference Kelas
                            </h2>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400 mt-1 max-w-lg mx-auto"
                            >
                                Ikuti tatap muka online bersama instruktur.
                                Tombol absen akan dibuka oleh instruktur pada
                                sesi Online Meeting untuk mencatat presensi.
                            </p>
                        </div>

                        <!-- Zoom Schedule Indicator -->
                        <div
                            v-if="
                                course.zoom_start_at ||
                                dailySchedule?.today_module?.zoom_start_at ||
                                todayZoomSchedule.hasSchedule
                            "
                            class="p-3.5 rounded-xl border text-xs text-slate-600 dark:text-slate-300 flex items-center justify-center gap-2"
                            :class="
                                effectiveZoomStatus === 'live'
                                    ? 'bg-rose-50 border-rose-200 dark:bg-rose-950/30 dark:border-rose-900/60 text-rose-800 dark:text-rose-300 font-bold'
                                    : 'bg-slate-50 border-slate-200 dark:bg-slate-800/60 dark:border-slate-700/60'
                            "
                        >
                            <Calendar class="w-4 h-4 text-indigo-500" />
                            <span>
                                Jadwal Sesi Online Meeting:
                                <strong>{{
                                    formatReadableDate(
                                        course.zoom_start_at ||
                                            dailySchedule?.today_module
                                                ?.zoom_start_at,
                                    )
                                }}</strong>
                                <span
                                    v-if="
                                        course.zoom_end_at ||
                                        dailySchedule?.today_module?.zoom_end_at
                                    "
                                >
                                    s/d
                                    <strong>{{
                                        formatReadableDate(
                                            course.zoom_end_at ||
                                                dailySchedule?.today_module
                                                    ?.zoom_end_at,
                                        )
                                    }}</strong></span
                                >
                            </span>
                        </div>

                        <!-- Zoom Credentials Box (Hidden when ended) -->
                        <div
                            v-if="effectiveZoomStatus !== 'ended'"
                            class="bg-slate-50 dark:bg-slate-800/60 p-4 rounded-xl border border-slate-200 dark:border-slate-700/60 text-left transition-all"
                        >
                            <!-- Case A: Scheduled & Time has NOT arrived yet (Locked State) -->
                            <div
                                v-if="
                                    todayZoomSchedule.hasSchedule &&
                                    !isZoomTimeStarted
                                "
                                class="flex flex-col sm:flex-row items-center justify-between gap-3 text-center sm:text-left py-1"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="p-2.5 rounded-xl bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20 shrink-0"
                                    >
                                        <Lock class="w-5 h-5" />
                                    </div>
                                    <div>
                                        <h4
                                            class="text-xs font-bold text-slate-800 dark:text-slate-200"
                                        >
                                            Meeting ID & Passcode Terkunci
                                        </h4>
                                        <p
                                            class="text-[11px] text-slate-500 dark:text-slate-400 mt-0.5"
                                        >
                                            Akan otomatis ditampilkan saat waktu
                                            sesi online meeting tiba
                                            <span
                                                v-if="todayZoomSchedule.startMs"
                                                class="font-bold text-indigo-600 dark:text-indigo-400"
                                            >
                                                ({{
                                                    formatReadableDate(
                                                        course.zoom_start_at ||
                                                            dailySchedule
                                                                ?.today_module
                                                                ?.zoom_start_at,
                                                    )
                                                }}) </span
                                            >.
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-bold bg-amber-100 dark:bg-amber-950/60 text-amber-800 dark:text-amber-300 border border-amber-200 dark:border-amber-900/50 shrink-0"
                                >
                                    <Clock class="w-3.5 h-3.5" />
                                    <span>Menunggu Jam Sesi</span>
                                </span>
                            </div>

                            <!-- Case B: Session Started / Live / Ended / Unscheduled (Revealed) -->
                            <div
                                v-else
                                class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                            >
                                <div>
                                    <span
                                        class="text-[10px] text-slate-400 font-semibold uppercase"
                                        >Meeting ID:</span
                                    >
                                    <p
                                        class="text-sm font-mono font-bold text-slate-900 dark:text-white"
                                    >
                                        {{
                                            course.zoom_meeting_id ||
                                            dailySchedule?.today_module
                                                ?.zoom_meeting_id ||
                                            "Tersedia di tautan Online Meeting"
                                        }}
                                    </p>
                                </div>
                                <div>
                                    <span
                                        class="text-[10px] text-slate-400 font-semibold uppercase"
                                        >Passcode:</span
                                    >
                                    <p
                                        class="text-sm font-mono font-bold text-slate-900 dark:text-white"
                                    >
                                        {{
                                            course.zoom_passcode ||
                                            dailySchedule?.today_module
                                                ?.zoom_passcode ||
                                            "-"
                                        }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- ZOOM JOIN BUTTON (CONDITIONAL BASED ON STATUS) -->
                        <div class="pt-2">
                            <!-- Case 1: Zoom is UPCOMING (Not started yet) -->
                            <div
                                v-if="effectiveZoomStatus === 'upcoming'"
                                class="space-y-3"
                            >
                                <button
                                    disabled
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-slate-400 dark:text-slate-500 bg-slate-100 dark:bg-slate-800 border border-slate-200 dark:border-slate-700 cursor-not-allowed shadow-none"
                                >
                                    <Lock class="w-4 h-4" />
                                    <span
                                        >Sesi Online Meeting Belum Dimulai</span
                                    >
                                </button>
                                <p
                                    class="text-xs text-amber-600 dark:text-amber-400 font-medium"
                                >
                                    Tombol masuk Online Meeting akan aktif saat
                                    jadwal sesi pelatihan tiba ({{
                                        formatReadableDate(
                                            course.zoom_start_at ||
                                                dailySchedule?.today_module
                                                    ?.zoom_start_at,
                                        )
                                    }}).
                                </p>
                            </div>

                            <!-- Case 2: Zoom is LIVE (Ongoing) -->
                            <div
                                v-else-if="effectiveZoomStatus === 'live'"
                                class="space-y-3"
                            >
                                <a
                                    v-if="
                                        course.zoom_link ||
                                        dailySchedule?.today_module?.zoom_link
                                    "
                                    :href="
                                        course.zoom_link ||
                                        dailySchedule?.today_module?.zoom_link
                                    "
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-7 py-3.5 rounded-xl text-sm font-black text-white bg-blue-600 hover:bg-blue-700 shadow-xl shadow-blue-600/30 animate-pulse transition-all transform active:scale-95"
                                >
                                    <Video class="w-5 h-5" />
                                    <span
                                        >GABUNG SESI ONLINE MEETING
                                        SEKARANG</span
                                    >
                                    <ExternalLink class="w-4 h-4 ml-1" />
                                </a>
                                <p v-else class="text-xs text-slate-400 italic">
                                    Tautan Online Meeting akan diinfokan oleh
                                    instruktur.
                                </p>
                            </div>

                            <!-- Case 3: Zoom is ENDED -->
                            <div
                                v-else-if="effectiveZoomStatus === 'ended'"
                                class="space-y-4"
                            >
                                <div
                                    class="p-6 sm:p-7 bg-gradient-to-r from-slate-50 via-indigo-50/50 to-blue-50/50 dark:from-slate-800 dark:via-slate-800/80 dark:to-indigo-950/40 rounded-2xl border border-indigo-200 dark:border-indigo-900/60 text-center max-w-xl mx-auto shadow-sm space-y-4"
                                >
                                    <div
                                        class="inline-flex p-3 bg-indigo-600 text-white rounded-2xl shadow-md"
                                    >
                                        <BookOpen class="w-6 h-6" />
                                    </div>
                                    <div>
                                        <h3
                                            class="text-base font-black text-slate-900 dark:text-white"
                                        >
                                            🏁 Sesi Tatap Muka Online Meeting
                                            Hari Ini Telah Selesai
                                        </h3>
                                        <p
                                            class="text-xs text-slate-600 dark:text-slate-300 mt-1 leading-relaxed"
                                        >
                                            Waktu sesi tatap muka Online Meeting
                                            telah berakhir.

                                            <span
                                                v-if="
                                                    dailySchedule?.tomorrow_mode ===
                                                    'asinkronus'
                                                "
                                                class="block font-semibold text-emerald-700 dark:text-emerald-300 mt-1.5"
                                            >
                                                📖 Jadwal besok: Pembelajaran
                                                Asinkronus (Mandiri).
                                                {{
                                                    dailySchedule.tomorrow_notes
                                                        ? "(" +
                                                          dailySchedule.tomorrow_notes +
                                                          ")"
                                                        : ""
                                                }}
                                            </span>
                                            <span
                                                class="block mt-1 text-slate-500 dark:text-slate-400"
                                            >
                                                Bagi Anda yang berhalangan hadir
                                                tepat waktu atau ingin
                                                mempelajari kembali materi,
                                                silakan akses
                                                <strong
                                                    >Tab 2: Materi &
                                                    Evaluasi</strong
                                                >.
                                            </span>
                                        </p>
                                    </div>

                                    <!-- UNIFIED ACTION BUTTON -->
                                    <div class="pt-1">
                                        <!-- Case 3A: Already Attended Today -> Simply Go to Tab 2 -->
                                        <div
                                            v-if="
                                                isTodayAttendanceCompleted ||
                                                enrollment.status ===
                                                    'completed'
                                            "
                                            class="space-y-2"
                                        >
                                            <button
                                                type="button"
                                                @click="
                                                    selectedPath = 'mandiri'
                                                "
                                                class="inline-flex items-center gap-2 px-7 py-3 rounded-xl text-xs sm:text-sm font-black text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/30 transition transform active:scale-95 cursor-pointer"
                                            >
                                                <span
                                                    >Lanjut ke Tab 2 (Materi &
                                                    Evaluasi)</span
                                                >
                                                <ArrowRight class="w-4 h-4" />
                                            </button>
                                        </div>

                                        <!-- Case 3B: NOT Attended Today -> Unified Self-Study Checkin & Go to Tab 2 -->
                                        <div
                                            v-else
                                            class="space-y-2 max-w-md mx-auto"
                                        >
                                            <button
                                                type="button"
                                                @click="submitSelfStudyCheckin"
                                                :disabled="isAttendingSelfStudy"
                                                class="inline-flex items-center justify-center gap-2 px-6 sm:px-7 py-3 rounded-xl text-xs sm:text-sm font-black text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-600/25 transition-all transform active:scale-95 disabled:opacity-50 cursor-pointer"
                                            >
                                                <Loader2
                                                    v-if="isAttendingSelfStudy"
                                                    class="w-4 h-4 animate-spin"
                                                />
                                                <CheckCircle2
                                                    v-else
                                                    class="w-4 h-4"
                                                />
                                                <span>{{
                                                    isAttendingSelfStudy
                                                        ? "Mencatat Presensi & Membuka Tab 2..."
                                                        : "Konfirmasi Absen Belajar Mandiri & Lanjut ke Tab 2"
                                                }}</span>
                                                <ArrowRight
                                                    v-if="!isAttendingSelfStudy"
                                                    class="w-4 h-4"
                                                />
                                            </button>
                                            <p
                                                class="text-[11px] text-slate-500 dark:text-slate-400 leading-snug"
                                            >
                                                Setelah konfirmasi, kehadiran
                                                Anda otomatis tercatat dan Tab 2
                                                (Materi & Evaluasi) langsung
                                                terbuka untuk Anda pelajari.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Case 4: Unscheduled fallback -->
                            <div v-else>
                                <a
                                    v-if="course.zoom_link"
                                    :href="course.zoom_link"
                                    target="_blank"
                                    class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-600/30 transition-all transform active:scale-95"
                                >
                                    <Video class="w-5 h-5" />
                                    <span>Gabung Sesi Online Meeting</span>
                                    <ExternalLink class="w-4 h-4 ml-1" />
                                </a>
                                <p v-else class="text-xs text-slate-400 italic">
                                    Tautan Online Meeting akan diinfokan oleh
                                    instruktur menjelang jam pelatihan dimulai.
                                </p>
                            </div>
                        </div>

                        <!-- ATTENDANCE STATUS TRIGGER -->
                        <!-- If effectiveZoomStatus is 'ended' and student hasn't attended yet, it is already handled cleanly in Case 3 above with the unified button, so we don't display the duplicate/confusing closed box -->
                        <div
                            v-if="
                                effectiveZoomStatus !== 'ended' ||
                                isTodayAttendanceCompleted ||
                                enrollment.status === 'completed'
                            "
                            class="border-t border-slate-200 dark:border-slate-800 pt-6 mt-6"
                        >
                            <!-- Case A: Already Attended Today / In Tab 1 -->
                            <div
                                v-if="
                                    isTodayAttendanceCompleted ||
                                    enrollment.status === 'completed'
                                "
                                class="p-5 rounded-2xl text-xs flex flex-col sm:flex-row items-center justify-between gap-4 shadow-sm"
                                :class="
                                    hasAttendedSelfStudy && !hasAttendedLiveZoom
                                        ? 'bg-blue-50 dark:bg-blue-950/40 border border-blue-500/30 text-blue-900 dark:text-blue-200'
                                        : 'bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-500/30 text-emerald-900 dark:text-emerald-200'
                                "
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        class="p-2.5 text-white rounded-xl shrink-0 shadow-sm"
                                        :class="
                                            hasAttendedSelfStudy &&
                                            !hasAttendedLiveZoom
                                                ? 'bg-blue-600'
                                                : 'bg-emerald-500'
                                        "
                                    >
                                        <BookOpen
                                            v-if="
                                                hasAttendedSelfStudy &&
                                                !hasAttendedLiveZoom
                                            "
                                            class="w-6 h-6"
                                        />
                                        <CheckCircle2 v-else class="w-6 h-6" />
                                    </span>
                                    <div>
                                        <p
                                            class="font-bold text-sm"
                                            :class="
                                                hasAttendedSelfStudy &&
                                                !hasAttendedLiveZoom
                                                    ? 'text-blue-900 dark:text-blue-100'
                                                    : 'text-emerald-900 dark:text-emerald-100'
                                            "
                                        >
                                            {{
                                                hasAttendedSelfStudy &&
                                                !hasAttendedLiveZoom
                                                    ? "Presensi Belajar Mandiri (Susulan) Tercatat!"
                                                    : "Presensi Online Meeting Berhasil Dicatat!"
                                            }}
                                        </p>
                                        <p
                                            class="text-xs mt-0.5"
                                            :class="
                                                hasAttendedSelfStudy &&
                                                !hasAttendedLiveZoom
                                                    ? 'text-blue-700 dark:text-blue-300'
                                                    : 'text-emerald-700 dark:text-emerald-300'
                                            "
                                        >
                                            {{
                                                hasAttendedSelfStudy &&
                                                !hasAttendedLiveZoom
                                                    ? "Kehadiran Anda tercatat via Belajar Mandiri (Susulan). Tab 2 (Materi & Evaluasi) telah dibuka!"
                                                    : isMultiDay
                                                      ? `Kehadiran tatap muka online Hari Ke-${dailySchedule?.current_day_number || 1} telah tercatat.`
                                                      : "Kehadiran Anda pada sesi tatap muka online telah tercatat. Tab 2 (Materi & Evaluasi) kini telah terbuka!"
                                            }}
                                        </p>
                                    </div>
                                </div>

                                <button
                                    v-if="isJalur2Visible"
                                    type="button"
                                    @click="selectedPath = 'mandiri'"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-white rounded-xl text-xs font-black shadow-md transition transform active:scale-95 shrink-0"
                                    :class="
                                        hasAttendedSelfStudy &&
                                        !hasAttendedLiveZoom
                                            ? 'bg-blue-600 hover:bg-blue-700 shadow-blue-600/20'
                                            : 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/20'
                                    "
                                >
                                    <span
                                        >Lanjut ke Tab 2 (Materi &
                                        Evaluasi)</span
                                    >
                                    <ArrowRight class="w-4 h-4" />
                                </button>
                            </div>

                            <!-- Case B: Attendance Window IS OPEN -->
                            <div
                                v-else-if="isAttendanceOpenNow"
                                class="space-y-4 p-6 bg-rose-50 dark:bg-rose-950/30 border border-rose-200 dark:border-rose-900/60 rounded-2xl animate-pulse"
                            >
                                <div
                                    class="flex items-center justify-center gap-2 text-rose-600 dark:text-rose-400 font-black text-sm"
                                >
                                    <span
                                        class="w-2.5 h-2.5 rounded-full bg-rose-600 animate-ping"
                                    ></span>
                                    SESI ABSENSI ONLINE MEETING SEDANG DIBUKA!
                                </div>
                                <div
                                    v-if="remainingAttendanceSeconds > 0"
                                    class="inline-block px-3 py-1 bg-rose-600 text-white font-mono text-xs font-bold rounded-full shadow-sm"
                                >
                                    <Timer class="w-3.5 h-3.5 inline mr-1" />
                                    Sisa Waktu Presensi:
                                    {{
                                        formatTimeRemaining(
                                            remainingAttendanceSeconds,
                                        )
                                    }}
                                </div>
                                <p
                                    class="text-xs text-rose-800 dark:text-rose-300"
                                >
                                    Instruktur telah membuka jendela presensi.
                                    Silakan klik tombol di bawah untuk mencatat
                                    kehadiran Anda sebelum waktu berakhir.
                                </p>
                                <button
                                    @click="submitZoomAttendance"
                                    :disabled="isAttending"
                                    class="w-full sm:w-auto px-8 py-3.5 rounded-xl text-sm font-black text-white bg-rose-600 hover:bg-rose-700 shadow-xl shadow-rose-600/30 transition-all transform active:scale-95 disabled:opacity-50 flex items-center justify-center gap-2 mx-auto"
                                >
                                    <Loader2
                                        v-if="isAttending"
                                        class="w-5 h-5 animate-spin"
                                    />
                                    <CheckCircle2 v-else class="w-5 h-5" />
                                    <span>{{
                                        isAttending
                                            ? "Mencatat Kehadiran..."
                                            : "KLIK ABSEN ONLINE SEKARANG"
                                    }}</span>
                                </button>
                            </div>

                            <!-- Case C: Attendance Window IS CLOSED / Late Option B -->
                            <div
                                v-else
                                class="space-y-4 p-6 bg-slate-50 dark:bg-slate-800/40 border border-slate-200 dark:border-slate-800 rounded-2xl text-slate-500 text-xs text-center"
                            >
                                <div
                                    class="flex items-center justify-center gap-1.5 font-bold text-slate-700 dark:text-slate-300"
                                >
                                    <Clock class="w-4 h-4 text-slate-400" />
                                    <span
                                        >Jam Absensi Online Belum Dibuka / Telah
                                        Ditutup</span
                                    >
                                </div>
                                <p
                                    v-if="isAttendanceScheduleUpcoming"
                                    class="max-w-md mx-auto text-xs text-indigo-600 dark:text-indigo-400 font-semibold"
                                >
                                    Presensi dijadwalkan buka pada pukul:
                                    {{
                                        formatReadableDate(
                                            course.zoom_attendance_scheduled_at ||
                                                dailySchedule?.today_module
                                                    ?.zoom_attendance_scheduled_at,
                                        )
                                    }}
                                    (Durasi:
                                    {{
                                        course.zoom_attendance_duration_minutes ||
                                        dailySchedule?.today_module
                                            ?.zoom_attendance_duration_minutes ||
                                        30
                                    }}
                                    menit).
                                </p>
                                <p
                                    v-else
                                    class="max-w-md mx-auto text-xs text-slate-500"
                                >
                                    Waktu presensi tatap muka online saat ini
                                    belum dibuka atau telah berakhir.
                                </p>

                                <!-- Opsi B: Konfirmasi Absen Mandiri (Susulan / Terlambat) -->
                                <!-- HANYA MUNCUL JIKA WAKTU YANG DIJADWALKAN TELAH LEWAT -->
                                <div
                                    v-if="isSchedulePassed"
                                    class="mt-4 pt-4 border-t border-slate-200 dark:border-slate-700/80 max-w-lg mx-auto space-y-2 text-center"
                                >
                                    <div
                                        class="inline-flex items-center gap-1.5 px-3 py-1 bg-blue-50 dark:bg-blue-950/40 text-blue-800 dark:text-blue-300 rounded-full font-bold text-[11px] border border-blue-200 dark:border-blue-800"
                                    >
                                        <BookOpen
                                            class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400"
                                        />
                                        <span
                                            >Terlambat / Berhalangan Hadir
                                            Online?</span
                                        >
                                    </div>
                                    <p
                                        class="text-xs text-slate-600 dark:text-slate-300 leading-relaxed max-w-md mx-auto"
                                    >
                                        Bagi Anda yang tidak sempat mengikuti
                                        tatap muka online, Anda dapat melakukan
                                        konfirmasi presensi melalui
                                        <strong
                                            >Jalur Belajar Mandiri
                                            (Susulan)</strong
                                        >.
                                    </p>
                                    <div class="pt-1">
                                        <button
                                            type="button"
                                            @click="submitSelfStudyCheckin"
                                            :disabled="isAttendingSelfStudy"
                                            class="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-600/25 transition-all transform active:scale-95 disabled:opacity-50 cursor-pointer"
                                        >
                                            <Loader2
                                                v-if="isAttendingSelfStudy"
                                                class="w-4 h-4 animate-spin"
                                            />
                                            <CheckCircle2
                                                v-else
                                                class="w-4 h-4"
                                            />
                                            <span>{{
                                                isAttendingSelfStudy
                                                    ? "Mencatat Presensi & Membuka Tab 2..."
                                                    : "Konfirmasi Absen Belajar Mandiri & Lanjut ke Tab 2"
                                            }}</span>
                                            <ArrowRight
                                                v-if="!isAttendingSelfStudy"
                                                class="w-3.5 h-3.5"
                                            />
                                        </button>
                                    </div>
                                    <p class="text-[11px] text-slate-400">
                                        Setelah konfirmasi, kehadiran Anda
                                        tercatat dan Tab 2 (Materi & Evaluasi)
                                        langsung terbuka untuk Anda pelajari.
                                    </p>
                                </div>

                                <!-- Keterangan saat jadwal sesi belum selesai -->
                                <div
                                    v-else-if="todayZoomSchedule.hasSchedule"
                                    class="mt-3 pt-3 border-t border-slate-200/60 dark:border-slate-800 text-[11px] text-slate-500 max-w-md mx-auto"
                                >
                                    <span>
                                        Opsi presensi susulan mandiri akan
                                        otomatis terbuka jika Anda berhalangan
                                        hadir setelah sesi tatap muka online
                                        berakhir
                                        <strong
                                            v-if="todayZoomSchedule.endMs"
                                            class="text-slate-700 dark:text-slate-300"
                                        >
                                            ({{
                                                formatReadableDate(
                                                    course.zoom_end_at ||
                                                        dailySchedule
                                                            ?.today_module
                                                            ?.zoom_end_at,
                                                )
                                            }}) </strong
                                        >.
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Multi-Day Daily Attendance Tracking Card -->
                <div
                    v-if="
                        isMultiDay &&
                        course.modules &&
                        course.modules.length > 0
                    "
                    class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 shadow-sm space-y-4"
                >
                    <div
                        class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3"
                    >
                        <div class="flex items-center gap-2">
                            <Calendar
                                class="w-4 h-4 text-indigo-600 dark:text-indigo-400"
                            />
                            <h3
                                class="text-xs font-black uppercase tracking-wider text-slate-900 dark:text-white"
                            >
                                Status Presensi Harian Sesi Tatap Muka (Tab 1)
                            </h3>
                        </div>
                        <span
                            class="text-xs font-bold text-slate-500 dark:text-slate-400"
                        >
                            {{
                                dailySchedule?.attended_module_ids?.length || 0
                            }}
                            dari {{ course.modules.length }} Hari Hadir
                        </span>
                    </div>

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3"
                    >
                        <div
                            v-for="(mod, mIdx) in course.modules"
                            :key="'daily-att-' + mod.id"
                            class="p-3.5 rounded-xl border transition-all flex items-center justify-between gap-2"
                            :class="
                                isModuleAttended(
                                    mod.id,
                                    getModuleDayNumber(mod, mIdx),
                                )
                                    ? 'bg-emerald-50/60 dark:bg-emerald-950/20 border-emerald-300 dark:border-emerald-800'
                                    : getModuleDayNumber(mod, mIdx) ===
                                        currentDayNumber
                                      ? 'bg-blue-50/60 dark:bg-blue-950/20 border-blue-300 dark:border-blue-800'
                                      : 'bg-slate-50/40 dark:bg-slate-800/20 border-slate-200 dark:border-slate-800 opacity-70'
                            "
                        >
                            <div class="truncate">
                                <span
                                    class="text-[10px] font-bold block"
                                    :class="
                                        isModuleAttended(
                                            mod.id,
                                            getModuleDayNumber(mod, mIdx),
                                        )
                                            ? 'text-emerald-700 dark:text-emerald-400'
                                            : getModuleDayNumber(mod, mIdx) ===
                                                currentDayNumber
                                              ? 'text-blue-700 dark:text-blue-400'
                                              : 'text-slate-400'
                                    "
                                >
                                    Hari Ke-{{ getModuleDayNumber(mod, mIdx) }}
                                </span>
                                <p
                                    class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate mt-0.5"
                                >
                                    {{ mod.title }}
                                </p>
                            </div>

                            <span
                                v-if="
                                    isModuleAttended(
                                        mod.id,
                                        getModuleDayNumber(mod, mIdx),
                                    )
                                "
                                class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950 px-2.5 py-0.5 rounded-full shrink-0"
                            >
                                <CheckCircle2
                                    class="w-3.5 h-3.5 text-emerald-600"
                                />
                                <span>Hadir</span>
                            </span>
                            <span
                                v-else-if="
                                    getModuleDayNumber(mod, mIdx) ===
                                        currentDayNumber && isAttendanceOpenNow
                                "
                                class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 dark:text-rose-300 bg-rose-100 dark:bg-rose-950 px-2.5 py-0.5 rounded-full shrink-0 animate-pulse"
                            >
                                <span>Buka Sekarang</span>
                            </span>
                            <span
                                v-else-if="
                                    getModuleDayNumber(mod, mIdx) ===
                                    currentDayNumber
                                "
                                class="text-[10px] font-bold text-blue-700 dark:text-blue-300 bg-blue-100 dark:bg-blue-950 px-2.5 py-0.5 rounded-full shrink-0"
                            >
                                Hari Ini
                            </span>
                            <span
                                v-else-if="
                                    getModuleDayNumber(mod, mIdx) <
                                    currentDayNumber
                                "
                                class="text-[10px] text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full shrink-0"
                            >
                                Lewat
                            </span>
                            <span
                                v-else
                                class="text-[10px] text-slate-400 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded-full shrink-0"
                            >
                                Mendatang
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ======================================================== -->
            <!-- Tab 2: BELAJAR MANDIRI (SUSULAN / MATERI)             -->
            <!-- ======================================================== -->
            <div v-if="selectedPath === 'mandiri'" class="space-y-6">
                <!-- Case: Locked if single day and Tab 1 not finished -->
                <div
                    v-if="!isJalur2Visible"
                    class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-8 text-center max-w-lg mx-auto space-y-4 shadow-sm"
                >
                    <div
                        class="w-16 h-16 mx-auto bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400 rounded-2xl flex items-center justify-center"
                    >
                        <Lock class="w-8 h-8" />
                    </div>
                    <h3
                        class="text-base font-bold text-slate-900 dark:text-white"
                    >
                        Tab 2 (Materi & Evaluasi) Belum Terbuka
                    </h3>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Untuk pelatihan 1 hari, peserta diwajibkan menyelesaikan
                        <strong>Tab 1: Status Pembelajaran & Absensi</strong>
                        terlebih dahulu.
                    </p>
                    <p
                        class="text-xs text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/40 p-3 rounded-xl border border-amber-200 dark:border-amber-900/60"
                    >
                        Silakan ikuti sesi tatap muka Online Meeting dan lakukan
                        absensi kehadiran di Tab 1 hingga status pembelajaran
                        selesai.
                    </p>
                    <button
                        @click="selectedPath = 'zoom'"
                        class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-sm"
                    >
                        Kembali ke Status Pembelajaran & Absensi (Tab 1)
                    </button>
                </div>

                <!-- Case: Unlocked - Full Modules & Lessons Sidebar + Content Viewer -->
                <div v-else class="space-y-4">
                    <!-- Multi-Day Training Banner -->
                    <div
                        v-if="isMultiDay"
                        class="p-4 bg-indigo-50/80 dark:bg-indigo-950/30 border border-indigo-200 dark:border-indigo-900/50 rounded-2xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="p-2.5 bg-indigo-600 text-white rounded-xl shrink-0 shadow-sm"
                            >
                                <Calendar class="w-4 h-4" />
                            </span>
                            <div>
                                <span
                                    class="font-bold text-indigo-950 dark:text-indigo-200"
                                >
                                    Pelatihan
                                    {{
                                        dailySchedule?.duration_days ||
                                        course.duration_in_days
                                    }}
                                    Hari • Saat ini Hari Ke-{{
                                        currentDayNumber
                                    }}
                                </span>
                                <p
                                    class="text-slate-600 dark:text-slate-300 text-[11px] mt-0.5"
                                >
                                    Materi unit kompetensi dibuka bertahap
                                    setiap hari. Materi yang terbuka saat ini
                                    adalah unit hingga
                                    <strong
                                        >Hari Ke-{{ currentDayNumber }}</strong
                                    >.
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span
                                class="text-xs font-black font-mono px-3 py-1.5 rounded-full bg-indigo-600 text-white shadow-sm"
                            >
                                Progres: {{ currentProgress }}%
                            </span>
                        </div>
                    </div>

                    <!-- Instruction Banner for Tab 2 (Single Day) -->
                    <div
                        v-else-if="enrollment.status !== 'completed'"
                        class="p-4 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-900/60 rounded-2xl text-xs text-blue-900 dark:text-blue-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-sm"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="p-2 bg-blue-600 text-white rounded-xl shrink-0 shadow-sm"
                            >
                                <BookOpen class="w-4 h-4" />
                            </span>
                            <div class="leading-relaxed">
                                <span class="font-bold"
                                    >Petunjuk Materi & Evaluasi Pembelajaran
                                    (Tab 2):</span
                                >
                                <span
                                    v-if="
                                        hasAttendedZoom ||
                                        enrollment?.attendance_path ===
                                            'live_zoom'
                                    "
                                    class="ml-1"
                                >
                                    Presensi sesi Online Meeting Tatap Muka Anda
                                    telah tercatat. Silakan selesaikan seluruh
                                    materi unit kompetensi dan kuis/soal
                                    evaluasi (<strong
                                        class="text-blue-700 dark:text-blue-300"
                                        >100%</strong
                                    >), lalu klik tombol
                                    <strong>"SELESAIKAN PEMBELAJARAN"</strong>
                                    untuk menerbitkan sertifikat kelulusan resmi
                                    Anda.
                                </span>
                                <span v-else class="ml-1">
                                    Pelajari setiap elemen kompetensi di bawah
                                    ini secara mandiri hingga tuntas (<strong
                                        class="text-blue-700 dark:text-blue-300"
                                        >100%</strong
                                    >) untuk menyelesaikan pembelajaran dan
                                    menerbitkan sertifikat kelulusan resmi Anda.
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            <span
                                class="text-xs font-black font-mono px-3 py-1.5 rounded-full bg-blue-600 text-white shadow-sm"
                            >
                                Progres: {{ currentProgress }}%
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Left: Module & Lesson Tree (4 cols) -->
                        <div class="lg:col-span-4 space-y-4">
                            <div
                                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm space-y-3"
                            >
                                <div
                                    class="flex items-center justify-between border-b pb-2"
                                >
                                    <span
                                        class="text-xs font-bold text-slate-900 dark:text-white uppercase tracking-wider"
                                    >
                                        Daftar Unit & Elemen Kompetensi
                                    </span>
                                    <span
                                        class="text-xs font-bold text-indigo-600 dark:text-indigo-400"
                                    >
                                        {{ completedIds.length }} /
                                        {{ allLessons.length }} Selesai
                                    </span>
                                </div>

                                <!-- Modules Accordion -->
                                <div
                                    class="space-y-3 max-h-[600px] overflow-y-auto pr-1"
                                >
                                    <div
                                        v-for="(mod, mIdx) in course.modules"
                                        :key="mod.id"
                                        class="border rounded-xl overflow-hidden transition-all"
                                        :class="
                                            isModuleLocked(mod, mIdx)
                                                ? 'border-slate-200 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/40 opacity-80'
                                                : 'border-slate-200 dark:border-slate-800'
                                        "
                                    >
                                        <div
                                            class="px-3.5 py-2.5 font-bold text-xs flex items-center justify-between gap-2"
                                            :class="
                                                isModuleLocked(mod, mIdx)
                                                    ? 'bg-slate-100/80 dark:bg-slate-800/50 text-slate-500 dark:text-slate-400'
                                                    : 'bg-slate-50 dark:bg-slate-800/80 text-slate-800 dark:text-slate-200'
                                            "
                                        >
                                            <div
                                                class="flex items-center gap-2 truncate"
                                            >
                                                <span
                                                    class="w-5 h-5 rounded-full text-[10px] font-black flex items-center justify-center shrink-0"
                                                    :class="
                                                        isModuleLocked(
                                                            mod,
                                                            mIdx,
                                                        )
                                                            ? 'bg-slate-200 text-slate-500'
                                                            : 'bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300'
                                                    "
                                                >
                                                    {{ mIdx + 1 }}
                                                </span>
                                                <span class="truncate">{{
                                                    mod.title
                                                }}</span>
                                            </div>

                                            <!-- Locked badge in multi-day -->
                                            <span
                                                v-if="isModuleLocked(mod, mIdx)"
                                                class="inline-flex items-center gap-1 text-[10px] font-bold text-amber-700 dark:text-amber-400 bg-amber-100/80 dark:bg-amber-950/60 px-2 py-0.5 rounded-full shrink-0"
                                            >
                                                <Lock class="w-3 h-3" />
                                                <span
                                                    >Hari Ke-{{
                                                        getModuleDayNumber(
                                                            mod,
                                                            mIdx,
                                                        )
                                                    }}</span
                                                >
                                            </span>
                                            <span
                                                v-else-if="isMultiDay"
                                                class="inline-flex items-center text-[10px] font-semibold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/40 px-2 py-0.5 rounded-full shrink-0"
                                            >
                                                Hari Ke-{{
                                                    getModuleDayNumber(
                                                        mod,
                                                        mIdx,
                                                    )
                                                }}
                                            </span>
                                        </div>

                                        <div
                                            class="divide-y divide-slate-100 dark:divide-slate-800/60"
                                        >
                                            <button
                                                v-for="(
                                                    les, lIdx
                                                ) in mod.lessons"
                                                :key="les.id"
                                                :disabled="
                                                    isModuleLocked(mod, mIdx)
                                                "
                                                @click="
                                                    selectLesson(les, mod, mIdx)
                                                "
                                                class="w-full text-left px-3 py-2.5 text-xs flex items-center justify-between transition-colors"
                                                :class="
                                                    isModuleLocked(mod, mIdx)
                                                        ? 'cursor-not-allowed opacity-50 text-slate-400'
                                                        : activeLesson?.id ===
                                                            les.id
                                                          ? 'bg-indigo-50 dark:bg-indigo-950/60 font-bold text-indigo-700 dark:text-indigo-300'
                                                          : 'hover:bg-slate-50/60 dark:hover:bg-slate-800/30 text-slate-700 dark:text-slate-300'
                                                "
                                            >
                                                <div
                                                    class="flex items-center gap-2 truncate pr-2"
                                                >
                                                    <span
                                                        class="p-1 rounded text-[10px]"
                                                        :class="[
                                                            isModuleLocked(
                                                                mod,
                                                                mIdx,
                                                            )
                                                                ? 'bg-slate-100 text-slate-400'
                                                                : {
                                                                      'bg-rose-100 text-rose-600':
                                                                          les.content_type ===
                                                                          'video',
                                                                      'bg-blue-100 text-blue-600':
                                                                          les.content_type ===
                                                                          'article',
                                                                      'bg-amber-100 text-amber-600':
                                                                          les.content_type ===
                                                                          'pdf',
                                                                      'bg-emerald-100 text-emerald-600':
                                                                          les.content_type ===
                                                                          'image',
                                                                  },
                                                        ]"
                                                    >
                                                        <Video
                                                            v-if="
                                                                les.content_type ===
                                                                'video'
                                                            "
                                                            class="w-3 h-3"
                                                        />
                                                        <FileText
                                                            v-else-if="
                                                                les.content_type ===
                                                                'article'
                                                            "
                                                            class="w-3 h-3"
                                                        />
                                                        <BookOpen
                                                            v-else-if="
                                                                les.content_type ===
                                                                'pdf'
                                                            "
                                                            class="w-3 h-3"
                                                        />
                                                        <ImageIcon
                                                            v-else
                                                            class="w-3 h-3"
                                                        />
                                                    </span>
                                                    <span class="truncate"
                                                        >{{ mIdx + 1 }}.{{
                                                            lIdx + 1
                                                        }}
                                                        {{ les.title }}</span
                                                    >
                                                </div>

                                                <span
                                                    v-if="
                                                        isModuleLocked(
                                                            mod,
                                                            mIdx,
                                                        )
                                                    "
                                                    class="text-slate-300 shrink-0"
                                                >
                                                    <Lock class="w-3.5 h-3.5" />
                                                </span>
                                                <span
                                                    v-else-if="
                                                        completedIds.includes(
                                                            les.id,
                                                        )
                                                    "
                                                    class="text-emerald-500 shrink-0"
                                                >
                                                    <CheckCircle2
                                                        class="w-4 h-4"
                                                    />
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-slate-300 shrink-0"
                                                >
                                                    <Clock
                                                        class="w-3.5 h-3.5"
                                                    />
                                                </span>
                                            </button>

                                            <!-- Optional Quizzes for this Unit (Supports Multiple Quizzes) -->
                                            <template
                                                v-for="qz in mod.quizzes &&
                                                mod.quizzes.length > 0
                                                    ? mod.quizzes
                                                    : mod.quiz
                                                      ? [mod.quiz]
                                                      : []"
                                                :key="'sidebar-quiz-' + qz.id"
                                            >
                                                <button
                                                    v-if="
                                                        qz.questions?.length > 0
                                                    "
                                                    type="button"
                                                    :disabled="
                                                        isModuleLocked(
                                                            mod,
                                                            mIdx,
                                                        )
                                                    "
                                                    @click="
                                                        selectQuiz(
                                                            qz,
                                                            mod,
                                                            mIdx,
                                                        )
                                                    "
                                                    class="w-full text-left px-3 py-2.5 text-xs flex items-center justify-between transition-colors border-t border-amber-200/80 dark:border-amber-900/40"
                                                    :class="
                                                        isModuleLocked(
                                                            mod,
                                                            mIdx,
                                                        )
                                                            ? 'cursor-not-allowed opacity-50 text-slate-400'
                                                            : activeItemType ===
                                                                    'quiz' &&
                                                                activeQuiz?.id ===
                                                                    qz.id
                                                              ? 'bg-amber-100/80 dark:bg-amber-950/80 font-bold text-amber-950 dark:text-amber-200'
                                                              : 'bg-amber-50/50 dark:bg-amber-950/20 hover:bg-amber-50 dark:hover:bg-amber-950/40 text-amber-900 dark:text-amber-300'
                                                    "
                                                >
                                                    <div
                                                        class="flex items-center gap-2 truncate pr-2"
                                                    >
                                                        <span
                                                            class="p-1 rounded bg-amber-200 dark:bg-amber-900/60 text-amber-800 dark:text-amber-300 shrink-0"
                                                        >
                                                            <HelpCircle
                                                                class="w-3 h-3"
                                                            />
                                                        </span>
                                                        <span
                                                            class="truncate"
                                                            >{{
                                                                qz.title
                                                            }}</span
                                                        >
                                                    </div>

                                                    <span
                                                        v-if="
                                                            isModuleLocked(
                                                                mod,
                                                                mIdx,
                                                            )
                                                        "
                                                        class="text-slate-300 shrink-0"
                                                    >
                                                        <Lock
                                                            class="w-3.5 h-3.5"
                                                        />
                                                    </span>
                                                    <span
                                                        v-else-if="
                                                            studentQuizAttempts[
                                                                qz.id
                                                            ]?.is_passed
                                                        "
                                                        class="inline-flex items-center gap-1 text-[10px] font-bold text-emerald-700 dark:text-emerald-300 bg-emerald-100 dark:bg-emerald-950 px-2 py-0.5 rounded-full shrink-0"
                                                    >
                                                        <CheckCircle2
                                                            class="w-3 h-3 text-emerald-600"
                                                        />
                                                        <span
                                                            >{{
                                                                studentQuizAttempts[
                                                                    qz.id
                                                                ].score
                                                            }}% (Lulus)</span
                                                        >
                                                    </span>
                                                    <span
                                                        v-else-if="
                                                            studentQuizAttempts[
                                                                qz.id
                                                            ]
                                                        "
                                                        class="inline-flex items-center gap-1 text-[10px] font-bold text-rose-700 dark:text-rose-300 bg-rose-100 dark:bg-rose-950 px-2 py-0.5 rounded-full shrink-0"
                                                    >
                                                        <span
                                                            >{{
                                                                studentQuizAttempts[
                                                                    qz.id
                                                                ].score
                                                            }}% (Ulang)</span
                                                        >
                                                    </span>
                                                    <span
                                                        v-else
                                                        class="text-[10px] font-medium text-amber-700 dark:text-amber-400 bg-amber-100/70 dark:bg-amber-950 px-1.5 py-0.5 rounded shrink-0"
                                                    >
                                                        KKM
                                                        {{ qz.passing_score }}%
                                                    </span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>

                                <!-- SELESAIKAN PEMBELAJARAN (UNLOCKS AT 100% PROGRESS) -->
                                <div class="border-t pt-3 space-y-2">
                                    <div
                                        v-if="enrollment.status === 'completed'"
                                        class="text-center p-3 bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-500/30 rounded-xl text-emerald-800 dark:text-emerald-300 text-xs font-bold flex items-center justify-center gap-2"
                                    >
                                        <CheckCircle2
                                            class="w-4 h-4 text-emerald-600"
                                        />
                                        <span
                                            >Status: SUDAH MENGIKUTI /
                                            SELESAI</span
                                        >
                                    </div>
                                    <div v-else>
                                        <button
                                            v-if="
                                                currentProgress >= 100 &&
                                                allLessons.length > 0
                                            "
                                            @click="submitCompleteCourse"
                                            :disabled="isAttending"
                                            class="w-full py-3.5 px-3 rounded-xl text-xs font-black text-white bg-emerald-600 hover:bg-emerald-500 shadow-md shadow-emerald-600/30 transition-all animate-pulse flex items-center justify-center gap-2 cursor-pointer disabled:opacity-50"
                                        >
                                            <Loader2
                                                v-if="isAttending"
                                                class="w-4 h-4 animate-spin"
                                            />
                                            <CheckCircle2
                                                v-else
                                                class="w-4 h-4"
                                            />
                                            <span>{{
                                                isAttending
                                                    ? "Memproses..."
                                                    : "SELESAIKAN PEMBELAJARAN"
                                            }}</span>
                                        </button>
                                        <div
                                            v-else
                                            class="p-2.5 bg-slate-50 dark:bg-slate-800 rounded-xl text-[11px] text-slate-400 text-center flex items-center justify-center gap-1.5"
                                        >
                                            <Lock class="w-3.5 h-3.5" />
                                            <span
                                                >Selesaikan 100% materi untuk
                                                menyelesaikan pembelajaran</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right: Active Lesson Content Viewer (8 cols) -->
                        <div class="lg:col-span-8">
                            <!-- CASE A: ACTIVE QUIZ TAKER -->
                            <div
                                v-if="activeItemType === 'quiz' && activeQuiz"
                                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-8 shadow-sm space-y-6"
                            >
                                <!-- Quiz Header -->
                                <div
                                    class="border-b pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                                >
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="text-[11px] font-bold text-amber-600 dark:text-amber-400 uppercase tracking-wider bg-amber-50 dark:bg-amber-950/60 px-2 py-0.5 rounded border border-amber-200 dark:border-amber-800/60"
                                            >
                                                Kuis Unit Kompetensi
                                            </span>
                                            <span
                                                class="text-xs text-slate-500"
                                            >
                                                {{ activeQuiz.module_title }}
                                            </span>
                                        </div>
                                        <h2
                                            class="text-xl font-black text-slate-900 dark:text-white mt-1"
                                        >
                                            {{ activeQuiz.title }}
                                        </h2>
                                        <p
                                            v-if="activeQuiz.description"
                                            class="text-xs text-slate-500 dark:text-slate-400 mt-1"
                                        >
                                            {{ activeQuiz.description }}
                                        </p>
                                    </div>

                                    <div
                                        class="flex items-center gap-2 shrink-0"
                                    >
                                        <span
                                            class="text-xs px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-semibold"
                                        >
                                            {{
                                                activeQuiz.questions?.length ||
                                                0
                                            }}
                                            Butir Soal
                                        </span>
                                        <span
                                            class="text-xs px-2.5 py-1 rounded-full bg-amber-100 dark:bg-amber-950/80 text-amber-800 dark:text-amber-300 font-bold border border-amber-200 dark:border-amber-800"
                                        >
                                            KKM: {{ activeQuiz.passing_score }}%
                                        </span>
                                    </div>
                                </div>

                                <!-- Result / Attempt Banner -->
                                <div
                                    v-if="
                                        quizResult ||
                                        studentQuizAttempts[activeQuiz.id]
                                    "
                                    class="p-4 rounded-xl border flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                    :class="
                                        (quizResult?.is_passed ??
                                        studentQuizAttempts[activeQuiz.id]
                                            ?.is_passed)
                                            ? 'bg-emerald-50 dark:bg-emerald-950/40 border-emerald-200 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200'
                                            : 'bg-amber-50 dark:bg-amber-950/40 border-amber-200 dark:border-amber-800 text-amber-900 dark:text-amber-200'
                                    "
                                >
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-10 h-10 rounded-full flex items-center justify-center shrink-0"
                                            :class="
                                                (quizResult?.is_passed ??
                                                studentQuizAttempts[
                                                    activeQuiz.id
                                                ]?.is_passed)
                                                    ? 'bg-emerald-600 text-white'
                                                    : 'bg-amber-500 text-white'
                                            "
                                        >
                                            <CheckCircle2
                                                v-if="
                                                    quizResult?.is_passed ??
                                                    studentQuizAttempts[
                                                        activeQuiz.id
                                                    ]?.is_passed
                                                "
                                                class="w-6 h-6"
                                            />
                                            <AlertCircle
                                                v-else
                                                class="w-6 h-6"
                                            />
                                        </div>
                                        <div>
                                            <p class="font-black text-sm">
                                                {{
                                                    (quizResult?.is_passed ??
                                                    studentQuizAttempts[
                                                        activeQuiz.id
                                                    ]?.is_passed)
                                                        ? "Selamat! Anda Telah Lulus Kuis Ini"
                                                        : "Belum Mencapai Nilai Kelulusan (KKM)"
                                                }}
                                            </p>
                                            <p
                                                class="text-xs opacity-90 mt-0.5"
                                            >
                                                Nilai Anda:
                                                <strong
                                                    >{{
                                                        quizResult?.score ??
                                                        studentQuizAttempts[
                                                            activeQuiz.id
                                                        ]?.score
                                                    }}%</strong
                                                >
                                                (Batas Lulus / KKM:
                                                {{
                                                    activeQuiz.passing_score
                                                }}%).
                                                {{
                                                    (quizResult?.is_passed ??
                                                    studentQuizAttempts[
                                                        activeQuiz.id
                                                    ]?.is_passed)
                                                        ? "Unit kompetensi ini telah dihitung ke dalam syarat kelulusan kelas Anda."
                                                        : "Silakan pelajari kembali materi pada unit ini dan Anda dapat mengulang kuis."
                                                }}
                                            </p>
                                        </div>
                                    </div>

                                    <div
                                        v-if="
                                            !(
                                                quizResult?.is_passed ??
                                                studentQuizAttempts[
                                                    activeQuiz.id
                                                ]?.is_passed
                                            )
                                        "
                                        class="shrink-0"
                                    >
                                        <button
                                            type="button"
                                            @click="retakeQuiz"
                                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs font-bold transition shadow-sm"
                                        >
                                            <RotateCcw class="w-3.5 h-3.5" />
                                            <span>Kerjakan Ulang</span>
                                        </button>
                                    </div>
                                </div>

                                <!-- Questions List Form -->
                                <div class="space-y-6">
                                    <div
                                        v-for="(
                                            q, qIdx
                                        ) in activeQuiz.questions"
                                        :key="q.id"
                                        class="p-5 rounded-xl border transition-all"
                                        :class="
                                            quizResult?.feedback?.find(
                                                (f) => f.question_id === q.id,
                                            )
                                                ? quizResult.feedback.find(
                                                      (f) =>
                                                          f.question_id ===
                                                          q.id,
                                                  ).is_correct
                                                    ? 'bg-emerald-50/40 dark:bg-emerald-950/20 border-emerald-200 dark:border-emerald-900/60'
                                                    : 'bg-rose-50/40 dark:bg-rose-950/20 border-rose-200 dark:border-rose-900/60'
                                                : 'bg-slate-50/60 dark:bg-slate-800/40 border-slate-200 dark:border-slate-800'
                                        "
                                    >
                                        <div class="flex items-start gap-3">
                                            <span
                                                class="w-6 h-6 rounded-full bg-slate-200 dark:bg-slate-700 text-slate-800 dark:text-slate-200 text-xs font-black flex items-center justify-center shrink-0 mt-0.5"
                                            >
                                                {{ qIdx + 1 }}
                                            </span>
                                            <div class="flex-1 space-y-3">
                                                <p
                                                    class="text-sm font-bold text-slate-900 dark:text-white leading-relaxed"
                                                >
                                                    {{ q.question_text }}
                                                </p>

                                                <!-- Options Choices -->
                                                <div class="space-y-2">
                                                    <label
                                                        v-for="opt in q.options"
                                                        :key="opt.key"
                                                        class="flex items-center gap-3 p-3 rounded-xl border text-xs cursor-pointer transition"
                                                        :class="[
                                                            quizAnswers[
                                                                q.id
                                                            ] === opt.key
                                                                ? 'border-indigo-600 bg-indigo-50/60 dark:bg-indigo-950/40 ring-1 ring-indigo-500 font-semibold'
                                                                : 'border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 hover:bg-slate-50 dark:hover:bg-slate-800/60 text-slate-700 dark:text-slate-300',
                                                            quizResult?.feedback?.find(
                                                                (f) =>
                                                                    f.question_id ===
                                                                    q.id,
                                                            )
                                                                ?.correct_answer ===
                                                            opt.key
                                                                ? 'border-emerald-500 bg-emerald-50 dark:bg-emerald-950/50 font-bold text-emerald-900 dark:text-emerald-200'
                                                                : '',
                                                            quizResult?.feedback?.find(
                                                                (f) =>
                                                                    f.question_id ===
                                                                        q.id &&
                                                                    f.user_answer ===
                                                                        opt.key &&
                                                                    !f.is_correct,
                                                            )
                                                                ? 'border-rose-500 bg-rose-50 dark:bg-rose-950/50 text-rose-900 dark:text-rose-200'
                                                                : '',
                                                        ]"
                                                    >
                                                        <input
                                                            type="radio"
                                                            :name="'q_' + q.id"
                                                            :value="opt.key"
                                                            v-model="
                                                                quizAnswers[
                                                                    q.id
                                                                ]
                                                            "
                                                            :disabled="
                                                                Boolean(
                                                                    quizResult,
                                                                ) ||
                                                                Boolean(
                                                                    studentQuizAttempts[
                                                                        activeQuiz
                                                                            .id
                                                                    ]
                                                                        ?.is_passed,
                                                                )
                                                            "
                                                            class="w-4 h-4 text-indigo-600 focus:ring-indigo-500"
                                                        />
                                                        <span
                                                            class="font-bold text-slate-900 dark:text-white"
                                                            >{{
                                                                opt.key
                                                            }}.</span
                                                        >
                                                        <span class="flex-1">{{
                                                            opt.text
                                                        }}</span>

                                                        <span
                                                            v-if="
                                                                quizResult?.feedback?.find(
                                                                    (f) =>
                                                                        f.question_id ===
                                                                        q.id,
                                                                )
                                                                    ?.correct_answer ===
                                                                opt.key
                                                            "
                                                            class="text-emerald-600 font-bold text-[11px] flex items-center gap-1"
                                                        >
                                                            <Check
                                                                class="w-3.5 h-3.5"
                                                            />
                                                            Jawaban Benar
                                                        </span>
                                                        <span
                                                            v-else-if="
                                                                quizResult?.feedback?.find(
                                                                    (f) =>
                                                                        f.question_id ===
                                                                            q.id &&
                                                                        f.user_answer ===
                                                                            opt.key &&
                                                                        !f.is_correct,
                                                                )
                                                            "
                                                            class="text-rose-600 font-bold text-[11px] flex items-center gap-1"
                                                        >
                                                            <X
                                                                class="w-3.5 h-3.5"
                                                            />
                                                            Jawaban Anda
                                                        </span>
                                                    </label>
                                                </div>

                                                <!-- Explanation after submit -->
                                                <div
                                                    v-if="
                                                        quizResult?.feedback?.find(
                                                            (f) =>
                                                                f.question_id ===
                                                                q.id,
                                                        )?.explanation
                                                    "
                                                    class="p-3 bg-blue-50 dark:bg-blue-950/40 border border-blue-100 dark:border-blue-900/40 rounded-lg text-xs text-blue-900 dark:text-blue-200 space-y-1"
                                                >
                                                    <span class="font-bold"
                                                        >Penjelasan:</span
                                                    >
                                                    <p>
                                                        {{
                                                            quizResult.feedback.find(
                                                                (f) =>
                                                                    f.question_id ===
                                                                    q.id,
                                                            ).explanation
                                                        }}
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Quiz Action Buttons -->
                                <div
                                    class="border-t pt-4 flex flex-col sm:flex-row items-center justify-between gap-3"
                                >
                                    <div class="text-xs text-slate-500">
                                        Terjawab:
                                        <strong>{{
                                            Object.keys(quizAnswers).filter(
                                                (k) => quizAnswers[k],
                                            ).length
                                        }}</strong>
                                        /
                                        {{ activeQuiz.questions?.length || 0 }}
                                        soal
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            v-if="
                                                !quizResult &&
                                                !studentQuizAttempts[
                                                    activeQuiz.id
                                                ]?.is_passed
                                            "
                                            type="button"
                                            @click="
                                                submitStudentQuiz(activeQuiz)
                                            "
                                            :disabled="isSubmittingQuiz"
                                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-xs font-black text-white bg-indigo-600 hover:bg-indigo-700 shadow-md shadow-indigo-600/25 transition disabled:opacity-50"
                                        >
                                            <Loader2
                                                v-if="isSubmittingQuiz"
                                                class="w-4 h-4 animate-spin"
                                            />
                                            <CheckCircle2
                                                v-else
                                                class="w-4 h-4"
                                            />
                                            <span>{{
                                                isSubmittingQuiz
                                                    ? "Memeriksa Jawaban..."
                                                    : "Kirim & Nilai Jawaban Kuis"
                                            }}</span>
                                        </button>

                                        <button
                                            v-else-if="
                                                !studentQuizAttempts[
                                                    activeQuiz.id
                                                ]?.is_passed
                                            "
                                            type="button"
                                            @click="retakeQuiz"
                                            class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-xs font-black text-white bg-amber-600 hover:bg-amber-700 transition"
                                        >
                                            <RotateCcw class="w-4 h-4" />
                                            <span>Kerjakan Ulang Kuis</span>
                                        </button>

                                        <div
                                            v-else
                                            class="flex flex-wrap items-center gap-3"
                                        >
                                            <div
                                                class="text-xs font-bold text-emerald-600 flex items-center gap-1.5 bg-emerald-50 dark:bg-emerald-950 px-3 py-2 rounded-lg"
                                            >
                                                <CheckCircle2 class="w-4 h-4" />
                                                <span
                                                    >Anda telah lulus kuis unit
                                                    ini!</span
                                                >
                                            </div>

                                            <button
                                                v-if="
                                                    currentProgress >= 100 &&
                                                    enrollment.status !==
                                                        'completed'
                                                "
                                                @click="submitCompleteCourse"
                                                :disabled="isAttending"
                                                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/30 transition-all animate-pulse disabled:opacity-50"
                                            >
                                                <Loader2
                                                    v-if="isAttending"
                                                    class="w-4 h-4 animate-spin"
                                                />
                                                <Award v-else class="w-4 h-4" />
                                                <span>{{
                                                    isAttending
                                                        ? "Memproses..."
                                                        : "Selesaikan Pembelajaran"
                                                }}</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- CASE B: ACTIVE LESSON VIEW -->
                            <div
                                v-else-if="activeLesson"
                                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 md:p-8 shadow-sm space-y-6"
                            >
                                <!-- Lesson Title & Metadata -->
                                <div
                                    class="border-b pb-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                                >
                                    <div>
                                        <span
                                            class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 uppercase tracking-wider"
                                        >
                                            {{ activeLesson.module_title }}
                                        </span>
                                        <h2
                                            class="text-xl font-black text-slate-900 dark:text-white mt-0.5"
                                        >
                                            {{ activeLesson.title }}
                                        </h2>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <span
                                            v-if="
                                                activeLesson.content_type ===
                                                'pdf'
                                            "
                                            class="text-xs px-2.5 py-1 rounded-full bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300 font-bold flex items-center gap-1"
                                        >
                                            <BookOpen class="w-3.5 h-3.5" />
                                            Dokumen PDF
                                        </span>
                                        <span
                                            class="text-xs px-2.5 py-1 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 font-semibold"
                                        >
                                            {{
                                                activeLesson.estimated_duration_minutes
                                            }}
                                            menit
                                        </span>
                                        <span
                                            v-if="
                                                completedIds.includes(
                                                    activeLesson.id,
                                                )
                                            "
                                            class="text-xs px-2.5 py-1 rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300 font-bold flex items-center gap-1"
                                        >
                                            <CheckCircle2 class="w-3.5 h-3.5" />
                                            Selesai
                                        </span>
                                    </div>
                                </div>

                                <!-- MEDIA DISPLAY -->
                                <!-- Type 1: Video -->
                                <div
                                    v-if="activeLesson.content_type === 'video'"
                                    class="space-y-4"
                                >
                                    <div
                                        v-if="activeLesson.video_url"
                                        class="relative w-full aspect-video rounded-xl overflow-hidden bg-black shadow-lg"
                                    >
                                        <iframe
                                            :src="
                                                getYoutubeEmbedUrl(
                                                    activeLesson.video_url,
                                                )
                                            "
                                            class="w-full h-full"
                                            frameborder="0"
                                            allow="
                                                accelerometer;
                                                autoplay;
                                                clipboard-write;
                                                encrypted-media;
                                                gyroscope;
                                                picture-in-picture;
                                            "
                                            allowfullscreen
                                        ></iframe>
                                    </div>
                                    <div
                                        v-else-if="activeLesson.media_path"
                                        class="relative w-full aspect-video rounded-xl overflow-hidden bg-black shadow-lg"
                                    >
                                        <video controls class="w-full h-full">
                                            <source
                                                :src="
                                                    '/storage/' +
                                                    activeLesson.media_path
                                                "
                                                type="video/mp4"
                                            />
                                            Browser Anda tidak mendukung tag
                                            video.
                                        </video>
                                    </div>
                                    <div
                                        v-else
                                        class="p-8 text-center bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400 text-xs"
                                    >
                                        Video pelatihan belum diunggah.
                                    </div>
                                </div>

                                <!-- Type 2: Image Slide -->
                                <div
                                    v-else-if="
                                        activeLesson.content_type === 'image'
                                    "
                                    class="space-y-4"
                                >
                                    <div
                                        v-if="activeLesson.media_path"
                                        class="rounded-xl overflow-hidden border border-slate-200 dark:border-slate-800 shadow-md"
                                    >
                                        <img
                                            :src="
                                                '/storage/' +
                                                activeLesson.media_path
                                            "
                                            :alt="activeLesson.title"
                                            class="w-full h-auto object-contain max-h-[500px] mx-auto"
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="p-8 text-center bg-slate-50 dark:bg-slate-800 rounded-xl text-slate-400 text-xs"
                                    >
                                        File gambar materi belum diunggah.
                                    </div>
                                </div>

                                <!-- Type 3: PDF Book / Slide Document -->
                                <div
                                    v-else-if="
                                        activeLesson.content_type === 'pdf'
                                    "
                                    class="space-y-4"
                                >
                                    <div v-if="activeLesson.media_path">
                                        <PdfBookViewer
                                            :pdf-url="
                                                getPdfUrl(
                                                    activeLesson.media_path,
                                                )
                                            "
                                            :title="activeLesson.title"
                                            :already-completed="
                                                completedIds.includes(
                                                    activeLesson.id,
                                                )
                                            "
                                            @completed="
                                                onPdfCompleted(activeLesson.id)
                                            "
                                        />
                                    </div>
                                    <div
                                        v-else
                                        class="p-12 text-center bg-slate-50 dark:bg-slate-800/60 rounded-2xl border border-dashed border-slate-300 dark:border-slate-700 text-slate-400 text-xs"
                                    >
                                        File dokumen PDF belum diunggah oleh
                                        instruktur.
                                    </div>
                                </div>

                                <!-- Type 4: Article / Text Content -->
                                <div
                                    v-if="activeLesson.content_text"
                                    class="prose dark:prose-invert max-w-none text-sm text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-line bg-slate-50/50 dark:bg-slate-800/30 p-6 rounded-xl border border-slate-100 dark:border-slate-800"
                                >
                                    {{ activeLesson.content_text }}
                                </div>

                                <!-- Mark Complete & Next Action -->
                                <div
                                    class="pt-6 border-t border-slate-200 dark:border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            v-if="
                                                activeLesson.content_type ===
                                                    'pdf' &&
                                                !canCompleteActiveLesson
                                            "
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-amber-500/10 border border-amber-500/20 text-amber-600 dark:text-amber-400 text-xs font-medium"
                                        >
                                            <AlertCircle
                                                class="w-4 h-4 shrink-0"
                                            />
                                            <span>
                                                Buka & baca slide hingga halaman
                                                terakhir untuk menyelesaikan.
                                            </span>
                                        </div>
                                        <span
                                            v-else
                                            class="text-xs text-slate-500"
                                        >
                                            Pastikan Anda telah menyimak materi
                                            ini sebelum menandai selesai.
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <button
                                            @click="markComplete(activeLesson)"
                                            :disabled="
                                                isSubmittingLesson ||
                                                !canCompleteActiveLesson
                                            "
                                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white shadow-md transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                                            :class="
                                                completedIds.includes(
                                                    activeLesson.id,
                                                )
                                                    ? 'bg-slate-700 hover:bg-slate-600'
                                                    : !canCompleteActiveLesson
                                                      ? 'bg-amber-600/70 hover:bg-amber-600/70'
                                                      : 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-600/25'
                                            "
                                        >
                                            <Loader2
                                                v-if="isSubmittingLesson"
                                                class="w-4 h-4 animate-spin"
                                            />
                                            <Lock
                                                v-else-if="
                                                    !canCompleteActiveLesson
                                                "
                                                class="w-4 h-4"
                                            />
                                            <CheckCircle2
                                                v-else
                                                class="w-4 h-4"
                                            />
                                            <span>{{
                                                completedIds.includes(
                                                    activeLesson.id,
                                                )
                                                    ? "Sudah Selesai (Lanjut Materi)"
                                                    : !canCompleteActiveLesson
                                                      ? "Baca Hingga Slide Terakhir Untuk Selesai"
                                                      : "Tandai Selesai & Lanjut"
                                            }}</span>
                                        </button>

                                        <button
                                            v-if="
                                                currentProgress >= 100 &&
                                                enrollment.status !==
                                                    'completed'
                                            "
                                            @click="submitCompleteCourse"
                                            :disabled="isAttending"
                                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-black text-white bg-emerald-600 hover:bg-emerald-500 shadow-lg shadow-emerald-600/30 transition-all animate-pulse cursor-pointer disabled:opacity-50"
                                        >
                                            <Loader2
                                                v-if="isAttending"
                                                class="w-4 h-4 animate-spin"
                                            />
                                            <Award v-else class="w-4 h-4" />
                                            <span>{{
                                                isAttending
                                                    ? "Memproses..."
                                                    : "Selesaikan Pembelajaran"
                                            }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div
                                v-else
                                class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-12 text-center text-slate-400"
                            >
                                Pilih salah satu elemen kompetensi di menu
                                sebelah kiri untuk memulai pembelajaran.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>
</template>
