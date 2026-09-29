<script setup>
import { TabsList } from "radix-vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    class: { type: String, default: "" },
});
</script>

<template>
    <TabsList
        :class="
            cn(
                'inline-flex h-10 items-center justify-center rounded-xl bg-zinc-100 p-1 text-zinc-500 select-none',
                props.class,
            )
        "
    >
        <slot />
    </TabsList>
</template>
