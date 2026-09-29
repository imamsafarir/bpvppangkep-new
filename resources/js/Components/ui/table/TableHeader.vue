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
    <thead :class="cn('[&_tr]:border-b bg-zinc-50/60', props.class)">
        <slot />
    </thead>
</template>
