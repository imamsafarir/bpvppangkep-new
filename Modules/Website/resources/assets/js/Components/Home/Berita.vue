<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";

const props = defineProps({
    berita: {
        type: Array,
        default: () => [],
    },
});

const beritaUtama = computed(() => {
    return props.berita.length > 0 ? props.berita[0] : null;
});

const subBeritaList = computed(() => {
    return props.berita.slice(1, 4);
});

const getImage = (foto) => {
    if (!foto) return "";
    if (Array.isArray(foto)) return `/storage/${foto[0]}`;
    if (typeof foto === "string" && foto.startsWith("[")) {
        try {
            const arr = JSON.parse(foto);
            return `/storage/${arr[0]}`;
        } catch {
            return `/storage/${foto}`;
        }
    }
    return `/storage/${foto}`;
};

const formatDate = (dateString) => {
    if (!dateString) return "";
    const date = new Date(dateString);
    return date.toLocaleDateString("id-ID", {
        day: "numeric",
        month: "short",
        year: "numeric",
    });
};

const stripTags = (html) => {
    if (!html) return "";
    return html.replace(/<[^>]*>?/gm, "");
};
</script>

<template>
    <section id="berita-home" class="bg-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div
                class="flex justify-between items-end mb-10 pb-5 border-b border-slate-100"
            >
                <div>
                    <span
                        class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                        >Kabar Balai</span
                    >
                    <h2
                        class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                    >
                        Berita & Artikel Terkini
                    </h2>
                </div>
                <Link
                    href="/berita-informasi/daftar-berita"
                    class="text-xs font-bold text-blue-600 hover:text-blue-800 uppercase tracking-wider flex items-center gap-1 transition-colors"
                >
                    Lihat Semua Berita
                    <i class="fas fa-chevron-right text-[10px]"></i>
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                <template v-if="beritaUtama">
                    <!-- Kiri: Berita Utama Besar -->
                    <div
                        class="lg:col-span-7 group relative flex flex-col space-y-4 break-words min-w-0"
                    >
                        <div
                            class="w-full aspect-video bg-slate-100 rounded-2xl overflow-hidden border border-slate-200/50 relative shadow-xs"
                        >
                            <img
                                v-if="beritaUtama.file_foto"
                                :src="getImage(beritaUtama.file_foto)"
                                alt="Berita Utama"
                                class="w-full h-full object-cover group-hover:scale-105 transition duration-500 ease-out"
                            />
                            <div class="absolute top-4 left-4 z-20">
                                <span
                                    class="text-[10px] font-black uppercase tracking-wider bg-blue-600 text-white px-3 py-1 rounded-md shadow-xs select-none"
                                >
                                    Sorotan
                                </span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <div
                                class="flex items-center gap-3 text-xs text-slate-400 font-medium select-none"
                            >
                                <span>{{
                                    formatDate(beritaUtama.created_at)
                                }}</span>
                                <span
                                    v-if="beritaUtama.tags"
                                    class="text-blue-600 font-bold"
                                >
                                    #{{
                                        Array.isArray(beritaUtama.tags)
                                            ? beritaUtama.tags[0]
                                            : beritaUtama.tags
                                    }}
                                </span>
                            </div>
                            <Link
                                :href="`/berita-informasi/berita/${beritaUtama.id}`"
                                class="block"
                            >
                                <h3
                                    class="text-xl sm:text-2xl font-black text-slate-900 leading-tight hover:text-blue-600 transition-colors line-clamp-2 break-words"
                                >
                                    {{ beritaUtama.judul_berita }}
                                </h3>
                            </Link>
                            <p
                                class="text-xs sm:text-sm text-slate-500 leading-relaxed font-normal line-clamp-3 break-words whitespace-normal"
                            >
                                {{ stripTags(beritaUtama.konten_berita) }}
                            </p>
                        </div>
                    </div>

                    <!-- Kanan: Daftar 3 Berita List Kecil -->
                    <div
                        class="lg:col-span-5 space-y-6 divide-y divide-slate-100 lg:divide-y-0 min-w-0"
                    >
                        <div
                            v-for="sub in subBeritaList"
                            :key="sub.id"
                            class="flex gap-4 items-center pt-4 first:pt-0 lg:pt-0 group break-words min-w-0"
                        >
                            <div
                                class="w-24 sm:w-28 aspect-video bg-slate-100 rounded-xl overflow-hidden flex-shrink-0 border border-slate-200/40 shadow-2xs"
                            >
                                <img
                                    v-if="sub.file_foto"
                                    :src="getImage(sub.file_foto)"
                                    alt="Thumb"
                                    class="w-full h-full object-cover group-hover:scale-105 transition duration-300 ease-out"
                                />
                            </div>
                            <div class="space-y-1 min-w-0 flex-1">
                                <span
                                    class="text-[10px] font-bold text-slate-400 block select-none"
                                >
                                    {{ formatDate(sub.created_at) }}
                                </span>
                                <Link
                                    :href="`/berita-informasi/berita/${sub.id}`"
                                    class="block"
                                >
                                    <h4
                                        class="font-extrabold text-xs sm:text-sm text-slate-900 leading-snug hover:text-blue-600 transition-colors line-clamp-2 break-words"
                                    >
                                        {{ sub.judul_berita }}
                                    </h4>
                                </Link>
                                <p
                                    class="text-[11px] text-slate-400 font-normal line-clamp-1 break-words whitespace-normal"
                                >
                                    {{ stripTags(sub.konten_berita) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </template>

                <div
                    v-else
                    class="col-span-full text-center py-12 border border-dashed border-slate-200 rounded-2xl bg-slate-50/50"
                >
                    <p class="text-slate-400 italic text-sm">
                        Belum ada unggahan artikel berita di backend.
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
