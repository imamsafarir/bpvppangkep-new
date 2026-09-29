<script setup>
import { useVModel } from "@vueuse/core";
import { cn } from "@/lib/utils";

const props = defineProps({
    defaultValue: {
        type: [String, Number],
        default: undefined,
    },
    modelValue: {
        type: [String, Number],
        default: undefined,
    },
    class: {
        type: String,
        default: "",
    },
});

const emits = defineEmits(["update:modelValue"]);
</script>

<template>
    <input
        :value="modelValue"
        @input="$emit('update:modelValue', $event.target.value)"
        :class="
            cn(
                'flex h-10 w-full rounded-xl border border-zinc-200 bg-white px-3 py-2 text-xs ring-offset-background file:border-0 file:bg-transparent file:text-xs file:font-medium placeholder:text-zinc-400 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-zinc-950 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 transition-shadow text-zinc-900',
                props.class,
            )
        "
    />
</template>
