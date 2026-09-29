<script setup>
import { ref, onMounted } from "vue";

const isOpen = ref(false);
const textSize = ref("normal");
const highContrast = ref(false);
const dyslexiaFont = ref(false);
const underlineLinks = ref(false);
const colorMode = ref("normal");
const cbMode = ref("normal");
const bigCursor = ref(false);
const readingGuide = ref(false);
const screenReader = ref(false);
const guideY = ref(0);

const onMouseMove = (e) => {
    if (readingGuide.value) {
        guideY.value = e.clientY;
    }
};

const applySettings = () => {
    const root = document.documentElement;

    root.classList.remove("ax-text-large", "ax-text-xlarge");
    if (textSize.value === "large") root.classList.add("ax-text-large");
    if (textSize.value === "xlarge") root.classList.add("ax-text-xlarge");

    root.classList.toggle("ax-high-contrast", highContrast.value);
    root.classList.toggle("ax-dyslexia-font", dyslexiaFont.value);
    root.classList.toggle("ax-force-underline", underlineLinks.value);
    root.classList.toggle("ax-big-cursor", bigCursor.value);

    root.classList.remove("ax-grayscale", "ax-sepia", "ax-invert");
    if (colorMode.value !== "normal") {
        root.classList.add(`ax-${colorMode.value}`);
    }

    root.classList.remove("ax-protanopia", "ax-deuteranopia", "ax-tritanopia");
    if (cbMode.value !== "normal") {
        root.classList.add(`ax-${cbMode.value}`);
    }

    localStorage.setItem("ax-text-size", textSize.value);
    localStorage.setItem("ax-contrast", highContrast.value);
    localStorage.setItem("ax-dyslexia", dyslexiaFont.value);
    localStorage.setItem("ax-underline", underlineLinks.value);
    localStorage.setItem("ax-color-mode", colorMode.value);
    localStorage.setItem("ax-cb-mode", cbMode.value);
    localStorage.setItem("ax-cursor", bigCursor.value);
    localStorage.setItem("ax-guide", readingGuide.value);
    localStorage.setItem("ax-reader", screenReader.value);
};

const toggleTextSize = () => {
    if (textSize.value === "normal") textSize.value = "large";
    else if (textSize.value === "large") textSize.value = "xlarge";
    else textSize.value = "normal";
    applySettings();
};

const toggleContrast = () => {
    highContrast.value = !highContrast.value;
    applySettings();
};

const toggleDyslexia = () => {
    dyslexiaFont.value = !dyslexiaFont.value;
    applySettings();
};

const toggleUnderline = () => {
    underlineLinks.value = !underlineLinks.value;
    applySettings();
};

const setColorMode = (mode) => {
    colorMode.value = colorMode.value === mode ? "normal" : mode;
    applySettings();
};

const setCbMode = (mode) => {
    cbMode.value = cbMode.value === mode ? "normal" : mode;
    applySettings();
};

const toggleCursor = () => {
    bigCursor.value = !bigCursor.value;
    applySettings();
};

const toggleGuide = () => {
    readingGuide.value = !readingGuide.value;
    applySettings();
};

const speak = (text) => {
    if (!("speechSynthesis" in window)) return;
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = "id-ID";
    window.speechSynthesis.speak(utterance);
};

const toggleScreenReader = () => {
    screenReader.value = !screenReader.value;
    applySettings();
    if (screenReader.value) {
        speak("Fitur pembaca layar diaktifkan.");
    }
};

const resetAll = () => {
    textSize.value = "normal";
    highContrast.value = false;
    dyslexiaFont.value = false;
    underlineLinks.value = false;
    colorMode.value = "normal";
    cbMode.value = "normal";
    bigCursor.value = false;
    readingGuide.value = false;
    screenReader.value = false;
    applySettings();
    if ("speechSynthesis" in window) window.speechSynthesis.cancel();
};

onMounted(() => {
    textSize.value = localStorage.getItem("ax-text-size") || "normal";
    highContrast.value = localStorage.getItem("ax-contrast") === "true";
    dyslexiaFont.value = localStorage.getItem("ax-dyslexia") === "true";
    underlineLinks.value = localStorage.getItem("ax-underline") === "true";
    colorMode.value = localStorage.getItem("ax-color-mode") || "normal";
    cbMode.value = localStorage.getItem("ax-cb-mode") || "normal";
    bigCursor.value = localStorage.getItem("ax-cursor") === "true";
    readingGuide.value = localStorage.getItem("ax-guide") === "true";
    screenReader.value = localStorage.getItem("ax-reader") === "true";
    applySettings();
    window.addEventListener("mousemove", onMouseMove);
});
</script>

<template>
    <!-- SVG Filters -->
    <svg
        id="ax-cb-filters"
        style="position: absolute; height: 0; width: 0; overflow: hidden"
        version="1.1"
        xmlns="http://www.w3.org/2000/svg"
    >
        <defs>
            <filter id="ax-filter-protanopia">
                <feColorMatrix
                    type="matrix"
                    values="0.567, 0.433, 0, 0, 0, 0.558, 0.442, 0, 0, 0, 0, 0.242, 0.758, 0, 0, 0, 0, 0, 1, 0"
                />
            </filter>
            <filter id="ax-filter-deuteranopia">
                <feColorMatrix
                    type="matrix"
                    values="0.625, 0.375, 0, 0, 0, 0.7, 0.3, 0, 0, 0, 0, 0.3, 0.7, 0, 0, 0, 0, 0, 1, 0"
                />
            </filter>
            <filter id="ax-filter-tritanopia">
                <feColorMatrix
                    type="matrix"
                    values="0.95, 0.05, 0, 0, 0, 0, 0.433, 0.567, 0, 0, 0, 0.475, 0.525, 0, 0, 0, 0, 0, 1, 0"
                />
            </filter>
        </defs>
    </svg>

    <!-- Reading Guide Line -->
    <div
        v-if="readingGuide"
        id="ax-reading-line"
        class="fixed left-0 right-0 h-1.5 bg-yellow-400 pointer-events-none z-[99999] shadow-md transition-all duration-75"
        :style="{ top: `${guideY}px` }"
    ></div>

    <!-- Toggle Button (Floating) -->
    <div class="fixed bottom-6 left-6 z-[9999] select-none">
        <button
            @click="isOpen = !isOpen"
            type="button"
            class="w-12 h-12 bg-blue-600 hover:bg-blue-700 text-white rounded-full flex items-center justify-center shadow-xl hover:shadow-blue-500/30 transition-all cursor-pointer focus:outline-none"
            title="Menu Aksesibilitas"
            aria-label="Menu Aksesibilitas"
        >
            <i class="fas fa-universal-access text-xl"></i>
        </button>

        <!-- Popup Panel -->
        <div
            v-if="isOpen"
            class="absolute bottom-16 left-0 w-80 sm:w-96 bg-white rounded-2xl shadow-2xl border border-slate-200 p-5 text-slate-800 z-[9999] max-h-[80vh] overflow-y-auto"
        >
            <div
                class="flex items-center justify-between pb-3 border-b border-slate-100"
            >
                <div class="flex items-center gap-2">
                    <i class="fas fa-universal-access text-blue-600"></i>
                    <h3 class="font-bold text-sm">Aksesibilitas Web</h3>
                </div>
                <button
                    @click="isOpen = false"
                    class="text-slate-400 hover:text-slate-600 cursor-pointer"
                >
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-2 mt-4 text-xs font-medium">
                <!-- Ukuran Teks -->
                <button
                    @click="toggleTextSize"
                    :class="
                        textSize !== 'normal'
                            ? 'bg-blue-50 border-blue-300 text-blue-700'
                            : 'bg-slate-50 border-slate-200 text-slate-700'
                    "
                    class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 hover:bg-blue-50 transition cursor-pointer"
                >
                    <i class="fas fa-text-height text-base"></i>
                    <span>Ukuran Teks: {{ textSize }}</span>
                </button>

                <!-- Kontras Tinggi -->
                <button
                    @click="toggleContrast"
                    :class="
                        highContrast
                            ? 'bg-blue-50 border-blue-300 text-blue-700'
                            : 'bg-slate-50 border-slate-200 text-slate-700'
                    "
                    class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 hover:bg-blue-50 transition cursor-pointer"
                >
                    <i class="fas fa-adjust text-base"></i>
                    <span>Kontras Tinggi</span>
                </button>

                <!-- Font Disleksia -->
                <button
                    @click="toggleDyslexia"
                    :class="
                        dyslexiaFont
                            ? 'bg-blue-50 border-blue-300 text-blue-700'
                            : 'bg-slate-50 border-slate-200 text-slate-700'
                    "
                    class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 hover:bg-blue-50 transition cursor-pointer"
                >
                    <i class="fas fa-font text-base"></i>
                    <span>Font Disleksia</span>
                </button>

                <!-- Garis Bawah Link -->
                <button
                    @click="toggleUnderline"
                    :class="
                        underlineLinks
                            ? 'bg-blue-50 border-blue-300 text-blue-700'
                            : 'bg-slate-50 border-slate-200 text-slate-700'
                    "
                    class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 hover:bg-blue-50 transition cursor-pointer"
                >
                    <i class="fas fa-underline text-base"></i>
                    <span>Garis Bawah Link</span>
                </button>

                <!-- Kursor Besar -->
                <button
                    @click="toggleCursor"
                    :class="
                        bigCursor
                            ? 'bg-blue-50 border-blue-300 text-blue-700'
                            : 'bg-slate-50 border-slate-200 text-slate-700'
                    "
                    class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 hover:bg-blue-50 transition cursor-pointer"
                >
                    <i class="fas fa-mouse-pointer text-base"></i>
                    <span>Kursor Besar</span>
                </button>

                <!-- Panduan Baca -->
                <button
                    @click="toggleGuide"
                    :class="
                        readingGuide
                            ? 'bg-blue-50 border-blue-300 text-blue-700'
                            : 'bg-slate-50 border-slate-200 text-slate-700'
                    "
                    class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 hover:bg-blue-50 transition cursor-pointer"
                >
                    <i class="fas fa-ruler-horizontal text-base"></i>
                    <span>Garis Pandu Baca</span>
                </button>

                <!-- Pembaca Suara -->
                <button
                    @click="toggleScreenReader"
                    :class="
                        screenReader
                            ? 'bg-blue-50 border-blue-300 text-blue-700'
                            : 'bg-slate-50 border-slate-200 text-slate-700'
                    "
                    class="p-2.5 rounded-xl border flex flex-col items-center gap-1.5 hover:bg-blue-50 transition cursor-pointer col-span-2"
                >
                    <i class="fas fa-volume-up text-base"></i>
                    <span>Pembaca Suara (TTS)</span>
                </button>
            </div>

            <!-- Mode Warna / Filter -->
            <div class="mt-3 pt-3 border-t border-slate-100">
                <span
                    class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block mb-1.5"
                    >Filter Warna</span
                >
                <div class="flex gap-1.5">
                    <button
                        v-for="m in ['grayscale', 'sepia', 'invert']"
                        :key="m"
                        @click="setColorMode(m)"
                        :class="
                            colorMode === m
                                ? 'bg-blue-600 text-white'
                                : 'bg-slate-100 text-slate-600 hover:bg-slate-200'
                        "
                        class="px-2.5 py-1 rounded-lg text-[11px] font-semibold capitalize cursor-pointer transition flex-1 text-center"
                    >
                        {{ m }}
                    </button>
                </div>
            </div>

            <!-- Reset Button -->
            <div class="mt-4 pt-3 border-t border-slate-100">
                <button
                    @click="resetAll"
                    class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl font-bold text-xs flex items-center justify-center gap-1.5 transition cursor-pointer"
                >
                    <i class="fas fa-rotate-left"></i>
                    <span>Reset Pengaturan</span>
                </button>
            </div>
        </div>
    </div>
</template>
