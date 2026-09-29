<script setup>
import { DialogClose } from "radix-vue";

const props = defineProps({
    asChild: { type: Boolean, default: false },
});
</script>

<template>
    <DialogClose v-bind="props">
        <slot />
    </DialogClose>
</template>
