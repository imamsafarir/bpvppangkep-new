<script setup>
import { ref, computed, watch, onMounted, onUnmounted, nextTick, markRaw } from "vue";
import {
    ChevronLeft,
    ChevronRight,
    Maximize2,
    Minimize2,
    ZoomIn,
    ZoomOut,
    CheckCircle2,
    AlertCircle,
    Loader2,
    Download,
    BookOpen,
    ExternalLink,
    Sparkles,
    RefreshCw,
    FileText,
    Volume2,
    VolumeX,
} from "lucide-vue-next";

const props = defineProps({
    pdfUrl: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: "Dokumen Materi",
    },
    alreadyCompleted: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(["page-change", "completed"]);

// DOM References
const viewerContainerRef = ref(null);
const canvasRef = ref(null);

// Viewer State
const viewMode = ref("slide"); // "slide" | "embed"
const isLoading = ref(true);
const loadingMessage = ref("Mempersiapkan Lembar Dokumen PDF...");
const isRendering = ref(false);
const errorMessage = ref("");
// PDF.js Document instance: MUST NOT be wrapped in Vue ref/reactive Proxy,
// otherwise PDF.js internal ES2022 private class fields (#d, #transport, etc.) throw:
// "TypeError: Cannot read private member #d from an object whose class did not declare it"
let pdfDocInstance = null;
const currentPage = ref(1);
const totalPages = ref(0);
const zoomLevel = ref(1.0);
const isFullscreen = ref(false);
const highestPageVisited = ref(1);
const hasCompleted = ref(props.alreadyCompleted);

// Book Fold & Flip Animation State
const isFlipEnabled = ref(true);
const isSoundEnabled = ref(true);
const isFlipping = ref(false);
const flipDirection = ref("next"); // "next" | "prev"
const turningLeafImage = ref(null);
const leafRotation = ref(0);
const foldShadowOpacity = ref(0);
const castShadowWidth = ref(0);
const canvasDisplayWidth = ref(800);
const canvasDisplayHeight = ref(450);

// Dynamic 3D Physical Book Stack Effect
const bookStackStyle = computed(() => {
    if (totalPages.value <= 1) {
        return {
            boxShadow:
                "0 15px 35px -10px rgba(0,0,0,0.5), 0 2px 6px rgba(0,0,0,0.2)",
        };
    }
    const rightRatio =
        (totalPages.value - currentPage.value) / Math.max(1, totalPages.value);
    const leftRatio =
        (currentPage.value - 1) / Math.max(1, totalPages.value);

    const rightThickness = Math.min(6, Math.max(1, Math.round(rightRatio * 6)));
    const leftThickness = Math.min(6, Math.max(0, Math.round(leftRatio * 6)));

    const shadows = [];
    shadows.push("0 20px 40px -12px rgba(0,0,0,0.6)");
    shadows.push("0 4px 8px rgba(0,0,0,0.25)");

    // Paper stack edge on right & bottom (pages ahead)
    for (let i = 1; i <= rightThickness; i++) {
        const color = i % 2 === 0 ? "#cbd5e1" : "#94a3b8";
        shadows.push(`${i}px ${i}px 0 ${color}`);
    }

    // Spine stack edge on left (pages already read)
    if (leftThickness > 0) {
        for (let i = 1; i <= leftThickness; i++) {
            const color = i % 2 === 0 ? "#94a3b8" : "#64748b";
            shadows.push(`-${i}px 0 0 ${color}`);
        }
    }

    return {
        boxShadow: shadows.join(", "),
    };
});

// Synthetic Paper Rustle Sound via Web Audio API (Zero External Assets)
const playPageFlipSound = () => {
    if (!isSoundEnabled.value) return;
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        if (ctx.state === "suspended") {
            ctx.resume();
        }

        const duration = 0.16;
        const bufferSize = Math.floor(ctx.sampleRate * duration);
        const buffer = ctx.createBuffer(1, bufferSize, ctx.sampleRate);
        const data = buffer.getChannelData(0);
        for (let i = 0; i < bufferSize; i++) {
            data[i] = (Math.random() * 2 - 1) * 0.35;
        }

        const noise = ctx.createBufferSource();
        noise.buffer = buffer;

        const filter = ctx.createBiquadFilter();
        filter.type = "bandpass";
        filter.frequency.setValueAtTime(1400, ctx.currentTime);
        filter.frequency.exponentialRampToValueAtTime(
            350,
            ctx.currentTime + duration,
        );
        filter.Q.value = 1.1;

        const gain = ctx.createGain();
        gain.gain.setValueAtTime(0.001, ctx.currentTime);
        gain.gain.linearRampToValueAtTime(0.12, ctx.currentTime + 0.02);
        gain.gain.exponentialRampToValueAtTime(
            0.0001,
            ctx.currentTime + duration,
        );

        noise.connect(filter);
        filter.connect(gain);
        gain.connect(ctx.destination);

        noise.start(ctx.currentTime);
        noise.stop(ctx.currentTime + duration + 0.01);
    } catch (_) {}
};

// Current page render task to cancel if user rapidly switches pages
let currentRenderTask = null;

// Progress Percentage based on furthest page reached
const readingProgress = computed(() => {
    if (totalPages.value <= 0) return 0;
    const progress = Math.round(
        (highestPageVisited.value / totalPages.value) * 100,
    );
    return Math.min(100, Math.max(0, progress));
});

// Helper: inject a script tag safely
const loadScript = (id, src) => {
    return new Promise((resolve, reject) => {
        const existing = document.getElementById(id);
        if (existing) {
            resolve();
            return;
        }
        const script = document.createElement("script");
        script.id = id;
        script.src = src;
        script.async = true;
        script.onload = () => resolve();
        script.onerror = (e) => reject(e);
        document.head.appendChild(script);
    });
};

// Load PDF.js library dynamically
const loadPdfJs = async () => {
    if (window.pdfjsLib && typeof window.pdfjsLib.getDocument === "function") {
        window.pdfjsLib.GlobalWorkerOptions.workerSrc =
            "/vendor/pdfjs/pdf.worker.min.js";
        return window.pdfjsLib;
    }

    try {
        // Only inject main library script tag.
        // Worker script MUST be loaded by PDF.js via Web Worker (not on window)
        await loadScript("pdfjs-lib-script", "/vendor/pdfjs/pdf.min.js");
    } catch (localErr) {
        console.warn("Gagal memuat PDF.js lokal, beralih ke CDN...", localErr);
        await loadScript(
            "pdfjs-cdn-lib",
            "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js",
        );
    }

    // Wait until window.pdfjsLib.getDocument is available (up to 4s)
    const startTime = Date.now();
    while (
        !window.pdfjsLib ||
        typeof window.pdfjsLib.getDocument !== "function"
    ) {
        if (Date.now() - startTime > 4000) {
            throw new Error("Library PDF.js gagal diinisialisasi pada browser.");
        }
        await new Promise((r) => setTimeout(r, 40));
    }

    window.pdfjsLib.GlobalWorkerOptions.workerSrc =
        "/vendor/pdfjs/pdf.worker.min.js";
    return window.pdfjsLib;
};

// Initialize & Load PDF Document
const initDocument = async () => {
    if (!props.pdfUrl) {
        errorMessage.value = "URL file PDF tidak valid atau belum diunggah.";
        isLoading.value = false;
        return;
    }

    isLoading.value = true;
    errorMessage.value = "";
    loadingMessage.value = "Memuat modul pembaca PDF...";

    try {
        const pdfjs = await loadPdfJs();
        loadingMessage.value = "Mengunduh berkas presentasi PDF...";

        let loadingTask;
        try {
            // First attempt: fetch file bytes directly (fast, resilient, avoids worker stream bugs)
            const response = await fetch(props.pdfUrl);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status} ${response.statusText}`);
            }
            const arrayBuffer = await response.arrayBuffer();
            loadingMessage.value = "Membaca lembar halaman materi...";
            loadingTask = pdfjs.getDocument({
                data: new Uint8Array(arrayBuffer),
            });
        } catch (fetchErr) {
            console.warn(
                "Direct fetch PDF gagal, mencoba via URL streaming...",
                fetchErr,
            );
            loadingTask = pdfjs.getDocument({
                url: props.pdfUrl,
                withCredentials: false,
            });
        }

        if (pdfDocInstance) {
            try {
                pdfDocInstance.destroy();
            } catch (_) {}
            pdfDocInstance = null;
        }

        const doc = await loadingTask.promise;
        pdfDocInstance = markRaw(doc);
        totalPages.value = doc.numPages;
        currentPage.value = 1;
        highestPageVisited.value = props.alreadyCompleted ? doc.numPages : 1;
        hasCompleted.value = props.alreadyCompleted;

        if (doc.numPages === 1 && !hasCompleted.value) {
            hasCompleted.value = true;
            emit("completed");
        }

        isLoading.value = false;
        await nextTick();
        await renderCurrentPage();
    } catch (err) {
        console.error("Gagal memuat dokumen PDF:", err);
        errorMessage.value =
            "Gagal memuat dokumen PDF: " +
            (err?.message || "Format file tidak dapat dibaca");
    } finally {
        isLoading.value = false;
    }
};

// Render specific page on canvas with high DPI sharpness
const renderCurrentPage = async () => {
    if (!pdfDocInstance || !canvasRef.value) return;

    if (currentRenderTask) {
        try {
            currentRenderTask.cancel();
        } catch (_) {}
        currentRenderTask = null;
    }

    isRendering.value = true;
    errorMessage.value = "";

    try {
        const page = await pdfDocInstance.getPage(currentPage.value);
        const canvas = canvasRef.value;
        if (!canvas) return;
        const context = canvas.getContext("2d");

        // Calculate available display width
        const container = viewerContainerRef.value;
        const rawWidth = container?.clientWidth || 800;
        const containerWidth = Math.max(
            300,
            rawWidth - (isFullscreen.value ? 48 : 32),
        );

        // Base unscaled viewport
        const unscaledViewport = page.getViewport({ scale: 1.0 });

        // Calculate fit-to-width base scale
        let fitScale = containerWidth / unscaledViewport.width;

        // In fullscreen mode, fit height as well
        if (isFullscreen.value && typeof window !== "undefined") {
            const availableHeight = window.innerHeight - 150;
            const heightScale = availableHeight / unscaledViewport.height;
            fitScale = Math.min(fitScale, heightScale);
        }

        const effectiveScale = Math.max(0.2, fitScale * zoomLevel.value);
        const outputScale = window.devicePixelRatio || 1;

        // Scale viewport directly by high-DPI factor for razor sharp graphics without matrix bugs
        const viewport = page.getViewport({
            scale: effectiveScale * outputScale,
        });

        canvas.width = Math.floor(viewport.width);
        canvas.height = Math.floor(viewport.height);
        canvasDisplayWidth.value = Math.floor(viewport.width / outputScale);
        canvasDisplayHeight.value = Math.floor(viewport.height / outputScale);
        canvas.style.width = canvasDisplayWidth.value + "px";
        canvas.style.height = canvasDisplayHeight.value + "px";

        // Clear canvas before painting
        context.clearRect(0, 0, canvas.width, canvas.height);

        const renderContext = {
            canvasContext: context,
            viewport: viewport,
        };

        currentRenderTask = page.render(renderContext);
        await currentRenderTask.promise;
    } catch (err) {
        if (err?.name !== "RenderingCancelledException") {
            console.error("Kesalahan saat merender halaman PDF:", err);
            errorMessage.value =
                "Gagal merender halaman: " + (err?.message || err);
        }
    } finally {
        isRendering.value = false;
        currentRenderTask = null;
    }
};

// Navigation: Next Page with 3D Book Page Fold
const nextPage = () => {
    if (currentPage.value >= totalPages.value) return;
    if (isFlipping.value) return;

    if (!isFlipEnabled.value || !canvasRef.value) {
        currentPage.value++;
        handlePageTransition();
        return;
    }

    try {
        const snap = canvasRef.value.toDataURL("image/jpeg", 0.92);
        turningLeafImage.value = snap;
        flipDirection.value = "next";
        isFlipping.value = true;
        leafRotation.value = 0;
        foldShadowOpacity.value = 0;
        castShadowWidth.value = 0;

        playPageFlipSound();

        // Advance page number and trigger background render of next page
        currentPage.value++;
        handlePageTransition();

        // Animate 3D folding leaf
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                leafRotation.value = -180;
                foldShadowOpacity.value = 1;
                castShadowWidth.value = 100;
            });
        });

        setTimeout(() => {
            isFlipping.value = false;
            leafRotation.value = 0;
            turningLeafImage.value = null;
            foldShadowOpacity.value = 0;
            castShadowWidth.value = 0;
        }, 580);
    } catch (e) {
        console.warn("Flip animation snapshot fallback:", e);
        currentPage.value++;
        handlePageTransition();
    }
};

// Navigation: Prev Page with 3D Book Page Fold
const prevPage = () => {
    if (currentPage.value <= 1) return;
    if (isFlipping.value) return;

    if (!isFlipEnabled.value || !canvasRef.value) {
        currentPage.value--;
        handlePageTransition();
        return;
    }

    try {
        const snap = canvasRef.value.toDataURL("image/jpeg", 0.92);
        turningLeafImage.value = snap;
        flipDirection.value = "prev";
        isFlipping.value = true;
        leafRotation.value = -180;
        foldShadowOpacity.value = 1;
        castShadowWidth.value = 100;

        playPageFlipSound();

        // Go to previous page and render on canvas underneath
        currentPage.value--;
        handlePageTransition();

        // Animate 3D folding leaf back
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                leafRotation.value = 0;
                foldShadowOpacity.value = 0;
                castShadowWidth.value = 0;
            });
        });

        setTimeout(() => {
            isFlipping.value = false;
            leafRotation.value = 0;
            turningLeafImage.value = null;
            foldShadowOpacity.value = 0;
            castShadowWidth.value = 0;
        }, 580);
    } catch (e) {
        console.warn("Flip animation snapshot fallback:", e);
        currentPage.value--;
        handlePageTransition();
    }
};

// Page transition side effects & completion detection
const handlePageTransition = () => {
    if (currentPage.value > highestPageVisited.value) {
        highestPageVisited.value = currentPage.value;
    }

    // Check if student reached the final page!
    if (
        (currentPage.value === totalPages.value ||
            highestPageVisited.value === totalPages.value) &&
        !hasCompleted.value
    ) {
        hasCompleted.value = true;
        emit("completed");
    }

    emit("page-change", {
        currentPage: currentPage.value,
        totalPages: totalPages.value,
        isCompleted: hasCompleted.value,
    });

    nextTick(() => {
        renderCurrentPage();
    });
};

// Zoom Controls
const zoomIn = () => {
    if (zoomLevel.value < 2.5) {
        zoomLevel.value = parseFloat((zoomLevel.value + 0.15).toFixed(2));
        renderCurrentPage();
    }
};

const zoomOut = () => {
    if (zoomLevel.value > 0.6) {
        zoomLevel.value = parseFloat((zoomLevel.value - 0.15).toFixed(2));
        renderCurrentPage();
    }
};

const resetZoom = () => {
    zoomLevel.value = 1.0;
    renderCurrentPage();
};

// Fullscreen API toggle
const toggleFullscreen = async () => {
    if (!viewerContainerRef.value) return;

    try {
        if (!document.fullscreenElement) {
            await viewerContainerRef.value.requestFullscreen();
            isFullscreen.value = true;
        } else {
            await document.exitFullscreen();
            isFullscreen.value = false;
        }
    } catch (err) {
        console.warn("Fullscreen request error:", err);
        isFullscreen.value = !isFullscreen.value;
    }
};

// Fullscreen change event listener
const onFullscreenChange = () => {
    isFullscreen.value = !!document.fullscreenElement;
    zoomLevel.value = 1.0;
    nextTick(() => {
        renderCurrentPage();
    });
};

// Keyboard Arrow Navigation
const handleKeyDown = (e) => {
    if (viewMode.value !== "slide") return;
    if (!isFullscreen.value && document.activeElement?.tagName === "INPUT") {
        return;
    }

    if (e.key === "ArrowRight" || e.key === "PageDown" || e.key === " ") {
        if (currentPage.value < totalPages.value) {
            e.preventDefault();
            nextPage();
        }
    } else if (e.key === "ArrowLeft" || e.key === "PageUp") {
        if (currentPage.value > 1) {
            e.preventDefault();
            prevPage();
        }
    } else if (e.key === "f" || e.key === "F") {
        if (!document.activeElement?.tagName?.match(/INPUT|TEXTAREA/)) {
            e.preventDefault();
            toggleFullscreen();
        }
    }
};

// Watchers
watch(
    () => props.pdfUrl,
    () => {
        initDocument();
    },
);

watch(
    () => props.alreadyCompleted,
    (newVal) => {
        if (newVal) {
            hasCompleted.value = true;
            if (totalPages.value > 0) {
                highestPageVisited.value = totalPages.value;
            }
        }
    },
);

onMounted(() => {
    initDocument();
    document.addEventListener("fullscreenchange", onFullscreenChange);
    window.addEventListener("keydown", handleKeyDown);
    window.addEventListener("resize", renderCurrentPage);
});

onUnmounted(() => {
    if (currentRenderTask) {
        try {
            currentRenderTask.cancel();
        } catch (_) {}
    }
    if (pdfDocInstance) {
        try {
            pdfDocInstance.destroy();
        } catch (_) {}
        pdfDocInstance = null;
    }
    document.removeEventListener("fullscreenchange", onFullscreenChange);
    window.removeEventListener("keydown", handleKeyDown);
    window.removeEventListener("resize", renderCurrentPage);
});
</script>

<template>
    <div
        ref="viewerContainerRef"
        class="pdf-book-viewer select-none transition-all flex flex-col rounded-2xl overflow-hidden border border-slate-200 bg-white shadow-xl relative"
        :class="{
            'fixed inset-0 z-50 rounded-none border-none w-screen h-screen':
                isFullscreen,
            'w-full min-h-[580px]': !isFullscreen,
        }"
    >
        <!-- Top Reading Progress Indicator Bar -->
        <div class="w-full bg-slate-100 h-1.5 relative overflow-hidden">
            <div
                class="h-full transition-all duration-300 ease-out"
                :class="
                    hasCompleted
                        ? 'bg-emerald-500'
                        : 'bg-gradient-to-r from-amber-500 to-indigo-500'
                "
                :style="{ width: `${readingProgress}%` }"
            ></div>
        </div>

        <!-- Header Controls Bar -->
        <header
            class="px-4 py-3 bg-white/95 backdrop-blur border-b border-slate-200 flex flex-wrap items-center justify-between text-slate-800 z-10 gap-3"
        >
            <div class="flex items-center gap-2.5 min-w-0">
                <span
                    class="p-1.5 rounded-lg bg-amber-50 text-amber-600 shrink-0 ring-1 ring-amber-200"
                >
                    <BookOpen class="w-4 h-4" />
                </span>
                <div class="min-w-0">
                    <h3 class="text-xs sm:text-sm font-bold truncate text-slate-900">
                        {{ title }}
                    </h3>
                    <p class="text-[11px] text-slate-500 flex items-center gap-1.5">
                        <span>{{ viewMode === 'slide' ? 'Mode Slide / Buku Digital' : 'Mode Dokumen Utuh' }}</span>
                        <span>&bull;</span>
                        <span v-if="totalPages > 0">
                            Slide {{ currentPage }} dari {{ totalPages }}
                        </span>
                        <span v-else>Memuat...</span>
                    </p>
                </div>
            </div>

            <!-- Header Right: Mode Switcher, Zoom & Fullscreen -->
            <div class="flex items-center gap-2 shrink-0">
                <!-- 3D Flip Animation Toggle -->
                <button
                    v-if="viewMode === 'slide'"
                    type="button"
                    @click="isFlipEnabled = !isFlipEnabled"
                    class="hidden sm:inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold border transition cursor-pointer"
                    :class="
                        isFlipEnabled
                            ? 'bg-amber-50 text-amber-700 border-amber-300'
                            : 'bg-slate-100 text-slate-600 border-slate-200 hover:text-slate-900 hover:bg-slate-200'
                    "
                    :title="
                        isFlipEnabled
                            ? 'Animasi Lipatan Buku 3D Aktif (Klik untuk Matikan)'
                            : 'Animasi Lipatan Buku 3D Nonaktif (Klik untuk Nyalakan)'
                    "
                >
                    <Sparkles class="w-3.5 h-3.5" :class="isFlipEnabled ? 'text-amber-600 animate-pulse' : 'text-slate-400'" />
                    <span class="hidden md:inline">{{ isFlipEnabled ? 'Lipatan Buku 3D' : 'Lipatan Mati' }}</span>
                </button>

                <!-- Sound Effect Toggle -->
                <button
                    v-if="viewMode === 'slide'"
                    type="button"
                    @click="isSoundEnabled = !isSoundEnabled"
                    class="p-1.5 rounded-lg border transition cursor-pointer"
                    :class="
                        isSoundEnabled
                            ? 'bg-indigo-50 text-indigo-700 border-indigo-200'
                            : 'bg-slate-100 text-slate-500 border-slate-200 hover:text-slate-800 hover:bg-slate-200'
                    "
                    :title="
                        isSoundEnabled
                            ? 'Suara Kertas Buku: Aktif (Klik untuk Senyap)'
                            : 'Suara Kertas Buku: Senyap (Klik untuk Aktifkan)'
                    "
                >
                    <Volume2 v-if="isSoundEnabled" class="w-4 h-4" />
                    <VolumeX v-else class="w-4 h-4 text-slate-400" />
                </button>

                <!-- Mode Switcher -->
                <button
                    type="button"
                    @click="viewMode = viewMode === 'slide' ? 'embed' : 'slide'"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition cursor-pointer"
                    :title="viewMode === 'slide' ? 'Beralih ke Tampilan Dokumen Utuh (Bawaan Browser)' : 'Beralih ke Mode Slide / Buku Digital'"
                >
                    <FileText v-if="viewMode === 'slide'" class="w-3.5 h-3.5 text-indigo-600" />
                    <BookOpen v-else class="w-3.5 h-3.5 text-amber-600" />
                    <span class="hidden sm:inline">{{ viewMode === 'slide' ? 'Dokumen Utuh' : 'Mode Slide' }}</span>
                </button>

                <!-- Open in New Tab Button -->
                <a
                    :href="pdfUrl"
                    target="_blank"
                    class="p-1.5 text-slate-500 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
                    title="Buka File Dokumen PDF di Tab Baru"
                >
                    <ExternalLink class="w-4 h-4" />
                </a>

                <!-- Zoom Controls (Slide Mode only) -->
                <div
                    v-if="viewMode === 'slide'"
                    class="hidden sm:flex items-center bg-slate-100 rounded-lg p-0.5 border border-slate-200"
                >
                    <button
                        type="button"
                        @click="zoomOut"
                        :disabled="zoomLevel <= 0.6 || isLoading"
                        class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-white rounded disabled:opacity-40 transition cursor-pointer"
                        title="Perkecil (-)"
                    >
                        <ZoomOut class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="resetZoom"
                        :disabled="isLoading"
                        class="px-2 py-1 text-[11px] font-mono font-semibold text-slate-700 hover:text-slate-900 cursor-pointer"
                        title="Reset Zoom (100%)"
                    >
                        {{ Math.round(zoomLevel * 100) }}%
                    </button>
                    <button
                        type="button"
                        @click="zoomIn"
                        :disabled="zoomLevel >= 2.5 || isLoading"
                        class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-white rounded disabled:opacity-40 transition cursor-pointer"
                        title="Perbesar (+)"
                    >
                        <ZoomIn class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Fullscreen Button -->
                <button
                    type="button"
                    @click="toggleFullscreen"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-indigo-600 hover:bg-indigo-500 text-white shadow-md shadow-indigo-600/30 transition cursor-pointer"
                    :title="
                        isFullscreen
                            ? 'Keluar Layar Penuh (Esc)'
                            : 'Layar Penuh / Full Screen (F)'
                    "
                >
                    <Minimize2 v-if="isFullscreen" class="w-4 h-4" />
                    <Maximize2 v-else class="w-4 h-4" />
                    <span class="hidden sm:inline">
                        {{ isFullscreen ? "Keluar Layar Penuh" : "Layar Penuh" }}
                    </span>
                </button>
            </div>
        </header>

        <!-- Viewer Center Body -->
        <main
            class="flex-1 bg-slate-100/80 flex flex-col items-center justify-center p-3 md:p-6 overflow-auto relative min-h-[460px]"
        >
            <!-- Loading Spinner Overlay -->
            <div
                v-if="isLoading"
                class="absolute inset-0 z-20 flex flex-col items-center justify-center bg-white/85 backdrop-blur-sm gap-3 text-slate-600 p-6 text-center"
            >
                <Loader2 class="w-8 h-8 text-amber-500 animate-spin" />
                <p class="text-xs font-medium tracking-wide">
                    {{ loadingMessage }}
                </p>
            </div>

            <!-- Error State with Fallback -->
            <div
                v-else-if="errorMessage && viewMode === 'slide'"
                class="p-6 max-w-md text-center space-y-4 bg-rose-50 border border-rose-200 rounded-2xl text-rose-800 z-10"
            >
                <AlertCircle class="w-8 h-8 mx-auto text-rose-500" />
                <h4 class="text-sm font-bold">Gagal Menampilkan Slide</h4>
                <p class="text-xs text-rose-700/80 leading-relaxed">
                    {{ errorMessage }}
                </p>
                <div class="flex flex-col sm:flex-row items-center justify-center gap-2 pt-2">
                    <button
                        type="button"
                        @click="initDocument"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 rounded-xl text-xs font-semibold transition cursor-pointer shadow-sm"
                    >
                        <RefreshCw class="w-3.5 h-3.5" />
                        <span>Coba Muat Ulang</span>
                    </button>
                    <button
                        type="button"
                        @click="viewMode = 'embed'"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl text-xs font-semibold transition cursor-pointer"
                    >
                        <FileText class="w-3.5 h-3.5" />
                        <span>Beralih ke Tampilan Dokumen Utuh</span>
                    </button>
                    <a
                        :href="pdfUrl"
                        target="_blank"
                        class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white rounded-xl text-xs font-bold transition shadow-lg shadow-rose-600/30"
                    >
                        <Download class="w-4 h-4" />
                        <span>Unduh PDF</span>
                    </a>
                </div>
            </div>

            <!-- MODE A: 3D SLIDE BOOK STAGE -->
            <div
                v-show="viewMode === 'slide' && !errorMessage"
                class="book-stage relative flex items-center justify-center my-auto w-full select-none"
            >
                <!-- Physical Book Wrapper with Dynamic Paper Stack Thickness -->
                <div
                    class="book-page-wrapper relative rounded-xl bg-white overflow-hidden ring-1 ring-black/20 mx-auto transition-all duration-300"
                    :style="[
                        bookStackStyle,
                        {
                            width: canvasDisplayWidth + 'px',
                            height: canvasDisplayHeight + 'px',
                        },
                    ]"
                >
                    <!-- Spine Binding Crease (Sisi Kiri Jilidan Buku) -->
                    <div class="book-spine-crease"></div>

                    <!-- Base Canvas Layer (Underneath Target Page) -->
                    <canvas ref="canvasRef" class="block w-full h-full object-contain mx-auto"></canvas>

                    <!-- Dynamic Cast Shadow on Base Canvas when Page is Turning -->
                    <div
                        v-if="isFlipping"
                        class="absolute inset-y-0 left-0 pointer-events-none transition-all duration-500 ease-out z-20"
                        :style="{
                            width: castShadowWidth + '%',
                            background:
                                'linear-gradient(to right, rgba(0,0,0,0.45) 0%, rgba(0,0,0,0.15) 35%, transparent 100%)',
                            opacity: foldShadowOpacity ? 0.75 : 0,
                        }"
                    ></div>

                    <!-- 3D TURNING LEAF / FLIPPING SHEET -->
                    <div
                        v-if="isFlipping && turningLeafImage"
                        class="turning-leaf absolute inset-0 z-30 pointer-events-none"
                        :style="{
                            transform: `rotateY(${leafRotation}deg) ${leafRotation !== 0 && leafRotation !== -180 ? 'skewY(-1deg)' : ''}`,
                            transition: 'transform 560ms cubic-bezier(0.25, 1, 0.45, 1)',
                            transformOrigin: 'left center',
                            transformStyle: 'preserve-3d',
                            willChange: 'transform',
                        }"
                    >
                        <!-- FRONT FACE OF TURNING LEAF (Turning away) -->
                        <div
                            class="leaf-front absolute inset-0 overflow-hidden rounded-r-xl bg-white"
                        >
                            <img
                                :src="turningLeafImage"
                                class="w-full h-full object-contain block select-none pointer-events-none"
                                alt="Halaman Balik"
                            />
                            <!-- Spine crease on turning sheet -->
                            <div class="book-spine-crease"></div>

                            <!-- Dynamic Page Fold & Curl Highlight/Shadow -->
                            <div
                                class="absolute inset-0 pointer-events-none transition-opacity duration-300"
                                :style="{
                                    background:
                                        'linear-gradient(to right, rgba(0,0,0,0.3) 0%, rgba(255,255,255,0.4) 25%, rgba(0,0,0,0.2) 65%, transparent 100%)',
                                    opacity: foldShadowOpacity ? 0.7 : 0,
                                }"
                            ></div>
                        </div>

                        <!-- BACK FACE OF TURNING LEAF (Visible when turned > 90deg) -->
                        <div
                            class="leaf-back absolute inset-0 overflow-hidden rounded-l-xl bg-slate-50"
                        >
                            <!-- Subtle translucent paper reverse bleed -->
                            <img
                                :src="turningLeafImage"
                                class="w-full h-full object-contain block opacity-15 scale-x-[-1] filter blur-[0.5px] select-none pointer-events-none"
                                alt="Belakang Halaman"
                            />
                            <!-- Paper texture gradient -->
                            <div
                                class="absolute inset-0"
                                style="background: linear-gradient(to left, rgba(0,0,0,0.25) 0%, rgba(255,255,255,0.4) 30%, rgba(0,0,0,0.06) 100%);"
                            ></div>
                            <!-- Spine shadow on back -->
                            <div
                                class="absolute inset-y-0 right-0 w-8 pointer-events-none"
                                style="background: linear-gradient(to left, rgba(0,0,0,0.35) 0%, transparent 100%);"
                            ></div>
                        </div>
                    </div>

                    <!-- Interactive Corner Curl Hint (Bottom Right Dog-Ear) -->
                    <div
                        v-if="currentPage < totalPages && !isFlipping && !isLoading"
                        @click.stop="nextPage"
                        class="group absolute bottom-0 right-0 w-12 h-12 z-20 cursor-pointer overflow-hidden transition-all duration-300 hover:w-16 hover:h-16"
                        title="Klik ujung lembaran untuk membalik halaman berikutnya"
                    >
                        <div
                            class="absolute bottom-0 right-0 w-0 h-0 border-b-[36px] border-l-[36px] border-b-amber-500/80 border-l-transparent drop-shadow-md transition-all group-hover:border-b-[48px] group-hover:border-l-[48px]"
                        ></div>
                        <div
                            class="absolute bottom-0 right-0 w-0 h-0 border-t-[34px] border-r-[34px] border-t-white/90 border-r-transparent drop-shadow-inner transition-all group-hover:border-t-[46px] group-hover:border-r-[46px]"
                        ></div>
                        <span
                            class="absolute bottom-1 right-1 text-[9px] font-bold text-amber-950 group-hover:scale-110 transition-transform"
                        >
                            &gt;
                        </span>
                    </div>

                    <!-- Left Margin Click Zone (Prev Page) -->
                    <div
                        v-if="currentPage > 1 && !isFlipping"
                        @click="prevPage"
                        class="absolute inset-y-0 left-0 w-1/5 z-10 cursor-w-resize group"
                        title="Klik sisi kiri untuk kembali ke halaman sebelumnya"
                    >
                        <div
                            class="opacity-0 group-hover:opacity-100 transition-opacity absolute left-3 top-1/2 -translate-y-1/2 p-2 rounded-full bg-black/40 text-white backdrop-blur-sm"
                        >
                            <ChevronLeft class="w-5 h-5" />
                        </div>
                    </div>

                    <!-- Right Margin Click Zone (Next Page) -->
                    <div
                        v-if="currentPage < totalPages && !isFlipping"
                        @click="nextPage"
                        class="absolute inset-y-0 right-0 w-1/5 z-10 cursor-e-resize group"
                        title="Klik sisi kanan untuk membalik ke halaman selanjutnya"
                    >
                        <div
                            class="opacity-0 group-hover:opacity-100 transition-opacity absolute right-3 top-1/2 -translate-y-1/2 p-2 rounded-full bg-black/40 text-white backdrop-blur-sm"
                        >
                            <ChevronRight class="w-5 h-5" />
                        </div>
                    </div>

                    <!-- Page Rendering Subtle Overlay -->
                    <div
                        v-if="isRendering && !isFlipping"
                        class="absolute inset-0 bg-slate-900/10 backdrop-blur-[1px] flex items-center justify-center rounded-xl transition-opacity pointer-events-none z-40"
                    >
                        <Loader2 class="w-6 h-6 text-indigo-600 animate-spin" />
                    </div>
                </div>
            </div>

            <!-- MODE B: EMBED / IFRAME BROWSER VIEWER -->
            <div
                v-if="viewMode === 'embed'"
                class="w-full h-full flex flex-col items-center justify-center flex-1"
            >
                <iframe
                    :src="pdfUrl + '#toolbar=1&navpanes=0'"
                    class="w-full h-[580px] rounded-xl border border-slate-200 bg-white shadow-xl"
                ></iframe>
                <div
                    class="w-full mt-3 p-3 bg-white border border-slate-200 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-2 text-xs"
                >
                    <span class="text-slate-600">
                        Mode Dokumen Utuh aktif. Anda dapat menggulir dan membaca seluruh isi materi PDF.
                    </span>
                    <button
                        v-if="!hasCompleted"
                        type="button"
                        @click="hasCompleted = true; emit('completed');"
                        class="px-4 py-2 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg font-bold shadow-md transition cursor-pointer"
                    >
                        Tandai Selesai Membaca Dokumen
                    </button>
                </div>
            </div>
        </main>

        <!-- Bottom Slide / Book Navigation Bar -->
        <footer
            class="px-4 py-3 bg-white/95 backdrop-blur border-t border-slate-200 flex flex-col sm:flex-row items-center justify-between gap-3 text-slate-800 z-10"
        >
            <!-- Left: Reading Requirement Notice -->
            <div class="flex items-center gap-2 text-xs">
                <div
                    v-if="hasCompleted"
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 font-bold text-[11px]"
                >
                    <CheckCircle2 class="w-3.5 h-3.5 shrink-0" />
                    <span>Tuntas Membaca Seluruh Slide (100%)</span>
                </div>
                <div
                    v-else
                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-[11px] font-medium"
                >
                    <Sparkles class="w-3.5 h-3.5 shrink-0 animate-pulse text-amber-600" />
                    <span>
                        Wajib membaca hingga slide terakhir ({{ totalPages || 1 }}) untuk
                        menyelesaikan materi ini.
                    </span>
                </div>
            </div>

            <!-- Center/Right: Book Flip Buttons (Prev - Indicator - Next) -->
            <div
                v-if="viewMode === 'slide'"
                class="flex items-center gap-2 shrink-0 w-full sm:w-auto justify-between sm:justify-end"
            >
                <!-- Previous Button -->
                <button
                    type="button"
                    @click="prevPage"
                    :disabled="currentPage <= 1 || isLoading"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200"
                    title="Slide Sebelumnya (Panah Kiri &larr;)"
                >
                    <ChevronLeft class="w-4 h-4" />
                    <span class="hidden sm:inline">Sebelumnya</span>
                </button>

                <!-- Page Dropdown / Direct Selector -->
                <div
                    class="flex items-center gap-1.5 bg-slate-100 border border-slate-200 px-3 py-1.5 rounded-xl text-xs font-bold font-mono"
                >
                    <span class="text-indigo-600">{{ currentPage }}</span>
                    <span class="text-slate-400">/</span>
                    <span class="text-slate-600">{{ totalPages || 1 }}</span>
                </div>

                <!-- Next Button -->
                <button
                    type="button"
                    @click="nextPage"
                    :disabled="currentPage >= totalPages || isLoading"
                    class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-bold transition shadow-sm cursor-pointer disabled:opacity-30 disabled:cursor-not-allowed bg-indigo-600 hover:bg-indigo-500 text-white shadow-indigo-600/25"
                    title="Slide Berikutnya (Panah Kanan &rarr;)"
                >
                    <span>Berikutnya</span>
                    <ChevronRight class="w-4 h-4" />
                </button>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.pdf-book-viewer :fullscreen {
    background-color: #f1f5f9;
}

/* 3D Book Stage & Physics */
.book-stage {
    perspective: 2400px;
    perspective-origin: 50% 50%;
}

.book-page-wrapper {
    position: relative;
    transform-style: preserve-3d;
    transition: box-shadow 0.4s ease;
}

/* Spine Crease (Lipatan Jilidan Buku di Sisi Kiri) */
.book-spine-crease {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 0;
    width: 28px;
    pointer-events: none;
    background: linear-gradient(
        to right,
        rgba(0, 0, 0, 0.42) 0%,
        rgba(0, 0, 0, 0.2) 25%,
        rgba(255, 255, 255, 0.12) 45%,
        rgba(0, 0, 0, 0.08) 60%,
        transparent 100%
    );
    box-shadow: inset 1px 0 0 rgba(255, 255, 255, 0.25);
    z-index: 15;
}

/* 3D Turning Leaf */
.turning-leaf {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    transform-origin: left center;
    transform-style: preserve-3d;
    pointer-events: none;
    z-index: 30;
    will-change: transform;
}

.leaf-front,
.leaf-back {
    position: absolute;
    inset: 0;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}

.leaf-back {
    transform: rotateY(180deg);
}
</style>

