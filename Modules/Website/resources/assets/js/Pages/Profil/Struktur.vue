<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    profil: { type: Object, default: () => ({}) },
});

const getStorageUrl = (path) => {
    if (!path) return "";
    return path.startsWith("http")
        ? path
        : path.startsWith("/")
          ? path
          : `/storage/${path}`;
};

const isPdf = (path) => {
    if (!path) return false;
    return path.split(".").pop().toLowerCase() === "pdf";
};
</script>

<template>
    <Head title="Struktur Organisasi" />
    <AppLayout :settings="settings">
        <main class="pt-32 pb-16 min-h-screen">
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
                    <span class="text-blue-600">Struktur Organisasi</span>
                </nav>

                <div
                    class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                >
                    <div
                        class="border-b border-slate-100 pb-5 flex flex-col sm:flex-row justify-between sm:items-end gap-4"
                    >
                        <div>
                            <span
                                class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                                >Bagan Birokrasi</span
                            >
                            <h1
                                class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight"
                            >
                                Struktur Organisasi Balai
                            </h1>
                        </div>
                        <a
                            v-if="profil && profil.struktur_organisasi"
                            :href="getStorageUrl(profil.struktur_organisasi)"
                            target="_blank"
                            class="bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold px-4 py-2.5 rounded-xl transition-all shadow-xs shrink-0 text-center"
                        >
                            <i class="fas fa-download mr-1"></i> Unduh Dokumen
                            Bagan
                        </a>
                    </div>

                    <div
                        class="flex justify-center bg-slate-50/50 rounded-2xl border border-slate-100 p-4"
                    >
                        <template v-if="profil && profil.struktur_organisasi">
                            <div
                                v-if="isPdf(profil.struktur_organisasi)"
                                class="w-full min-h-[600px] h-[850px] rounded-xl overflow-hidden border border-slate-200 shadow-xs"
                            >
                                <iframe
                                    :src="
                                        getStorageUrl(
                                            profil.struktur_organisasi,
                                        ) + '#toolbar=1'
                                    "
                                    class="w-full h-full border-0 rounded-xl bg-white"
                                    title="Bagan Struktur Organisasi"
                                ></iframe>
                            </div>
                            <img
                                v-else
                                :src="getStorageUrl(profil.struktur_organisasi)"
                                alt="Bagan Struktur Organisasi BPVP Pangkep"
                                class="max-w-full h-auto rounded-xl shadow-xs"
                            />
                        </template>
                        <div
                            v-else
                            class="text-center py-16 space-y-2 text-slate-400"
                        >
                            <div class="text-4xl text-slate-300">
                                <i class="fas fa-sitemap"></i>
                            </div>
                            <p class="italic text-xs">
                                File bagan struktur organisasi belum diunggah.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
