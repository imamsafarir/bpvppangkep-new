<script setup>
import { TabsTrigger } from "radix-vue";
import { cn } from "@/lib/utils";

const props = defineProps({
    value: { type: String, required: true },
    disabled: { type: Boolean, default: false },
    class: { type: String, default: "" },
});
</script>

<template>
    <TabsTrigger
        :value="value"
        :disabled="disabled"
        :class="
            cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-lg px-3 py-1.5 text-xs font-semibold ring-offset-background transition-all focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 data-[state=active]:bg-white data-[state=active]:text-zinc-950 data-[state=active]:shadow-xs cursor-pointer',
                props.class,
            )
        "
    >
        <slot />
    </TabsTrigger>
</template>
