<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const aboutWords = [
    "Melalui Keterbukaan.",
    "Dengan Transparansi.",
    "Demi Akuntabilitas.",
];
const aboutText = ref("");
let wordIndex = 0;
let charIndex = 0;
let isDeleting = false;
let timeoutId = null;

const typeEffect = () => {
    const currentWord = aboutWords[wordIndex];
    if (isDeleting) {
        aboutText.value = currentWord.substring(0, charIndex - 1);
        charIndex--;
    } else {
        aboutText.value = currentWord.substring(0, charIndex + 1);
        charIndex++;
    }

    let typeSpeed = isDeleting ? 30 : 60;
    if (!isDeleting && charIndex === currentWord.length) {
        typeSpeed = 2500;
        isDeleting = true;
    } else if (isDeleting && aboutText.value === "") {
        isDeleting = false;
        wordIndex = (wordIndex + 1) % aboutWords.length;
        typeSpeed = 300;
    }
    timeoutId = setTimeout(typeEffect, typeSpeed);
};

onMounted(() => {
    timeoutId = setTimeout(typeEffect, 1500);
});

onUnmounted(() => {
    if (timeoutId) clearTimeout(timeoutId);
});
</script>

<template>
    <section
        id="about"
        class="bg-slate-100/70 py-20 scroll-mt-20 border-t border-slate-100"
    >
        <div
            class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid lg:grid-cols-12 gap-12 items-center"
        >
            <div class="lg:col-span-7 space-y-6">
                <h1
                    class="text-3xl sm:text-4xl md:text-5xl font-black text-slate-900 tracking-tight leading-tight min-h-[5.5rem] sm:min-h-[7rem]"
                >
                    <span class="block">Membangun Kepercayaan,</span>
                    <span
                        class="text-blue-600 block sm:inline-block border-r-2 border-blue-600 pr-1 animate-pulse"
                    >
                        {{ aboutText }}
                    </span>
                </h1>
                <p
                    class="text-slate-600 text-sm sm:text-base font-normal leading-relaxed max-w-2xl"
                >
                    Kami menyajikan informasi publik secara transparan, akurat,
                    dan dapat dipertanggungjawabkan kepada seluruh lapisan
                    masyarakat sebagai wujud pelaksanaan reformasi birokrasi di
                    lingkungan kerja Balai Pelatihan.
                </p>
                <div class="pt-2 flex flex-wrap gap-4">
                    <a
                        href="#documents"
                        class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-6 py-3.5 rounded-xl shadow-lg shadow-blue-600/10 transition-all"
                    >
                        Lihat Informasi Publik
                    </a>
                    <a
                        :href="`https://api.whatsapp.com/send?phone=${settings?.whatsapp_number ?? '6285343747243'}&text=Halo%20PPID%20BPVP%20Pangkep...`"
                        target="_blank"
                        class="border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-bold px-6 py-3.5 rounded-xl transition-all"
                    >
                        Permohonan Informasi via WA
                    </a>
                </div>
            </div>

            <!-- Maps Embed -->
            <div
                class="lg:col-span-5 rounded-2xl overflow-hidden shadow-xl border border-slate-200 aspect-4/3 min-h-[300px] bg-slate-100 flex items-center justify-center"
            >
                <div
                    v-if="settings?.google_maps_embed"
                    v-html="settings.google_maps_embed"
                    class="w-full h-full [&>iframe]:w-full [&>iframe]:h-full [&>iframe]:border-0"
                ></div>
                <div v-else class="text-xs text-slate-400 p-4 text-center">
                    Gunakan menu Pengaturan di panel admin untuk menampilkan
                    peta navigasi Google Maps instansi di sini.
                </div>
            </div>
        </div>
    </section>
</template>
