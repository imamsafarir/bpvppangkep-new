<script setup>
import { computed } from "vue";
import { ArrowUp, ArrowDown, ArrowUpDown } from "lucide-vue-next";

const props = defineProps({
    title: {
        type: String,
        default: "",
    },
    column: {
        type: String,
        required: true,
    },
    sortKey: {
        type: String,
        default: "",
    },
    sortDirection: {
        type: String,
        default: "asc", // 'asc' or 'desc'
    },
    align: {
        type: String,
        default: "left", // 'left', 'center', 'right'
    },
});

const emit = defineEmits(["sort"]);

const isActive = computed(() => props.sortKey === props.column);

const isAsc = computed(
    () => isActive.value && (props.sortDirection || "").toLowerCase() === "asc",
);

const isDesc = computed(
    () =>
        isActive.value && (props.sortDirection || "").toLowerCase() === "desc",
);

const toggleSort = () => {
    let nextDirection = "asc";
    if (isActive.value) {
        nextDirection = isAsc.value ? "desc" : "asc";
    }
    emit("sort", props.column, nextDirection);
};
</script>

<template>
    <button
        type="button"
        @click="toggleSort"
        :class="[
            'group inline-flex items-center gap-1.5 py-1 font-semibold text-xs transition-colors cursor-pointer select-none focus:outline-hidden',
            isActive
                ? 'text-blue-600 font-bold'
                : 'text-slate-600 hover:text-slate-900',
            align === 'right'
                ? 'ml-auto justify-end'
                : align === 'center'
                  ? 'mx-auto justify-center'
                  : 'justify-start',
        ]"
        :title="`Urutkan berdasarkan ${title || column}`"
    >
        <span>
            <slot>{{ title }}</slot>
        </span>

        <span class="inline-flex items-center shrink-0">
            <ArrowUp
                v-if="isAsc"
                class="w-3.5 h-3.5 text-blue-600 transition-transform"
            />
            <ArrowDown
                v-else-if="isDesc"
                class="w-3.5 h-3.5 text-blue-600 transition-transform"
            />
            <ArrowUpDown
                v-else
                class="w-3.5 h-3.5 text-slate-300 group-hover:text-slate-500 transition-colors"
            />
        </span>
    </button>
</template>
