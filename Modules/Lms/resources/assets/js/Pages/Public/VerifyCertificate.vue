<script setup>
import { Head, Link } from "@inertiajs/vue3";
import {
    Award,
    CheckCircle2,
    XCircle,
    Building2,
    Calendar,
    User,
    CreditCard,
    FileCheck,
    GraduationCap,
    ShieldCheck,
    ExternalLink,
    Download,
    BadgeCheck,
    Printer,
    Info,
} from "lucide-vue-next";

const props = defineProps({
    isValid: Boolean,
    hash: String,
    data: Object,
});

const printPage = () => {
    window.print();
};
</script>

<template>
    <div
        class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-800 dark:text-slate-100 flex flex-col justify-between"
    >
        <Head
            title="Verifikasi Keabsahan Sertifikat Digital - BPVP Pangkajene dan Kepulauan"
        />

        <!-- Header -->
        <header
            class="bg-white dark:bg-slate-900 border-b border-slate-200 dark:border-slate-800 shadow-sm print:hidden"
        >
            <div
                class="max-w-4xl mx-auto px-4 h-16 flex items-center justify-between"
            >
                <div class="flex items-center gap-3">
                    <span
                        class="p-2.5 bg-indigo-600 text-white rounded-xl shadow-md flex items-center justify-center"
                    >
                        <GraduationCap class="w-5 h-5" />
                    </span>
                    <div>
                        <h1
                            class="text-sm font-black text-slate-900 dark:text-white leading-tight uppercase tracking-wide"
                        >
                            Verifikasi Sertifikat Digital
                        </h1>
                        <p class="text-[10px] text-slate-500 font-medium">
                            BPVP Pangkajene dan Kepulauan &bull; Kemnaker RI
                        </p>
                    </div>
                </div>

                <a
                    href="/"
                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 flex items-center gap-1.5 transition-colors"
                >
                    <span>Website Balai</span>
                    <ExternalLink class="w-3.5 h-3.5" />
                </a>
            </div>
        </header>

        <!-- Main Body -->
        <main
            class="max-w-3xl w-full mx-auto px-4 py-8 flex-1 flex flex-col justify-center"
        >
            <!-- VALID STATE -->
            <div
                v-if="isValid && data"
                class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-slate-200/80 dark:border-slate-800 overflow-hidden"
            >
                <!-- Top Status Banner -->
                <div
                    class="bg-gradient-to-r from-emerald-600 to-teal-700 p-6 sm:p-8 text-white text-center space-y-2.5"
                >
                    <div
                        class="w-16 h-16 mx-auto bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center shadow-lg ring-4 ring-white/10"
                    >
                        <ShieldCheck class="w-10 h-10 text-white" />
                    </div>
                    <div>
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[11px] font-black uppercase tracking-widest bg-white text-emerald-800 shadow-sm"
                        >
                            <BadgeCheck class="w-3.5 h-3.5 text-emerald-600" />
                            DOKUMEN VALID &bull; TERVERIFIKASI RESMI
                        </span>
                    </div>
                    <h2
                        class="text-xl sm:text-2xl font-black tracking-tight text-white pt-1"
                    >
                        Sertifikat Pelatihan Terdaftar Sah
                    </h2>
                    <p
                        class="text-xs sm:text-sm text-emerald-50 max-w-xl mx-auto leading-relaxed"
                    >
                        Dokumen sertifikat dan data kelulusan ini terdaftar
                        secara resmi serta sah pada pangkalan data Balai
                        Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan
                        Kepulauan, Kementerian Ketenagakerjaan Republik
                        Indonesia.
                    </p>
                </div>

                <!-- Verified Details Content -->
                <div class="p-6 sm:p-8 space-y-6">
                    <!-- Certificate Number Highlighting -->
                    <div
                        class="bg-slate-50 dark:bg-slate-800/60 p-5 rounded-2xl border border-slate-200 dark:border-slate-700/60 text-center space-y-1"
                    >
                        <span
                            class="text-[10px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-widest block"
                        >
                            NOMOR REGISTRASI SERTIFIKAT
                        </span>
                        <p
                            class="text-lg sm:text-xl font-mono font-black text-slate-900 dark:text-white"
                        >
                            {{ data.certificate_number }}
                        </p>
                        <div class="pt-1">
                            <span
                                class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 dark:bg-emerald-950/70 dark:text-emerald-300"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                Tanggal Penerbitan: {{ data.issued_at }}
                            </span>
                        </div>
                    </div>

                    <!-- Details Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <!-- Card 1: Nama Peserta -->
                        <div
                            class="p-4 bg-slate-50/70 dark:bg-slate-800/40 rounded-2xl border border-slate-200/70 dark:border-slate-800 space-y-2 flex flex-col justify-between"
                        >
                            <div>
                                <span
                                    class="text-slate-400 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5"
                                >
                                    <User class="w-3.5 h-3.5 text-indigo-500" />
                                    Nama Penerima Sertifikat
                                </span>
                                <p
                                    class="text-base font-black text-slate-900 dark:text-white uppercase mt-1"
                                >
                                    {{ data.participant_name }}
                                </p>
                                <p
                                    v-if="data.participant_nik"
                                    class="text-[11px] font-mono text-slate-500 dark:text-slate-400 mt-0.5"
                                >
                                    NIK: {{ data.participant_nik }}
                                </p>
                            </div>
                            <div
                                class="pt-2 border-t border-slate-100 dark:border-slate-800/60"
                            >
                                <span
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"
                                >
                                    <CheckCircle2 class="w-3 h-3" /> Status:
                                    Dinyatakan Lulus &amp; Kompeten
                                </span>
                            </div>
                        </div>

                        <!-- Card 2: Asal Instansi / Utusan -->
                        <div
                            class="p-4 bg-slate-50/70 dark:bg-slate-800/40 rounded-2xl border border-slate-200/70 dark:border-slate-800 space-y-2 flex flex-col justify-between"
                        >
                            <div>
                                <span
                                    class="text-slate-400 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5"
                                >
                                    <Building2
                                        class="w-3.5 h-3.5 text-indigo-500"
                                    />
                                    Asal Instansi / Utusan Lembaga
                                </span>
                                <p
                                    class="text-sm font-bold text-slate-900 dark:text-white mt-1"
                                >
                                    {{ data.agency_or_institution }}
                                </p>
                            </div>
                            <div
                                class="pt-2 border-t border-slate-100 dark:border-slate-800/60"
                            >
                                <span
                                    v-if="
                                        data.agency_or_institution ===
                                        'Masyarakat Umum / Mandiri'
                                    "
                                    class="text-[11px] text-slate-500 dark:text-slate-400 font-medium"
                                >
                                    Kategori: Peserta Mandiri (Umum)
                                </span>
                                <span
                                    v-else
                                    class="text-[11px] text-indigo-600 dark:text-indigo-400 font-medium"
                                >
                                    Instansi / Organisasi Pengutus
                                </span>
                            </div>
                        </div>

                        <!-- Card 3: Program Pelatihan -->
                        <div
                            class="p-4 bg-slate-50/70 dark:bg-slate-800/40 rounded-2xl border border-slate-200/70 dark:border-slate-800 space-y-2 flex flex-col justify-between"
                        >
                            <div>
                                <span
                                    class="text-slate-400 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5"
                                >
                                    <Award
                                        class="w-3.5 h-3.5 text-indigo-500"
                                    />
                                    Program Pelatihan Vokasi
                                </span>
                                <p
                                    class="text-sm font-bold text-slate-900 dark:text-white mt-1"
                                >
                                    {{ data.course_title }}
                                </p>
                            </div>
                            <div
                                class="pt-2 border-t border-slate-100 dark:border-slate-800/60 flex flex-wrap gap-1.5 items-center"
                            >
                                <span
                                    class="px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 text-[10px] font-semibold"
                                >
                                    {{ data.category }}
                                </span>
                                <span
                                    class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-medium"
                                >
                                    {{ data.batch_name }}
                                </span>
                                <span
                                    class="px-2 py-0.5 rounded-md bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-[10px] font-medium"
                                >
                                    {{ data.duration_days }} Hari
                                </span>
                            </div>
                        </div>

                        <!-- Card 4: Metode Pembelajaran & Presensi -->
                        <div
                            class="p-4 bg-slate-50/70 dark:bg-slate-800/40 rounded-2xl border border-slate-200/70 dark:border-slate-800 space-y-2 flex flex-col justify-between"
                        >
                            <div>
                                <span
                                    class="text-slate-400 text-[10px] font-bold uppercase tracking-wider flex items-center gap-1.5"
                                >
                                    <FileCheck
                                        class="w-3.5 h-3.5 text-indigo-500"
                                    />
                                    Metode Pembelajaran &amp; Presensi
                                </span>
                                <p
                                    class="text-sm font-bold text-slate-900 dark:text-white mt-1"
                                >
                                    {{ data.attendance_method }}
                                </p>
                            </div>
                            <div
                                class="pt-2 border-t border-slate-100 dark:border-slate-800/60"
                            >
                                <span
                                    class="text-[11px] text-slate-500 dark:text-slate-400 font-medium"
                                >
                                    Kehadiran &amp; jam pelatihan tuntas 100%
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons (Download / Print) -->
                    <div
                        class="pt-2 flex flex-wrap items-center justify-center gap-3 print:hidden"
                    >
                        <a
                            v-if="data.download_url"
                            :href="data.download_url"
                            target="_blank"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white rounded-xl text-xs font-bold shadow-md hover:shadow-lg transition-all"
                        >
                            <Download class="w-4 h-4" />
                            <span>Unduh Salinan Sertifikat (PDF)</span>
                        </a>
                        <button
                            type="button"
                            @click="printPage"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-800 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-200 rounded-xl text-xs font-semibold transition-colors"
                        >
                            <Printer class="w-4 h-4" />
                            <span>Cetak Bukti Verifikasi</span>
                        </button>
                    </div>

                    <!-- Issuer Credential Footer -->
                    <div
                        class="border-t border-slate-200 dark:border-slate-800 pt-5 text-center text-xs text-slate-500 dark:text-slate-400 space-y-2"
                    >
                        <div class="space-y-0.5">
                            <p
                                class="font-bold text-slate-700 dark:text-slate-200 text-sm"
                            >
                                {{ data.institution }}
                            </p>
                            <p
                                class="text-xs text-slate-500 dark:text-slate-400"
                            >
                                {{ data.ministry }}
                            </p>
                        </div>
                        <div
                            class="p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800 max-w-lg mx-auto"
                        >
                            <p
                                class="text-[10px] text-slate-400 uppercase tracking-wider font-semibold"
                            >
                                Kode Autentikasi / Hash Keamanan TTE:
                            </p>
                            <p
                                class="text-[11px] font-mono font-medium text-slate-600 dark:text-slate-300 break-all select-all mt-0.5"
                            >
                                {{ hash }}
                            </p>
                        </div>
                        <p
                            class="text-[10px] text-slate-400 pt-1 leading-relaxed max-w-lg mx-auto"
                        >
                            Verifikasi keabsahan ini merupakan rujukan resmi
                            yang membuktikan bahwa pemegang sertifikat telah
                            menyelesaikan seluruh kewajiban kurikulum pelatihan
                            sesuai standar Balai Pelatihan Vokasi dan
                            Produktivitas (BPVP) Pangkajene dan Kepulauan.
                        </p>
                    </div>
                </div>
            </div>

            <!-- INVALID STATE -->
            <div
                v-else
                class="bg-white dark:bg-slate-900 rounded-3xl shadow-xl border border-rose-200 dark:border-rose-900/60 p-8 text-center space-y-5 max-w-md mx-auto"
            >
                <div
                    class="w-16 h-16 mx-auto bg-rose-50 dark:bg-rose-950/60 text-rose-600 rounded-2xl flex items-center justify-center shadow-inner"
                >
                    <XCircle class="w-10 h-10" />
                </div>
                <div>
                    <h3
                        class="text-lg font-black text-slate-900 dark:text-white"
                    >
                        Sertifikat Tidak Ditemukan / Tidak Valid
                    </h3>
                    <p
                        class="text-xs text-slate-500 dark:text-slate-400 mt-1.5 leading-relaxed"
                    >
                        Kode autentikasi verifikasi yang Anda pindai tidak cocok
                        dengan pangkalan data sertifikat resmi BPVP Pangkajene
                        dan Kepulauan.
                    </p>
                </div>

                <div
                    class="p-3.5 bg-slate-50 dark:bg-slate-800 rounded-xl font-mono text-xs text-slate-600 dark:text-slate-300 break-all border border-slate-200 dark:border-slate-700"
                >
                    <span
                        class="block text-[10px] text-slate-400 font-sans uppercase font-bold mb-1"
                        >Kode Hash yang Diperiksa:</span
                    >
                    {{ hash || "Tidak ada kode hash" }}
                </div>

                <div
                    class="text-left text-xs bg-amber-50 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 p-3.5 rounded-xl border border-amber-200/70 dark:border-amber-900/50 space-y-1.5"
                >
                    <div class="font-bold flex items-center gap-1.5">
                        <Info
                            class="w-4 h-4 shrink-0 text-amber-600 dark:text-amber-400"
                        />
                        Panduan Pengecekan:
                    </div>
                    <ul
                        class="list-disc list-inside space-y-1 text-[11px] text-amber-700 dark:text-amber-300/90"
                    >
                        <li>
                            Pastikan QR Code yang Anda pindai berasal langsung
                            dari dokumen sertifikat resmi.
                        </li>
                        <li>
                            Pastikan alamat URL tidak terpotong saat disalin
                            atau dikirimkan.
                        </li>
                        <li>
                            Hubungi pihak BPVP Pangkajene dan Kepulauan apabila
                            membutuhkan konfirmasi lebih lanjut.
                        </li>
                    </ul>
                </div>

                <div class="pt-2">
                    <a
                        href="/"
                        class="inline-block px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold shadow-md transition-colors"
                    >
                        Kembali ke Beranda
                    </a>
                </div>
            </div>
        </main>

        <!-- Footer -->
        <footer
            class="py-4 text-center text-xs text-slate-400 border-t border-slate-200 dark:border-slate-800 bg-white/50 dark:bg-slate-900/50 print:hidden"
        >
            &copy; {{ new Date().getFullYear() }} Balai Pelatihan Vokasi dan
            Produktivitas Pangkajene dan Kepulauan. Kementerian Ketenagakerjaan
            RI.
        </footer>
    </div>
</template>
