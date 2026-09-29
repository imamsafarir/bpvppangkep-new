<script setup>
import { DialogTitle } from "radix-vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    class: { type: String, default: "" },
});
</script>

<template>
    <DialogTitle
        :class="
            cn(
                'text-base font-bold leading-none tracking-tight text-zinc-900',
                props.class,
            )
        "
    >
        <slot />
    </DialogTitle>
</template>
