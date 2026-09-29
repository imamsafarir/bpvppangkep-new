<script setup>
import { onMounted, ref, computed } from "vue";
import { Head, usePage } from "@inertiajs/vue3";
import {
    CheckCircle2,
    ArrowRight,
    ExternalLink,
    ShieldCheck,
} from "lucide-vue-next";

const props = defineProps({
    destinationUrl: { type: String, required: true },
});

const page = usePage();
const faviconUrl = computed(() => {
    const path = page.props.settings?.favicon_path;
    if (!path) return "/favicon.ico";
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    const clean = path.replace(/^\/?storage\//, "").replace(/^\//, "");
    return `/storage/${clean}`;
});

const countdown = ref(2);

onMounted(() => {
    const timer = setInterval(() => {
        countdown.value--;
        if (countdown.value <= 0) {
            clearInterval(timer);
            window.location.href = props.destinationUrl;
        }
    }, 1000);
});
</script>

<template>
    <Head title="Terima Kasih - BPVP Pangkep">
        <link v-if="faviconUrl" rel="icon" :href="faviconUrl" />
        <link v-if="faviconUrl" rel="shortcut icon" :href="faviconUrl" />
    </Head>

    <div
        class="min-h-screen bg-slate-50 flex flex-col justify-between p-4 sm:p-6 lg:p-8 font-sans antialiased text-zinc-800"
    >
        <!-- Top header -->
        <header
            class="w-full max-w-md mx-auto flex items-center justify-center py-2"
        >
            <div
                class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 shadow-2xs"
            >
                <ShieldCheck class="w-3.5 h-3.5 text-emerald-600" />
                <span>Tautan Resmi</span>
            </div>
        </header>

        <!-- Main Card Container -->
        <main class="w-full max-w-md mx-auto my-auto">
            <div
                class="w-full bg-white rounded-3xl border border-zinc-200 shadow-xl shadow-zinc-200/50 p-6 sm:p-8 text-center space-y-5 animate-in fade-in zoom-in-95 duration-300"
            >
                <!-- Icon Success -->
                <div
                    class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-emerald-50 text-emerald-600 ring-8 ring-emerald-50/60 mb-1"
                >
                    <CheckCircle2 class="w-12 h-12" />
                </div>

                <div class="space-y-1.5">
                    <h1
                        class="text-xl sm:text-2xl font-bold tracking-tight text-zinc-900"
                    >
                        Terima Kasih!
                    </h1>
                    <p
                        class="text-xs sm:text-sm text-zinc-500 leading-relaxed max-w-xs mx-auto"
                    >
                        Data Anda telah berhasil kami catat. Sedang
                        menghubungkan Anda ke tautan tujuan resmi...
                    </p>
                </div>

                <!-- Loader Spinner & Countdown -->
                <div
                    class="p-3 rounded-2xl bg-zinc-50 border border-zinc-200 flex items-center justify-center gap-2 text-blue-600 font-semibold text-xs"
                >
                    <svg
                        class="animate-spin h-4 w-4 text-blue-600"
                        fill="none"
                        viewBox="0 0 24 24"
                    >
                        <circle
                            class="opacity-25"
                            cx="12"
                            cy="12"
                            r="10"
                            stroke="currentColor"
                            stroke-width="4"
                        ></circle>
                        <path
                            class="opacity-75"
                            fill="currentColor"
                            d="M4 12a8 8 0 018-8v8H4z"
                        ></path>
                    </svg>
                    <span
                        >Mengalihkan otomatis dalam
                        {{ countdown }} detik...</span
                    >
                </div>

                <div class="pt-2">
                    <a
                        :href="destinationUrl"
                        class="w-full inline-flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-colors"
                    >
                        <span>Lanjutkan Sekarang</span>
                        <ExternalLink class="w-3.5 h-3.5" />
                    </a>
                </div>

                <div class="border-t border-zinc-100 pt-3">
                    <p class="text-[11px] text-zinc-400">
                        Balai Pelatihan Vokasi dan Produktivitas Pangkep
                    </p>
                </div>
            </div>
        </main>

        <!-- Bottom Footer -->
        <footer
            class="w-full max-w-md mx-auto text-center py-3 text-[11px] text-zinc-400"
        >
            &copy; {{ new Date().getFullYear() }} Balai Pelatihan Vokasi dan
            Produktivitas Pangkep
        </footer>
    </div>
</template>
