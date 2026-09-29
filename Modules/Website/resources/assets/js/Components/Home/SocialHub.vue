<script setup>
import { ref, onMounted, onUnmounted, computed } from "vue";

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
});

const trackRef = ref(null);
const scrollSpeed = 0.7;
const currentSpeed = ref(0.7);
const scrollPercent = ref(0);
const isDragging = ref(false);
let animationId = null;

const fbUrl = computed(
    () =>
        props.settings?.facebook_url || "https://www.facebook.com/bpvppangkep",
);
const igUrl = computed(
    () =>
        props.settings?.instagram_url ||
        "https://www.instagram.com/bpvppangkep",
);
const ytUrl = computed(
    () =>
        props.settings?.youtube_url || "https://www.youtube.com/channel/UCxxxx",
);
const ttUrl = computed(
    () => props.settings?.tiktok_url || "https://www.tiktok.com/@bpvp.pangkep",
);

const items = computed(() => {
    const ig = igUrl.value;
    const tt = ttUrl.value;
    const fb = fbUrl.value;
    const yt = ytUrl.value;

    const base = [
        {
            name: "Instagram Official",
            icon: "fab fa-instagram text-pink-500",
            url: "instagram.com/bpvppangkep",
            type: "instagram",
            border: "border-pink-500",
            src: ig.includes("/embed") ? ig : ig.replace(/\/$/, "") + "/embed",
        },
        {
            name: "TikTok Feed Stream",
            icon: "fab fa-tiktok text-slate-300",
            url: "tiktok.com/@bpvp.pangkep",
            type: "tiktok",
            border: "border-amber-400",
            src: tt.includes("embed") ? tt : tt.replace("com/@", "com/embed/@"),
        },
        {
            name: "Facebook Timeline",
            icon: "fab fa-facebook text-blue-500",
            url: "facebook.com/bpvppangkep",
            type: "facebook",
            border: "border-blue-500",
            src: `https://www.facebook.com/plugins/page.php?href=${encodeURIComponent(fb)}&tabs=timeline&width=500&height=490&small_header=true&adapt_container_width=true&hide_cover=false&show_facepile=false`,
        },
        {
            name: "YouTube Channel",
            icon: "fab fa-youtube text-red-500",
            url: "youtube.com/bpvppangkep",
            type: "youtube",
            border: "border-red-500",
            src: yt.includes("embed")
                ? yt
                : yt.replace(
                      "youtube.com/channel/",
                      "youtube.com/embed/videoseries?list=",
                  ),
        },
    ];

    return [...base, ...base];
});

const onScroll = () => {
    if (isDragging.value || !trackRef.value) return;
    const el = trackRef.value;
    const maxScroll = el.scrollWidth - el.clientWidth;
    if (maxScroll > 0) {
        scrollPercent.value = (el.scrollLeft / maxScroll) * 100;
    }
};

const handleRangeInput = () => {
    if (!trackRef.value) return;
    const el = trackRef.value;
    el.scrollLeft =
        (scrollPercent.value / 100) * (el.scrollWidth - el.clientWidth);
};

const handleRangeChange = () => {
    isDragging.value = false;
    currentSpeed.value = scrollSpeed;
};

onMounted(() => {
    const loop = () => {
        if (!isDragging.value && trackRef.value) {
            trackRef.value.scrollLeft += currentSpeed.value;
            if (trackRef.value.scrollLeft >= trackRef.value.scrollWidth / 2) {
                trackRef.value.scrollLeft = 0;
            }
        }
        animationId = requestAnimationFrame(loop);
    };
    animationId = requestAnimationFrame(loop);
});

onUnmounted(() => {
    if (animationId) {
        cancelAnimationFrame(animationId);
    }
});
</script>

<template>
    <section
        id="multi-browser-hub"
        class="bg-gradient-to-b from-white to-slate-50/80 py-16 border-t border-slate-100 overflow-hidden"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- HEADER -->
            <div class="text-center mb-10">
                <span
                    class="text-xs font-bold text-amber-500 uppercase tracking-widest bg-amber-50 px-3 py-1 rounded-md border border-amber-200/50 inline-block mb-1"
                >
                    <i class="fas fa-desktop mr-1"></i> Live Desktop Hub
                </span>
                <h2
                    class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                >
                    Multi-Browser Live Feed
                </h2>
                <p class="text-xs text-slate-500 mt-1">
                    Arahkan kursor untuk menghentikan carousel, atau seret
                    slider manual di bawah untuk mengontrol halaman.
                </p>
            </div>

            <!-- CAROUSEL WRAPPER -->
            <div class="w-full space-y-6">
                <!-- TRACK BARIS FRAME BROWSER -->
                <div
                    ref="trackRef"
                    class="flex gap-8 py-6 overflow-x-auto scrollbar-none select-none"
                    style="
                        white-space: nowrap;
                        -ms-overflow-style: none;
                        scrollbar-width: none;
                    "
                    @scroll="onScroll"
                    @mouseenter="!isDragging && (currentSpeed = 0)"
                    @mouseleave="!isDragging && (currentSpeed = scrollSpeed)"
                    @touchstart="!isDragging && (currentSpeed = 0)"
                    @touchend="!isDragging && (currentSpeed = scrollSpeed)"
                >
                    <div
                        v-for="(item, index) in items"
                        :key="index"
                        class="inline-block w-[420px] sm:w-[500px] h-[560px] bg-slate-950 rounded-2xl shadow-xl border border-slate-800 overflow-hidden flex-shrink-0 flex flex-col transition-all duration-300"
                    >
                        <!-- BROWSER BAR HEADER -->
                        <div
                            :class="[
                                'bg-slate-900 px-4 pt-3 pb-2 border-t-2 border-b border-slate-800 flex-shrink-0',
                                item.border,
                            ]"
                        >
                            <div
                                class="flex items-center justify-between mb-1.5"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex gap-1.5">
                                        <span
                                            class="w-2 h-2 bg-red-500 rounded-full block"
                                        ></span>
                                        <span
                                            class="w-2 h-2 bg-yellow-500 rounded-full block"
                                        ></span>
                                        <span
                                            class="w-2 h-2 bg-green-500 rounded-full block"
                                        ></span>
                                    </div>
                                    <span
                                        class="text-[11px] text-slate-300 font-bold truncate flex items-center gap-1.5"
                                    >
                                        <i :class="[item.icon, 'text-xs']"></i>
                                        {{ item.name }}
                                    </span>
                                </div>
                                <i
                                    class="fas fa-redo-alt text-slate-600 text-[9px]"
                                ></i>
                            </div>
                            <div
                                class="w-full bg-slate-950 text-slate-500 text-[9px] font-mono px-2.5 py-1 rounded border border-slate-800/80 truncate text-left"
                            >
                                🔒 https://{{ item.url }}
                            </div>
                        </div>

                        <!-- CONTENT VIEWPORT SCREEN -->
                        <div
                            class="flex-1 w-full bg-white overflow-hidden relative"
                            style="height: calc(100% - 75px)"
                        >
                            <iframe
                                v-if="item.src && !item.src.includes('UCxxxx')"
                                :src="item.src"
                                class="w-full border-none bg-white block"
                                style="
                                    width: 100%;
                                    height: 100% !important;
                                    min-height: 480px;
                                    max-height: 100%;
                                    margin: 0;
                                    padding: 0;
                                "
                                loading="lazy"
                                allow="
                                    autoplay;
                                    clipboard-write;
                                    encrypted-media;
                                    picture-in-picture;
                                    web-share;
                                "
                                allowfullscreen
                            ></iframe>
                            <div
                                v-else
                                class="absolute inset-0 flex flex-col items-center justify-center text-slate-500 text-xs p-6 text-center bg-slate-900"
                            >
                                <i
                                    :class="[
                                        item.icon,
                                        'text-3xl mb-2 text-white/20',
                                    ]"
                                ></i>
                                <span class="text-white/60 font-bold block"
                                    >Kanal Belum Terhubung</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CONTROL INPUT RANGE SLIDER BAR MANUALLY -->
                <div
                    class="max-w-xs mx-auto pt-2 flex items-center gap-3 justify-center select-none"
                >
                    <i
                        class="fas fa-chevron-left text-slate-400 text-[10px]"
                    ></i>

                    <div class="relative w-full h-1 bg-slate-200 rounded-full">
                        <div
                            class="absolute top-0 left-0 h-full bg-amber-400 rounded-full transition-all duration-75"
                            :style="{ width: scrollPercent + '%' }"
                        ></div>

                        <input
                            type="range"
                            min="0"
                            max="100"
                            v-model="scrollPercent"
                            @mousedown="isDragging = true"
                            @touchstart="isDragging = true"
                            @input="handleRangeInput"
                            @change="handleRangeChange"
                            class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-30"
                        />
                    </div>

                    <i
                        class="fas fa-chevron-right text-slate-400 text-[10px]"
                    ></i>
                </div>
            </div>
        </div>
    </section>
</template>
