<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    berita_list: { type: Array, default: () => [] },
});

const searchQuery = ref("");
const currentTag = ref("all");

const parsedItems = computed(() => {
    return props.berita_list.map((item) => {
        let foto = item.file_foto;
        if (typeof foto === "string" && foto.startsWith("[")) {
            try {
                foto = JSON.parse(foto);
            } catch {
                /* ignore */
            }
        }
        const gambar = Array.isArray(foto) ? foto[0] : foto;

        let rawTags = item.tags ?? "Berita";
        let tagFinal = "Berita";
        if (
            typeof rawTags === "string" &&
            (rawTags.startsWith("[") || rawTags.startsWith("{"))
        ) {
            try {
                const parsed = JSON.parse(rawTags);
                tagFinal = Array.isArray(parsed) ? parsed[0] : rawTags;
            } catch {
                tagFinal = rawTags;
            }
        } else if (Array.isArray(rawTags)) {
            tagFinal = rawTags[0] ?? "Berita";
        } else {
            tagFinal = rawTags;
        }

        const date = item.created_at ? new Date(item.created_at) : null;
        const tanggal = date
            ? date.toLocaleDateString("id-ID", {
                  day: "numeric",
                  month: "long",
                  year: "numeric",
              })
            : "Baru saja";
        const cleanContent = (item.konten_berita || "").replace(
            /<[^>]*>?/gm,
            "",
        );

        return {
            id: item.id,
            judul: item.judul_berita,
            tag: String(tagFinal).trim().toLowerCase(),
            tag_label: String(tagFinal).trim(),
            ringkasan:
                cleanContent.length > 120
                    ? cleanContent.substring(0, 120) + "..."
                    : cleanContent,
            gambar_url: gambar ? `/storage/${gambar}` : null,
            tanggal: tanggal,
            url: `/berita-informasi/berita/${item.id}`,
        };
    });
});

const availableTags = computed(() => {
    const tagsSet = new Set();
    parsedItems.value.forEach((item) => {
        if (item.tag_label) tagsSet.add(item.tag_label);
    });
    return Array.from(tagsSet);
});

const filteredItems = computed(() => {
    return parsedItems.value.filter((item) => {
        const matchesTag =
            currentTag.value === "all" ||
            item.tag_label.toLowerCase() === currentTag.value.toLowerCase();
        const matchesSearch =
            item.judul
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase()) ||
            item.ringkasan
                .toLowerCase()
                .includes(searchQuery.value.toLowerCase());
        return matchesTag && matchesSearch;
    });
});
</script>

<template>
    <AppLayout
        title="Berita & Pengumuman Terbaru"
        description="Kumpulan berita, pengumuman seleksi pelatihan vokasi, agenda kegiatan, dan kabar terkini seputar Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkep Kemnaker RI."
        :settings="settings"
    >
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
                    <span class="text-slate-500">Kabar Balai</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-blue-600">Berita</span>
                </nav>

                <div class="space-y-6">
                    <div class="border-b border-slate-200 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Informasi Terkini</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                        >
                            Berita & Kegiatan Balai
                        </h1>
                    </div>

                    <!-- Search & Filter Bar -->
                    <div
                        class="flex flex-col lg:flex-row lg:items-center justify-between gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/60 shadow-xs"
                    >
                        <div class="relative w-full lg:max-w-xs">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-slate-400 text-xs"
                            >
                                <i class="fas fa-search"></i>
                            </span>
                            <input
                                v-model="searchQuery"
                                type="text"
                                placeholder="Cari judul atau isi berita..."
                                class="w-full pl-9 pr-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-medium text-slate-800 placeholder-slate-400 focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 transition shadow-xs"
                            />
                        </div>

                        <div
                            class="flex flex-wrap items-center gap-1.5 select-none"
                        >
                            <span
                                class="text-[11px] font-bold text-slate-400 uppercase tracking-wider mr-1 hidden sm:inline-block"
                                >Filter Tags:</span
                            >
                            <button
                                @click="currentTag = 'all'"
                                :class="
                                    currentTag === 'all'
                                        ? 'bg-blue-600 text-white font-bold shadow-xs'
                                        : 'bg-white text-slate-600 font-semibold border border-slate-200 hover:bg-slate-100/80'
                                "
                                class="text-xs px-3.5 py-2 rounded-xl transition cursor-pointer"
                            >
                                🌐 Semua
                            </button>
                            <button
                                v-for="tag in availableTags"
                                :key="tag"
                                @click="currentTag = tag"
                                :class="
                                    currentTag === tag
                                        ? 'bg-blue-600 text-white font-bold shadow-xs'
                                        : 'bg-white text-slate-600 font-semibold border border-slate-200 hover:bg-slate-100/80'
                                "
                                class="text-xs px-3.5 py-2 rounded-xl transition cursor-pointer"
                            >
                                # {{ tag }}
                            </button>
                        </div>
                    </div>

                    <!-- Grid Berita -->
                    <div
                        v-if="filteredItems.length > 0"
                        class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <Link
                            v-for="item in filteredItems"
                            :key="item.id"
                            :href="item.url"
                            class="bg-white rounded-2xl overflow-hidden border border-slate-200/60 shadow-xs group flex flex-col justify-between transition hover:shadow-md"
                        >
                            <div>
                                <div
                                    class="aspect-video bg-slate-200 overflow-hidden relative border-b border-slate-100"
                                >
                                    <img
                                        v-if="item.gambar_url"
                                        :src="item.gambar_url"
                                        :alt="item.judul"
                                        class="w-full h-full object-cover group-hover:scale-105 transition duration-300"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full flex items-center justify-center text-slate-400 text-3xl"
                                    >
                                        <i class="fas fa-newspaper"></i>
                                    </div>
                                </div>
                                <div class="p-5 space-y-2">
                                    <span
                                        class="text-[9px] font-black uppercase tracking-wider text-blue-600 bg-blue-50 px-2.5 py-0.5 rounded-sm border border-blue-100/50 inline-block"
                                    >
                                        {{ item.tag_label }}
                                    </span>
                                    <h3
                                        class="font-extrabold text-slate-900 text-base leading-tight group-hover:text-blue-600 transition-colors line-clamp-2"
                                    >
                                        {{ item.judul }}
                                    </h3>
                                    <p
                                        class="text-xs text-slate-500 line-clamp-2 font-normal leading-relaxed"
                                    >
                                        {{ item.ringkasan }}
                                    </p>
                                </div>
                            </div>
                            <div
                                class="px-5 pb-5 pt-2 text-[11px] text-slate-400 font-medium select-none"
                            >
                                {{ item.tanggal }}
                            </div>
                        </Link>
                    </div>

                    <div
                        v-else
                        class="col-span-full bg-white rounded-3xl border border-dashed border-slate-200 p-16 text-center shadow-xs"
                    >
                        <div
                            class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-4 border border-slate-100"
                        >
                            <i class="fas fa-search"></i>
                        </div>
                        <h4 class="font-bold text-slate-800 text-base">
                            Berita Tidak Ditemukan
                        </h4>
                        <p
                            class="text-xs text-slate-400 max-w-sm mx-auto mt-1 leading-relaxed"
                        >
                            Tidak ada arsip berita yang sesuai dengan kata kunci
                            pencarian atau pilihan tag filter saat ini.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
