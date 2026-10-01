<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const sliders = computed(() => {
    if (!props.settings?.sliders) return [];
    let raw = props.settings.sliders;
    if (typeof raw === "string") {
        try {
            raw = JSON.parse(raw);
        } catch {
            return [];
        }
        if (typeof raw === "string") {
            try {
                raw = JSON.parse(raw);
            } catch {}
        }
    }
    return Array.isArray(raw) ? raw : [];
});

const getSlideUrl = (slide) => {
    if (!slide) return "";
    const rawPath =
        typeof slide === "string"
            ? slide
            : slide.image_url || slide.image || "";
    if (!rawPath) return "";
    if (rawPath.startsWith("http://") || rawPath.startsWith("https://"))
        return rawPath;
    const clean = rawPath.replace(/^\/?storage\//, "").replace(/^\//, "");
    const v = props.settings?.updated_at
        ? `?v=${new Date(props.settings.updated_at).getTime()}`
        : "";
    return `/storage/${clean}${v}`;
};

const currentSlider = ref(0);
let autoplayInterval = null;

const startAutoplay = () => {
    if (sliders.value.length > 1 && !autoplayInterval) {
        autoplayInterval = setInterval(() => {
            currentSlider.value =
                (currentSlider.value + 1) % sliders.value.length;
        }, 5000);
    }
};

const stopAutoplay = () => {
    if (autoplayInterval) {
        clearInterval(autoplayInterval);
        autoplayInterval = null;
    }
};

// Typewriter effect
const words = ["Kompeten", "Unggul", "Siap Kerja", "Berproduktivitas Tinggi"];
const text = ref("");
let wordIndex = 0;
let charIndex = 0;
let isDeleting = false;
let typeTimeout = null;

const typeEffect = () => {
    const currentWord = words[wordIndex];
    if (isDeleting) {
        text.value = currentWord.substring(0, charIndex - 1);
        charIndex--;
    } else {
        text.value = currentWord.substring(0, charIndex + 1);
        charIndex++;
    }

    let typeSpeed = isDeleting ? 40 : 80;
    if (!isDeleting && charIndex === currentWord.length) {
        typeSpeed = 2200;
        isDeleting = true;
    } else if (isDeleting && text.value === "") {
        isDeleting = false;
        wordIndex = (wordIndex + 1) % words.length;
        typeSpeed = 400;
    }
    typeTimeout = setTimeout(typeEffect, typeSpeed);
};

onMounted(() => {
    startAutoplay();
    typeTimeout = setTimeout(typeEffect, 1200);
});

onUnmounted(() => {
    stopAutoplay();
    if (typeTimeout) clearTimeout(typeTimeout);
});
</script>

<template>
    <section
        class="w-full relative overflow-hidden bg-slate-950 h-[60vh] sm:h-[100dvh]"
        @mouseenter="stopAutoplay"
        @mouseleave="startAutoplay"
    >
        <!-- Sliders -->
        <div class="w-full h-full relative">
            <template v-if="sliders.length > 0">
                <div
                    v-for="(slide, index) in sliders"
                    :key="index"
                    v-show="currentSlider === index"
                    class="absolute inset-0 w-full h-full transition-opacity duration-1000"
                >
                    <img
                        :src="getSlideUrl(slide)"
                        alt="Slider Banner"
                        class="w-full h-full object-cover object-center transform transition-transform duration-500"
                    />
                </div>
            </template>
            <div
                v-else
                class="absolute inset-0 bg-gradient-to-br from-slate-900 to-blue-950 flex items-center justify-center text-white/40 text-sm"
            >
                <i class="fas fa-images mr-2 text-base animate-pulse"></i> Belum
                ada gambar cover slider.
            </div>
        </div>

        <div
            class="absolute inset-0 bg-gradient-to-t from-slate-950 via-slate-950/50 to-slate-950/40 mix-blend-multiply z-10"
        ></div>

        <!-- Hero Content & Typewriter -->
        <div
            class="absolute inset-0 z-20 flex items-center justify-center px-4 overflow-hidden"
        >
            <div class="absolute inset-0 pointer-events-none select-none z-0">
                <div
                    class="absolute w-80 h-80 bg-blue-500/15 rounded-full blur-3xl -top-16 -left-16 animate-pulse"
                    style="animation-duration: 7s"
                ></div>
                <div
                    class="absolute w-[450px] h-[450px] bg-amber-500/5 rounded-full blur-3xl bottom-5 right-5 animate-pulse"
                    style="animation-duration: 11s"
                ></div>
            </div>

            <div
                class="text-center max-w-7xl space-y-3 sm:space-y-5 z-10 px-4 w-full mx-auto overflow-hidden"
            >
                <h1 class="space-y-2 sm:space-y-3">
                    <span
                        class="block text-xs sm:text-2xl md:text-3xl lg:text-4xl font-extrabold tracking-tight text-white drop-shadow-[0_4px_12px_rgba(0,0,0,0.7)] uppercase"
                    >
                        Selamat Datang di Situs Resmi
                    </span>
                    <span
                        class="block font-black bg-gradient-to-r from-amber-300 via-yellow-100 to-amber-400 bg-clip-text text-transparent drop-shadow-[0_4px_10px_rgba(0,0,0,0.6)] leading-tight tracking-tight uppercase flex flex-col items-center gap-1 sm:gap-2 w-full"
                    >
                        <span
                            class="block text-sm sm:text-xl md:text-2xl lg:text-4xl xl:text-5xl max-w-full tracking-tighter sm:tracking-tight break-words text-center"
                        >
                            Balai Pelatihan Vokasi dan Produktivitas
                        </span>
                        <span
                            class="block text-base sm:text-3xl md:text-4xl lg:text-5xl xl:text-6xl text-amber-400 font-black tracking-normal"
                        >
                            Pangkajene dan Kepulauan
                        </span>
                    </span>
                </h1>
                <div
                    class="text-[11px] sm:text-base md:text-lg font-medium text-slate-200/90 tracking-wide"
                >
                    Mewujudkan Tenaga Kerja yang
                    <span
                        class="text-amber-400 font-black border-r-2 border-amber-400 pl-1 pb-0.5 animate-pulse"
                    >
                        {{ text }}
                    </span>
                </div>
                <div
                    class="pt-3 border-t border-white/15 max-w-xs mx-auto opacity-90"
                >
                    <p
                        class="text-[9px] sm:text-xs text-amber-300 font-mono font-bold uppercase tracking-widest"
                    >
                        Kementerian Ketenagakerjaan RI
                    </p>
                </div>
            </div>
        </div>

        <!-- Slider Dots -->
        <div
            v-if="sliders.length > 1"
            class="absolute bottom-4 sm:bottom-10 left-0 right-0 flex justify-center gap-3 z-30"
        >
            <button
                v-for="(_, index) in sliders"
                :key="index"
                @click="currentSlider = index"
                class="h-1.5 rounded-full transition-all duration-300 cursor-pointer"
                :class="
                    currentSlider === index
                        ? 'w-8 bg-amber-400'
                        : 'w-2 bg-white/30 hover:bg-white/60'
                "
            ></button>
        </div>
    </section>
</template>
