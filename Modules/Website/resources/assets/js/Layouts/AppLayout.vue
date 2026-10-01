<script setup>
import { computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import Navbar from "../Components/Navbar.vue";
import Footer from "../Components/Footer.vue";
import AccessibilityWidget from "../Components/AccessibilityWidget.vue";
import PwaInstallPrompt from "@/Components/PwaInstallPrompt.vue";

const props = defineProps({
    title: {
        type: String,
        default: "",
    },
    description: {
        type: String,
        default: "",
    },
    keywords: {
        type: String,
        default: "",
    },
    image: {
        type: String,
        default: "",
    },
    type: {
        type: String,
        default: "website",
    },
    settings: {
        type: Object,
        default: () => ({}),
    },
    isHome: {
        type: Boolean,
        default: false,
    },
});

const page = usePage();
const computedSettings = computed(() => {
    if (props.settings && Object.keys(props.settings).length > 0) {
        return props.settings;
    }
    return page.props.settings || {};
});

const siteName = computed(() => {
    const raw = computedSettings.value?.website_name;
    return raw && raw !== "Laravel" ? raw : "BPVP Pangkep";
});

const computedTitle = computed(() => {
    if (props.isHome) {
        return `${siteName.value} - Balai Pelatihan Vokasi & Produktivitas Kemnaker RI`;
    }
    if (props.title) {
        return `${props.title} | ${siteName.value} - Kemnaker RI`;
    }
    return `${siteName.value} - Balai Pelatihan Vokasi & Produktivitas Kemnaker RI`;
});

const defaultDescription =
    "Website Resmi Balai Pelatihan Vokasi dan Produktivitas (BPVP) Pangkajene dan Kepulauan - Kementerian Ketenagakerjaan RI. Pusat pelatihan kerja berbasis kompetensi, uji sertifikasi BNSP gratis, dan layanan keterbukaan informasi publik (PPID).";

const computedDescription = computed(() => {
    return props.description || defaultDescription;
});

const defaultKeywords =
    "BPVP Pangkep, Balai Pelatihan Vokasi dan Produktivitas Pangkep, Kemnaker RI, BLK Pangkep, Pelatihan Gratis, Sertifikasi BNSP, Pelatihan Kerja Makassar Pangkep, Kejuruan Otomotif, Teknik Las, Listrik, TIK, Garmen, PPID BPVP Pangkep, Pelatihan Vokasi Sulawesi Selatan";

const computedKeywords = computed(() => {
    return props.keywords
        ? `${props.keywords}, ${defaultKeywords}`
        : defaultKeywords;
});

const computedImage = computed(() => {
    if (props.image) {
        if (
            props.image.startsWith("http://") ||
            props.image.startsWith("https://")
        ) {
            return props.image;
        }
        if (props.image.startsWith("/")) {
            return typeof window !== "undefined"
                ? `${window.location.origin}${props.image}`
                : props.image;
        }
        return typeof window !== "undefined"
            ? `${window.location.origin}/${props.image}`
            : `/${props.image}`;
    }
    return typeof window !== "undefined"
        ? `${window.location.origin}/images/bpvp-pangkep-og.png`
        : "/images/bpvp-pangkep-og.png";
});

const computedCanonical = computed(() => {
    if (typeof window !== "undefined") {
        return window.location.href.split("?")[0];
    }
    return "";
});
</script>

<template>
    <Head>
        <title>{{ computedTitle }}</title>
        <meta name="title" :content="computedTitle" head-key="meta-title" />
        <meta
            name="description"
            :content="computedDescription"
            head-key="description"
        />
        <meta name="keywords" :content="computedKeywords" head-key="keywords" />
        <meta property="og:type" :content="type" head-key="og:type" />
        <meta
            property="og:site_name"
            :content="`${siteName} - Kemnaker RI`"
            head-key="og:site_name"
        />
        <meta
            v-if="computedCanonical"
            property="og:url"
            :content="computedCanonical"
            head-key="og:url"
        />
        <meta
            property="og:title"
            :content="computedTitle"
            head-key="og:title"
        />
        <meta
            property="og:description"
            :content="computedDescription"
            head-key="og:description"
        />
        <meta
            property="og:image"
            :content="computedImage"
            head-key="og:image"
        />
        <meta
            property="og:image:width"
            content="1200"
            head-key="og:image:width"
        />
        <meta
            property="og:image:height"
            content="630"
            head-key="og:image:height"
        />
        <meta
            property="og:image:alt"
            :content="computedTitle"
            head-key="og:image:alt"
        />
        <meta property="og:locale" content="id_ID" head-key="og:locale" />

        <meta
            name="twitter:card"
            content="summary_large_image"
            head-key="twitter:card"
        />
        <meta
            v-if="computedCanonical"
            name="twitter:url"
            :content="computedCanonical"
            head-key="twitter:url"
        />
        <meta
            name="twitter:title"
            :content="computedTitle"
            head-key="twitter:title"
        />
        <meta
            name="twitter:description"
            :content="computedDescription"
            head-key="twitter:description"
        />
        <meta
            name="twitter:image"
            :content="computedImage"
            head-key="twitter:image"
        />
        <meta
            name="twitter:site"
            content="@bpvppangkep"
            head-key="twitter:site"
        />

        <link
            v-if="computedSettings?.favicon_path"
            rel="icon"
            :href="`/storage/${computedSettings.favicon_path}?v=${computedSettings?.updated_at ? new Date(computedSettings.updated_at).getTime() : Date.now()}`"
        />
    </Head>

    <div
        class="min-h-screen flex flex-col bg-slate-50 text-slate-800 font-sans"
    >
        <!-- Navbar -->
        <Navbar :settings="computedSettings" :is-home="isHome" />

        <!-- Main Content -->
        <main class="flex-1 w-full" :class="isHome ? '' : 'pt-24'">
            <slot />
        </main>

        <!-- Footer -->
        <Footer :settings="computedSettings" />

        <!-- Accessibility Widget -->
        <AccessibilityWidget />

        <!-- PWA Install Prompt & Update Handler -->
        <PwaInstallPrompt />
    </div>
</template>
