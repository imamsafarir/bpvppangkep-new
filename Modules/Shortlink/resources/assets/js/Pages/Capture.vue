<script setup>
import { computed } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";
import {
    Link2,
    ArrowRight,
    User,
    Phone,
    Mail,
    ShieldCheck,
    CheckCircle2,
} from "lucide-vue-next";

const props = defineProps({
    shortlink: { type: Object, required: true },
    fields: { type: Array, default: () => ["nama", "whatsapp"] },
    errors: { type: Object, default: () => ({}) },
});

const page = usePage();
const faviconUrl = computed(() => {
    const path = page.props.settings?.favicon_path;
    if (!path) return "/favicon.ico";
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    const clean = path.replace(/^\/?storage\//, "").replace(/^\//, "");
    return `/storage/${clean}`;
});

const form = useForm({
    nama: "",
    whatsapp: "",
    email: "",
});

const isValid = computed(() => {
    if (props.fields.includes("nama") && !form.nama.trim()) return false;
    if (props.fields.includes("whatsapp") && !form.whatsapp.trim())
        return false;
    if (props.fields.includes("email")) {
        if (!form.email.trim()) return false;
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailPattern.test(form.email.trim())) return false;
    }
    return true;
});

const submitForm = () => {
    if (!isValid.value || form.processing) return;
    form.post(`/s/${props.shortlink.code}`);
};
</script>

<template>
    <Head
        :title="shortlink.custom_title || 'Formulir Pengunjung - BPVP Pangkep'"
    >
        <link v-if="faviconUrl" rel="icon" :href="faviconUrl" />
        <link v-if="faviconUrl" rel="shortcut icon" :href="faviconUrl" />
    </Head>

    <div
        class="min-h-screen bg-slate-50 flex flex-col justify-between p-4 sm:p-6 lg:p-8 font-sans antialiased text-zinc-800"
    >
        <!-- Top decorative navbar / header -->
        <header
            class="w-full max-w-lg mx-auto flex items-center justify-center py-2"
        >
            <div
                class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-3 py-1 rounded-full border border-emerald-200 shadow-2xs"
            >
                <ShieldCheck class="w-3.5 h-3.5 text-emerald-600" />
                <span>Tautan Resmi</span>
            </div>
        </header>

        <!-- Main Card Container -->
        <main class="w-full max-w-lg mx-auto my-auto">
            <div
                class="bg-white rounded-3xl border border-zinc-200 shadow-xl shadow-zinc-200/50 overflow-hidden"
            >
                <!-- Card Header -->
                <div
                    class="bg-gradient-to-br from-blue-700 via-blue-600 to-indigo-700 text-white p-6 sm:p-8 text-center relative overflow-hidden"
                >
                    <div
                        class="absolute -right-8 -top-8 w-32 h-32 rounded-full bg-white/10 blur-xl pointer-events-none"
                    ></div>
                    <div
                        class="absolute -left-8 -bottom-8 w-32 h-32 rounded-full bg-blue-400/20 blur-xl pointer-events-none"
                    ></div>

                    <div
                        class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-white/15 backdrop-blur-md mb-3 ring-4 ring-white/20"
                    >
                        <Link2 class="w-7 h-7 text-white" />
                    </div>

                    <h1
                        class="text-xl sm:text-2xl font-bold tracking-tight text-white leading-snug"
                    >
                        {{
                            shortlink.custom_title ||
                            "Selamat Datang di BPVP Pangkep"
                        }}
                    </h1>
                </div>

                <!-- Card Body Form -->
                <div class="p-6 sm:p-8 space-y-5">
                    <div
                        v-if="shortlink.custom_description"
                        class="text-xs text-zinc-600 bg-blue-50/70 border border-blue-100 p-3.5 rounded-2xl leading-relaxed"
                    >
                        {{ shortlink.custom_description }}
                    </div>

                    <div
                        v-else
                        class="text-xs text-zinc-500 text-center leading-relaxed"
                    >
                        Silakan lengkapi informasi singkat di bawah ini sebelum
                        melanjutkan ke tautan tujuan resmi.
                    </div>

                    <!-- Errors alert -->
                    <div
                        v-if="Object.keys(errors).length > 0"
                        class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs space-y-1"
                    >
                        <div class="font-bold">Mohon periksa isian Anda:</div>
                        <ul class="list-disc list-inside space-y-0.5 pl-1">
                            <li v-for="(error, key) in errors" :key="key">
                                {{ error }}
                            </li>
                        </ul>
                    </div>

                    <form @submit.prevent="submitForm" class="space-y-4">
                        <!-- Nama Lengkap -->
                        <div v-if="fields.includes('nama')" class="space-y-1.5">
                            <label
                                class="block text-xs font-bold text-zinc-700"
                            >
                                Nama Lengkap
                                <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400"
                                >
                                    <User class="w-4 h-4" />
                                </span>
                                <input
                                    type="text"
                                    v-model="form.nama"
                                    required
                                    placeholder="Masukkan nama lengkap Anda"
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-zinc-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-zinc-800 transition outline-none"
                                />
                            </div>
                        </div>

                        <!-- WhatsApp -->
                        <div
                            v-if="fields.includes('whatsapp')"
                            class="space-y-1.5"
                        >
                            <label
                                class="block text-xs font-bold text-zinc-700"
                            >
                                Nomor WhatsApp
                                <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400"
                                >
                                    <Phone class="w-4 h-4" />
                                </span>
                                <input
                                    type="tel"
                                    v-model="form.whatsapp"
                                    required
                                    placeholder="Contoh: 081234567890"
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-zinc-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-zinc-800 transition outline-none font-mono"
                                />
                            </div>
                        </div>

                        <!-- Email -->
                        <div
                            v-if="fields.includes('email')"
                            class="space-y-1.5"
                        >
                            <label
                                class="block text-xs font-bold text-zinc-700"
                            >
                                Alamat Email
                                <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-zinc-400"
                                >
                                    <Mail class="w-4 h-4" />
                                </span>
                                <input
                                    type="email"
                                    v-model="form.email"
                                    required
                                    placeholder="nama@email.com"
                                    class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-zinc-200 focus:border-blue-600 focus:ring-2 focus:ring-blue-100 text-sm text-zinc-800 transition outline-none"
                                />
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <button
                            type="submit"
                            :disabled="!isValid || form.processing"
                            :class="[
                                'w-full py-3.5 px-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2 group mt-2',
                                isValid && !form.processing
                                    ? 'cursor-pointer bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white shadow-lg shadow-blue-600/25'
                                    : 'cursor-not-allowed bg-zinc-200 text-zinc-400 shadow-none',
                            ]"
                        >
                            <span>{{
                                form.processing
                                    ? "Menyimpan data..."
                                    : shortlink.custom_button_text ||
                                      "Lanjutkan ke Tautan"
                            }}</span>
                            <ArrowRight
                                class="w-4 h-4 transform group-hover:translate-x-1 transition-transform"
                            />
                        </button>
                    </form>
                </div>

                <!-- Card Footer Info -->
                <div
                    class="bg-zinc-50 px-6 py-3 border-t border-zinc-100 text-center"
                >
                    <p class="text-[11px] text-zinc-400 font-medium">
                        Data Anda aman dan hanya digunakan untuk keperluan
                        layanan resmi BPVP Pangkep.
                    </p>
                </div>
            </div>
        </main>

        <!-- Bottom Footer -->
        <footer
            class="w-full max-w-lg mx-auto text-center py-3 text-[11px] text-zinc-400"
        >
            &copy; {{ new Date().getFullYear() }} Balai Pelatihan Vokasi dan
            Produktivitas Pangkep
        </footer>
    </div>
</template>
