<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    totalInformasi: { type: Number, default: 0 },
    totalJdih: { type: Number, default: 0 },
    totalBerita: { type: Number, default: 0 },
    totalUnduhan: { type: Number, default: 0 },
    totalKunjungan: { type: Number, default: 0 },
});
</script>

<template>
    <section id="statistik" class="bg-slate-100/70 py-16 scroll-mt-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-10 text-center lg:text-left">
                <h3
                    class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight flex items-center justify-center lg:justify-start gap-2"
                >
                    <i class="fas fa-chart-pie text-blue-600"></i> Statistik
                    Layanan Publik
                </h3>
                <p class="text-xs text-slate-500 mt-1">
                    Transparansi jumlah ketersediaan data berkas penunjang PPID
                    dan akuntabilitas informasi balai.
                </p>
            </div>

            <div
                class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 sm:gap-6"
            >
                <!-- Kartu 1: Dokumen PPID -->
                <a
                    href="#documents"
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/50 shadow-xs text-center flex flex-col items-center group hover:border-blue-500/30 hover:shadow-md transition duration-300 cursor-pointer"
                >
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 text-lg mb-4 group-hover:bg-blue-600 group-hover:text-white transition-all"
                    >
                        <i class="fas fa-folder-open"></i>
                    </div>
                    <h2
                        class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                    >
                        {{ totalInformasi }}
                    </h2>
                    <p
                        class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mt-1"
                    >
                        Dokumen PPID
                    </p>
                </a>

                <!-- Kartu 2: Produk Hukum -->
                <Link
                    href="/jdih"
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/50 shadow-xs text-center flex flex-col items-center group hover:border-emerald-500/30 hover:shadow-md transition duration-300 cursor-pointer"
                >
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600 text-lg mb-4 group-hover:bg-emerald-600 group-hover:text-white transition-all"
                    >
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <h2
                        class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                    >
                        {{ totalJdih }}
                    </h2>
                    <p
                        class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mt-1"
                    >
                        Produk Hukum
                    </p>
                </Link>

                <!-- Kartu 3: Berita & Artikel -->
                <Link
                    href="/berita-informasi/daftar-berita"
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/50 shadow-xs text-center flex flex-col items-center group hover:border-indigo-500/30 hover:shadow-md transition duration-300 cursor-pointer"
                >
                    <div
                        class="w-12 h-12 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 text-lg mb-4 group-hover:bg-indigo-600 group-hover:text-white transition-all"
                    >
                        <i class="fas fa-newspaper"></i>
                    </div>
                    <h2
                        class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                    >
                        {{ totalBerita }}
                    </h2>
                    <p
                        class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mt-1"
                    >
                        Berita & Artikel
                    </p>
                </Link>

                <!-- Kartu 4: Total Unduhan -->
                <div
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/50 shadow-xs text-center flex flex-col items-center hover:border-amber-500/30 transition duration-300"
                >
                    <div
                        class="w-12 h-12 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 text-lg mb-4"
                    >
                        <i class="fas fa-cloud-download-alt"></i>
                    </div>
                    <h2
                        class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                    >
                        {{ totalUnduhan }}
                    </h2>
                    <p
                        class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mt-1"
                    >
                        Total Unduhan
                    </p>
                </div>

                <!-- Kartu 5: Total Kunjungan -->
                <div
                    class="bg-white p-5 sm:p-6 rounded-2xl border border-slate-200/50 shadow-xs text-center flex flex-col items-center hover:border-rose-500/30 transition duration-300"
                >
                    <div
                        class="w-12 h-12 rounded-xl bg-rose-50 flex items-center justify-center text-rose-600 text-lg mb-4"
                    >
                        <i class="fas fa-eye"></i>
                    </div>
                    <h2
                        class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                    >
                        {{ totalKunjungan }}
                    </h2>
                    <p
                        class="text-[10px] sm:text-xs font-bold text-slate-400 uppercase tracking-widest mt-1"
                    >
                        Pengunjung
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
