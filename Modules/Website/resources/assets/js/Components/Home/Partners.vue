<script setup>
import { computed } from "vue";

const props = defineProps({
    partners: {
        type: Array,
        default: () => [],
    },
});

const loopItems = computed(() => {
    if (props.partners.length <= 5) return props.partners;
    return [
        ...props.partners,
        ...props.partners,
        ...props.partners,
        ...props.partners,
    ];
});
</script>

<template>
    <section class="py-10 bg-white overflow-hidden border-y border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8 text-center">
            <h3
                class="text-[10px] sm:text-xs font-black text-slate-400 uppercase tracking-[0.2em]"
            >
                Telah Dipercaya & Bekerja Sama Dengan
            </h3>
        </div>

        <template v-if="partners.length > 0">
            <!-- 1 - 5 Partners: Centered Static -->
            <div
                v-if="partners.length <= 5"
                class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8"
            >
                <div
                    class="flex flex-wrap items-center justify-center gap-10 sm:gap-16"
                >
                    <div
                        v-for="(item, idx) in partners"
                        :key="idx"
                        class="flex items-center gap-3 grayscale hover:grayscale-0 opacity-70 hover:opacity-100 transition-all duration-300 cursor-pointer select-none"
                    >
                        <img
                            v-if="item.logo"
                            :src="`/storage/${item.logo}`"
                            :alt="item.nama_instansi ?? 'Mitra'"
                            class="h-10 sm:h-12 w-auto max-w-[160px] object-contain flex-shrink-0"
                        />
                        <div
                            v-else
                            class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center text-blue-600 shadow-inner border border-slate-200 flex-shrink-0"
                        >
                            <i class="fas fa-building text-sm"></i>
                        </div>
                        <span
                            v-if="item.nama_instansi"
                            class="text-base sm:text-lg font-black text-slate-800 whitespace-nowrap tracking-tight"
                        >
                            {{ item.nama_instansi }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- > 5 Partners: Marquee Slider -->
            <div v-else class="relative w-full flex overflow-x-hidden group">
                <div
                    class="absolute inset-y-0 left-0 w-40 sm:w-96 bg-gradient-to-r from-white to-transparent z-10 pointer-events-none"
                ></div>
                <div
                    class="absolute inset-y-0 right-0 w-40 sm:w-96 bg-gradient-to-l from-white to-transparent z-10 pointer-events-none"
                ></div>

                <div
                    class="animate-scroll-x flex items-center gap-12 sm:gap-16 pl-12 sm:pl-16"
                >
                    <div
                        v-for="(item, idx) in loopItems"
                        :key="idx"
                        class="flex items-center gap-3 grayscale hover:grayscale-0 opacity-60 hover:opacity-100 transition-all duration-300 cursor-pointer select-none"
                    >
                        <img
                            v-if="item.logo"
                            :src="`/storage/${item.logo}`"
                            :alt="item.nama_instansi ?? 'Mitra'"
                            class="h-10 sm:h-12 w-auto max-w-[140px] object-contain flex-shrink-0"
                        />
                        <div
                            v-else
                            class="w-10 h-10 bg-slate-50 rounded-full flex items-center justify-center text-blue-600 shadow-inner border border-slate-200 flex-shrink-0"
                        >
                            <i class="fas fa-building text-sm"></i>
                        </div>
                        <span
                            v-if="item.nama_instansi"
                            class="text-base sm:text-lg font-black text-slate-800 whitespace-nowrap tracking-tight"
                        >
                            {{ item.nama_instansi }}
                        </span>
                    </div>
                </div>
            </div>
        </template>

        <div v-else class="text-center text-xs text-slate-400 italic py-6">
            Belum ada data instansi kerja sama yang ditambahkan.
        </div>
    </section>
</template>

<style scoped>
@keyframes scroll-x {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-50%);
    }
}

.animate-scroll-x {
    animation: scroll-x 40s linear infinite;
    width: max-content;
}

.group:hover .animate-scroll-x {
    animation-play-state: paused;
}
</style>
