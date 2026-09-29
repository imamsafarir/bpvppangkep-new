<script setup>
import { TabsRoot, useForwardPropsEmits } from "radix-vue";

const props = defineProps({
    defaultValue: { type: String, default: undefined },
    modelValue: { type: String, default: undefined },
    orientation: { type: String, default: "horizontal" },
    dir: { type: String, default: "ltr" },
    activationMode: { type: String, default: "automatic" },
});
const emits = defineEmits(["update:modelValue"]);
const forwarded = useForwardPropsEmits(props, emits);
</script>

<template>
    <TabsRoot v-bind="forwarded">
        <slot />
    </TabsRoot>
</template>
