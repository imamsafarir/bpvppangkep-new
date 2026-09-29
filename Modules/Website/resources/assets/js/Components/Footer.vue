<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { Link } from "@inertiajs/vue3";
import { triggerInstallPrompt } from "@/pwa";

defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const showScrollTop = ref(false);

const handleScroll = () => {
    showScrollTop.value = window.scrollY > 400;
};

const scrollToTop = () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
};

onMounted(() => {
    window.addEventListener("scroll", handleScroll);
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
    <footer
        id="kontak"
        class="text-slate-300 relative bg-transparent select-none mt-16 z-30"
    >
        <!-- Waves decoration -->
        <div
            class="w-full absolute -top-[100px] left-0 overflow-hidden leading-none pointer-events-none h-[115px] z-10"
        >
            <svg
                class="relative block w-[200%] h-full"
                viewBox="0 0 1200 150"
                preserveAspectRatio="none"
            >
                <path
                    class="wave-layer-3"
                    d="M0,40 C150,90 350,10 500,60 C650,110 850,30 1000,70 C1150,110 1300,40 1440,80 L1440,200 L0,200 Z"
                    fill="#15406a"
                ></path>
                <path
                    class="wave-layer-2"
                    d="M0,50 C180,20 320,90 540,40 C760,-10 920,80 1120,50 C1320,20 1380,70 1440,40 L1440,200 L0,200 Z"
                    fill="#15406a"
                ></path>
                <path
                    class="wave-layer-1"
                    d="M0,60 C200,30 400,80 600,50 C800,20 1000,70 1200,40 C1400,10 1420,60 1440,50 L1440,200 L0,200 Z"
                    fill="#15406a"
                ></path>
            </svg>
        </div>

        <div
            class="relative z-20 w-full pt-12 pb-8"
            style="background-color: #1b446f"
        >
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-1 md:grid-cols-12 gap-10 border-b border-white/10 pb-12"
            >
                <!-- Kolom 1: Profil -->
                <div class="md:col-span-5 space-y-4 text-left">
                    <div class="flex items-center gap-3 select-none">
                        <img
                            :src="
                                settings?.logo_path
                                    ? `/storage/${settings.logo_path}`
                                    : 'https://kemnaker.go.id/assets/images/logo.png'
                            "
                            alt="Logo Footer"
                            class="h-12 w-auto object-contain bg-white/10 p-1.5 rounded-xl"
                        />
                        <div class="leading-tight">
                            <span
                                class="font-black text-white text-base block tracking-tight"
                            >
                                {{ settings?.website_name ?? "BPVP PANGKEP" }}
                            </span>
                            <span
                                class="text-[9px] font-bold block uppercase tracking-wider text-slate-300"
                            >
                                KEMNAKER RI
                            </span>
                        </div>
                    </div>
                    <div
                        class="text-xs sm:text-sm leading-relaxed font-normal text-slate-300/90 break-words"
                    >
                        <strong
                            class="text-white block font-extrabold tracking-tight mb-1"
                        >
                            Balai Pelatihan Vokasi dan Produktivitas Pangkajene
                            dan Kepulauan
                        </strong>
                        <div class="flex items-start gap-2 mt-2">
                            <i
                                class="fas fa-map-marker-alt text-amber-400 mt-1 flex-shrink-0 w-4"
                            ></i>
                            <span>{{
                                settings?.address ??
                                "Jl. Poros Makassar - Parepare KM. 83, Mandalle, Kab. Pangkajene dan Kepulauan, Sulawesi Selatan"
                            }}</span>
                        </div>
                    </div>
                </div>

                <!-- Kolom 2: Tautan Pintas -->
                <div class="md:col-span-3 space-y-3 md:pl-6 text-left">
                    <h4
                        class="text-white font-extrabold text-sm uppercase tracking-wider border-l-2 border-amber-400 pl-2"
                    >
                        Tautan Pintas
                    </h4>
                    <ul class="text-xs sm:text-sm space-y-2 font-semibold">
                        <li>
                            <Link
                                href="/"
                                class="hover:text-amber-400 transition-colors flex items-center gap-1.5"
                                >› Beranda</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/profil/tentang-kami"
                                class="hover:text-amber-400 transition-colors flex items-center gap-1.5"
                                >› Profil Balai</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/informasi/kejuruan"
                                class="hover:text-amber-400 transition-colors flex items-center gap-1.5"
                                >› Program Kejuruan</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/berita-informasi/daftar-berita"
                                class="hover:text-amber-400 transition-colors flex items-center gap-1.5"
                                >› Kabar Berita</Link
                            >
                        </li>
                        <li>
                            <Link
                                href="/jdih"
                                class="hover:text-amber-400 transition-colors flex items-center gap-1.5"
                                >› JDIH Hukum</Link
                            >
                        </li>
                    </ul>
                </div>

                <!-- Kolom 3: Kontak & Medsos -->
                <div class="md:col-span-4 space-y-3 w-full text-left">
                    <h4
                        class="text-white font-extrabold text-sm uppercase tracking-wider border-l-2 border-amber-400 pl-2"
                    >
                        Kontak Hubung
                    </h4>
                    <ul class="text-xs sm:text-sm space-y-2.5 font-medium">
                        <li
                            v-if="settings?.email"
                            class="flex items-center gap-2"
                        >
                            <div
                                class="w-7 h-7 bg-white/5 rounded-lg flex items-center justify-center text-indigo-300 flex-shrink-0"
                            >
                                <i class="fas fa-envelope"></i>
                            </div>
                            <span class="truncate">{{ settings.email }}</span>
                        </li>
                        <li
                            v-if="settings?.phone_number"
                            class="flex items-center gap-2"
                        >
                            <div
                                class="w-7 h-7 bg-white/5 rounded-lg flex items-center justify-center text-emerald-400 flex-shrink-0"
                            >
                                <i class="fas fa-phone"></i>
                            </div>
                            <span>{{ settings.phone_number }}</span>
                        </li>
                    </ul>

                    <div class="pt-2 flex flex-wrap gap-2">
                        <a
                            v-if="settings?.instagram_url"
                            :href="settings.instagram_url"
                            target="_blank"
                            class="w-8 h-8 rounded-xl bg-white/5 hover:bg-pink-600 hover:text-white text-pink-400 flex items-center justify-center transition-all shadow-2xs"
                        >
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a
                            v-if="settings?.tiktok_url"
                            :href="settings.tiktok_url"
                            target="_blank"
                            class="w-8 h-8 rounded-xl bg-white/5 hover:bg-black hover:text-white text-slate-200 flex items-center justify-center transition-all shadow-2xs"
                        >
                            <i class="fab fa-tiktok"></i>
                        </a>
                        <a
                            v-if="settings?.facebook_url"
                            :href="settings.facebook_url"
                            target="_blank"
                            class="w-8 h-8 rounded-xl bg-white/5 hover:bg-blue-600 hover:text-white text-blue-400 flex items-center justify-center transition-all shadow-2xs"
                        >
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a
                            v-if="settings?.youtube_url"
                            :href="settings.youtube_url"
                            target="_blank"
                            class="w-8 h-8 rounded-xl bg-white/5 hover:bg-red-600 hover:text-white text-red-400 flex items-center justify-center transition-all shadow-2xs"
                        >
                            <i class="fab fa-youtube"></i>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Copyright -->
            <div
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-8 flex flex-col sm:flex-row justify-between items-center gap-4 text-[11px] font-medium text-slate-300/70"
            >
                <p>
                    &copy; 2026 Balai Pelatihan Vokasi dan Produktivitas
                    {{ settings?.website_name ?? "BPVP Pangkep" }}. All Rights
                    Reserved.
                </p>
                <div class="flex items-center gap-2 flex-wrap justify-center">
                    <button
                        @click="triggerInstallPrompt"
                        type="button"
                        class="inline-flex items-center gap-1.5 text-[11px] font-semibold text-amber-300 hover:text-amber-200 bg-white/10 hover:bg-white/15 px-3 py-1.5 rounded-lg border border-white/10 transition cursor-pointer"
                        title="Pasang BPVP Pangkep - Super APP di perangkat Anda"
                    >
                        <i class="fas fa-mobile-alt text-xs"></i>
                        <span>Pasang Super APP</span>
                    </button>
                    <p
                        class="tracking-widest uppercase text-[10px] text-white font-bold bg-white/5 px-3 py-1 rounded-md border border-white/5"
                    >
                        KEMNAKER RI
                    </p>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button
            v-show="showScrollTop"
            @click="scrollToTop"
            class="fixed bottom-6 right-6 w-10 h-10 bg-amber-400 hover:bg-amber-500 text-slate-900 rounded-xl flex items-center justify-center shadow-lg hover:shadow-amber-400/20 active:scale-95 transition-all z-50 cursor-pointer"
            title="Kembali ke Atas"
        >
            <i class="fas fa-chevron-up text-sm font-black"></i>
        </button>
    </footer>
</template>

<style scoped>
@keyframes wave-drift-right {
    0% {
        transform: translate3d(0, 0, 0) scaleY(1);
    }
    50% {
        transform: translate3d(-25%, 0, 0) scaleY(0.85) skewY(1deg);
    }
    100% {
        transform: translate3d(-50%, 0, 0) scaleY(1);
    }
}

@keyframes wave-drift-left {
    0% {
        transform: translate3d(-50%, 0, 0) scaleY(1);
    }
    50% {
        transform: translate3d(-25%, 0, 0) scaleY(0.9) skewY(-1deg);
    }
    100% {
        transform: translate3d(0, 0, 0) scaleY(1);
    }
}

.wave-layer-1 {
    animation: wave-drift-right 12s cubic-bezier(0.4, 0.45, 0.55, 0.6) infinite;
    opacity: 0.95;
}
.wave-layer-2 {
    animation: wave-drift-left 8s cubic-bezier(0.35, 0.45, 0.65, 0.7) infinite;
    opacity: 0.4;
}
.wave-layer-3 {
    animation: wave-drift-right 22s cubic-bezier(0.5, 0.5, 0.5, 0.5) infinite;
    opacity: 0.2;
}
</style>
