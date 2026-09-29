<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    testimoni: { type: Array, default: () => [] },
});

const activeIndex = ref(null);
const totalItems = computed(() => props.testimoni?.length || 0);

const stripTags = (html) => {
    if (!html) return "";
    return html.replace(/<[^>]*>/g, "");
};

const prevItem = () => {
    if (totalItems.value === 0) return;
    activeIndex.value =
        activeIndex.value === 0 ? totalItems.value - 1 : activeIndex.value - 1;
};

const nextItem = () => {
    if (totalItems.value === 0) return;
    activeIndex.value =
        activeIndex.value === totalItems.value - 1 ? 0 : activeIndex.value + 1;
};
</script>

<template>
    <Head title="Testimoni Alumni" />
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
                    <span class="text-blue-600">Testimoni</span>
                </nav>

                <div class="space-y-8">
                    <!-- HEADER PAGE -->
                    <div class="border-b border-slate-200 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Ulasan Peserta</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                        >
                            Apa Kata Mereka Tentang BPVP Pangkep?
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Ulasan jujur dan kisah sukses langsung dari alumni
                            setelah mengikuti program pelatihan vokasi.
                        </p>
                    </div>

                    <!-- GRID -->
                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6"
                    >
                        <template v-if="testimoni && testimoni.length > 0">
                            <div
                                v-for="(item, index) in testimoni"
                                :key="index"
                                @click="activeIndex = index"
                                class="bg-white rounded-2xl border border-slate-200/60 shadow-xs p-6 flex flex-col justify-between relative group hover:shadow-md hover:border-blue-500/30 transition duration-300 cursor-pointer select-none h-full"
                            >
                                <!-- Quote Icon -->
                                <div
                                    class="absolute top-6 right-6 text-slate-100 text-5xl font-serif pointer-events-none select-none group-hover:text-blue-50/70 transition-colors duration-300"
                                >
                                    “
                                </div>

                                <div class="space-y-4">
                                    <div
                                        class="text-xs text-slate-600 leading-relaxed italic font-normal line-clamp-4 break-words"
                                    >
                                        "{{
                                            stripTags(item.isi_testimoni) ||
                                            "Tidak ada ulasan tertulis."
                                        }}"
                                    </div>
                                </div>

                                <!-- Profile Info -->
                                <div
                                    class="flex items-center gap-3 mt-6 pt-4 border-t border-slate-100 overflow-hidden"
                                >
                                    <div
                                        class="w-10 h-10 rounded-full overflow-hidden bg-slate-100 shrink-0 border border-slate-200"
                                    >
                                        <img
                                            v-if="item.foto_alumni"
                                            :src="
                                                '/storage/' + item.foto_alumni
                                            "
                                            :alt="item.nama_alumni"
                                            class="w-full h-full object-cover"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center bg-blue-50 text-blue-500 text-sm font-bold"
                                        >
                                            {{
                                                (item.nama_alumni || "A")
                                                    .substring(0, 1)
                                                    .toUpperCase()
                                            }}
                                        </div>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h5
                                            class="font-extrabold text-slate-900 text-xs truncate group-hover:text-blue-600 transition-colors"
                                        >
                                            {{ item.nama_alumni }}
                                        </h5>
                                        <p
                                            class="text-[10px] text-slate-400 truncate mt-0.5"
                                        >
                                            {{ item.pekerjaan }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- EMPTY -->
                        <div
                            v-else
                            class="col-span-1 sm:col-span-2 lg:col-span-3 bg-white rounded-3xl border border-slate-200/60 p-12 text-center shadow-xs"
                        >
                            <div
                                class="w-16 h-16 bg-slate-50 text-slate-400 flex items-center justify-center text-2xl rounded-2xl mx-auto mb-4 border border-slate-100"
                            >
                                <i class="fas fa-comment-dots"></i>
                            </div>
                            <h3 class="text-base font-bold text-slate-800">
                                Belum Ada Ulasan
                            </h3>
                            <p
                                class="text-xs text-slate-400 mt-1 max-w-sm mx-auto leading-relaxed"
                            >
                                Lembar ulasan testimoni alumni pelatihan belum
                                diisi melalui panel admin balai.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- MODAL -->
                <Teleport to="body">
                    <div
                        v-if="activeIndex !== null && testimoni[activeIndex]"
                        class="fixed inset-0 z-50 overflow-y-auto"
                    >
                        <div
                            class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs"
                            @click="activeIndex = null"
                        ></div>

                        <div
                            class="flex min-h-full items-center justify-center p-4 sm:p-6 relative"
                        >
                            <!-- NAV PREV -->
                            <button
                                @click.stop="prevItem"
                                class="fixed left-4 md:left-8 top-1/2 -translate-y-1/2 z-50 w-10 h-10 md:w-12 md:h-12 rounded-full bg-white/90 text-slate-700 hover:bg-blue-600 hover:text-white flex items-center justify-center shadow-lg border border-slate-200/50 transition cursor-pointer"
                            >
                                <i
                                    class="fas fa-chevron-left text-sm md:text-base"
                                ></i>
                            </button>

                            <!-- MODAL CONTENT -->
                            <div
                                class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all w-full max-w-xl flex flex-col my-8 z-20 break-words"
                            >
                                <button
                                    @click="activeIndex = null"
                                    class="absolute right-4 top-4 z-30 w-8 h-8 rounded-full bg-white/80 backdrop-blur-md text-slate-500 hover:text-slate-800 flex items-center justify-center shadow-xs border border-slate-200/50 transition cursor-pointer"
                                >
                                    <i class="fas fa-times text-sm"></i>
                                </button>

                                <div class="p-6 sm:p-8 space-y-6 pt-10">
                                    <div
                                        class="text-blue-500/20 text-6xl font-serif h-4 -mb-4 select-none pointer-events-none"
                                    >
                                        “
                                    </div>
                                    <div
                                        class="text-sm sm:text-base text-slate-700 leading-relaxed italic max-h-[35vh] overflow-y-auto pr-2 custom-scrollbar break-words"
                                    >
                                        <div
                                            class="prose prose-sm prose-slate max-w-none break-words whitespace-normal"
                                            v-html="
                                                testimoni[activeIndex]
                                                    .isi_testimoni ||
                                                '<p class=\'italic text-slate-400\'>Belum ada teks ulasan resmi.</p>'
                                            "
                                        ></div>
                                    </div>

                                    <div
                                        class="flex items-center gap-4 pt-5 border-t border-slate-100"
                                    >
                                        <div
                                            class="w-12 h-12 rounded-full overflow-hidden bg-slate-100 shrink-0 border border-slate-200"
                                        >
                                            <img
                                                v-if="
                                                    testimoni[activeIndex]
                                                        .foto_alumni
                                                "
                                                :src="
                                                    '/storage/' +
                                                    testimoni[activeIndex]
                                                        .foto_alumni
                                                "
                                                :alt="
                                                    testimoni[activeIndex]
                                                        .nama_alumni
                                                "
                                                class="w-full h-full object-cover"
                                            />
                                            <div
                                                v-else
                                                class="w-full h-full flex items-center justify-center bg-blue-50 text-blue-500 text-base font-bold"
                                            >
                                                {{
                                                    (
                                                        testimoni[activeIndex]
                                                            .nama_alumni || "A"
                                                    )
                                                        .substring(0, 1)
                                                        .toUpperCase()
                                                }}
                                            </div>
                                        </div>
                                        <div class="overflow-hidden">
                                            <h4
                                                class="font-black text-slate-900 text-sm tracking-tight"
                                            >
                                                {{
                                                    testimoni[activeIndex]
                                                        .nama_alumni
                                                }}
                                            </h4>
                                            <p
                                                class="text-xs text-slate-400 mt-0.5"
                                            >
                                                {{
                                                    testimoni[activeIndex]
                                                        .pekerjaan
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Footer Modal -->
                                <div
                                    class="bg-slate-50 px-6 py-4 flex justify-between items-center rounded-b-2xl border-t border-slate-100 select-none"
                                >
                                    <span
                                        class="text-[10px] sm:text-xs font-bold text-slate-400"
                                    >
                                        Ulasan {{ activeIndex + 1 }} dari
                                        {{ totalItems }} Testimoni
                                    </span>
                                    <button
                                        @click="activeIndex = null"
                                        class="bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold py-2 px-5 text-xs rounded-xl transition cursor-pointer"
                                    >
                                        Tutup
                                    </button>
                                </div>
                            </div>

                            <!-- NAV NEXT -->
                            <button
                                @click.stop="nextItem"
                                class="fixed right-4 md:right-8 top-1/2 -translate-y-1/2 z-50 w-10 h-10 md:w-12 md:h-12 rounded-full bg-white/90 text-slate-700 hover:bg-blue-600 hover:text-white flex items-center justify-center shadow-lg border border-slate-200/50 transition cursor-pointer"
                            >
                                <i
                                    class="fas fa-chevron-right text-sm md:text-base"
                                ></i>
                            </button>
                        </div>
                    </div>
                </Teleport>
            </div>
        </main>
    </AppLayout>
</template>
