<script setup>
import { ref, computed } from "vue";
import { Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    berita: { type: Object, required: true },
    prevBerita: { type: Object, default: null },
    nextBerita: { type: Object, default: null },
    beritaTerkait: { type: Array, default: () => [] },
});

const copied = ref(false);

const gambarUtama = computed(() => {
    let foto = props.berita.file_foto;
    if (typeof foto === "string" && foto.startsWith("[")) {
        try {
            foto = JSON.parse(foto);
        } catch {
            /* ignore */
        }
    }
    const g = Array.isArray(foto) ? foto[0] : foto;
    return g ? `/storage/${g}` : null;
});

const formatDate = (dateString) => {
    if (!dateString) return "Baru saja";
    const date = new Date(dateString);
    return (
        date.toLocaleDateString("id-ID", {
            day: "numeric",
            month: "long",
            year: "numeric",
            hour: "2-digit",
            minute: "2-digit",
        }) + " WITA"
    );
};

const copyLink = () => {
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(window.location.href);
    } else {
        const textArea = document.createElement("textarea");
        textArea.value = window.location.href;
        textArea.style.position = "fixed";
        textArea.style.left = "-999999px";
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand("copy");
        } catch (err) {
            console.error(err);
        }
        document.body.removeChild(textArea);
    }
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2500);
};

const getTerkaitFoto = (foto) => {
    if (!foto) return null;
    if (typeof foto === "string" && foto.startsWith("[")) {
        try {
            const arr = JSON.parse(foto);
            return `/storage/${arr[0]}`;
        } catch {
            return `/storage/${foto}`;
        }
    }
    if (Array.isArray(foto)) return `/storage/${foto[0]}`;
    return `/storage/${foto}`;
};

const cleanDescription = computed(() => {
    if (!props.berita?.konten_berita) return "";
    return props.berita.konten_berita
        .replace(/<[^>]*>/g, " ")
        .replace(/\s+/g, " ")
        .trim()
        .slice(0, 160);
});

const stringKeywords = computed(() => {
    const raw = props.berita?.tags;
    if (Array.isArray(raw)) {
        return raw.filter(Boolean).join(", ");
    }
    if (typeof raw === "string") {
        if (raw.startsWith("[") && raw.endsWith("]")) {
            try {
                const parsed = JSON.parse(raw);
                if (Array.isArray(parsed)) {
                    return parsed.filter(Boolean).join(", ");
                }
            } catch {
                /* ignore */
            }
        }
        return raw;
    }
    return "";
});
</script>

<template>
    <AppLayout
        :title="berita.judul_berita"
        :description="cleanDescription"
        :image="gambarUtama"
        type="article"
        :keywords="stringKeywords"
        :settings="settings"
    >
        <main class="py-12 min-h-screen bg-slate-50/50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
                <!-- Breadcrumb -->
                <nav
                    class="flex items-center gap-2 text-xs font-semibold text-slate-400 uppercase tracking-wider select-none"
                >
                    <Link href="/" class="hover:text-blue-600 transition-colors"
                        >Home</Link
                    >
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <Link
                        href="/berita-informasi/daftar-berita"
                        class="hover:text-blue-600 transition-colors"
                        >Berita</Link
                    >
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span
                        class="text-blue-600 truncate max-w-[200px] sm:max-w-xs"
                        >{{ berita.judul_berita }}</span
                    >
                </nav>

                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                    <!-- Column Kiri: Artikel Utama -->
                    <div class="lg:col-span-8 space-y-6">
                        <article
                            class="bg-white rounded-3xl border border-slate-200/60 shadow-xs overflow-hidden"
                        >
                            <div
                                v-if="gambarUtama"
                                class="w-full aspect-video bg-slate-100 overflow-hidden"
                            >
                                <img
                                    :src="gambarUtama"
                                    :alt="berita.judul_berita"
                                    class="w-full h-full object-cover"
                                />
                            </div>

                            <div class="p-6 sm:p-10 space-y-6">
                                <div
                                    class="border-b border-slate-100 pb-5 space-y-2"
                                >
                                    <span
                                        class="text-[10px] font-bold bg-blue-50 text-blue-600 px-2.5 py-1 rounded-full uppercase tracking-wider inline-block"
                                    >
                                        Artikel Berita
                                    </span>
                                    <h1
                                        class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight"
                                    >
                                        {{ berita.judul_berita }}
                                    </h1>
                                    <div
                                        class="flex items-center gap-2 text-xs text-slate-400 pt-1 font-medium"
                                    >
                                        <i class="far fa-calendar-alt"></i>
                                        <span>{{
                                            formatDate(berita.created_at)
                                        }}</span>
                                        <span class="text-slate-200">•</span>
                                        <i class="far fa-user"></i>
                                        <span>Administrator</span>
                                    </div>
                                </div>

                                <!-- Konten Berita -->
                                <div
                                    class="text-slate-900 text-sm sm:text-base leading-relaxed max-w-none rich-text-content prose break-words [&_p]:my-2.5 [&_p]:leading-relaxed [&_h1]:text-2xl [&_h1]:font-black [&_h1]:text-slate-900 [&_h1]:mt-5 [&_h1]:mb-2 [&_h1]:tracking-tight [&_h2]:text-xl [&_h2]:font-extrabold [&_h2]:text-slate-900 [&_h2]:mt-4 [&_h2]:mb-2 [&_h3]:text-lg [&_h3]:font-bold [&_h3]:text-slate-800 [&_h3]:mt-3 [&_h3]:mb-1.5 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-3 [&_ul_li]:my-1 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-3 [&_ol_li]:my-1 [&_blockquote]:border-l-4 [&_blockquote]:border-blue-600 [&_blockquote]:bg-blue-50/40 [&_blockquote]:py-2 [&_blockquote]:px-4 [&_blockquote]:rounded-r-xl [&_blockquote]:italic [&_blockquote]:my-4 [&_blockquote]:text-slate-700 [&_table]:w-full [&_table]:border-collapse [&_table]:border [&_table]:border-slate-300 [&_table]:my-4 [&_th]:border [&_th]:border-slate-300 [&_th]:p-2.5 [&_th]:bg-slate-100 [&_th]:font-bold [&_th]:text-xs [&_th]:text-slate-700 [&_td]:border [&_td]:border-slate-300 [&_td]:p-2.5 [&_td]:text-xs [&_td]:text-slate-600 [&_a]:text-blue-600 [&_a]:underline [&_a]:font-semibold [&_a:hover]:text-blue-800 [&_hr]:border-t [&_hr]:border-slate-200 [&_hr]:my-6 [&_img]:rounded-2xl [&_img]:border [&_img]:border-slate-200 [&_img]:shadow-xs [&_img]:my-4 [&_img]:max-w-full"
                                    v-html="berita.konten_berita"
                                ></div>

                                <!-- Share Buttons -->
                                <div
                                    class="pt-6 border-t border-slate-100 space-y-3"
                                >
                                    <h4
                                        class="text-xs font-bold text-slate-400 uppercase tracking-widest"
                                    >
                                        Bagikan Berita Ini
                                    </h4>
                                    <div
                                        class="flex flex-wrap items-center gap-2"
                                    >
                                        <button
                                            @click="copyLink"
                                            class="inline-flex items-center gap-2 px-3 py-2 bg-slate-100 text-slate-600 rounded-xl font-bold text-xs transition cursor-pointer"
                                            :class="
                                                copied
                                                    ? 'bg-green-500 text-white'
                                                    : 'hover:bg-slate-200'
                                            "
                                        >
                                            <i
                                                class="fas text-sm"
                                                :class="
                                                    copied
                                                        ? 'fa-check'
                                                        : 'fa-link'
                                                "
                                            ></i>
                                            <span>{{
                                                copied
                                                    ? "Tautan Tersalin!"
                                                    : "Salin Link"
                                            }}</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </article>

                        <!-- Navigasi Prev / Next -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                            <div>
                                <Link
                                    v-if="prevBerita"
                                    :href="`/berita-informasi/berita/${prevBerita.id}`"
                                    class="group block p-4 bg-white hover:bg-blue-50/50 border border-slate-200/60 rounded-2xl transition shadow-xs"
                                >
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1"
                                    >
                                        <i class="fas fa-arrow-left"></i> Berita
                                        Sebelumnya
                                    </span>
                                    <span
                                        class="text-xs font-bold text-slate-700 group-hover:text-blue-600 transition line-clamp-1"
                                    >
                                        {{ prevBerita.judul_berita }}
                                    </span>
                                </Link>
                            </div>
                            <div class="text-right">
                                <Link
                                    v-if="nextBerita"
                                    :href="`/berita-informasi/berita/${nextBerita.id}`"
                                    class="group block p-4 bg-white hover:bg-blue-50/50 border border-slate-200/60 rounded-2xl transition shadow-xs"
                                >
                                    <span
                                        class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block mb-1"
                                    >
                                        Berita Selanjutnya
                                        <i class="fas fa-arrow-right"></i>
                                    </span>
                                    <span
                                        class="text-xs font-bold text-slate-700 group-hover:text-blue-600 transition line-clamp-1"
                                    >
                                        {{ nextBerita.judul_berita }}
                                    </span>
                                </Link>
                            </div>
                        </div>
                    </div>

                    <!-- Column Kanan: Berita Lainnya (Sticky) -->
                    <div class="lg:col-span-4 lg:sticky lg:top-32 space-y-4">
                        <template v-if="beritaTerkait.length > 0">
                            <h3
                                class="text-base font-black text-slate-900 tracking-tight flex items-center gap-2 px-1"
                            >
                                <i class="fas fa-newspaper text-blue-600"></i>
                                Baca Berita Lainnya
                            </h3>
                            <div
                                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-4"
                            >
                                <Link
                                    v-for="terkait in beritaTerkait"
                                    :key="terkait.id"
                                    :href="`/berita-informasi/berita/${terkait.id}`"
                                    class="p-3 bg-white hover:bg-blue-50/40 rounded-2xl border border-slate-200/60 flex gap-3 items-center group transition shadow-xs"
                                >
                                    <div
                                        class="w-20 h-20 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0"
                                    >
                                        <img
                                            v-if="
                                                getTerkaitFoto(
                                                    terkait.file_foto,
                                                )
                                            "
                                            :src="
                                                getTerkaitFoto(
                                                    terkait.file_foto,
                                                )
                                            "
                                            :alt="terkait.judul_berita"
                                            class="w-full h-full object-cover group-hover:scale-105 transition"
                                        />
                                        <div
                                            v-else
                                            class="w-full h-full flex items-center justify-center text-slate-300"
                                        >
                                            <i class="fas fa-newspaper"></i>
                                        </div>
                                    </div>
                                    <div class="min-w-0 flex-1 space-y-1">
                                        <span
                                            class="text-[10px] text-slate-400 font-medium block"
                                        >
                                            {{ formatDate(terkait.created_at) }}
                                        </span>
                                        <h4
                                            class="font-bold text-xs text-slate-900 group-hover:text-blue-600 transition line-clamp-2 leading-snug"
                                        >
                                            {{ terkait.judul_berita }}
                                        </h4>
                                    </div>
                                </Link>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
