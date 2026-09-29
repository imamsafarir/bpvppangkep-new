<script setup>
import { ref, computed, onMounted, onUnmounted } from "vue";

const isOpen = ref(false);
const activeTab = ref("vision"); // 'vision' | 'text' | 'tools'

// State variables
const textSize = ref("normal"); // 'normal' | 'large' | 'xlarge'
const textSpacing = ref(false);
const dyslexiaFont = ref(false);
const underlineLinks = ref(false);
const highContrast = ref(false);
const cbMode = ref("normal"); // 'normal' | 'protanopia' | 'deuteranopia' | 'tritanopia' | 'monochrome' | 'invert' | 'sepia'
const bigCursor = ref(false);
const stopAnimations = ref(false);
const readingGuide = ref(false);
const screenReader = ref(false);

const guideY = ref(0);
const isSpeaking = ref(false);
let activeSpeakingEl = null;

// Speech synthesis settings
const speechRate = ref(1.0);
const availableVoices = ref([]);
const indonesianVoices = ref([]);
const selectedVoiceUri = ref("");

// Total active features counter
const activeCount = computed(() => {
    let count = 0;
    if (textSize.value !== "normal") count++;
    if (textSpacing.value) count++;
    if (dyslexiaFont.value) count++;
    if (underlineLinks.value) count++;
    if (highContrast.value) count++;
    if (cbMode.value !== "normal") count++;
    if (bigCursor.value) count++;
    if (stopAnimations.value) count++;
    if (readingGuide.value) count++;
    if (screenReader.value) count++;
    return count;
});

// Reading guide mouse follower
const onMouseMove = (e) => {
    if (readingGuide.value) {
        guideY.value = e.clientY;
    }
};

// Apply all classes to document.documentElement
const applySettings = () => {
    if (typeof document === "undefined") return;
    const root = document.documentElement;

    // 1. Text Sizing
    root.classList.remove("ax-text-large", "ax-text-xlarge");
    if (textSize.value === "large") root.classList.add("ax-text-large");
    if (textSize.value === "xlarge") root.classList.add("ax-text-xlarge");

    // 2. Text Spacing
    root.classList.toggle("ax-text-spacing", textSpacing.value);

    // 3. Dyslexia Font
    root.classList.toggle("ax-dyslexia-font", dyslexiaFont.value);

    // 4. Underline Links
    root.classList.toggle("ax-force-underline", underlineLinks.value);

    // 5. Big Cursor
    root.classList.toggle("ax-big-cursor", bigCursor.value);

    // 6. Stop Animations
    root.classList.toggle("ax-stop-animations", stopAnimations.value);

    // 7. High Contrast
    root.classList.toggle("ax-high-contrast", highContrast.value);

    // 8. Screen Reader Mode
    root.classList.toggle("ax-screen-reader-mode", screenReader.value);

    // 9. Color blindness & filters
    root.classList.remove(
        "ax-protanopia",
        "ax-deuteranopia",
        "ax-tritanopia",
        "ax-monochrome",
        "ax-invert",
        "ax-sepia",
    );
    if (cbMode.value !== "normal") {
        root.classList.add(`ax-${cbMode.value}`);
    }

    // Save state to localStorage
    try {
        localStorage.setItem("ax-text-size", textSize.value);
        localStorage.setItem("ax-text-spacing", String(textSpacing.value));
        localStorage.setItem("ax-dyslexia", String(dyslexiaFont.value));
        localStorage.setItem("ax-underline", String(underlineLinks.value));
        localStorage.setItem("ax-contrast", String(highContrast.value));
        localStorage.setItem("ax-cb-mode", cbMode.value);
        localStorage.setItem("ax-cursor", String(bigCursor.value));
        localStorage.setItem(
            "ax-stop-animations",
            String(stopAnimations.value),
        );
        localStorage.setItem("ax-guide", String(readingGuide.value));
        localStorage.setItem("ax-reader", String(screenReader.value));
        localStorage.setItem("ax-tts-rate", String(speechRate.value));
        if (selectedVoiceUri.value) {
            localStorage.setItem("ax-tts-voice-uri", selectedVoiceUri.value);
        }
    } catch {
        // ignore localStorage quota errors
    }
};

// Handlers
const setTextSizeDirect = (size) => {
    textSize.value = size;
    applySettings();
};

const toggleTextSpacing = () => {
    textSpacing.value = !textSpacing.value;
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

const toggleContrast = () => {
    highContrast.value = !highContrast.value;
    if (highContrast.value && cbMode.value === "invert") {
        cbMode.value = "normal";
    }
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

const toggleAnimations = () => {
    stopAnimations.value = !stopAnimations.value;
    applySettings();
};

const toggleGuide = () => {
    readingGuide.value = !readingGuide.value;
    applySettings();
};

// Update available voices strictly
const updateVoices = () => {
    if (typeof window === "undefined" || !("speechSynthesis" in window)) return;
    const voices = window.speechSynthesis.getVoices() || [];
    if (voices.length === 0) return;
    availableVoices.value = voices;

    // Filter Indonesian voices strictly (DO NOT use includes('in') as that matches en-IN, fi-IN, etc.)
    const indo = voices.filter((v) => {
        const lang = (v.lang || "").toLowerCase().replace("_", "-");
        const name = (v.name || "").toLowerCase();

        const isIndoLang = lang === "id" || lang.startsWith("id-");
        const isIndoName =
            name.includes("indonesia") ||
            name.includes("bahasa indonesia") ||
            name.includes("andika") ||
            name.includes("gadis") ||
            name.includes("ardi") ||
            name.includes("damayanti") ||
            name.includes("lestari");

        return isIndoLang || isIndoName;
    });

    indonesianVoices.value = indo;

    const savedVoiceUri = localStorage.getItem("ax-tts-voice-uri");
    if (savedVoiceUri && voices.some((v) => v.voiceURI === savedVoiceUri)) {
        selectedVoiceUri.value = savedVoiceUri;
    } else if (indo.length > 0) {
        // Prioritize natural / online voices
        const naturalVoice = indo.find(
            (v) =>
                v.name.toLowerCase().includes("natural") ||
                v.name.toLowerCase().includes("online") ||
                v.name.toLowerCase().includes("google"),
        );
        selectedVoiceUri.value = (naturalVoice || indo[0]).voiceURI;
    } else {
        const defaultVoice = voices.find((v) => v.default) || voices[0];
        selectedVoiceUri.value = defaultVoice ? defaultVoice.voiceURI : "";
    }
};

// Text sanitizer to prevent robotic spelling
const sanitizeTextForSpeech = (rawText) => {
    if (!rawText) return "";

    // Replace multiple spaces & newlines with single space
    let text = rawText
        .replace(/[\r\n\t]+/g, " ")
        .replace(/\s+/g, " ")
        .trim();

    // Remove URLs, icons, symbols, bullet characters, emojis
    text = text.replace(/https?:\/\/\S+/gi, "tautan web");
    text = text.replace(/[•●★☆►▼▲◀→←↑↓✓✔✕✖…|/\\_#~`^*]/g, " ");

    // Handle Indonesian acronyms and uppercase words:
    // If words are in ALL-CAPS (e.g. "BERITA TERBARU", "PELATIHAN"),
    // TTS engines will spell them letter-by-letter (B-E-R-I-T-A).
    // Convert them to natural titlecase/sentence case.
    const acronymMap = {
        BPVP: "B P V P",
        BLK: "B L K",
        BKN: "B K N",
        KTP: "K T P",
        SIM: "S I M",
        RI: "Republik Indonesia",
        UPTD: "U P T D",
        UPT: "U P T",
        APBN: "A P B N",
        APBD: "A P B D",
        PWA: "P W A",
        ISO: "I S O",
        IT: "I T",
        SMK: "S M K",
        SMA: "S M A",
        SMP: "S M P",
        SD: "S D",
        TNI: "T N I",
        POLRI: "Polri",
        ASN: "A S N",
        PNS: "P N S",
        PPPK: "P P P K",
        SP4N: "S P 4 N",
        LAPOR: "Lapor",
        KEMNAKER: "Kemnaker",
        BNSP: "B N S P",
        PPID: "P P I D",
        FAQ: "Tanya Jawab",
        SIP: "S I P",
        SIAPKERJA: "Siap Kerja",
        SKKNI: "S K K N I",
    };

    const words = text.split(/\s+/);
    const normalizedWords = words.map((word) => {
        const match = word.match(/^([^a-zA-Z0-9]*)(.*?)([^a-zA-Z0-9]*)$/);
        if (!match) return word;
        const prefix = match[1] || "";
        const core = match[2] || "";
        const suffix = match[3] || "";

        const upper = core.toUpperCase();

        if (acronymMap[upper]) {
            return `${prefix}${acronymMap[upper]}${suffix}`;
        }

        // If word is entirely UPPERCASE and longer than 1 character (e.g. "BERITA", "PELATIHAN", "PANGKEP")
        if (core.length > 1 && core === upper && !/^[0-9]+$/.test(core)) {
            const converted =
                core.charAt(0).toUpperCase() + core.slice(1).toLowerCase();
            return `${prefix}${converted}${suffix}`;
        }

        return word;
    });

    text = normalizedWords.join(" ");

    // Ensure sentences end with proper punctuation for natural cadence
    text = text.replace(/\s+/g, " ").trim();
    if (!/[.!?]$/.test(text)) {
        text += ".";
    }

    return text;
};

// TTS Speech Synthesis Engine
const cancelSpeech = () => {
    if (typeof window !== "undefined" && "speechSynthesis" in window) {
        window.speechSynthesis.cancel();
    }
    if (activeSpeakingEl) {
        activeSpeakingEl.classList.remove("ax-tts-speaking");
        activeSpeakingEl = null;
    }
    isSpeaking.value = false;
};

const speakText = (text, targetEl = null) => {
    if (typeof window === "undefined" || !("speechSynthesis" in window)) return;
    if (!text || !text.trim()) return;

    cancelSpeech();

    const cleanText = sanitizeTextForSpeech(text);
    if (!cleanText) return;

    if (targetEl && targetEl.classList) {
        activeSpeakingEl = targetEl;
        activeSpeakingEl.classList.add("ax-tts-speaking");
    }

    const utterance = new SpeechSynthesisUtterance(cleanText);
    utterance.lang = "id-ID";
    utterance.rate = Number(speechRate.value) || 1.0;
    utterance.pitch = 1.0;

    // Pick active voice
    if (selectedVoiceUri.value && availableVoices.value.length > 0) {
        const v = availableVoices.value.find(
            (voice) => voice.voiceURI === selectedVoiceUri.value,
        );
        if (v) utterance.voice = v;
    } else if (indonesianVoices.value.length > 0) {
        utterance.voice = indonesianVoices.value[0];
    }

    utterance.onstart = () => {
        isSpeaking.value = true;
    };
    utterance.onend = () => {
        cancelSpeech();
    };
    utterance.onerror = () => {
        cancelSpeech();
    };

    window.speechSynthesis.speak(utterance);
};

const testSpeech = () => {
    speakText(
        "Halo, ini adalah contoh suara pembaca layar bahasa Indonesia BPVP Pangkep. Fitur siap digunakan.",
    );
};

const toggleScreenReader = () => {
    screenReader.value = !screenReader.value;
    applySettings();
    if (screenReader.value) {
        speakText(
            "Pembaca layar diaktifkan. Klik pada teks, judul, atau tombol mana saja untuk mendengarkan bacaan suara.",
        );
    } else {
        cancelSpeech();
    }
};

// Document click listener for screen reader
const onDocumentClick = (e) => {
    if (!screenReader.value) return;

    // Ignore clicks inside the accessibility widget or TTS bar
    const widget = document.getElementById("ax-widget-container");
    const ttsBar = document.getElementById("ax-tts-bar");
    if (widget && widget.contains(e.target)) return;
    if (ttsBar && ttsBar.contains(e.target)) return;

    // Find closest readable element
    const readableEl =
        e.target.closest(
            "p, h1, h2, h3, h4, h5, h6, a, button, li, label, blockquote, td, th, dt, dd",
        ) || e.target;

    const textToSpeak =
        readableEl.getAttribute?.("aria-label") ||
        readableEl.getAttribute?.("alt") ||
        readableEl.getAttribute?.("title") ||
        readableEl.innerText ||
        readableEl.textContent;

    if (textToSpeak && textToSpeak.trim().length > 0) {
        speakText(textToSpeak, readableEl);
    }
};

// Reset all settings
const resetAll = () => {
    textSize.value = "normal";
    textSpacing.value = false;
    dyslexiaFont.value = false;
    underlineLinks.value = false;
    highContrast.value = false;
    cbMode.value = "normal";
    bigCursor.value = false;
    stopAnimations.value = false;
    readingGuide.value = false;
    screenReader.value = false;
    speechRate.value = 1.0;
    cancelSpeech();
    applySettings();
};

onMounted(() => {
    try {
        textSize.value = localStorage.getItem("ax-text-size") || "normal";
        textSpacing.value = localStorage.getItem("ax-text-spacing") === "true";
        dyslexiaFont.value = localStorage.getItem("ax-dyslexia") === "true";
        underlineLinks.value = localStorage.getItem("ax-underline") === "true";
        highContrast.value = localStorage.getItem("ax-contrast") === "true";
        cbMode.value = localStorage.getItem("ax-cb-mode") || "normal";
        bigCursor.value = localStorage.getItem("ax-cursor") === "true";
        stopAnimations.value =
            localStorage.getItem("ax-stop-animations") === "true";
        readingGuide.value = localStorage.getItem("ax-guide") === "true";
        screenReader.value = localStorage.getItem("ax-reader") === "true";
        speechRate.value =
            parseFloat(localStorage.getItem("ax-tts-rate")) || 1.0;
        selectedVoiceUri.value = localStorage.getItem("ax-tts-voice-uri") || "";
    } catch {
        // ignore
    }

    applySettings();

    window.addEventListener("mousemove", onMouseMove, { passive: true });
    document.addEventListener("click", onDocumentClick, true);

    if (typeof window !== "undefined" && "speechSynthesis" in window) {
        updateVoices();
        window.speechSynthesis.onvoiceschanged = updateVoices;
    }
});

onUnmounted(() => {
    window.removeEventListener("mousemove", onMouseMove);
    document.removeEventListener("click", onDocumentClick, true);
    cancelSpeech();
});
</script>

<template>
    <!-- SVG Filters for Color Blindness -->
    <svg
        id="ax-cb-filters"
        style="position: absolute; height: 0; width: 0; overflow: hidden"
        aria-hidden="true"
        version="1.1"
        xmlns="http://www.w3.org/2000/svg"
    >
        <defs>
            <!-- Protanopia: Red-Blind -->
            <filter id="ax-filter-protanopia">
                <feColorMatrix
                    type="matrix"
                    values="0.567, 0.433, 0, 0, 0, 0.558, 0.442, 0, 0, 0, 0, 0.242, 0.758, 0, 0, 0, 0, 0, 1, 0"
                />
            </filter>
            <!-- Deuteranopia: Green-Blind -->
            <filter id="ax-filter-deuteranopia">
                <feColorMatrix
                    type="matrix"
                    values="0.625, 0.375, 0, 0, 0, 0.7, 0.3, 0, 0, 0, 0, 0.3, 0.7, 0, 0, 0, 0, 0, 1, 0"
                />
            </filter>
            <!-- Tritanopia: Blue-Blind -->
            <filter id="ax-filter-tritanopia">
                <feColorMatrix
                    type="matrix"
                    values="0.95, 0.05, 0, 0, 0, 0, 0.433, 0.567, 0, 0, 0, 0.475, 0.525, 0, 0, 0, 0, 0, 1, 0"
                />
            </filter>
        </defs>
    </svg>

    <!-- Reading Guide Ruler Line -->
    <div
        v-if="readingGuide"
        id="ax-reading-line"
        class="fixed left-0 right-0 h-2 bg-amber-400/90 pointer-events-none z-[999999] shadow-[0_0_15px_rgba(251,191,36,0.9)] -translate-y-1/2 transition-[top] duration-75"
        :style="{ top: `${guideY}px` }"
    ></div>

    <!-- Active TTS Audio Floating Bar -->
    <div
        v-if="isSpeaking"
        id="ax-tts-bar"
        class="fixed top-5 left-1/2 -translate-x-1/2 z-[999999] bg-slate-900/95 text-white px-4 py-2.5 rounded-full shadow-2xl border border-blue-500/40 flex items-center gap-3 backdrop-blur animate-bounce"
    >
        <span class="flex h-3 w-3 relative">
            <span
                class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"
            ></span>
            <span
                class="relative inline-flex rounded-full h-3 w-3 bg-blue-500"
            ></span>
        </span>
        <span class="text-xs font-medium">Sedang membacakan teks...</span>
        <button
            @click="cancelSpeech"
            type="button"
            class="px-2.5 py-1 bg-red-600 hover:bg-red-500 text-white rounded-full text-[11px] font-bold transition cursor-pointer flex items-center gap-1 shadow"
        >
            <i class="fas fa-stop text-[10px]"></i>
            <span>Hentikan</span>
        </button>
    </div>

    <!-- Accessibility Widget Container -->
    <div
        id="ax-widget-container"
        class="fixed bottom-6 left-6 z-[99998] select-none font-sans"
    >
        <!-- Floating Toggle Trigger Button -->
        <button
            @click="isOpen = !isOpen"
            type="button"
            class="relative group w-13 h-13 rounded-full flex items-center justify-center shadow-xl transition-all duration-300 cursor-pointer focus:outline-none focus:ring-4 focus:ring-blue-300"
            :class="
                activeCount > 0
                    ? 'bg-gradient-to-r from-blue-600 via-indigo-600 to-blue-700 text-white shadow-blue-500/30'
                    : 'bg-gradient-to-r from-slate-900 to-blue-900 text-white hover:shadow-slate-900/30 hover:scale-105'
            "
            title="Menu Aksesibilitas Web"
            aria-label="Menu Aksesibilitas Web"
        >
            <i
                class="fas fa-universal-access text-2xl transition-transform duration-300 group-hover:rotate-12"
            ></i>

            <!-- Active Features Count Badge -->
            <span
                v-if="activeCount > 0"
                class="absolute -top-1 -right-1 bg-amber-400 text-slate-900 font-extrabold text-[11px] w-5 h-5 rounded-full flex items-center justify-center border-2 border-white shadow animate-pulse"
                :title="`${activeCount} fitur aksesibilitas aktif`"
            >
                {{ activeCount }}
            </span>
        </button>

        <!-- Accessibility Modal Popup Dialog -->
        <div
            v-if="isOpen"
            id="ax-widget-panel"
            class="absolute bottom-16 left-0 w-[calc(100vw-2.5rem)] sm:w-[430px] max-w-[430px] bg-white rounded-2xl shadow-2xl border border-slate-200/90 text-slate-800 z-[99999] overflow-hidden flex flex-col max-h-[85vh] transition-all animate-in fade-in zoom-in-95 duration-200"
        >
            <!-- Header -->
            <div
                class="px-5 py-4 bg-gradient-to-r from-slate-900 via-blue-950 to-indigo-950 text-white flex items-center justify-between shadow-sm shrink-0"
            >
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center text-amber-400 border border-white/10"
                    >
                        <i class="fas fa-universal-access text-lg"></i>
                    </div>
                    <div>
                        <h3
                            class="font-bold text-sm tracking-tight text-white flex items-center gap-2"
                        >
                            <span>Aksesibilitas Web</span>
                            <span
                                v-if="activeCount > 0"
                                class="bg-amber-400 text-slate-950 text-[10px] font-extrabold px-1.5 py-0.5 rounded-full"
                            >
                                {{ activeCount }} Aktif
                            </span>
                        </h3>
                        <p class="text-[11px] text-white/70">
                            Fitur Buta Warna, Font Disleksia & Audio TTS
                        </p>
                    </div>
                </div>

                <button
                    @click="isOpen = false"
                    type="button"
                    class="w-7 h-7 rounded-lg bg-white/10 hover:bg-white/20 text-white/80 hover:text-white flex items-center justify-center transition cursor-pointer"
                    aria-label="Tutup Menu"
                >
                    <i class="fas fa-times text-xs"></i>
                </button>
            </div>

            <!-- Tab Navigation -->
            <div
                class="flex border-b border-slate-200 bg-slate-50/80 px-2 pt-2 gap-1 shrink-0 text-xs font-semibold"
            >
                <button
                    @click="activeTab = 'vision'"
                    type="button"
                    class="flex-1 py-2 px-2 text-center rounded-t-lg transition flex items-center justify-center gap-1.5 cursor-pointer border-b-2"
                    :class="
                        activeTab === 'vision'
                            ? 'bg-white text-blue-700 border-blue-600 shadow-sm font-bold'
                            : 'text-slate-600 hover:text-slate-900 border-transparent hover:bg-slate-100'
                    "
                >
                    <i class="fas fa-eye text-xs"></i>
                    <span>Penglihatan</span>
                </button>
                <button
                    @click="activeTab = 'text'"
                    type="button"
                    class="flex-1 py-2 px-2 text-center rounded-t-lg transition flex items-center justify-center gap-1.5 cursor-pointer border-b-2"
                    :class="
                        activeTab === 'text'
                            ? 'bg-white text-blue-700 border-blue-600 shadow-sm font-bold'
                            : 'text-slate-600 hover:text-slate-900 border-transparent hover:bg-slate-100'
                    "
                >
                    <i class="fas fa-font text-xs"></i>
                    <span>Teks & Font</span>
                </button>
                <button
                    @click="activeTab = 'tools'"
                    type="button"
                    class="flex-1 py-2 px-2 text-center rounded-t-lg transition flex items-center justify-center gap-1.5 cursor-pointer border-b-2"
                    :class="
                        activeTab === 'tools'
                            ? 'bg-white text-blue-700 border-blue-600 shadow-sm font-bold'
                            : 'text-slate-600 hover:text-slate-900 border-transparent hover:bg-slate-100'
                    "
                >
                    <i class="fas fa-tools text-xs"></i>
                    <span>Alat Bantu</span>
                </button>
            </div>

            <!-- Tab Content (Scrollable) -->
            <div class="p-4 overflow-y-auto flex-1 space-y-4 text-xs">
                <!-- TAB 1: VISION & COLOR BLINDNESS -->
                <div v-show="activeTab === 'vision'" class="space-y-4">
                    <!-- Kontras Tinggi -->
                    <div
                        @click="toggleContrast"
                        class="p-3 rounded-xl border transition cursor-pointer flex items-center justify-between"
                        :class="
                            highContrast
                                ? 'bg-blue-50/70 border-blue-300 ring-2 ring-blue-500/20'
                                : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                                :class="
                                    highContrast
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                <i class="fas fa-adjust"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800">
                                    Kontras Tinggi
                                </h4>
                                <p
                                    class="text-[11px] text-slate-500 leading-tight"
                                >
                                    Latar hitam gelap & teks kuning/sian terang
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                            :class="
                                highContrast
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-200 text-slate-600'
                            "
                        >
                            {{ highContrast ? "Aktif" : "Off" }}
                        </span>
                    </div>

                    <!-- Filter Buta Warna -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label
                                class="font-bold text-slate-700 flex items-center gap-1.5"
                            >
                                <i class="fas fa-palette text-blue-600"></i>
                                <span
                                    >Filter Defisiensi Warna & Penglihatan</span
                                >
                            </label>
                            <span
                                v-if="cbMode !== 'normal'"
                                class="text-[10px] text-blue-600 font-bold capitalize"
                            >
                                {{ cbMode }}
                            </span>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <!-- Normal -->
                            <button
                                @click="setCbMode('normal')"
                                type="button"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer flex flex-col gap-1"
                                :class="
                                    cbMode === 'normal'
                                        ? 'bg-blue-50 border-blue-400 ring-2 ring-blue-500/20 font-bold text-blue-800'
                                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-xs"
                                        >Warna Asli</span
                                    >
                                    <i
                                        v-if="cbMode === 'normal'"
                                        class="fas fa-check text-blue-600 text-[10px]"
                                    ></i>
                                </div>
                                <span
                                    class="text-[10px] text-slate-500 font-normal"
                                    >Standar normal</span
                                >
                            </button>

                            <!-- Protanopia -->
                            <button
                                @click="setCbMode('protanopia')"
                                type="button"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer flex flex-col gap-1"
                                :class="
                                    cbMode === 'protanopia'
                                        ? 'bg-blue-50 border-blue-400 ring-2 ring-blue-500/20 font-bold text-blue-800'
                                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-xs"
                                        >Protanopia</span
                                    >
                                    <i
                                        v-if="cbMode === 'protanopia'"
                                        class="fas fa-check text-blue-600 text-[10px]"
                                    ></i>
                                </div>
                                <span
                                    class="text-[10px] text-slate-500 font-normal"
                                    >Buta warna merah</span
                                >
                            </button>

                            <!-- Deuteranopia -->
                            <button
                                @click="setCbMode('deuteranopia')"
                                type="button"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer flex flex-col gap-1"
                                :class="
                                    cbMode === 'deuteranopia'
                                        ? 'bg-blue-50 border-blue-400 ring-2 ring-blue-500/20 font-bold text-blue-800'
                                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-xs"
                                        >Deuteranopia</span
                                    >
                                    <i
                                        v-if="cbMode === 'deuteranopia'"
                                        class="fas fa-check text-blue-600 text-[10px]"
                                    ></i>
                                </div>
                                <span
                                    class="text-[10px] text-slate-500 font-normal"
                                    >Buta warna hijau (umum)</span
                                >
                            </button>

                            <!-- Tritanopia -->
                            <button
                                @click="setCbMode('tritanopia')"
                                type="button"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer flex flex-col gap-1"
                                :class="
                                    cbMode === 'tritanopia'
                                        ? 'bg-blue-50 border-blue-400 ring-2 ring-blue-500/20 font-bold text-blue-800'
                                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-xs"
                                        >Tritanopia</span
                                    >
                                    <i
                                        v-if="cbMode === 'tritanopia'"
                                        class="fas fa-check text-blue-600 text-[10px]"
                                    ></i>
                                </div>
                                <span
                                    class="text-[10px] text-slate-500 font-normal"
                                    >Buta warna biru-kuning</span
                                >
                            </button>

                            <!-- Monokrom -->
                            <button
                                @click="setCbMode('monochrome')"
                                type="button"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer flex flex-col gap-1"
                                :class="
                                    cbMode === 'monochrome'
                                        ? 'bg-blue-50 border-blue-400 ring-2 ring-blue-500/20 font-bold text-blue-800'
                                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-xs"
                                        >Monokrom</span
                                    >
                                    <i
                                        v-if="cbMode === 'monochrome'"
                                        class="fas fa-check text-blue-600 text-[10px]"
                                    ></i>
                                </div>
                                <span
                                    class="text-[10px] text-slate-500 font-normal"
                                    >Total abu-abu (grayscale)</span
                                >
                            </button>

                            <!-- Invert Cerdas -->
                            <button
                                @click="setCbMode('invert')"
                                type="button"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer flex flex-col gap-1"
                                :class="
                                    cbMode === 'invert'
                                        ? 'bg-blue-50 border-blue-400 ring-2 ring-blue-500/20 font-bold text-blue-800'
                                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-xs"
                                        >Invert Warna</span
                                    >
                                    <i
                                        v-if="cbMode === 'invert'"
                                        class="fas fa-check text-blue-600 text-[10px]"
                                    ></i>
                                </div>
                                <span
                                    class="text-[10px] text-slate-500 font-normal"
                                    >Balik warna (foto aman)</span
                                >
                            </button>

                            <!-- Sepia -->
                            <button
                                @click="setCbMode('sepia')"
                                type="button"
                                class="p-2.5 rounded-xl border text-left transition cursor-pointer flex flex-col gap-1 col-span-2"
                                :class="
                                    cbMode === 'sepia'
                                        ? 'bg-blue-50 border-blue-400 ring-2 ring-blue-500/20 font-bold text-blue-800'
                                        : 'bg-slate-50 border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold text-xs"
                                        >Sepia Hangat</span
                                    >
                                    <i
                                        v-if="cbMode === 'sepia'"
                                        class="fas fa-check text-blue-600 text-[10px]"
                                    ></i>
                                </div>
                                <span
                                    class="text-[10px] text-slate-500 font-normal"
                                    >Mengurangi paparan cahaya biru untuk
                                    kenyamanan mata</span
                                >
                            </button>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: TEXT & DYSLEXIA -->
                <div v-show="activeTab === 'text'" class="space-y-4">
                    <!-- Font Disleksia Resmi OpenDyslexic (opendyslexic.org) -->
                    <div
                        @click="toggleDyslexia"
                        class="p-3.5 rounded-xl border transition cursor-pointer flex items-center justify-between"
                        :class="
                            dyslexiaFont
                                ? 'bg-blue-50/80 border-blue-300 ring-2 ring-blue-500/20'
                                : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-9 h-9 rounded-lg flex items-center justify-center text-sm"
                                :class="
                                    dyslexiaFont
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                <i class="fas fa-book-reader"></i>
                            </div>
                            <div>
                                <h4
                                    class="font-bold text-slate-800 flex items-center gap-1.5"
                                >
                                    <span>Font Resmi OpenDyslexic</span>
                                    <span
                                        class="bg-indigo-100 text-indigo-700 text-[9px] font-bold px-1.5 py-0.2 rounded"
                                    >
                                        opendyslexic.org
                                    </span>
                                </h4>
                                <p
                                    class="text-[11px] text-slate-500 leading-tight mt-0.5"
                                >
                                    Rilis resmi dengan gravitasi tebal di bawah
                                    untuk mencegah huruf berputar atau tertukar
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-2.5 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider shrink-0 ml-2"
                            :class="
                                dyslexiaFont
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-200 text-slate-600'
                            "
                        >
                            {{ dyslexiaFont ? "Aktif" : "Off" }}
                        </span>
                    </div>

                    <!-- Ukuran Teks -->
                    <div
                        class="p-3 bg-slate-50 rounded-xl border border-slate-200 space-y-2"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="font-bold text-slate-700 flex items-center gap-1.5"
                            >
                                <i class="fas fa-text-height text-blue-600"></i>
                                <span>Ukuran Teks</span>
                            </span>
                            <span
                                class="text-[11px] font-semibold text-blue-700"
                            >
                                {{
                                    textSize === "normal"
                                        ? "Normal (100%)"
                                        : textSize === "large"
                                          ? "Besar (115%)"
                                          : "Ekstra Besar (130%)"
                                }}
                            </span>
                        </div>
                        <div class="grid grid-cols-3 gap-1.5">
                            <button
                                @click="setTextSizeDirect('normal')"
                                type="button"
                                class="py-1.5 px-2 rounded-lg text-xs font-bold transition cursor-pointer text-center"
                                :class="
                                    textSize === 'normal'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                100%
                            </button>
                            <button
                                @click="setTextSizeDirect('large')"
                                type="button"
                                class="py-1.5 px-2 rounded-lg text-xs font-bold transition cursor-pointer text-center"
                                :class="
                                    textSize === 'large'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                115%
                            </button>
                            <button
                                @click="setTextSizeDirect('xlarge')"
                                type="button"
                                class="py-1.5 px-2 rounded-lg text-xs font-bold transition cursor-pointer text-center"
                                :class="
                                    textSize === 'xlarge'
                                        ? 'bg-blue-600 text-white shadow-sm'
                                        : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
                                "
                            >
                                130%
                            </button>
                        </div>
                    </div>

                    <!-- Spasi Teks Renggang -->
                    <div
                        @click="toggleTextSpacing"
                        class="p-3 rounded-xl border transition cursor-pointer flex items-center justify-between"
                        :class="
                            textSpacing
                                ? 'bg-blue-50/80 border-blue-300 ring-2 ring-blue-500/20'
                                : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                                :class="
                                    textSpacing
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                <i class="fas fa-arrows-alt-h"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800">
                                    Spasi Teks Renggang
                                </h4>
                                <p
                                    class="text-[11px] text-slate-500 leading-tight"
                                >
                                    Memperluas jarak spasi antar-huruf, kata,
                                    dan baris kalimat
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                            :class="
                                textSpacing
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-200 text-slate-600'
                            "
                        >
                            {{ textSpacing ? "Aktif" : "Off" }}
                        </span>
                    </div>

                    <!-- Garis Bawah Link -->
                    <div
                        @click="toggleUnderline"
                        class="p-3 rounded-xl border transition cursor-pointer flex items-center justify-between"
                        :class="
                            underlineLinks
                                ? 'bg-blue-50/80 border-blue-300 ring-2 ring-blue-500/20'
                                : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                                :class="
                                    underlineLinks
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                <i class="fas fa-underline"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800">
                                    Garis Bawah Link
                                </h4>
                                <p
                                    class="text-[11px] text-slate-500 leading-tight"
                                >
                                    Beri garis bawah & sorot tebal pada semua
                                    tautan halaman
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                            :class="
                                underlineLinks
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-200 text-slate-600'
                            "
                        >
                            {{ underlineLinks ? "Aktif" : "Off" }}
                        </span>
                    </div>
                </div>

                <!-- TAB 3: TOOLS & READING AIDS -->
                <div v-show="activeTab === 'tools'" class="space-y-3">
                    <!-- Pembaca Suara TTS Box -->
                    <div
                        class="p-3.5 rounded-xl border transition flex flex-col gap-2.5"
                        :class="
                            screenReader
                                ? 'bg-blue-50/80 border-blue-300 ring-2 ring-blue-500/20'
                                : 'bg-slate-50 border-slate-200'
                        "
                    >
                        <div
                            @click="toggleScreenReader"
                            class="flex items-center justify-between cursor-pointer"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-8 h-8 rounded-lg flex items-center justify-center text-sm shrink-0"
                                    :class="
                                        screenReader
                                            ? 'bg-blue-600 text-white'
                                            : 'bg-slate-200 text-slate-700'
                                    "
                                >
                                    <i class="fas fa-volume-up"></i>
                                </div>
                                <div>
                                    <h4 class="font-bold text-slate-800">
                                        Pembaca Suara (TTS Bahasa Indonesia)
                                    </h4>
                                    <p
                                        class="text-[11px] text-slate-500 leading-tight"
                                    >
                                        Klik teks / tombol pada website untuk
                                        mendengarkan lafal alami
                                    </p>
                                </div>
                            </div>
                            <span
                                class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider shrink-0 ml-2"
                                :class="
                                    screenReader
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-600'
                                "
                            >
                                {{ screenReader ? "Aktif" : "Off" }}
                            </span>
                        </div>

                        <!-- Expanded TTS Controls when screenReader is active -->
                        <div
                            v-if="screenReader"
                            class="pt-2 border-t border-blue-200/80 space-y-2 mt-1"
                        >
                            <!-- Voice Selector (if multiple voices) -->
                            <div
                                v-if="indonesianVoices.length > 1"
                                class="space-y-1"
                            >
                                <label
                                    class="text-[10px] font-bold text-slate-600 uppercase"
                                >
                                    Pilih Suara Bahasa Indonesia:
                                </label>
                                <select
                                    v-model="selectedVoiceUri"
                                    @change="applySettings"
                                    class="w-full bg-white border border-slate-200 rounded-lg px-2 py-1 text-[11px] text-slate-800 focus:outline-none focus:ring-1 focus:ring-blue-500 font-medium"
                                >
                                    <option
                                        v-for="v in indonesianVoices"
                                        :key="v.voiceURI"
                                        :value="v.voiceURI"
                                    >
                                        {{ v.name }}
                                    </option>
                                </select>
                            </div>

                            <div
                                v-else-if="indonesianVoices.length === 1"
                                class="text-[11px] text-slate-600 flex items-center gap-1.5"
                            >
                                <i
                                    class="fas fa-check-circle text-emerald-600 text-xs"
                                ></i>
                                <span
                                    >Suara:
                                    <strong>{{
                                        indonesianVoices[0].name
                                    }}</strong>
                                    (id-ID)</span
                                >
                            </div>

                            <div
                                v-else
                                class="text-[10px] text-amber-700 bg-amber-50 p-2 rounded-lg border border-amber-200 leading-relaxed"
                            >
                                <i class="fas fa-info-circle mr-1"></i>
                                Menggunakan suara bawaan browser dengan fonetik
                                Indonesia. Anda dapat menambahkan paket suara
                                Bahasa Indonesia di setelan Windows/browser
                                untuk hasil lebih jernih.
                            </div>

                            <!-- Speed selector & Test Voice button -->
                            <div
                                class="flex items-center justify-between gap-2 pt-1"
                            >
                                <div class="flex items-center gap-1">
                                    <span
                                        class="text-[10px] font-bold text-slate-500"
                                        >Kecepatan:</span
                                    >
                                    <button
                                        v-for="rate in [0.9, 1.0, 1.15]"
                                        :key="rate"
                                        @click="
                                            speechRate = rate;
                                            applySettings();
                                        "
                                        type="button"
                                        class="px-2 py-0.5 rounded text-[10px] font-bold transition cursor-pointer"
                                        :class="
                                            speechRate === rate
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-white border border-slate-200 text-slate-700 hover:bg-slate-100'
                                        "
                                    >
                                        {{
                                            rate === 0.9
                                                ? "Pelan"
                                                : rate === 1.0
                                                  ? "Normal"
                                                  : "Cepat"
                                        }}
                                    </button>
                                </div>

                                <button
                                    @click="testSpeech"
                                    type="button"
                                    class="px-2.5 py-1 bg-white hover:bg-blue-50 text-blue-700 border border-blue-200 rounded-lg text-[10px] font-bold transition cursor-pointer flex items-center gap-1 shadow-xs"
                                    title="Dengarkan contoh suara"
                                >
                                    <i class="fas fa-play text-[9px]"></i>
                                    <span>Tes Suara</span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Garis Pandu Baca (Ruler) -->
                    <div
                        @click="toggleGuide"
                        class="p-3 rounded-xl border transition cursor-pointer flex items-center justify-between"
                        :class="
                            readingGuide
                                ? 'bg-blue-50/80 border-blue-300 ring-2 ring-blue-500/20'
                                : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                                :class="
                                    readingGuide
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                <i class="fas fa-ruler-horizontal"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800">
                                    Garis Pandu Baca
                                </h4>
                                <p
                                    class="text-[11px] text-slate-500 leading-tight"
                                >
                                    Garis horizontal mengikuti kursor menjaga
                                    fokus membaca
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                            :class="
                                readingGuide
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-200 text-slate-600'
                            "
                        >
                            {{ readingGuide ? "Aktif" : "Off" }}
                        </span>
                    </div>

                    <!-- Kursor Pembesar -->
                    <div
                        @click="toggleCursor"
                        class="p-3 rounded-xl border transition cursor-pointer flex items-center justify-between"
                        :class="
                            bigCursor
                                ? 'bg-blue-50/80 border-blue-300 ring-2 ring-blue-500/20'
                                : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                                :class="
                                    bigCursor
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                <i class="fas fa-mouse-pointer"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800">
                                    Kursor Pembesar
                                </h4>
                                <p
                                    class="text-[11px] text-slate-500 leading-tight"
                                >
                                    Kursor 44px kontras tinggi dengan outline
                                    putih
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                            :class="
                                bigCursor
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-200 text-slate-600'
                            "
                        >
                            {{ bigCursor ? "Aktif" : "Off" }}
                        </span>
                    </div>

                    <!-- Matikan Animasi -->
                    <div
                        @click="toggleAnimations"
                        class="p-3 rounded-xl border transition cursor-pointer flex items-center justify-between"
                        :class="
                            stopAnimations
                                ? 'bg-blue-50/80 border-blue-300 ring-2 ring-blue-500/20'
                                : 'bg-slate-50 border-slate-200 hover:bg-slate-100/70'
                        "
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="w-8 h-8 rounded-lg flex items-center justify-center text-sm"
                                :class="
                                    stopAnimations
                                        ? 'bg-blue-600 text-white'
                                        : 'bg-slate-200 text-slate-700'
                                "
                            >
                                <i class="fas fa-pause-circle"></i>
                            </div>
                            <div>
                                <h4 class="font-bold text-slate-800">
                                    Hentikan Animasi
                                </h4>
                                <p
                                    class="text-[11px] text-slate-500 leading-tight"
                                >
                                    Hentikan semua pergerakan visual (ramah
                                    vestibular)
                                </p>
                            </div>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-extrabold uppercase tracking-wider"
                            :class="
                                stopAnimations
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-slate-200 text-slate-600'
                            "
                        >
                            {{ stopAnimations ? "Aktif" : "Off" }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Footer Action Panel -->
            <div
                class="p-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between gap-2 shrink-0"
            >
                <button
                    @click="resetAll"
                    type="button"
                    class="py-2 px-3 bg-white hover:bg-red-50 text-slate-700 hover:text-red-700 rounded-xl border border-slate-200 hover:border-red-200 font-bold text-xs flex items-center gap-1.5 transition cursor-pointer shadow-sm"
                    title="Kembalikan semua pengaturan ke awal"
                >
                    <i class="fas fa-rotate-left text-xs"></i>
                    <span>Reset Semua</span>
                </button>

                <button
                    @click="isOpen = false"
                    type="button"
                    class="py-2 px-4 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-bold text-xs flex items-center gap-1.5 transition cursor-pointer shadow-sm shadow-blue-500/20"
                >
                    <i class="fas fa-check text-xs"></i>
                    <span>Terapkan & Tutup</span>
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Custom subtle scrollbar inside accessibility panel */
#ax-widget-panel ::-webkit-scrollbar {
    width: 5px;
}
#ax-widget-panel ::-webkit-scrollbar-track {
    background: transparent;
}
#ax-widget-panel ::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 10px;
}
#ax-widget-panel ::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
