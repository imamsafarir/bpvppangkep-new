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
</script>

<template>
    <Head
        :title="
            title
                ? `${title} - ${computedSettings?.website_name ?? 'BPVP Pangkep'}`
                : (computedSettings?.website_name ?? 'BPVP Pangkep')
        "
    >
        <link
            v-if="computedSettings?.favicon_path"
            rel="icon"
            :href="`/storage/${computedSettings.favicon_path}`"
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
