<script setup>
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

defineProps({
    settings: { type: Object, default: () => ({}) },
    profil: { type: Object, default: () => ({}) },
});
</script>

<template>
    <AppLayout
        title="Tugas Pokok & Fungsi Organisasi"
        description="Tugas pokok dan fungsi Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan Kepulauan sesuai Permenaker RI."
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
                    <span class="text-slate-500">Profil</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-blue-600">Tugas &amp; Fungsi</span>
                </nav>

                <div
                    class="bg-white rounded-3xl border border-slate-200/60 shadow-xs p-6 sm:p-10 space-y-6"
                >
                    <div class="border-b border-slate-100 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Tupoksi Kerja</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight leading-tight"
                        >
                            Tugas Pokok &amp; Fungsi Balai
                        </h1>
                    </div>

                    <div
                        class="text-slate-600 text-sm sm:text-base leading-relaxed text-justify tugas-fungsi-content"
                    >
                        <div
                            v-if="profil && profil.tugas_fungsi"
                            class="prose prose-slate max-w-none"
                            v-html="profil.tugas_fungsi"
                        ></div>
                        <div
                            v-else
                            class="text-center py-12 text-slate-400 italic"
                        >
                            Data Tugas &amp; Fungsi balai belum diisi.
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </AppLayout>
</template>

<style>
.tugas-fungsi-content p,
.tugas-fungsi-content div {
    display: block;
    margin-top: 0 !important;
    margin-bottom: 1.25rem !important;
}

.tugas-fungsi-content br {
    display: block;
    content: "";
    margin-top: 0.75rem;
}

.tugas-fungsi-content h2 {
    display: block;
    font-size: 1.5rem !important;
    font-weight: 800 !important;
    color: #0f172a;
    margin-top: 1.75rem !important;
    margin-bottom: 0.75rem !important;
    letter-spacing: -0.025em;
}

.tugas-fungsi-content ol {
    display: block;
    list-style-type: decimal !important;
    padding-left: 1.5rem !important;
    margin-bottom: 1.25rem !important;
}

.tugas-fungsi-content ul {
    display: block;
    list-style-type: disc !important;
    padding-left: 1.5rem !important;
    margin-bottom: 1.25rem !important;
}

.tugas-fungsi-content li {
    display: list-item;
    margin-bottom: 0.5rem !important;
}
</style>
