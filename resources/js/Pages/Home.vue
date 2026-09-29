<script setup>
import { Head } from "@inertiajs/vue3";

defineProps({
    appName: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <Head title="Home" />

    <div
        class="flex min-h-screen flex-col items-center justify-center bg-gray-50"
    >
        <div class="text-center">
            <h1 class="text-4xl font-semibold text-gray-800">
                Selamat datang di {{ appName }}
            </h1>
            <p class="mt-4 text-gray-500">
                Laravel + Inertia.js + Vue 3 + Modules siap digunakan.
            </p>
        </div>
    </div>
</template>
