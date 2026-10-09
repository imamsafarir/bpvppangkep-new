<script setup>
import { Head, Link } from "@inertiajs/vue3";
import { ref } from "vue";
import AppLayout from "../../Layouts/AppLayout.vue";
import PdfRenderer from "../../Components/PdfRenderer.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    pelayanan: { type: Object, default: () => ({}) },
});

const fullscreenIndex = ref(null);

const toggleFullscreen = (index) => {
    if (fullscreenIndex.value === index) {
        fullscreenIndex.value = null;
    } else {
        fullscreenIndex.value = index;
    }
};

const getMaklumatList = () => {
    const raw = props.pelayanan?.maklumat_pelayanan;
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

const getStorageUrl = (path) => {
    if (!path) return "";
    return path.startsWith("http")
        ? path
        : path.startsWith("/")
          ? path
          : `/storage/${path}`;
};

const getPdfUrl = (path) => {
    const base = getStorageUrl(path);
    if (!base) return "";
    return `${base}#toolbar=0&navpanes=0&scrollbar=0&view=FitH`;
};

const isImageFile = (path) => {
    if (!path) return false;
    const ext = path.split(".").pop().toLowerCase();
    return ["jpg", "jpeg", "png", "webp", "gif", "avif"].includes(ext);
};

const isPdfFile = (path) => {
    if (!path) return false;
    return path.split(".").pop().toLowerCase() === "pdf";
};

const getFileName = (path) => {
    if (!path) return "";
    return path.split("/").pop().split("\\").pop();
};
</script>

<template>
    <AppLayout
        title="Maklumat Pelayanan Publik"
        description="Maklumat resmi komitmen pelayanan prima Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan Kepulauan, Kemnaker RI."
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
                    ><span class="text-blue-600">Maklumat Pelayanan</span>
                </nav>

                <div class="space-y-6">
                    <div
                        class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                    >
                        <div class="border-b border-slate-100 pb-5">
                            <span
                                class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                                >Janji Layanan</span
                            >
                            <h1
                                class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                            >
                                Maklumat Pelayanan
                            </h1>
                            <p class="text-xs text-slate-500 mt-1">
                                Pernyataan tertulis mengenai kesanggupan dan
                                kewajiban memberikan pelayanan dengan standar
                                yang ditetapkan.
                            </p>
                        </div>

                        <div class="space-y-8">
                            <template v-if="getMaklumatList().length > 0">
                                <div
                                    v-for="(item, index) in getMaklumatList()"
                                    :key="index"
                                    class="p-6 bg-slate-50 rounded-2xl border border-slate-200/50 space-y-6 hover:border-blue-500/20 transition-all"
                                >
                                    <div
                                        class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200/60 pb-3"
                                    >
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-base shrink-0"
                                            >
                                                <i class="fas fa-scroll"></i>
                                            </div>
                                            <h3
                                                class="font-extrabold text-slate-900 text-sm sm:text-base"
                                            >
                                                {{
                                                    item.judul_maklumat ||
                                                    "Maklumat Pelayanan"
                                                }}
                                            </h3>
                                        </div>

                                        <a
                                            v-if="
                                                item.file_maklumat &&
                                                !isImageFile(item.file_maklumat)
                                            "
                                            :href="
                                                getStorageUrl(
                                                    item.file_maklumat,
                                                )
                                            "
                                            target="_blank"
                                            class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs shrink-0"
                                        >
                                            <i class="fas fa-file-pdf"></i>
                                            Lihat / Unduh PDF
                                        </a>
                                    </div>

                                    <!-- 1. Tampilan Dokumen PDF Resmi (Muncul Langsung di Halaman) -->
                                    <div
                                        v-if="
                                            item.file_maklumat &&
                                            isPdfFile(item.file_maklumat)
                                        "
                                        class="w-full rounded-2xl border border-slate-200/90 bg-white overflow-hidden shadow-xs space-y-3 p-3 sm:p-5"
                                    >
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3"
                                        >
                                            <div
                                                class="flex items-center gap-2.5"
                                            >
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-red-50 text-red-600 flex items-center justify-center text-sm font-bold shrink-0"
                                                >
                                                    <i
                                                        class="fas fa-file-pdf"
                                                    ></i>
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-xs font-bold text-slate-800 block"
                                                    >
                                                        Dokumen Resmi Maklumat
                                                        Pelayanan
                                                    </span>
                                                    <span
                                                        class="text-[11px] text-slate-400 font-mono"
                                                    >
                                                        {{
                                                            getFileName(
                                                                item.file_maklumat,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    @click="toggleFullscreen(index)"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all"
                                                >
                                                    <i
                                                        class="fas fa-expand text-[10px]"
                                                    ></i>
                                                    Layar Penuh
                                                </button>
                                                <a
                                                    :href="
                                                        getStorageUrl(
                                                            item.file_maklumat,
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
                                                            item.file_maklumat,
                                                        )
                                                    "
                                                    download
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                                                >
                                                    <i
                                                        class="fas fa-download text-[10px]"
                                                    ></i>
                                                    Unduh PDF
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Embed PDF Native Dynamic Renderer -->
                                        <div class="w-full">
                                            <PdfRenderer
                                                :url="getStorageUrl(item.file_maklumat)"
                                                :title="item.judul_maklumat || 'Dokumen Maklumat Pelayanan'"
                                            />
                                        </div>
                                    </div>

                                    <!-- 2. Tampilan Gambar Utama (Jika file adalah gambar) -->
                                    <div
                                        v-if="
                                            item.file_maklumat &&
                                            isImageFile(item.file_maklumat)
                                        "
                                        class="w-full overflow-hidden rounded-xl border border-slate-200/80 bg-white p-2 shadow-xs"
                                    >
                                        <img
                                            :src="
                                                getStorageUrl(
                                                    item.file_maklumat,
                                                )
                                            "
                                            :alt="item.judul_maklumat"
                                            class="w-full h-auto object-contain max-h-[800px] rounded-lg mx-auto"
                                        />
                                    </div>

                                    <!-- 3. Keterangan / Redaksi Komitmen Pelayanan -->
                                    <div
                                        v-if="item.keterangan_maklumat"
                                        class="bg-white p-5 sm:p-7 rounded-2xl border border-slate-200/60 text-xs sm:text-sm text-slate-600 leading-relaxed text-justify prose prose-slate max-w-none shadow-2xs"
                                        v-html="item.keterangan_maklumat"
                                    ></div>
                                </div>
                            </template>

                            <div v-else class="text-center py-12">
                                <div
                                    class="w-16 h-16 bg-slate-50 text-slate-400 flex items-center justify-center text-2xl rounded-2xl mx-auto mb-4"
                                >
                                    <i class="fas fa-comment-slash"></i>
                                </div>
                                <h3 class="text-sm font-bold text-slate-800">
                                    Data Belum Tersedia
                                </h3>
                                <p class="text-xs text-slate-400 mt-1">
                                    Dokumen maklumat pelayanan resmi belum diisi
                                    di panel admin.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
