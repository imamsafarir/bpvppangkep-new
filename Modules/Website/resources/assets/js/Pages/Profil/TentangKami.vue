<script setup>
import { Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

defineProps({
    settings: { type: Object, default: () => ({}) },
    profil: { type: Object, default: () => ({}) },
});
</script>

<template>
    <AppLayout title="Tentang Kami" :settings="settings">
        <main class="py-12 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Breadcrumbs -->
                <nav
                    class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-8 uppercase tracking-wider select-none"
                >
                    <Link href="/" class="hover:text-blue-600 transition-colors"
                        >Home</Link
                    >
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-slate-500">Profil</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-blue-600">Tentang Kami</span>
                </nav>

                <div
                    class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                >
                    <div class="border-b border-slate-100 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Profil Balai</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight"
                        >
                            Tentang Balai Pelatihan Vokasi dan Produktivitas
                            Pangkep
                        </h1>
                    </div>

                    <div
                        class="text-slate-600 text-sm sm:text-base leading-relaxed text-justify prose max-w-none"
                    >
                        <div
                            v-if="profil?.tentang_kami"
                            v-html="profil.tentang_kami"
                        ></div>
                        <div v-else class="text-center py-12 space-y-3">
                            <div class="text-slate-300 text-5xl">
                                <i class="fas fa-building-user"></i>
                            </div>
                            <p class="text-slate-400 italic">
                                Informasi profil "Tentang Kami" belum diisi di
                                panel admin.
                            </p>
                        </div>
                    </div>

                    <div
                        class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row justify-between items-center gap-4 text-xs font-semibold text-slate-400"
                    >
                        <div class="flex items-center gap-2">
                            <i class="fas fa-shield-halved text-blue-600"></i>
                            <span
                                >Informasi Terverifikasi &bull; BPVP
                                Pangkep</span
                            >
                        </div>
                        <span class="font-mono text-[10px]">
                            Pembaruan Terakhir:
                            {{
                                profil?.updated_at
                                    ? new Date(
                                          profil.updated_at,
                                      ).toLocaleDateString("id-ID")
                                    : "2026"
                            }}
                        </span>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
