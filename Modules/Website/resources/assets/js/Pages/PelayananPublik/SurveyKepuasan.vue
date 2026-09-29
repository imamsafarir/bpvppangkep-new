<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    pelayanan: { type: Object, default: () => ({}) },
});

const getEmbedUrl = (val) => {
    if (!val) return "";
    if (val.includes("<iframe")) {
        const match = val.match(/src=["']([^"']+)["']/i);
        if (match && match[1]) return match[1];
    }
    return val;
};
</script>

<template>
    <Head title="Survey Kepuasan Masyarakat" />
    <AppLayout :settings="settings">
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
                    ><span class="text-blue-600"
                        >Survey Kepuasan Masyarakat</span
                    >
                </nav>

                <div
                    class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                >
                    <div class="border-b border-slate-100 pb-5">
                        <div
                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                        >
                            <div>
                                <span
                                    class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                                    >Evaluasi Layanan</span
                                >
                                <h1
                                    class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                                >
                                    Survey Kepuasan Masyarakat
                                </h1>
                                <p class="text-xs text-slate-500 mt-1">
                                    Ulasan Anda sangat berharga bagi peningkatan
                                    mutu dan akuntabilitas sistem pelayanan
                                    publik balai kami.
                                </p>
                            </div>
                            <a
                                v-if="
                                    pelayanan &&
                                    pelayanan.survey_kepuasan_masyarakat
                                "
                                :href="
                                    getEmbedUrl(
                                        pelayanan.survey_kepuasan_masyarakat,
                                    )
                                "
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs shrink-0"
                            >
                                <i
                                    class="fas fa-external-link-alt text-[10px]"
                                ></i>
                                <span>Buka Formulir Survey</span>
                            </a>
                        </div>
                    </div>

                    <div
                        class="w-full rounded-2xl overflow-hidden border border-slate-200/80 bg-slate-50 min-h-[600px]"
                    >
                        <iframe
                            v-if="
                                pelayanan &&
                                pelayanan.survey_kepuasan_masyarakat
                            "
                            :src="
                                getEmbedUrl(
                                    pelayanan.survey_kepuasan_masyarakat,
                                )
                            "
                            width="100%"
                            height="850"
                            frameborder="0"
                            marginheight="0"
                            marginwidth="0"
                            class="w-full border-0 rounded-2xl"
                            >Memuat…</iframe
                        >
                        <p
                            v-else
                            class="text-slate-400 italic text-center py-12"
                        >
                            Kuesioner atau tautan survey kepuasan masyarakat
                            belum dikonfigurasi.
                        </p>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>
