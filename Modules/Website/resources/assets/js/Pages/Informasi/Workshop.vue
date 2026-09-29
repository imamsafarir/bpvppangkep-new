<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

defineProps({
    settings: { type: Object, default: () => ({}) },
    workshop: { type: Array, default: () => [] },
});

const openIndex = ref(null);

const stripTags = (html) => {
    if (!html) return "";
    return html.replace(/<[^>]*>/g, "");
};

const closeModal = () => {
    openIndex.value = null;
};
</script>

<template>
    <Head title="Ruang Kelas & Workshop" />
    <AppLayout :settings="settings">
        <main class="pt-32 pb-16 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <!-- BREADCRUMB -->
                <nav
                    class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-8 uppercase tracking-wider select-none"
                >
                    <Link href="/" class="hover:text-blue-600 transition-colors"
                        >Home</Link
                    >
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-slate-500">Informasi</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-blue-600"
                        >Ruang Kelas &amp; Workshop</span
                    >
                </nav>

                <div class="space-y-8">
                    <!-- HEADER -->
                    <div class="border-b border-slate-200 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Prasarana Utama</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                        >
                            Ruang Kelas &amp; Workshop Pelatihan
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Daftar fasilitas ruang teori dan area praktik
                            kompetensi kerja di lingkungan kampus.
                        </p>
                    </div>

                    <!-- GRID -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6"
                    >
                        <template v-if="workshop.length">
                            <div
                                v-for="(item, index) in workshop"
                                :key="index"
                                class="h-full"
                            >
                                <!-- CARD -->
                                <div
                                    @click="openIndex = index"
                                    class="bg-white rounded-2xl overflow-hidden border border-slate-200/60 shadow-xs group flex flex-col justify-between h-full hover:shadow-md hover:border-blue-500/30 transition duration-300 cursor-pointer select-none"
                                >
                                    <div>
                                        <div
                                            class="aspect-video bg-slate-100 overflow-hidden relative border-b border-slate-100"
                                        >
                                            <div
                                                class="absolute inset-0 bg-slate-900/5 group-hover:bg-slate-900/20 transition duration-300 z-10 flex items-center justify-center"
                                            >
                                                <span
                                                    class="bg-white/90 backdrop-blur-xs text-xs font-bold text-slate-800 px-3 py-1.5 rounded-lg shadow-sm opacity-0 group-hover:opacity-100 transition-all duration-300 transform translate-y-2 group-hover:translate-y-0"
                                                >
                                                    Detail Ruangan
                                                </span>
                                            </div>
                                            <img
                                                v-if="item.foto_ruangan"
                                                :src="
                                                    '/storage/' +
                                                    item.foto_ruangan
                                                "
                                                :alt="item.nama_ruangan"
                                                class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex flex-col items-center justify-center text-slate-300 bg-slate-50 gap-2"
                                            >
                                                <i
                                                    class="fas fa-building text-3xl"
                                                ></i>
                                                <span
                                                    class="text-[10px] text-slate-400 font-medium"
                                                    >Tidak ada foto</span
                                                >
                                            </div>
                                        </div>
                                        <div class="p-5">
                                            <span
                                                class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block"
                                                >Fasilitas Kelas</span
                                            >
                                            <h4
                                                class="font-extrabold text-slate-900 text-sm mt-1 mb-2 group-hover:text-blue-600 transition-colors duration-300"
                                            >
                                                {{ item.nama_ruangan }}
                                            </h4>
                                            <div
                                                class="text-xs text-slate-500 leading-relaxed line-clamp-3 font-normal"
                                            >
                                                {{
                                                    stripTags(
                                                        item.deskripsi_ruangan,
                                                    ) ||
                                                    "Detail sarana ruang workshop pelatihan kerja."
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="px-5 pb-5 pt-2 flex items-center text-xs font-bold text-blue-600 gap-1.5 group-hover:gap-2.5 transition-all"
                                    >
                                        <span>Lihat Ruangan</span>
                                        <i
                                            class="fas fa-arrow-right text-[10px]"
                                        ></i>
                                    </div>
                                </div>

                                <!-- MODAL -->
                                <Teleport to="body">
                                    <div
                                        v-if="openIndex === index"
                                        class="fixed inset-0 z-50 overflow-y-auto"
                                    >
                                        <div
                                            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                                            @click="closeModal"
                                        ></div>
                                        <div
                                            class="flex min-h-full items-center justify-center p-4 sm:p-6 text-center"
                                        >
                                            <div
                                                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-2xl flex flex-col my-8"
                                            >
                                                <button
                                                    @click="closeModal"
                                                    class="absolute right-4 top-4 z-10 w-8 h-8 rounded-full bg-white/80 backdrop-blur-md text-slate-500 hover:text-slate-800 flex items-center justify-center shadow-xs border border-slate-200/50 transition cursor-pointer"
                                                >
                                                    <i
                                                        class="fas fa-times text-sm"
                                                    ></i>
                                                </button>

                                                <div
                                                    class="w-full h-64 sm:h-80 overflow-hidden bg-slate-50 border-b border-slate-100 relative"
                                                >
                                                    <img
                                                        v-if="item.foto_ruangan"
                                                        :src="
                                                            '/storage/' +
                                                            item.foto_ruangan
                                                        "
                                                        :alt="item.nama_ruangan"
                                                        class="w-full h-full object-cover"
                                                    />
                                                    <div
                                                        v-else
                                                        class="w-full h-full flex flex-col items-center justify-center text-slate-300 bg-slate-100 gap-2"
                                                    >
                                                        <i
                                                            class="fas fa-tools text-5xl"
                                                        ></i>
                                                        <span
                                                            class="text-xs text-slate-400 font-medium"
                                                            >Foto Ruangan Belum
                                                            Diunggah</span
                                                        >
                                                    </div>
                                                </div>

                                                <div
                                                    class="p-6 sm:p-8 space-y-4"
                                                >
                                                    <div>
                                                        <span
                                                            class="text-[10px] font-bold text-blue-600 uppercase tracking-widest block mb-1"
                                                            >Prasarana Ruang
                                                            Teori &amp;
                                                            Praktik</span
                                                        >
                                                        <h2
                                                            class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"
                                                        >
                                                            {{
                                                                item.nama_ruangan
                                                            }}
                                                        </h2>
                                                    </div>
                                                    <div
                                                        class="text-sm text-slate-600 leading-relaxed max-h-[35vh] overflow-y-auto pr-2 custom-scrollbar"
                                                    >
                                                        <div
                                                            class="prose prose-sm prose-slate max-w-none"
                                                            v-html="
                                                                item.deskripsi_ruangan ||
                                                                '<p class=\'italic text-slate-400\'>Belum ada rincian spesifikasi peralatan untuk ruangan ini.</p>'
                                                            "
                                                        ></div>
                                                    </div>
                                                </div>

                                                <div
                                                    class="bg-slate-50 px-6 py-4 flex justify-end rounded-b-2xl border-t border-slate-100"
                                                >
                                                    <button
                                                        @click="closeModal"
                                                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2 px-5 text-xs rounded-xl transition cursor-pointer"
                                                    >
                                                        Tutup
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </Teleport>
                            </div>
                        </template>

                        <!-- EMPTY STATE -->
                        <div
                            v-else
                            class="col-span-1 sm:col-span-2 md:col-span-3 bg-white rounded-3xl border border-dashed border-slate-200 p-12 text-center shadow-xs"
                        >
                            <div
                                class="w-16 h-16 bg-slate-50 text-slate-400 flex items-center justify-center text-2xl rounded-2xl mx-auto mb-4 border border-slate-100"
                            >
                                <i class="fas fa-tools"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">
                                Belum Ada Data Ruangan
                            </h3>
                            <p
                                class="text-xs text-slate-400 mt-1 max-w-md mx-auto leading-relaxed"
                            >
                                Konten prasarana ruang kelas teori dan workshop
                                kejuruan belum dikonfigurasi melalui panel admin
                                balai.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
