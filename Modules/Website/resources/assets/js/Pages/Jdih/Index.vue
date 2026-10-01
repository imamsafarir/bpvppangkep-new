<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    jdih_list: { type: Array, default: () => [] },
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

const filteredItems = computed(() => {
    let list = (props.jdih_list || []).map((item) => ({
        id: item.id,
        nomor: item.nomor_peraturan,
        nama: item.judul_peraturan,
        status: item.status_peraturan,
        deskripsi: item.tentang || "Tidak ada catatan penjelasan tambahan.",
        tanggal_raw: item.created_at ? new Date(item.created_at).getTime() : 0,
        tanggal_formatted: item.tanggal_formatted || item.created_at || "-",
        url_lihat: getStorageUrl(item.file_dokumen || item.file_path),
        url_unduh: item.download_url || "/jdih/download/" + item.id,
    }));

    if (search.value.trim()) {
        const q = search.value.toLowerCase();
        list = list.filter(
            (item) =>
                (item.nama && item.nama.toLowerCase().includes(q)) ||
                (item.nomor && item.nomor.toLowerCase().includes(q)) ||
                (item.deskripsi && item.deskripsi.toLowerCase().includes(q)),
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
        title="JDIH - Jaringan Dokumentasi & Informasi Hukum"
        description="Portal JDIH BPVP Pangkep Kemnaker RI. Akses dan unduh dokumen peraturan ketenagakerjaan, keputusan menteri, dan regulasi pelatihan vokasi."
        :settings="settings"
    >
        <main class="pt-32 pb-16 min-h-screen bg-slate-50/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- Navigation Breadcrumbs -->
                <nav
                    class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-8 uppercase tracking-wider select-none"
                >
                    <Link href="/" class="hover:text-blue-600 transition-colors"
                        >Home</Link
                    >
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-blue-600">JDIH</span>
                </nav>

                <div
                    class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                >
                    <!-- Header & Toolbar -->
                    <div
                        class="border-b border-slate-100 pb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-4"
                    >
                        <div>
                            <span
                                class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                                >Produk Hukum</span
                            >
                            <h1
                                class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight"
                            >
                                Jaringan Dokumentasi dan Informasi Hukum (JDIH)
                            </h1>
                            <p class="text-xs text-slate-500 mt-1">
                                Pusat dokumentasi peraturan, regulasi, dan
                                kebijakan resmi terkait ketenagakerjaan dan
                                operasional balai.
                            </p>
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
                                    placeholder="Cari nomor atau judul regulasi..."
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

                    <!-- Tabel Data -->
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
                                        Informasi Dokumen Peraturan / Keputusan
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
                                    <td class="py-4 px-6 space-y-2.5">
                                        <!-- Status & Nomor -->
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <span
                                                v-if="item.status === 'berlaku'"
                                                class="text-[9px] bg-emerald-50 text-emerald-700 border border-emerald-200/60 font-black px-2 py-0.5 rounded-sm uppercase tracking-wide"
                                            >
                                                Masih Berlaku
                                            </span>
                                            <span
                                                v-else-if="
                                                    item.status ===
                                                    'tidak_berlaku'
                                                "
                                                class="text-[9px] bg-rose-50 text-rose-700 border border-rose-200/60 font-black px-2 py-0.5 rounded-sm uppercase tracking-wide"
                                            >
                                                Dicabut / Tidak Berlaku
                                            </span>

                                            <div
                                                class="text-[11px] font-mono text-slate-500 font-bold bg-slate-100 px-2 py-0.5 rounded-sm border border-slate-200/50"
                                            >
                                                <span
                                                    class="text-slate-400 font-sans font-medium"
                                                    >No:</span
                                                >
                                                {{ item.nomor }}
                                            </div>
                                        </div>

                                        <!-- Judul -->
                                        <div
                                            class="font-extrabold text-slate-900 text-sm sm:text-base leading-snug tracking-tight"
                                        >
                                            {{ item.nama }}
                                        </div>

                                        <!-- Tentang -->
                                        <div
                                            class="bg-slate-50/70 rounded-xl p-3 border border-slate-200/40 space-y-1"
                                        >
                                            <span
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block"
                                                >Tentang Peraturan:</span
                                            >
                                            <p
                                                class="text-[11px] sm:text-xs text-slate-600 font-normal leading-relaxed line-clamp-3 whitespace-normal"
                                            >
                                                {{ item.deskripsi }}
                                            </p>
                                        </div>
                                    </td>
                                    <td
                                        class="py-4 px-6 text-slate-500 font-medium"
                                    >
                                        {{ item.tanggal_formatted }}
                                    </td>
                                    <td class="py-4 px-6 text-center">
                                        <div
                                            class="flex items-center justify-center gap-2"
                                        >
                                            <a
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
                                                :href="item.url_unduh"
                                                class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-600 text-blue-600 hover:text-white rounded-lg font-bold transition-all text-[11px]"
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

                    <!-- Empty fallback -->
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
                            Tidak Ada Dokumen JDIH
                        </h4>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{
                                search
                                    ? "Tidak ada produk hukum yang cocok dengan kriteria pencarian."
                                    : "Daftar berkas produk hukum/regulasi belum diunggah."
                            }}
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
