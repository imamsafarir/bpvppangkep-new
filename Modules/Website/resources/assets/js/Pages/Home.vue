<script setup>
import AppLayout from "../Layouts/AppLayout.vue";
import Hero from "../Components/Home/Hero.vue";
import CtaPelatihan from "../Components/Home/CtaPelatihan.vue";
import Berita from "../Components/Home/Berita.vue";
import About from "../Components/Home/About.vue";
import Statistik from "../Components/Home/Statistik.vue";
import Documents from "../Components/Home/Documents.vue";
import Faq from "../Components/Home/Faq.vue";
import Partners from "../Components/Home/Partners.vue";
import Popup from "../Components/Home/Popup.vue";

defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    berita_terbaru: {
        type: Array,
        default: () => [],
    },
    informasi: {
        type: Array,
        default: () => [],
    },
    partners: {
        type: Array,
        default: () => [],
    },
    faqs: {
        type: Array,
        default: () => [],
    },
    total_informasi: {
        type: Number,
        default: 0,
    },
    total_jdih: {
        type: Number,
        default: 0,
    },
    total_berita: {
        type: Number,
        default: 0,
    },
    total_unduhan: {
        type: Number,
        default: 0,
    },
    total_kunjungan: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <AppLayout title="Beranda" :settings="settings" :is-home="true">
        <div class="relative w-full overflow-hidden">
            <Hero :settings="settings" />
            <CtaPelatihan />
            <Berita :berita="berita_terbaru" />
            <About :settings="settings" />
            <Statistik
                :total-informasi="total_informasi"
                :total-jdih="total_jdih"
                :total-berita="total_berita"
                :total-unduhan="total_unduhan"
                :total-kunjungan="total_kunjungan"
            />
            <Documents :informasi="informasi" />
            <Faq :faqs="faqs" />
            <Partners :partners="partners" />
            <Popup :settings="settings" />
        </div>
    </AppLayout>
</template>
