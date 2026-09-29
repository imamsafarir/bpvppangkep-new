<script setup>
import { ref, onMounted, computed } from "vue";
import { pwaState, promptInstall, updateApp } from "@/pwa";
import {
    Download,
    X,
    Sparkles,
    RefreshCw,
    Share2,
    PlusSquare,
    Smartphone,
    Check,
} from "lucide-vue-next";

const isDismissed = ref(false);
const showIosModal = ref(false);

onMounted(() => {
    // Check if dismissed recently (in last 3 days)
    const dismissedTime = localStorage.getItem("bpvp_pwa_dismissed");
    if (dismissedTime) {
        const diff = Date.now() - parseInt(dismissedTime, 10);
        if (diff < 3 * 24 * 60 * 60 * 1000) {
            isDismissed.value = true;
        }
    }

    window.addEventListener("bpvp:open-pwa-install", () => {
        isDismissed.value = false;
        if (pwaState.isIos) {
            showIosModal.value = true;
        } else if (pwaState.canInstall) {
            handleInstall();
        } else if (pwaState.isStandalone) {
            alert(
                "Aplikasi BPVP Pangkep - Super APP sudah terpasang dan sedang berjalan!",
            );
        } else {
            // Android / Desktop fallback if prompt already fired or not ready
            showIosModal.value = true;
        }
    });
});

const dismissPrompt = () => {
    isDismissed.value = true;
    localStorage.setItem("bpvp_pwa_dismissed", Date.now().toString());
};

const handleInstall = async () => {
    if (pwaState.isIos) {
        showIosModal.value = true;
        return;
    }
    const installed = await promptInstall();
    if (installed) {
        isDismissed.value = true;
    }
};

const shouldShowBanner = computed(() => {
    if (pwaState.isStandalone || pwaState.isInstalled) return false;
    if (isDismissed.value) return false;
    // Show on Android/Chrome/Edge if canInstall is true, or on iOS if not standalone
    return pwaState.canInstall || (pwaState.isIos && !pwaState.isStandalone);
});
</script>

<template>
    <div>
        <!-- 1. UPDATE READY TOAST -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="transform translate-y-4 opacity-0"
            enter-to-class="transform translate-y-0 opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100"
            leave-to-class="transform translate-y-4 opacity-0"
        >
            <div
                v-if="pwaState.hasUpdate"
                class="fixed top-5 right-5 z-50 max-w-sm bg-gradient-to-r from-blue-900 to-indigo-950 text-white p-4 rounded-2xl shadow-2xl border border-blue-500/30 flex items-center gap-3.5 backdrop-blur-md"
            >
                <div
                    class="p-2.5 bg-blue-500/20 rounded-xl text-blue-300 shrink-0"
                >
                    <RefreshCw class="w-5 h-5 animate-spin" />
                </div>
                <div class="flex-1 text-xs">
                    <div class="font-bold text-white text-sm">
                        Pembaruan Tersedia
                    </div>
                    <p class="text-blue-200/90 text-[11px] mt-0.5">
                        Versi baru BPVP Super APP telah siap digunakan.
                    </p>
                </div>
                <button
                    @click="updateApp"
                    class="bg-blue-500 hover:bg-blue-600 text-white font-semibold text-xs px-3 py-1.5 rounded-lg shrink-0 cursor-pointer transition shadow-md"
                >
                    Muat Ulang
                </button>
            </div>
        </transition>

        <!-- 2. FLOATING INSTALL BANNER -->
        <transition
            enter-active-class="transition duration-400 ease-out"
            enter-from-class="transform translate-y-8 opacity-0 scale-95"
            enter-to-class="transform translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="transform translate-y-0 opacity-100 scale-100"
            leave-to-class="transform translate-y-8 opacity-0 scale-95"
        >
            <div
                v-if="shouldShowBanner"
                class="fixed bottom-5 left-4 right-4 sm:left-auto sm:right-6 sm:max-w-md z-40 bg-white/95 backdrop-blur-md rounded-2xl p-4 sm:p-4.5 border border-blue-200/80 shadow-2xl text-slate-800 transition-all hover:border-blue-300"
                style="box-shadow: 0 20px 40px -10px rgba(10, 46, 80, 0.25)"
            >
                <div class="flex items-start gap-3.5">
                    <!-- App Icon -->
                    <div class="relative shrink-0">
                        <img
                            src="/icons/icon-192x192.png"
                            alt="BPVP Pangkep Super APP"
                            class="w-12 h-12 rounded-xl object-cover shadow-md border border-slate-100"
                        />
                        <div
                            class="absolute -bottom-1 -right-1 bg-amber-500 text-white text-[9px] font-black px-1.5 py-0.5 rounded-full uppercase tracking-wider shadow"
                        >
                            APP
                        </div>
                    </div>

                    <!-- App Info -->
                    <div class="flex-1 min-w-0 pr-4">
                        <div class="flex items-center gap-1.5 flex-wrap">
                            <h4
                                class="font-bold text-sm text-slate-900 leading-tight"
                            >
                                BPVP Pangkep
                            </h4>
                            <span
                                class="inline-flex items-center gap-1 bg-blue-100 text-blue-800 text-[10px] font-bold px-2 py-0.5 rounded-full"
                            >
                                <Sparkles class="w-2.5 h-2.5 text-blue-600" />
                                Super APP
                            </span>
                        </div>
                        <p
                            class="text-xs text-slate-500 mt-1 leading-snug line-clamp-2"
                        >
                            Pasang di layar utama perangkat untuk akses cepat,
                            notifikasi, dan fitur offline resmi BPVP Pangkep.
                        </p>
                    </div>

                    <!-- Close button -->
                    <button
                        @click="dismissPrompt"
                        class="text-slate-400 hover:text-slate-600 p-1 -mr-1 -mt-1 rounded-lg hover:bg-slate-100 transition cursor-pointer"
                        title="Tutup banner"
                    >
                        <X class="w-4 h-4" />
                    </button>
                </div>

                <!-- Action Buttons -->
                <div
                    class="mt-3.5 pt-3 border-t border-slate-100 flex items-center justify-end gap-2"
                >
                    <button
                        @click="dismissPrompt"
                        class="text-xs font-medium text-slate-500 hover:text-slate-700 px-3 py-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer"
                    >
                        Nanti Saja
                    </button>
                    <button
                        @click="handleInstall"
                        class="inline-flex items-center gap-1.5 bg-gradient-to-r from-blue-900 to-indigo-900 hover:from-blue-800 hover:to-indigo-800 text-white font-semibold text-xs px-4 py-2 rounded-xl shadow-md shadow-blue-900/20 transition cursor-pointer active:scale-95"
                    >
                        <Download class="w-3.5 h-3.5" />
                        Pasang Aplikasi
                    </button>
                </div>
            </div>
        </transition>

        <!-- 3. IOS INSTALL INSTRUCTIONS MODAL -->
        <transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="showIosModal"
                class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-end sm:items-center justify-center p-4"
                @click.self="showIosModal = false"
            >
                <div
                    class="bg-white w-full max-w-sm rounded-3xl p-6 text-slate-800 shadow-2xl animate-in slide-in-from-bottom-4 duration-300"
                >
                    <div
                        class="flex items-center justify-between pb-3 border-b border-slate-100"
                    >
                        <div class="flex items-center gap-2.5">
                            <img
                                src="/icons/icon-192x192.png"
                                alt="Logo"
                                class="w-9 h-9 rounded-lg shadow-xs"
                            />
                            <div>
                                <h3 class="font-bold text-sm text-slate-900">
                                    Pasang di iPhone / iPad
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    BPVP Pangkep - Super APP
                                </p>
                            </div>
                        </div>
                        <button
                            @click="showIosModal = false"
                            class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition cursor-pointer"
                        >
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="mt-4 space-y-3.5 text-xs text-slate-600">
                        <div
                            class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl"
                        >
                            <div
                                class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 font-bold flex items-center justify-center text-[11px] shrink-0 mt-0.5"
                            >
                                1
                            </div>
                            <p class="leading-relaxed">
                                Tekan tombol <strong>Bagikan (Share)</strong>
                                <span
                                    class="inline-flex items-center justify-center px-1.5 py-0.5 bg-white border border-slate-200 rounded text-blue-600 mx-1"
                                >
                                    <Share2 class="w-3 h-3" />
                                </span>
                                di bagian bawah layar browser Safari Anda.
                            </p>
                        </div>

                        <div
                            class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl"
                        >
                            <div
                                class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 font-bold flex items-center justify-center text-[11px] shrink-0 mt-0.5"
                            >
                                2
                            </div>
                            <p class="leading-relaxed">
                                Gulir ke bawah lalu pilih menu
                                <strong
                                    >"Tambahkan ke Layar Utama" (Add to Home
                                    Screen)</strong
                                >
                                <span
                                    class="inline-flex items-center justify-center px-1.5 py-0.5 bg-white border border-slate-200 rounded text-slate-700 mx-1"
                                >
                                    <PlusSquare class="w-3 h-3" /> </span
                                >.
                            </p>
                        </div>

                        <div
                            class="flex items-start gap-3 p-3 bg-slate-50 rounded-xl"
                        >
                            <div
                                class="w-6 h-6 rounded-full bg-blue-100 text-blue-800 font-bold flex items-center justify-center text-[11px] shrink-0 mt-0.5"
                            >
                                3
                            </div>
                            <p class="leading-relaxed">
                                Ketuk <strong>"Tambah" (Add)</strong> di pojok
                                kanan atas. Ikon aplikasi akan langsung muncul
                                di beranda iPhone Anda!
                            </p>
                        </div>
                    </div>

                    <button
                        @click="showIosModal = false"
                        class="w-full mt-5 bg-blue-900 hover:bg-blue-800 text-white font-semibold text-xs py-3 rounded-xl transition cursor-pointer"
                    >
                        Saya Mengerti
                    </button>
                </div>
            </div>
        </transition>
    </div>
</template>
