<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    pelayanan: { type: Object, default: () => ({}) },
});

const getStorageUrl = (path) => {
    if (!path) return "";
    return path.startsWith("http")
        ? path
        : path.startsWith("/")
          ? path
          : `/storage/${path}`;
};

const isPdfFile = (path) => {
    if (!path) return false;
    return path.split(".").pop().toLowerCase() === "pdf";
};

const isImageFile = (path) => {
    if (!path) return false;
    const ext = path.split(".").pop().toLowerCase();
    return ["jpg", "jpeg", "png", "webp", "gif", "avif"].includes(ext);
};

const getAlurList = () => {
    const raw = props.pelayanan?.alur_pelayanan;
    if (!raw) return [];
    if (typeof raw === "string") {
        try {
            return JSON.parse(raw);
        } catch {
            return [];
        }
    }
    return Array.isArray(raw) ? raw : [];
};

const hasMainAlur = () => {
    return Boolean(
        props.pelayanan?.foto_alur_pelayanan ||
        props.pelayanan?.deskripsi_alur_pelayanan,
    );
};
</script>

<template>
    <AppLayout
        title="Alur & Prosedur Pelayanan Publik"
        description="Bagan dan panduan alur prosedur pelayanan pendaftaran pelatihan vokasi, sertifikasi, konsultasi, dan PPID BPVP Pangkep."
        :settings="settings"
    >
        <main class="pt-32 pb-16 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav
                    class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-8 uppercase tracking-wider select-none"
                >
                    <Link href="/" class="hover:text-blue-600 transition-colors"
                        >Home</Link
                    >
                    <i class="fas fa-chevron-right text-[9px]"></i
                    ><span class="text-slate-500">Pelayanan</span>
                    <i class="fas fa-chevron-right text-[9px]"></i
                    ><span class="text-blue-600">Alur Pelayanan</span>
                </nav>

                <div
                    class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                >
                    <div class="border-b border-slate-100 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Prosedur</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                        >
                            Alur Mekanisme Pelayanan
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Diagram tahapan prosedur pelaksanaan pelayanan
                            publik terpadu di lingkungan balai.
                        </p>
                    </div>

                    <div class="space-y-10">
                        <!-- 1. Bagan Alur Utama dari Database (foto_alur_pelayanan & deskripsi_alur_pelayanan) -->
                        <div
                            v-if="hasMainAlur()"
                            class="p-6 sm:p-8 bg-slate-50 rounded-2xl border border-slate-200/60 space-y-6 shadow-xs"
                        >
                            <!-- PDF Document Embedded -->
                            <div
                                v-if="
                                    pelayanan.foto_alur_pelayanan &&
                                    isPdfFile(pelayanan.foto_alur_pelayanan)
                                "
                                class="w-full rounded-2xl border border-slate-200 bg-white overflow-hidden shadow-xs p-4 sm:p-5 space-y-3"
                            >
                                <div
                                    class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3"
                                >
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-sm font-bold shrink-0"
                                        >
                                            <i class="fas fa-file-pdf"></i>
                                        </div>
                                        <div>
                                            <span
                                                class="text-xs font-bold text-slate-800 block"
                                                >Bagan Alur Prosedur Pelayanan
                                                (PDF)</span
                                            >
                                            <span
                                                class="text-[11px] text-slate-400 font-mono"
                                                >{{
                                                    pelayanan.foto_alur_pelayanan
                                                        .split("/")
                                                        .pop()
                                                }}</span
                                            >
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <a
                                            :href="
                                                getStorageUrl(
                                                    pelayanan.foto_alur_pelayanan,
                                                )
                                            "
                                            target="_blank"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all"
                                        >
                                            <i
                                                class="fas fa-external-link-alt text-[10px]"
                                            ></i>
                                            Buka Tab Baru
                                        </a>
                                        <a
                                            :href="
                                                getStorageUrl(
                                                    pelayanan.foto_alur_pelayanan,
                                                )
                                            "
                                            download
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                                        >
                                            <i
                                                class="fas fa-download text-[10px]"
                                            ></i>
                                            Unduh Dokumen
                                        </a>
                                    </div>
                                </div>
                                <div
                                    class="w-full bg-slate-100 rounded-xl overflow-hidden min-h-[500px] h-[750px] sm:h-[900px] border border-slate-200/60"
                                >
                                    <iframe
                                        :src="
                                            getStorageUrl(
                                                pelayanan.foto_alur_pelayanan,
                                            ) + '#toolbar=1'
                                        "
                                        class="w-full h-full border-0 rounded-xl"
                                        title="Bagan Alur Pelayanan"
                                    ></iframe>
                                </div>
                            </div>

                            <!-- Image Bagan Alur -->
                            <div
                                v-else-if="pelayanan.foto_alur_pelayanan"
                                class="w-full overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-3 shadow-xs"
                            >
                                <img
                                    :src="
                                        getStorageUrl(
                                            pelayanan.foto_alur_pelayanan,
                                        )
                                    "
                                    alt="Bagan Alur Pelayanan"
                                    class="max-w-full h-auto object-contain max-h-[850px] rounded-xl mx-auto"
                                />
                            </div>

                            <!-- Deskripsi Alur Pelayanan -->
                            <div
                                v-if="pelayanan.deskripsi_alur_pelayanan"
                                class="bg-white p-6 sm:p-8 rounded-2xl border border-slate-200/60 text-xs sm:text-sm text-slate-600 leading-relaxed text-justify prose prose-slate max-w-none shadow-2xs"
                                v-html="pelayanan.deskripsi_alur_pelayanan"
                            ></div>
                        </div>

                        <!-- 2. Legacy / Multi-step Alur jika ada -->
                        <template v-else-if="getAlurList().length > 0">
                            <div
                                v-for="(item, index) in getAlurList()"
                                :key="index"
                                class="p-6 bg-slate-50 rounded-2xl border border-slate-200/50 space-y-6 hover:border-blue-500/20 transition-all"
                            >
                                <div
                                    class="flex items-center gap-3 border-b border-slate-200/60 pb-3"
                                >
                                    <div
                                        class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base shrink-0"
                                    >
                                        <i class="fas fa-route"></i>
                                    </div>
                                    <h3
                                        class="font-extrabold text-slate-900 text-sm sm:text-base"
                                    >
                                        {{
                                            item.judul_alur ||
                                            "Langkah Pelayanan"
                                        }}
                                    </h3>
                                </div>

                                <div
                                    v-if="item.foto_alur"
                                    class="w-full overflow-hidden rounded-xl border border-slate-200/80 bg-white p-2 shadow-xs"
                                >
                                    <img
                                        :src="getStorageUrl(item.foto_alur)"
                                        :alt="item.judul_alur"
                                        class="max-w-full h-auto object-contain max-h-[800px] rounded-lg mx-auto"
                                    />
                                </div>

                                <div
                                    v-if="item.deskripsi_alur"
                                    class="bg-white p-5 rounded-xl border border-slate-200/40 text-xs sm:text-sm text-slate-600 leading-relaxed text-justify prose prose-slate max-w-none shadow-2xs"
                                    v-html="item.deskripsi_alur"
                                ></div>
                            </div>
                        </template>

                        <div v-else class="text-center py-12">
                            <div
                                class="w-16 h-16 bg-slate-50 text-slate-400 flex items-center justify-center text-2xl rounded-2xl mx-auto mb-4"
                            >
                                <i class="fas fa-map-signs"></i>
                            </div>
                            <h3 class="text-sm font-bold text-slate-800">
                                Data Belum Tersedia
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">
                                Bagan alur prosedur mekanisme pelayanan belum
                                dikonfigurasi di panel admin.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
