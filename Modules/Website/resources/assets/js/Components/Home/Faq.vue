<script setup>
import { ref } from "vue";

defineProps({
    faqs: {
        type: Array,
        default: () => [
            {
                pertanyaan:
                    "Bagaimana cara mendaftar pelatihan di BPVP Pangkep?",
                jawaban:
                    "Pendaftaran dapat dilakukan secara online melalui portal resmi SIAPkerja / Skillhub Kemnaker (skillhub.kemnaker.go.id) atau langsung datang ke Kios Siap Kerja di BPVP Pangkep.",
            },
            {
                pertanyaan: "Apakah pelatihan di BPVP Pangkep dipungut biaya?",
                jawaban:
                    "Seluruh program pelatihan reguler (APBN) di BPVP Pangkep <strong>GRATIS (tidak dipungut biaya)</strong>. Peserta bahkan mendapatkan fasilitas seragam, konsumsi, dan sertifikat pelatihan.",
            },
            {
                pertanyaan: "Apa saja persyaratan untuk mengikuti pelatihan?",
                jawaban:
                    "Persyaratan umum mencakup: WNI, usia minimal 17 tahun, sehat jasmani dan rohani, tidak sedang menempuh pendidikan formal, serta melampirkan fotokopi KTP dan ijazah terakhir.",
            },
            {
                pertanyaan: "Berapa lama durasi program pelatihan?",
                jawaban:
                    "Durasi pelatihan bervariasi sesuai program kejuruan, umumnya berlangsung antara 1 hingga 3 bulan (160 - 320 Jam Pelatihan / JP).",
            },
        ],
    },
});

const activeTab = ref(null);

const toggleTab = (index) => {
    activeTab.value = activeTab.value === index ? null : index;
};
</script>

<template>
    <section id="faq" class="bg-slate-50 py-24 scroll-mt-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6">
            <div class="text-center mb-12">
                <span
                    class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-2"
                    >Pusat Bantuan</span
                >
                <h3
                    class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight mb-3"
                >
                    Hal yang sering ditanyakan
                </h3>
                <p class="text-slate-500 text-sm sm:text-base max-w-xl mx-auto">
                    Temukan jawaban atas pertanyaan umum terkait program
                    pelatihan Vokasi Nasional kami.
                </p>
            </div>

            <div class="space-y-3">
                <div
                    v-for="(faq, index) in faqs"
                    :key="index"
                    class="bg-white rounded-xl border border-slate-200/60 shadow-xs hover:border-blue-500/30 transition-colors overflow-hidden"
                >
                    <div
                        @click="toggleTab(index)"
                        class="p-5 flex justify-between items-center cursor-pointer select-none group"
                    >
                        <h4
                            class="font-bold text-sm sm:text-base text-slate-800 group-hover:text-blue-600 transition-colors pr-6"
                        >
                            {{ faq.pertanyaan }}
                        </h4>
                        <div
                            class="w-8 h-8 rounded-full bg-slate-50 flex items-center justify-center flex-shrink-0 group-hover:bg-blue-50 transition-colors"
                        >
                            <i
                                class="fas text-xs transition-transform duration-300"
                                :class="
                                    activeTab === index
                                        ? 'fa-chevron-up text-blue-600'
                                        : 'fa-chevron-down text-slate-400'
                                "
                            ></i>
                        </div>
                    </div>

                    <div
                        v-show="activeTab === index"
                        class="px-5 pb-6 text-sm text-slate-600 leading-relaxed border-t border-slate-50 pt-4"
                    >
                        <div v-html="faq.jawaban"></div>
                    </div>
                </div>
            </div>

            <div
                class="mt-12 text-center bg-white p-8 rounded-2xl border border-slate-200/60 shadow-sm"
            >
                <h4 class="text-lg font-bold text-slate-800 mb-2">
                    Masih punya pertanyaan?
                </h4>
                <p class="text-sm text-slate-500 mb-6">
                    Tim dukungan kami siap membantu menjawab pertanyaan Anda
                    lebih lanjut.
                </p>
                <a
                    href="https://bantuan.kemnaker.go.id/"
                    target="_blank"
                    class="inline-flex items-center justify-center bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded-xl transition-all shadow-md gap-2"
                >
                    <i class="fas fa-headset text-sm"></i> Kunjungi Pusat
                    Bantuan
                </a>
            </div>
        </div>
    </section>
</template>
