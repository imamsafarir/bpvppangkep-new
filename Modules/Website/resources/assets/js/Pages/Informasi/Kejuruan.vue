<script setup>
import { ref } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

defineProps({
    settings: { type: Object, default: () => ({}) },
    kejuruan: { type: Array, default: () => [] },
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
    <Head title="Kejuruan Pelatihan" />
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
                    <span class="text-blue-600">Kejuruan Pelatihan</span>
                </nav>

                <div class="space-y-6">
                    <!-- HEADER -->
                    <div class="border-b border-slate-200 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Program Pelatihan</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                        >
                            Kejuruan Pelatihan Aktif
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Daftar kejuruan program pelatihan kerja terstandar
                            kompetensi di BPVP Pangkep.
                        </p>
                    </div>

                    <!-- GRID -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <template v-if="kejuruan.length">
                            <div v-for="(item, index) in kejuruan" :key="index">
                                <!-- CARD -->
                                <div
                                    @click="openIndex = index"
                                    class="bg-white rounded-2xl border border-slate-200/60 shadow-xs overflow-hidden hover:shadow-md hover:border-blue-500/30 transition duration-300 group cursor-pointer h-full flex flex-col justify-between"
                                >
                                    <div>
                                        <div
                                            v-if="item.foto_kejuruan"
                                            class="w-full h-48 overflow-hidden bg-slate-100 border-b border-slate-100 relative"
                                        >
                                            <img
                                                :src="
                                                    '/storage/' +
                                                    item.foto_kejuruan
                                                "
                                                :alt="item.nama_kejuruan"
                                                class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
                                            />
                                            <div
                                                class="absolute inset-0 bg-slate-900/10 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center"
                                            >
                                                <span
                                                    class="bg-white/90 backdrop-blur-xs text-xs font-bold text-slate-800 px-3 py-1.5 rounded-lg shadow-sm"
                                                    >Lihat Detail</span
                                                >
                                            </div>
                                        </div>

                                        <div class="p-5">
                                            <div
                                                v-if="!item.foto_kejuruan"
                                                class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl mb-4 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300"
                                            >
                                                <i
                                                    class="fas fa-graduation-cap"
                                                ></i>
                                            </div>
                                            <h3
                                                class="font-extrabold text-slate-900 text-base mb-2 group-hover:text-blue-600 transition-colors duration-300"
                                            >
                                                {{ item.nama_kejuruan }}
                                            </h3>
                                            <div
                                                class="text-xs text-slate-500 mt-1 line-clamp-3 leading-relaxed font-normal"
                                            >
                                                {{
                                                    stripTags(
                                                        item.deskripsi_kejuruan,
                                                    ) ||
                                                    "Pelatihan berbasis kompetensi siap kerja."
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        class="px-5 pb-5 pt-2 flex items-center text-xs font-bold text-blue-600 gap-1.5 group-hover:gap-2.5 transition-all"
                                    >
                                        <span>Selengkapnya</span>
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
                                        <transition
                                            enter-active-class="transition ease-out duration-300"
                                            enter-from-class="opacity-0"
                                            enter-to-class="opacity-100"
                                            leave-active-class="transition ease-in duration-200"
                                            leave-from-class="opacity-100"
                                            leave-to-class="opacity-0"
                                        >
                                            <div
                                                class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                                                @click="closeModal"
                                            ></div>
                                        </transition>
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
                                                    v-if="item.foto_kejuruan"
                                                    class="w-full h-64 sm:h-80 overflow-hidden bg-slate-50 border-b border-slate-100"
                                                >
                                                    <img
                                                        :src="
                                                            '/storage/' +
                                                            item.foto_kejuruan
                                                        "
                                                        :alt="
                                                            item.nama_kejuruan
                                                        "
                                                        class="w-full h-full object-cover"
                                                    />
                                                </div>

                                                <div
                                                    class="p-6 sm:p-8 space-y-4"
                                                >
                                                    <div
                                                        v-if="
                                                            !item.foto_kejuruan
                                                        "
                                                        class="w-12 h-12 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center text-xl"
                                                    >
                                                        <i
                                                            class="fas fa-graduation-cap"
                                                        ></i>
                                                    </div>
                                                    <div>
                                                        <span
                                                            class="text-[10px] font-bold text-blue-600 uppercase tracking-widest block mb-1"
                                                            >Detail Program
                                                            Pelatihan</span
                                                        >
                                                        <h2
                                                            class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight"
                                                        >
                                                            {{
                                                                item.nama_kejuruan
                                                            }}
                                                        </h2>
                                                    </div>
                                                    <div
                                                        class="text-sm text-slate-600 leading-relaxed max-h-[40vh] overflow-y-auto pr-2 custom-scrollbar"
                                                    >
                                                        <div
                                                            class="prose prose-sm prose-slate max-w-none"
                                                            v-html="
                                                                item.deskripsi_kejuruan ||
                                                                '<p class=\'italic text-slate-400\'>Belum ada rincian deskripsi resmi untuk kejuruan ini.</p>'
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
                                                        Tutup Detail
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
                            class="col-span-full text-center py-16 bg-white rounded-3xl border border-dashed border-slate-200 p-8 shadow-xs"
                        >
                            <div
                                class="w-14 h-14 rounded-2xl bg-slate-50 text-slate-400 flex items-center justify-center text-2xl mx-auto mb-4 border border-slate-100"
                            >
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h4 class="font-bold text-slate-800 text-base">
                                Belum Ada Program Kejuruan
                            </h4>
                            <p
                                class="text-xs text-slate-400 max-w-md mx-auto mt-1 leading-relaxed"
                            >
                                Daftar kejuruan program pelatihan reguler aktif
                                saat ini belum diinput atau sedang dalam proses
                                pembaruan oleh admin balai.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
