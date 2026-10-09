<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    dokumen: { type: Array, default: () => [] },
});

const search = ref("");
const sortBy = ref("newest");

const getStorageUrl = (path) => {
    if (!path) return "";
    return path.startsWith("http")
        ? path
        : path.startsWith("/")
          ? path
          : `/storage/${path}`;
};

const stripHtml = (html) => {
    if (!html) return "";
    let text = html.replace(/<script[^>]*>([\S\s]*?)<\/script>/gmi, "");
    text = text.replace(/<style[^>]*>([\S\s]*?)<\/style>/gmi, "");
    text = text.replace(/<[^>]+>/gm, " ");
    text = text
        .replace(/&nbsp;/g, " ")
        .replace(/&amp;/g, "&")
        .replace(/&quot;/g, '"')
        .replace(/&#39;/g, "'")
        .replace(/&lt;/g, "<")
        .replace(/&gt;/g, ">");
    return text.replace(/\s+/g, " ").trim();
};

const selectedDoc = ref(null);
const isDetailModalOpen = ref(false);

const openDetailModal = (item) => {
    selectedDoc.value = item;
    isDetailModalOpen.value = true;
};

const closeDetailModal = () => {
    selectedDoc.value = null;
    isDetailModalOpen.value = false;
};

const filteredItems = computed(() => {
    let list = (props.dokumen || []).map((item) => {
        const rawDeskripsi = item.deskripsi || item.deskripsi_singkat || "";
        const cleanDeskripsi = stripHtml(rawDeskripsi);
        return {
            id: item.id,
            nama: item.nama_dokumen,
            deskripsi_raw: rawDeskripsi,
            deskripsi_clean: cleanDeskripsi,
            deskripsi_preview:
                cleanDeskripsi.length > 130
                    ? cleanDeskripsi.substring(0, 130) + "..."
                    : cleanDeskripsi,
            has_rich_content:
                Boolean(rawDeskripsi) &&
                (rawDeskripsi.includes("<") || cleanDeskripsi.length > 130),
            tanggal_raw: item.created_at ? new Date(item.created_at).getTime() : 0,
            tanggal_formatted: item.tanggal_formatted || item.created_at || "-",
            url_lihat: getStorageUrl(item.file_path),
            url_unduh: item.download_url || "/informasi-publik/download/" + item.id,
        };
    });

    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(
            (item) =>
                (item.nama && item.nama.toLowerCase().includes(q)) ||
                (item.deskripsi_clean && item.deskripsi_clean.toLowerCase().includes(q)),
        );
    }

    if (sortBy.value === "newest") {
        return list.sort((a, b) => b.tanggal_raw - a.tanggal_raw);
    } else {
        return list.sort((a, b) => a.tanggal_raw - b.tanggal_raw);
    }
});
</script>

<template>
    <AppLayout
        title="Informasi Publik Serta Merta"
        description="Dokumentasi dan pengumuman informasi publik serta merta PPID BPVP Pangkep mengenai keadaan darurat atau kepentingan hajat hidup publik."
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
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-slate-500">Informasi Publik</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-blue-600">Informasi Serta Merta</span>
                </nav>

                <div
                    class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                >
                    <div
                        class="border-b border-slate-100 pb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <span
                                class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                                >Keterbukaan Informasi</span
                            >
                            <h1
                                class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                            >
                                Informasi Yang Wajib Disediakan Serta Merta
                            </h1>
                        </div>

                        <div
                            class="flex flex-col sm:flex-row gap-3 w-full md:w-auto"
                        >
                            <div class="relative flex-1 sm:w-64">
                                <i
                                    class="fas fa-search absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"
                                ></i>
                                <input
                                    type="text"
                                    v-model="search"
                                    placeholder="Cari dokumen..."
                                    class="w-full text-xs pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:bg-white transition-all"
                                />
                            </div>
                            <div class="relative">
                                <i
                                    class="fas fa-sort-amount-down absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"
                                ></i>
                                <select
                                    v-model="sortBy"
                                    class="text-xs pl-10 pr-8 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:border-blue-500 focus:bg-white transition-all appearance-none cursor-pointer font-medium"
                                >
                                    <option value="newest">Terbaru</option>
                                    <option value="oldest">Terlama</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div
                        class="overflow-x-auto rounded-2xl border border-slate-100"
                        v-if="filteredItems.length > 0"
                    >
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr
                                    class="bg-slate-50 text-slate-500 text-[11px] font-bold uppercase tracking-wider border-b border-slate-100"
                                >
                                    <th class="py-4 px-6 w-12 text-center">
                                        No
                                    </th>
                                    <th class="py-4 px-6">
                                        Nama Dokumen / Informasi
                                    </th>
                                    <th class="py-4 px-6 w-40">
                                        Tanggal Unggah
                                    </th>
                                    <th class="py-4 px-6 w-44 text-center">
                                        Aksi
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="text-xs divide-y divide-slate-50">
                                <tr
                                    v-for="(item, index) in filteredItems"
                                    :key="item.id"
                                    class="hover:bg-slate-50/80 transition-colors"
                                >
                                    <td
                                        class="py-4 px-6 text-center font-medium text-slate-400"
                                    >
                                        {{ index + 1 }}
                                    </td>
                                    <td class="py-4 px-6 space-y-1">
                                        <div class="font-bold text-slate-900 text-sm leading-snug">
                                            {{ item.nama }}
                                        </div>
                                        <div v-if="item.deskripsi_clean" class="text-xs text-slate-500 leading-relaxed">
                                            {{ item.deskripsi_preview }}
                                            <button
                                                v-if="item.has_rich_content"
                                                type="button"
                                                @click="openDetailModal(item)"
                                                class="inline-flex items-center gap-1 ml-1 text-blue-600 hover:text-blue-700 font-bold hover:underline cursor-pointer"
                                            >
                                                <span>Baca Selengkapnya</span>
                                                <i class="fas fa-arrow-right text-[9px]"></i>
                                            </button>
                                        </div>
                                        <div v-else class="text-xs text-slate-400 italic">
                                            Tidak ada deskripsi tambahan
                                        </div>
                                    </td>
                                    <td
                                        class="py-4 px-6 text-slate-500 font-medium whitespace-nowrap"
                                    >
                                        {{ item.tanggal_formatted }}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <button
                                                v-if="item.has_rich_content"
                                                type="button"
                                                @click="openDetailModal(item)"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-700 rounded-lg font-bold transition-all text-[11px]"
                                                title="Lihat Keterangan Rinci"
                                            >
                                                <i class="fas fa-file-alt text-[10px]"></i>
                                                Detail
                                            </button>
                                            <a
                                                v-if="item.url_lihat"
                                                :href="item.url_lihat"
                                                target="_blank"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg font-bold transition-all text-[11px]"
                                            >
                                                <i
                                                    class="fas fa-eye text-[10px]"
                                                ></i>
                                                Lihat
                                            </a>
                                            <a
                                                v-if="item.url_unduh"
                                                :href="item.url_unduh"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-bold transition-all text-[11px] shadow-2xs"
                                            >
                                                <i
                                                    class="fas fa-download text-[10px]"
                                                ></i>
                                                Unduh
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div
                        v-else
                        class="text-center py-12 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200"
                    >
                        <div
                            class="w-12 h-12 rounded-xl bg-slate-100 text-slate-400 flex items-center justify-center text-lg mx-auto mb-3"
                        >
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <h4 class="font-bold text-slate-800 text-sm">
                            Tidak Ada Dokumen
                        </h4>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{
                                search
                                    ? "Tidak ada dokumen yang cocok dengan kata kunci."
                                    : "Daftar berkas informasi belum diunggah."
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </main>

        <!-- MODAL DETAIL DOKUMEN & DESKRIPSI LENGKAP -->
        <div
            v-if="isDetailModalOpen && selectedDoc"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs animate-in fade-in duration-200"
            @click.self="closeDetailModal"
        >
            <div
                class="bg-white rounded-3xl shadow-2xl border border-slate-100 w-full max-w-2xl max-h-[85vh] flex flex-col overflow-hidden animate-in zoom-in-95 duration-200"
            >
                <div class="p-6 border-b border-slate-100 flex items-start justify-between gap-4 bg-slate-50/50">
                    <div class="space-y-1">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-1 rounded-md">
                            Informasi Publik
                        </span>
                        <h3 class="text-base sm:text-lg font-black text-slate-900 leading-snug">
                            {{ selectedDoc.nama }}
                        </h3>
                        <p class="text-xs text-slate-400">
                            Diunggah pada: {{ selectedDoc.tanggal_formatted }}
                        </p>
                    </div>
                    <button
                        type="button"
                        @click="closeDetailModal"
                        class="w-8 h-8 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-500 hover:text-slate-800 flex items-center justify-center transition-colors shrink-0 cursor-pointer"
                    >
                        <i class="fas fa-times text-sm"></i>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-4">
                    <div>
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                            Keterangan & Uraian Dokumen
                        </h4>
                        <!-- Render HTML Rich Text Content -->
                        <div
                            class="rich-text-content prose prose-slate max-w-none text-xs sm:text-sm text-slate-700 bg-slate-50/60 p-4 rounded-2xl border border-slate-100"
                            v-html="selectedDoc.deskripsi_raw || '<p class=\'text-slate-400 italic\'>Tidak ada deskripsi tambahan.</p>'"
                        ></div>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 border-t border-slate-100 flex items-center justify-between gap-3">
                    <button
                        type="button"
                        @click="closeDetailModal"
                        class="px-4 py-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 rounded-xl text-xs font-bold transition-all cursor-pointer"
                    >
                        Tutup
                    </button>
                    <div class="flex items-center gap-2">
                        <a
                            v-if="selectedDoc.url_lihat"
                            :href="selectedDoc.url_lihat"
                            target="_blank"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all"
                        >
                            <i class="fas fa-eye text-xs"></i>
                            Buka Berkas
                        </a>
                        <a
                            v-if="selectedDoc.url_unduh"
                            :href="selectedDoc.url_unduh"
                            class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs"
                        >
                            <i class="fas fa-download text-xs"></i>
                            Unduh Dokumen
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
