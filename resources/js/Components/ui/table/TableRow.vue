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
    <tr
        :class="
            cn(
                'border-b border-zinc-100 transition-colors hover:bg-zinc-50/70 data-[state=selected]:bg-zinc-100',
                props.class,
            )
        "
    >
        <slot />
    </tr>
</template>
