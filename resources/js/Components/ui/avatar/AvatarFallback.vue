<script setup>
import { AvatarFallback } from "radix-vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    class: { type: String, default: "" },
});
</script>

<template>
    <AvatarFallback
        :class="
            cn(
                'flex h-full w-full items-center justify-center rounded-full bg-zinc-100 font-bold text-xs text-zinc-700 select-none',
                props.class,
            )
        "
    >
        <slot />
    </AvatarFallback>
</template>
