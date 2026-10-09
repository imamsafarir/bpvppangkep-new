<script setup>
import { ref, onMounted, onBeforeUnmount, watch, nextTick } from "vue";
import * as pdfjsLib from "pdfjs-dist";

// Configure worker using local bundled or cdn worker matching version
pdfjsLib.GlobalWorkerOptions.workerSrc = new URL(
    "pdfjs-dist/build/pdf.worker.min.mjs",
    import.meta.url,
).toString();

const props = defineProps({
    url: {
        type: String,
        required: true,
    },
    title: {
        type: String,
        default: "Dokumen PDF",
    },
});

const containerRef = ref(null);
const pagesContainerRef = ref(null);
const isLoading = ref(true);
const errorMessage = ref("");
const totalPages = ref(0);
const isFullscreen = ref(false);

let pdfDoc = null;
let renderTaskQueue = [];
let resizeObserver = null;

const renderAllPages = async () => {
    if (!pdfDoc || !pagesContainerRef.value) return;

    // Clear previous canvases
    pagesContainerRef.value.innerHTML = "";
    const containerWidth = pagesContainerRef.value.clientWidth || 800;
    const pixelRatio = Math.min(window.devicePixelRatio || 1, 2);

    for (let pageNum = 1; pageNum <= pdfDoc.numPages; pageNum++) {
        try {
            const page = await pdfDoc.getPage(pageNum);
            const unscaledViewport = page.getViewport({ scale: 1.0 });

            // Calculate scale to fit container width exactly
            const scale = (containerWidth / unscaledViewport.width) * pixelRatio;
            const viewport = page.getViewport({ scale: scale });

            const pageWrapper = document.createElement("div");
            pageWrapper.className =
                "w-full flex justify-center mb-4 last:mb-0 bg-white shadow-xs rounded-xl overflow-hidden";

            const canvas = document.createElement("canvas");
            const context = canvas.getContext("2d");
            canvas.width = Math.floor(viewport.width);
            canvas.height = Math.floor(viewport.height);
            canvas.style.width = "100%";
            canvas.style.height = "auto";
            canvas.style.display = "block";

            pageWrapper.appendChild(canvas);
            pagesContainerRef.value.appendChild(pageWrapper);

            const renderContext = {
                canvasContext: context,
                viewport: viewport,
            };

            await page.render(renderContext).promise;
        } catch (err) {
            console.error(`Error rendering page ${pageNum}:`, err);
        }
    }
};

const loadPdf = async () => {
    if (!props.url) return;
    isLoading.value = true;
    errorMessage.value = "";

    try {
        const loadingTask = pdfjsLib.getDocument({
            url: props.url,
            cMapUrl: "https://unpkg.com/pdfjs-dist@4.10.38/cmaps/",
            cMapPacked: true,
        });

        pdfDoc = await loadingTask.promise;
        totalPages.value = pdfDoc.numPages;
        await nextTick();
        await renderAllPages();
    } catch (err) {
        console.error("PDF loading error:", err);
        errorMessage.value =
            "Gagal merender dokumen langsung. Silakan gunakan tombol unduh / buka tab baru.";
    } finally {
        isLoading.value = false;
    }
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
    nextTick(() => {
        renderAllPages();
    });
};

// Handle window resizing to keep responsive full width
let resizeTimeout = null;
const handleResize = () => {
    clearTimeout(resizeTimeout);
    resizeTimeout = setTimeout(() => {
        renderAllPages();
    }, 200);
};

watch(
    () => props.url,
    () => {
        loadPdf();
    },
);

onMounted(() => {
    loadPdf();
    window.addEventListener("resize", handleResize);
});

onBeforeUnmount(() => {
    window.removeEventListener("resize", handleResize);
});
</script>

<template>
    <div
        ref="containerRef"
        class="w-full bg-slate-100/70 rounded-2xl overflow-hidden border border-slate-200/80 transition-all duration-300 flex flex-col"
        :class="[
            isFullscreen
                ? 'fixed inset-0 z-50 bg-slate-900/95 overflow-y-auto p-4 sm:p-8 rounded-none'
                : 'relative',
        ]"
    >
        <!-- Fullscreen Top Bar -->
        <div
            v-if="isFullscreen"
            class="sticky top-0 z-20 flex items-center justify-between px-4 py-3 bg-slate-900/90 backdrop-blur-md rounded-2xl border border-white/10 text-white mb-6 shadow-xl"
        >
            <div class="flex items-center gap-2.5 truncate mr-4">
                <i class="fas fa-file-pdf text-red-400"></i>
                <span class="text-xs sm:text-sm font-bold truncate">{{ title }}</span>
                <span
                    v-if="totalPages > 0"
                    class="text-[11px] px-2 py-0.5 rounded-full bg-white/10 text-slate-300 shrink-0 font-medium"
                >
                    {{ totalPages }} Halaman
                </span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a
                    :href="url"
                    download
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                >
                    <i class="fas fa-download text-[10px]"></i>
                    <span class="hidden sm:inline">Unduh</span>
                </a>
                <button
                    type="button"
                    @click="toggleFullscreen"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white/15 hover:bg-white/25 text-white rounded-xl text-xs font-bold transition-all"
                >
                    <i class="fas fa-compress text-xs"></i>
                    <span>Tutup Layar Penuh</span>
                </button>
            </div>
        </div>

        <!-- Inline Floating Controls (Non-Fullscreen) -->
        <div
            v-if="!isFullscreen && !isLoading && !errorMessage"
            class="absolute top-4 right-4 z-10 flex items-center gap-2"
        >
            <button
                type="button"
                @click="toggleFullscreen"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-900/80 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold backdrop-blur-xs shadow-md transition-all cursor-pointer"
                title="Tampilan Layar Penuh"
            >
                <i class="fas fa-expand text-[10px]"></i>
                <span>Layar Penuh</span>
            </button>
        </div>

        <!-- Loading State -->
        <div
            v-if="isLoading"
            class="py-24 px-6 flex flex-col items-center justify-center text-center space-y-3"
        >
            <div
                class="w-10 h-10 border-3 border-blue-600 border-t-transparent rounded-full animate-spin"
            ></div>
            <p class="text-xs font-medium text-slate-500">
                Memuat dan merender dokumen resmi...
            </p>
        </div>

        <!-- Error State Fallback -->
        <div
            v-else-if="errorMessage"
            class="p-8 text-center space-y-3 bg-red-50/50 rounded-2xl m-4 border border-red-100"
        >
            <i class="fas fa-exclamation-circle text-2xl text-red-500"></i>
            <p class="text-xs text-red-700 font-medium">{{ errorMessage }}</p>
            <a
                :href="url"
                target="_blank"
                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-xl text-xs font-bold hover:bg-blue-700 transition-all"
            >
                <i class="fas fa-external-link-alt text-xs"></i>
                Buka Dokumen di Tab Baru
            </a>
        </div>

        <!-- Rendered Pages Container -->
        <div
            v-show="!isLoading && !errorMessage"
            ref="pagesContainerRef"
            class="w-full transition-all"
            :class="[
                isFullscreen ? 'max-w-5xl mx-auto' : 'p-2 sm:p-4',
            ]"
        ></div>
    </div>
</template>

