<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

defineProps({
    settings: { type: Object, default: () => ({}) },
    profil: { type: Object, default: () => ({}) },
});
</script>

<template>
    <AppLayout
        title="Profil PPID (Pejabat Pengelola Informasi & Dokumentasi)"
        description="Layanan Pejabat Pengelola Informasi dan Dokumentasi (PPID) BPVP Pangkep. Komitmen keterbukaan informasi publik dan akuntabilitas pemerintah."
        :settings="settings"
    >
        <main class="py-12 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav
                    class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-8 uppercase tracking-wider select-none"
                >
                    <Link href="/" class="hover:text-blue-600 transition-colors"
                        >Home</Link
                    >
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-slate-500">Profil</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-blue-600">PPID</span>
                </nav>

                <div
                    class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                >
                    <div class="border-b border-slate-100 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Layanan Informasi</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight"
                        >
                            Pejabat Pengelola Informasi dan Dokumentasi (PPID)
                        </h1>
                    </div>

                    <div
                        class="text-slate-600 text-sm sm:text-base leading-relaxed text-justify prose max-w-none"
                    >
                        <div v-if="profil?.ppid" v-html="profil.ppid"></div>
                        <div
                            v-else
                            class="text-center py-12 text-slate-400 italic"
                        >
                            <i
                                class="fas fa-circle-info text-3xl text-slate-300 block mb-2"
                            ></i>
                            Konten informasi layanan PPID belum diisi.
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
