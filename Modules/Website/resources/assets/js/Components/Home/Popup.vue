<script setup>
import { ref, onMounted, onUnmounted } from "vue";

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const showPopup = ref(true);
const timeLeft = ref(10);
let timer = null;

const closePopup = () => {
    showPopup.value = false;
    if (timer) clearInterval(timer);
};

const getPopupUrl = (path) => {
    if (!path) return "";
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    const clean = path.replace(/^\/?storage\//, "").replace(/^\//, "");
    const v = props.settings?.updated_at
        ? `?v=${new Date(props.settings.updated_at).getTime()}`
        : "";
    return `/storage/${clean}${v}`;
};

onMounted(() => {
    if (props.settings?.is_popup_active && props.settings?.popup_image_path) {
        timer = setInterval(() => {
            timeLeft.value--;
            if (timeLeft.value <= 0) {
                closePopup();
            }
        }, 1000);
    } else {
        showPopup.value = false;
    }
});

onUnmounted(() => {
    if (timer) clearInterval(timer);
});
</script>

<template>
    <div
        v-if="
            settings?.is_popup_active && settings?.popup_image_path && showPopup
        "
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/60 backdrop-blur-sm transition-opacity duration-300"
    >
        <div
            class="bg-white rounded-3xl overflow-hidden max-w-md w-full relative shadow-2xl border border-slate-100/80 flex flex-col"
        >
            <!-- Close Button -->
            <button
                @click="closePopup"
                class="absolute top-4 right-4 bg-black/40 hover:bg-black/70 text-white rounded-full w-8 h-8 flex items-center justify-center font-bold text-sm transition shadow-md z-20 cursor-pointer"
            >
                ✕
            </button>

            <!-- Image -->
            <div class="w-full overflow-hidden">
                <a
                    v-if="settings?.popup_redirect_url"
                    :href="settings.popup_redirect_url"
                    target="_blank"
                    class="block"
                >
                    <img
                        :src="getPopupUrl(settings.popup_image_path)"
                        alt="Iklan Pengumuman"
                        class="w-full h-auto object-cover max-h-[65vh]"
                    />
                </a>
                <img
                    v-else
                    :src="getPopupUrl(settings.popup_image_path)"
                    alt="Iklan Pengumuman"
                    class="w-full h-auto object-cover max-h-[65vh]"
                />
            </div>

            <!-- Countdown Footer -->
            <div
                class="bg-slate-50 px-4 py-3.5 border-t border-slate-100 text-center select-none flex-shrink-0"
            >
                <p class="text-xs font-bold text-slate-500 tracking-wide">
                    Otomatis tertutup dalam
                    <span class="text-amber-500 text-sm font-black mx-1">{{
                        timeLeft
                    }}</span>
                    detik
                </p>
            </div>
        </div>
    </div>
</template>
