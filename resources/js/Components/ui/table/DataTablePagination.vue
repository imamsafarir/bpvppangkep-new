<script setup>
import { computed } from "vue";
import { router } from "@inertiajs/vue3";
import {
    ChevronLeft,
    ChevronRight,
    ChevronsLeft,
    ChevronsRight,
} from "lucide-vue-next";
import { Button } from "@/Components/ui/button";

const props = defineProps({
    pagination: {
        type: Object,
        default: () => ({}),
    },
    // Optional client-side pagination props
    currentPage: {
        type: Number,
        default: 1,
    },
    totalPages: {
        type: Number,
        default: 1,
    },
    totalItems: {
        type: Number,
        default: 0,
    },
    from: {
        type: Number,
        default: 0,
    },
    to: {
        type: Number,
        default: 0,
    },
});

const emit = defineEmits(["page-change"]);

const isServerSide = computed(() => {
    return Boolean(
        props.pagination &&
        (props.pagination.links?.length > 0 ||
            props.pagination.total !== undefined),
    );
});

const displayTotal = computed(() => {
    if (isServerSide.value) return props.pagination.total ?? 0;
    return props.totalItems ?? 0;
});

const displayFrom = computed(() => {
    if (isServerSide.value) {
        return props.pagination.from ?? (displayTotal.value > 0 ? 1 : 0);
    }
    return props.from || (displayTotal.value > 0 ? 1 : 0);
});

const displayTo = computed(() => {
    if (isServerSide.value) {
        return props.pagination.to ?? displayTotal.value;
    }
    return props.to || displayTotal.value;
});

const currentPageNum = computed(() => {
    if (isServerSide.value) return props.pagination.current_page || 1;
    return props.currentPage || 1;
});

const lastPageNum = computed(() => {
    if (isServerSide.value) return props.pagination.last_page || 1;
    return props.totalPages || 1;
});

const firstPageUrl = computed(() => {
    if (!isServerSide.value) return null;
    return props.pagination.first_page_url || null;
});

const lastPageUrl = computed(() => {
    if (!isServerSide.value) return null;
    return props.pagination.last_page_url || null;
});

const prevPageUrl = computed(() => {
    if (!isServerSide.value) return null;
    return props.pagination.prev_page_url || null;
});

const nextPageUrl = computed(() => {
    if (!isServerSide.value) return null;
    return props.pagination.next_page_url || null;
});

const pageLinks = computed(() => {
    if (!isServerSide.value) return [];
    const allLinks = props.pagination.links || [];
    if (allLinks.length <= 2) {
        return allLinks.filter(
            (l) =>
                !l.label.includes("&laquo;") &&
                !l.label.includes("&raquo;") &&
                !l.label.toLowerCase().includes("previous") &&
                !l.label.toLowerCase().includes("next"),
        );
    }
    return allLinks.slice(1, -1);
});

const visitUrl = (url) => {
    if (!url) return;
    router.visit(url, {
        preserveScroll: true,
        preserveState: true,
    });
};

const goToClientPage = (page) => {
    if (page < 1 || page > props.totalPages || page === props.currentPage)
        return;
    emit("page-change", page);
};

// Generate client-side pagination page items
const clientPages = computed(() => {
    const current = props.currentPage || 1;
    const total = props.totalPages || 1;
    if (total <= 7) {
        return Array.from({ length: total }, (_, i) => i + 1);
    }
    if (current <= 4) {
        return [1, 2, 3, 4, 5, "...", total];
    }
    if (current >= total - 3) {
        return [1, "...", total - 4, total - 3, total - 2, total - 1, total];
    }
    return [1, "...", current - 1, current, current + 1, "...", total];
});
</script>

<template>
    <div
        v-if="displayTotal > 0"
        class="flex flex-col sm:flex-row items-center justify-between gap-4 px-4 py-3 bg-white border-t border-slate-200/80 text-xs text-slate-500 select-none"
    >
        <!-- Info text (Left side) -->
        <div class="flex items-center gap-2 text-xs text-slate-500 font-medium">
            <span>
                Menampilkan
                <span class="font-bold text-slate-900">{{ displayFrom }}</span>
                sampai
                <span class="font-bold text-slate-900">{{ displayTo }}</span>
                dari
                <span class="font-bold text-slate-900">{{ displayTotal }}</span>
                data
            </span>
            <span v-if="lastPageNum > 1" class="text-slate-300">|</span>
            <span
                v-if="lastPageNum > 1"
                class="text-[11px] text-slate-400 font-mono"
            >
                Hal {{ currentPageNum }} / {{ lastPageNum }}
            </span>
        </div>

        <!-- Pagination Controls (Far Right side) -->
        <!-- Server-side Pagination Links (Inertia) -->
        <div v-if="isServerSide" class="flex items-center gap-1 sm:ml-auto">
            <!-- First Page (ChevronsLeft) -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPageNum <= 1 || !firstPageUrl"
                @click="visitUrl(firstPageUrl)"
                class="h-8 w-8 p-0 rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none cursor-pointer"
                title="Halaman Pertama"
            >
                <ChevronsLeft class="w-3.5 h-3.5" />
            </Button>

            <!-- Previous Page (ChevronLeft) -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPageNum <= 1 || !prevPageUrl"
                @click="visitUrl(prevPageUrl)"
                class="h-8 px-2.5 rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none font-semibold gap-1 cursor-pointer"
                title="Halaman Sebelumnya"
            >
                <ChevronLeft class="w-3.5 h-3.5" />
                <span class="hidden md:inline">Sebelumnya</span>
            </Button>

            <!-- Numbered Pages on the Right -->
            <template v-if="pageLinks.length > 0">
                <template v-for="(link, i) in pageLinks" :key="i">
                    <span
                        v-if="link.label === '...'"
                        class="px-1 text-slate-400 font-mono"
                        >...</span
                    >

                    <Button
                        v-else
                        :variant="link.active ? 'default' : 'outline'"
                        size="sm"
                        :disabled="!link.url"
                        @click="visitUrl(link.url)"
                        :class="[
                            'h-8 min-w-8 px-2 rounded-xl text-xs font-semibold font-mono transition-all cursor-pointer',
                            link.active
                                ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-xs font-bold border-transparent'
                                : 'border-slate-200 text-slate-700 hover:bg-slate-50',
                        ]"
                    >
                        <span v-html="link.label"></span>
                    </Button>
                </template>
            </template>

            <!-- Fallback if only 1 page: show active [ 1 ] button -->
            <Button
                v-else
                variant="default"
                size="sm"
                disabled
                class="h-8 min-w-8 px-2 rounded-xl text-xs font-bold font-mono bg-blue-600 text-white border-transparent shadow-xs"
            >
                1
            </Button>

            <!-- Next Page (ChevronRight) -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPageNum >= lastPageNum || !nextPageUrl"
                @click="visitUrl(nextPageUrl)"
                class="h-8 px-2.5 rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none font-semibold gap-1 cursor-pointer"
                title="Halaman Selanjutnya"
            >
                <span class="hidden md:inline">Selanjutnya</span>
                <ChevronRight class="w-3.5 h-3.5" />
            </Button>

            <!-- Last Page (ChevronsRight) -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPageNum >= lastPageNum || !lastPageUrl"
                @click="visitUrl(lastPageUrl)"
                class="h-8 w-8 p-0 rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none cursor-pointer"
                title="Halaman Terakhir"
            >
                <ChevronsRight class="w-3.5 h-3.5" />
            </Button>
        </div>

        <!-- Client-side Pagination Controls -->
        <div v-else class="flex items-center gap-1 sm:ml-auto">
            <!-- First Page -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage <= 1"
                @click="goToClientPage(1)"
                class="h-8 w-8 p-0 rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none cursor-pointer"
                title="Halaman Pertama"
            >
                <ChevronsLeft class="w-3.5 h-3.5" />
            </Button>

            <!-- Previous Page -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage <= 1"
                @click="goToClientPage(currentPage - 1)"
                class="h-8 px-2.5 rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none font-semibold gap-1 cursor-pointer"
            >
                <ChevronLeft class="w-3.5 h-3.5" />
                <span class="hidden md:inline">Sebelumnya</span>
            </Button>

            <!-- Page numbers -->
            <template v-for="(p, idx) in clientPages" :key="idx">
                <span v-if="p === '...'" class="px-1 text-slate-400 font-mono"
                    >...</span
                >
                <Button
                    v-else
                    :variant="currentPage === p ? 'default' : 'outline'"
                    size="sm"
                    @click="goToClientPage(p)"
                    :class="[
                        'h-8 min-w-8 px-2 rounded-xl text-xs font-semibold font-mono transition-all cursor-pointer',
                        currentPage === p
                            ? 'bg-blue-600 hover:bg-blue-700 text-white shadow-xs font-bold border-transparent'
                            : 'border-slate-200 text-slate-700 hover:bg-slate-50',
                    ]"
                >
                    {{ p }}
                </Button>
            </template>

            <!-- Next Page -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage >= totalPages"
                @click="goToClientPage(currentPage + 1)"
                class="h-8 px-2.5 rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none font-semibold gap-1 cursor-pointer"
            >
                <span class="hidden md:inline">Selanjutnya</span>
                <ChevronRight class="w-3.5 h-3.5" />
            </Button>

            <!-- Last Page -->
            <Button
                variant="outline"
                size="sm"
                :disabled="currentPage >= totalPages"
                @click="goToClientPage(totalPages)"
                class="h-8 w-8 p-0 rounded-xl border-slate-200 text-slate-700 hover:bg-slate-50 disabled:opacity-30 disabled:pointer-events-none cursor-pointer"
                title="Halaman Terakhir"
            >
                <ChevronsRight class="w-3.5 h-3.5" />
            </Button>
        </div>
    </div>
</template>
