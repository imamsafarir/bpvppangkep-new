<script setup>
import { ref } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    galeri_list: { type: Array, default: () => [] },
});

const activeModal = ref(null);
const photoIndex = ref(0);

const getFotos = (item) => {
    let f = item.file_foto;
    if (typeof f === "string" && f.startsWith("[")) {
        try {
            return JSON.parse(f);
        } catch {
            return [];
        }
    }
    return Array.isArray(f) ? f : f ? [f] : [];
};

const openAlbum = (album) => {
    activeModal.value = album;
    photoIndex.value = 0;
};

const closeAlbum = () => {
    activeModal.value = null;
    photoIndex.value = 0;
};

const nextPhoto = (total) => {
    photoIndex.value = (photoIndex.value + 1) % total;
};

const prevPhoto = (total) => {
    photoIndex.value = (photoIndex.value - 1 + total) % total;
};
</script>

<template>
    <AppLayout
        title="Galeri Dokumentasi Kegiatan"
        description="Dokumentasi foto dan galeri kegiatan pelatihan vokasi, workshop, uji kompetensi, dan kegiatan balai di BPVP Pangkep."
        :settings="settings"
    >
        <main class="py-12 min-h-screen bg-slate-50/50">
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
                    <span class="text-blue-600">Galeri Kegiatan</span>
                </nav>

                <div class="space-y-8">
                    <div class="border-b border-slate-200 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Dokumentasi</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                        >
                            Galeri Foto Kegiatan
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Dokumentasi visual serangkaian agenda, proses
                            pelatihan, dan momentum penting balai.
                        </p>
                    </div>

                    <!-- Grid Album -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-y-10 gap-x-6 pt-4"
                    >
                        <div
                            v-for="galeri in galeri_list"
                            :key="galeri.id"
                            class="relative group select-none cursor-pointer"
                            @click="openAlbum(galeri)"
                        >
                            <!-- Tumpukan Foto Efek -->
                            <div
                                v-if="getFotos(galeri).length > 1"
                                class="absolute inset-0 transform translate-x-2.5 -translate-y-2 bg-slate-300/60 border border-slate-400/20 rounded-2xl transition duration-300 group-hover:translate-x-4 group-hover:-translate-y-3.5 shadow-2xs"
                            ></div>
                            <div
                                v-if="getFotos(galeri).length > 1"
                                class="absolute inset-0 transform translate-x-1.5 -translate-y-1 bg-slate-200 border border-slate-300/40 rounded-2xl transition duration-300 group-hover:translate-x-2 group-hover:-translate-y-2 shadow-xs"
                            ></div>

                            <!-- Kartu Album Utama -->
                            <div
                                class="relative bg-white rounded-2xl border border-slate-200/70 p-2.5 shadow-xs transition duration-300 group-hover:border-blue-500/30 group-hover:shadow-md flex flex-col justify-between h-full z-10"
                            >
                                <div>
                                    <div
                                        class="aspect-square bg-slate-100 rounded-xl flex items-center justify-center text-slate-400 relative overflow-hidden group/img"
                                    >
                                        <img
                                            v-if="getFotos(galeri)[0]"
                                            :src="`/storage/${getFotos(galeri)[0]}`"
                                            :alt="galeri.keterangan_galeri"
                                            class="w-full h-full object-cover group-hover/img:scale-105 transition duration-500"
                                        />
                                        <div
                                            v-else
                                            class="text-3xl text-slate-300"
                                        >
                                            <i class="fas fa-images"></i>
                                        </div>

                                        <div
                                            class="absolute top-2.5 right-2.5 bg-black/60 backdrop-blur-xs text-white text-[10px] font-bold px-2 py-0.5 rounded-md flex items-center gap-1"
                                        >
                                            <i class="fas fa-camera"></i>
                                            <span
                                                >{{
                                                    getFotos(galeri).length
                                                }}
                                                Foto</span
                                            >
                                        </div>
                                    </div>
                                    <div class="pt-3 px-1">
                                        <h3
                                            class="font-extrabold text-slate-900 text-sm leading-snug line-clamp-2 group-hover:text-blue-600 transition"
                                        >
                                            {{ galeri.keterangan_galeri }}
                                        </h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lightbox / Album Modal -->
            <div
                v-if="activeModal"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                @click.self="closeAlbum"
            >
                <div
                    class="relative max-w-4xl w-full flex flex-col items-center"
                >
                    <button
                        @click="closeAlbum"
                        class="absolute -top-12 right-0 text-white hover:text-amber-400 text-2xl font-bold p-2 cursor-pointer"
                    >
                        ✕
                    </button>

                    <div
                        class="relative w-full aspect-video bg-black/40 rounded-2xl overflow-hidden flex items-center justify-center"
                    >
                        <img
                            v-if="getFotos(activeModal)[photoIndex]"
                            :src="`/storage/${getFotos(activeModal)[photoIndex]}`"
                            :alt="activeModal.keterangan_galeri"
                            class="max-w-full max-h-full object-contain"
                        />

                        <!-- Prev / Next Controls -->
                        <button
                            v-if="getFotos(activeModal).length > 1"
                            @click="prevPhoto(getFotos(activeModal).length)"
                            class="absolute left-4 w-10 h-10 rounded-full bg-black/50 hover:bg-black/80 text-white flex items-center justify-center cursor-pointer"
                        >
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button
                            v-if="getFotos(activeModal).length > 1"
                            @click="nextPhoto(getFotos(activeModal).length)"
                            class="absolute right-4 w-10 h-10 rounded-full bg-black/50 hover:bg-black/80 text-white flex items-center justify-center cursor-pointer"
                        >
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>

                    <div class="mt-4 text-center text-white">
                        <p class="font-bold text-sm">
                            {{ activeModal.keterangan_galeri }}
                        </p>
                        <p class="text-xs text-white/60 mt-1">
                            Foto {{ photoIndex + 1 }} dari
                            {{ getFotos(activeModal).length }}
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
