<script setup>
import { cva } from "class-variance-authority";
import { cn } from "@/lib/utils";

const badgeVariants = cva(
    "inline-flex items-center rounded-full border px-2.5 py-0.5 text-[11px] font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2 select-none",
    {
        variants: {
            variant: {
                default:
                    "border-transparent bg-zinc-900 text-zinc-50 shadow-2xs hover:bg-zinc-800",
                secondary:
                    "border-transparent bg-zinc-100 text-zinc-900 hover:bg-zinc-200",
                destructive:
                    "border-transparent bg-rose-50 text-rose-700 border-rose-200",
                outline: "text-zinc-950 border-zinc-200",
                success:
                    "border-emerald-200 bg-emerald-50 text-emerald-700 font-bold",
                info: "border-blue-200 bg-blue-50 text-blue-700 font-bold",
            },
        },
        defaultVariants: {
            variant: "default",
        },
    },
);

const props = defineProps({
    variant: {
        type: String,
        default: "default",
    },
    class: {
        type: String,
        default: "",
    },
});
</script>

<template>
    <div :class="cn(badgeVariants({ variant }), props.class)">
        <slot />
    </div>
</template>
