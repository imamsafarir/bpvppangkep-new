<script setup>
import { ref, computed } from "vue";
import { Head, useForm, Link, usePage } from "@inertiajs/vue3";

defineProps({
    status: {
        type: String,
        default: null,
    },
});

const page = usePage();
const settings = computed(() => page.props.settings || {});

const faviconUrl = computed(() => {
    const path = settings.value?.favicon_path;
    if (!path) return "/favicon.ico";
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    const clean = path.replace(/^\/?storage\//, "").replace(/^\//, "");
    const v = settings.value?.updated_at
        ? new Date(settings.value.updated_at).getTime()
        : Date.now();
    return `/storage/${clean}?v=${v}`;
});

const websiteName = computed(
    () => settings.value?.website_name || "BPVP Pangkep",
);

const showPassword = ref(false);

const form = useForm({
    login: "",
    password: "",
    remember: false,
});

const submit = () => {
    form.post("/login", {
        onFinish: () => form.reset("password"),
    });
};
</script>

<template>
    <Head :title="`Masuk Akun - ${websiteName}`">
        <link rel="icon" type="image/x-icon" :href="faviconUrl" />
        <link rel="icon" :href="faviconUrl" />
        <link rel="shortcut icon" :href="faviconUrl" />
        <link rel="apple-touch-icon" :href="faviconUrl" />
    </Head>

    <div
        class="min-h-screen bg-slate-900 flex flex-col justify-center py-12 sm:px-6 lg:px-8 relative overflow-hidden font-sans select-none"
    >
        <!-- Ambient glowing backgrounds -->
        <div
            class="absolute -top-40 -left-40 w-96 h-96 bg-blue-600/20 rounded-full blur-3xl pointer-events-none"
        ></div>
        <div
            class="absolute -bottom-40 -right-40 w-96 h-96 bg-indigo-600/20 rounded-full blur-3xl pointer-events-none"
        ></div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10 px-4">
            <!-- Logo & Brand Header -->
            <div class="text-center mb-8">
                <Link
                    href="/"
                    class="inline-flex items-center gap-3 group transition-transform active:scale-95"
                >
                    <div
                        class="w-13 h-13 rounded-2xl bg-gradient-to-tr from-blue-600 via-indigo-600 to-sky-400 p-0.5 shadow-xl shadow-blue-500/20 flex items-center justify-center"
                    >
                        <div
                            class="w-full h-full bg-slate-900 rounded-[14px] flex items-center justify-center p-2"
                        >
                            <img
                                v-if="settings?.favicon_path"
                                :src="faviconUrl"
                                class="w-8 h-8 object-contain"
                                alt="Logo"
                            />
                            <i
                                v-else
                                class="fas fa-cubes text-2xl text-transparent bg-clip-text bg-gradient-to-tr from-blue-400 to-sky-300"
                            ></i>
                        </div>
                    </div>
                    <div class="text-left">
                        <span
                            class="block text-xl font-black text-white tracking-tight leading-none group-hover:text-blue-400 transition-colors"
                        >
                            {{ websiteName }}
                        </span>
                        <span
                            class="text-[11px] font-semibold text-slate-400 uppercase tracking-widest mt-1 block"
                        >
                            Portal Layanan Terpadu
                        </span>
                    </div>
                </Link>
            </div>

            <!-- Main Login Card -->
            <div
                class="bg-slate-800/80 backdrop-blur-xl border border-slate-700/60 shadow-2xl rounded-3xl p-6 sm:p-8"
            >
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-white tracking-tight">
                        Masuk ke Sistem
                    </h2>
                    <p class="text-xs text-slate-400 mt-1">
                        Gunakan email atau username terdaftar untuk mengakses
                        modul &amp; aplikasi Anda.
                    </p>
                </div>

                <!-- Session Status Flash -->
                <div
                    v-if="status"
                    class="mb-5 p-3.5 rounded-xl bg-blue-500/10 border border-blue-500/20 text-blue-400 text-xs font-medium flex items-center gap-2.5"
                >
                    <i class="fas fa-info-circle text-sm"></i>
                    <span>{{ status }}</span>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Username / Email Field -->
                    <div>
                        <label
                            for="login"
                            class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2"
                        >
                            Email atau Username
                        </label>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500"
                            >
                                <i class="fas fa-user text-sm"></i>
                            </span>
                            <input
                                id="login"
                                v-model="form.login"
                                type="text"
                                autocomplete="username"
                                required
                                placeholder="nama@email.com atau username"
                                class="w-full pl-10 pr-4 py-3 bg-slate-900/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                :class="{
                                    'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20':
                                        form.errors.login,
                                }"
                            />
                        </div>
                        <p
                            v-if="form.errors.login"
                            class="mt-1.5 text-xs text-rose-400 font-medium flex items-center gap-1"
                        >
                            <i
                                class="fas fa-exclamation-triangle text-[10px]"
                            ></i>
                            {{ form.errors.login }}
                        </p>
                    </div>

                    <!-- Password Field -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label
                                for="password"
                                class="block text-xs font-bold text-slate-300 uppercase tracking-wider"
                            >
                                Kata Sandi
                            </label>
                        </div>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500"
                            >
                                <i class="fas fa-lock text-sm"></i>
                            </span>
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                autocomplete="current-password"
                                required
                                placeholder="••••••••"
                                class="w-full pl-10 pr-10 py-3 bg-slate-900/80 border border-slate-700 rounded-xl text-white placeholder-slate-500 text-xs focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 transition-all"
                                :class="{
                                    'border-rose-500 focus:border-rose-500 focus:ring-rose-500/20':
                                        form.errors.password,
                                }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-500 hover:text-slate-300 transition-colors cursor-pointer"
                                tabindex="-1"
                            >
                                <i
                                    :class="
                                        showPassword
                                            ? 'fas fa-eye-slash'
                                            : 'fas fa-eye'
                                    "
                                    class="text-xs"
                                ></i>
                            </button>
                        </div>
                        <p
                            v-if="form.errors.password"
                            class="mt-1.5 text-xs text-rose-400 font-medium flex items-center gap-1"
                        >
                            <i
                                class="fas fa-exclamation-triangle text-[10px]"
                            ></i>
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me -->
                    <div class="flex items-center justify-between pt-1">
                        <label
                            class="flex items-center gap-2 cursor-pointer select-none"
                        >
                            <input
                                type="checkbox"
                                v-model="form.remember"
                                class="w-4 h-4 rounded bg-slate-900 border-slate-700 text-blue-600 focus:ring-blue-500/30 focus:ring-offset-0 focus:ring-2 cursor-pointer"
                            />
                            <span
                                class="text-xs text-slate-400 hover:text-slate-300 font-medium"
                                >Ingat Sesi Saya</span
                            >
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div class="pt-2">
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full py-3.5 px-4 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-500 hover:to-indigo-500 active:from-blue-700 active:to-indigo-700 text-white font-bold rounded-xl text-xs shadow-lg shadow-blue-600/25 transition-all transform active:scale-[0.98] disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2 cursor-pointer"
                        >
                            <i
                                v-if="form.processing"
                                class="fas fa-spinner fa-spin text-sm"
                            ></i>
                            <span v-if="form.processing">Memverifikasi...</span>
                            <span v-else>Masuk ke Portal</span>
                            <i
                                v-if="!form.processing"
                                class="fas fa-arrow-right text-[11px]"
                            ></i>
                        </button>
                    </div>
                </form>

                <!-- Divider and Back to Home -->
                <div
                    class="mt-6 pt-5 border-t border-slate-700/60 flex items-center justify-between text-xs"
                >
                    <Link
                        href="/"
                        class="text-slate-400 hover:text-white transition-colors flex items-center gap-1.5"
                    >
                        <i class="fas fa-arrow-left text-[10px]"></i>
                        <span>Kembali ke Beranda</span>
                    </Link>

                    <span class="text-slate-500 text-[11px]">
                        &copy; {{ new Date().getFullYear() }} BPVP Pangkep
                    </span>
                </div>
            </div>

            <!-- Info Footer -->
            <div class="text-center mt-6 text-xs text-slate-500">
                <p>
                    Akses multi-aplikasi terpusat berbasis hak akses (Role-Based
                    Access Control)
                </p>
            </div>
        </div>
    </div>
</template>
