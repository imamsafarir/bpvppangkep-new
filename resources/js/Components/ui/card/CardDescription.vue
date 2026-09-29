<script setup>
import { cn } from "@/lib/utils";

const props = defineProps({
    class: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <p :class="cn('text-xs text-zinc-500', props.class)">
        <slot />
    </p>
</template>
