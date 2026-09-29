<script setup>
import { Primitive } from "radix-vue";
import { cva } from "class-variance-authority";
import { cn } from "@/lib/utils";

const buttonVariants = cva(
    "inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-xl text-xs font-semibold ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 cursor-pointer select-none",
    {
        variants: {
            variant: {
                default: "bg-zinc-900 text-zinc-50 shadow-sm hover:bg-zinc-800",
                destructive:
                    "bg-rose-600 text-white shadow-xs hover:bg-rose-700",
                outline:
                    "border border-zinc-200 bg-white hover:bg-zinc-100 hover:text-zinc-900 text-zinc-700 shadow-2xs",
                secondary:
                    "bg-zinc-100 text-zinc-900 shadow-2xs hover:bg-zinc-200",
                ghost: "hover:bg-zinc-100 hover:text-zinc-900 text-zinc-600",
                link: "text-blue-600 underline-offset-4 hover:underline",
                primary: "bg-blue-600 text-white shadow-sm hover:bg-blue-700",
            },
            size: {
                default: "h-9 px-4 py-2",
                sm: "h-8 rounded-lg px-3 text-xs",
                lg: "h-10 rounded-xl px-6 text-sm",
                icon: "h-9 w-9",
            },
        },
        defaultVariants: {
            variant: "default",
            size: "default",
        },
    },
);

const props = defineProps({
    as: {
        type: String,
        default: "button",
    },
    asChild: {
        type: Boolean,
        default: false,
    },
    variant: {
        type: String,
        default: "default",
    },
    size: {
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
    <Primitive
        :as="as"
        :as-child="asChild"
        :class="cn(buttonVariants({ variant, size }), props.class)"
    >
        <slot />
    </Primitive>
</template>
