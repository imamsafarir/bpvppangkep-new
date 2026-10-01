<script setup>
import { ref } from "vue";
import { Head, router } from "@inertiajs/vue3";
import axios from "axios";
import {
    GraduationCap,
    Mail,
    User,
    CreditCard,
    Phone,
    Lock,
    ArrowRight,
    CheckCircle2,
    AlertCircle,
    BookOpen,
    MapPin,
    Hash,
} from "lucide-vue-next";

// Steps: 1 = Input Email, 2 = Konfirmasi Profil
const step = ref(1);
const emailInput = ref("");
const isChecking = ref(false);
const errorMessage = ref("");

const participantData = ref({
    id: null,
    name: "",
    email: "",
    training_transaction_code: "",
    nik: "",
    phone: "",
    agency_or_institution: "",
    address: "",
    gender: "",
});
const registeredCourses = ref([]);

const checkEmail = async () => {
    if (!emailInput.value) {
        errorMessage.value = "Silakan masukkan alamat email Anda.";
        return;
    }

    isChecking.value = true;
    errorMessage.value = "";

    try {
        const response = await axios.post("/lms/login/check-email", {
            email: emailInput.value,
        });

        const data = response.data;

        if (data.registered) {
            participantData.value = {
                id: data.participant.id,
                name: data.participant.name,
                email: data.participant.email,
                training_transaction_code:
                    data.participant.training_transaction_code || "",
                nik: data.participant.nik || "",
                phone: data.participant.phone || "",
                agency_or_institution:
                    data.participant.agency_or_institution || "",
                address: data.participant.address || "",
                gender: data.participant.gender || "",
            };
            registeredCourses.value = data.courses || [];
            step.value = 2;
        } else {
            errorMessage.value = data.message || "Email tidak ditemukan.";
        }
    } catch (err) {
        if (err.response?.data?.message) {
            errorMessage.value = err.response.data.message;
        } else if (err.response?.data?.errors?.email?.[0]) {
            errorMessage.value = err.response.data.errors.email[0];
        } else {
            errorMessage.value =
                "Terjadi kesalahan saat memeriksa email. Silakan coba lagi.";
        }
    } finally {
        isChecking.value = false;
    }
};

const confirmProfile = () => {
    router.post("/lms/login/confirm", {
        email: participantData.value.email,
        name: participantData.value.name,
        nik: participantData.value.nik,
        phone: participantData.value.phone,
        address: participantData.value.address,
        gender: participantData.value.gender,
    });
};
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-blue-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8"
    >
        <Head title="Login Siswa LMS - BPVP Pangkep" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div
                class="inline-flex p-3 bg-indigo-600/20 rounded-2xl ring-1 ring-indigo-500/30 text-indigo-400 mb-4 shadow-xl"
            >
                <GraduationCap class="w-10 h-10" />
            </div>
            <h2
                class="text-2xl sm:text-3xl font-black tracking-tight text-white"
            >
                LMS BPVP Pangkep
            </h2>
            <p class="mt-2 text-xs sm:text-sm text-slate-400">
                Portal Pembelajaran Mandiri & Presensi Tatap Muka Resmi
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg px-4">
            <div
                class="bg-white/10 backdrop-blur-xl border border-white/10 py-8 px-6 sm:px-10 rounded-2xl shadow-2xl text-slate-200"
            >
                <!-- STEP 1: INPUT EMAIL -->
                <div v-if="step === 1" class="space-y-6">
                    <div>
                        <span
                            class="text-[11px] font-bold tracking-widest text-indigo-400 uppercase"
                            >Langkah 1 dari 2</span
                        >
                        <h3 class="text-lg font-bold text-white mt-1">
                            Masukkan Email Terdaftar
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Gunakan alamat email yang Anda gunakan saat
                            mendaftar pelatihan di BPVP Pangkep.
                        </p>
                    </div>

                    <div
                        v-if="errorMessage"
                        class="p-3.5 bg-rose-500/20 border border-rose-500/30 rounded-xl text-rose-200 text-xs flex items-start gap-2"
                    >
                        <AlertCircle
                            class="w-4 h-4 text-rose-400 shrink-0 mt-0.5"
                        />
                        <span>{{ errorMessage }}</span>
                    </div>

                    <form @submit.prevent="checkEmail" class="space-y-4">
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-300 mb-1.5"
                            >
                                Alamat Email Peserta
                            </label>
                            <div class="relative">
                                <Mail
                                    class="w-4 h-4 absolute left-3.5 top-3.5 text-slate-400"
                                />
                                <input
                                    v-model="emailInput"
                                    type="email"
                                    required
                                    placeholder="nama@email.com"
                                    class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-900/60 border border-slate-700/80 text-white placeholder-slate-500 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="isChecking"
                            class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-lg shadow-indigo-600/30 disabled:opacity-50"
                        >
                            <span>{{
                                isChecking ? "Memeriksa Data..." : "Lanjutkan"
                            }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </form>

                    <div
                        class="pt-4 border-t border-white/5 text-center text-xs text-slate-400"
                    >
                        Belum mendaftar pelatihan? Kunjungi
                        <a href="/" class="text-indigo-400 hover:underline"
                            >Portal BPVP Pangkep</a
                        >
                    </div>
                </div>

                <!-- STEP 2: KONFIRMASI DATA PROFIL -->
                <div v-else-if="step === 2" class="space-y-6">
                    <div>
                        <span
                            class="text-[11px] font-bold tracking-widest text-emerald-400 uppercase"
                            >Langkah 2 dari 2</span
                        >
                        <h3 class="text-lg font-bold text-white mt-1">
                            Verifikasi Data Identitas Peserta
                        </h3>
                        <p class="text-xs text-slate-400 mt-1">
                            Periksa kembali data diri Anda. Data ini telah
                            diverifikasi dan akan dicetak pada Sertifikat
                            Kelulusan resmi Anda.
                        </p>
                    </div>

                    <!-- Read-only Security Notice -->
                    <div
                        class="p-3 bg-indigo-500/15 border border-indigo-500/30 rounded-xl text-indigo-200 text-xs flex items-start gap-2.5"
                    >
                        <Lock class="w-4 h-4 text-indigo-400 shrink-0 mt-0.5" />
                        <span class="leading-relaxed">
                            Data profil disinkronkan langsung dari basis data
                            pendaftaran resmi dan bersifat
                            <strong>tetap (hanya untuk dilihat)</strong> demi
                            keabsahan sertifikat ber-QR TTE.
                        </span>
                    </div>

                    <form @submit.prevent="confirmProfile" class="space-y-4">
                        <!-- Email & Transaction Code Info -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-slate-300 mb-1"
                                >
                                    Email Terdaftar
                                </label>
                                <div class="relative">
                                    <Mail
                                        class="w-4 h-4 absolute left-3 top-3 text-slate-400"
                                    />
                                    <input
                                        v-model="participantData.email"
                                        type="email"
                                        disabled
                                        readonly
                                        class="w-full pl-9 pr-3 py-2 rounded-lg bg-slate-900/80 border border-slate-700/60 text-slate-200 text-xs cursor-not-allowed select-none"
                                    />
                                </div>
                            </div>

                            <div
                                v-if="participantData.training_transaction_code"
                            >
                                <label
                                    class="block text-[11px] font-semibold text-slate-300 mb-1"
                                >
                                    Kode Transaksi Pelatihan
                                </label>
                                <div class="relative">
                                    <Hash
                                        class="w-4 h-4 absolute left-3 top-3 text-slate-400"
                                    />
                                    <input
                                        :value="
                                            participantData.training_transaction_code
                                        "
                                        type="text"
                                        disabled
                                        readonly
                                        class="w-full pl-9 pr-3 py-2 rounded-lg bg-slate-900/80 border border-slate-700/60 text-indigo-300 text-xs font-mono font-bold cursor-not-allowed select-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Name (Read-only) -->
                        <div>
                            <label
                                class="block text-[11px] font-semibold text-slate-300 mb-1"
                            >
                                Nama Lengkap & Gelar (Sesuai KTP / Ijazah)
                            </label>
                            <div class="relative">
                                <User
                                    class="w-4 h-4 absolute left-3 top-3 text-slate-400"
                                />
                                <input
                                    v-model="participantData.name"
                                    type="text"
                                    disabled
                                    readonly
                                    class="w-full pl-9 pr-3 py-2 rounded-lg bg-slate-900/80 border border-slate-700/60 text-white font-bold text-xs cursor-not-allowed select-none"
                                />
                            </div>
                        </div>

                        <!-- NIK & Phone (Read-only) -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-slate-300 mb-1"
                                >
                                    NIK (KTP)
                                </label>
                                <div class="relative">
                                    <CreditCard
                                        class="w-4 h-4 absolute left-3 top-3 text-slate-400"
                                    />
                                    <input
                                        v-model="participantData.nik"
                                        type="text"
                                        disabled
                                        readonly
                                        placeholder="-"
                                        class="w-full pl-9 pr-3 py-2 rounded-lg bg-slate-900/80 border border-slate-700/60 text-slate-200 text-xs font-mono cursor-not-allowed select-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    class="block text-[11px] font-semibold text-slate-300 mb-1"
                                >
                                    No. Handphone / WhatsApp
                                </label>
                                <div class="relative">
                                    <Phone
                                        class="w-4 h-4 absolute left-3 top-3 text-slate-400"
                                    />
                                    <input
                                        v-model="participantData.phone"
                                        type="text"
                                        disabled
                                        readonly
                                        placeholder="-"
                                        class="w-full pl-9 pr-3 py-2 rounded-lg bg-slate-900/80 border border-slate-700/60 text-slate-200 text-xs cursor-not-allowed select-none"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Address (Read-only) -->
                        <div>
                            <label
                                class="block text-[11px] font-semibold text-slate-300 mb-1"
                            >
                                Alamat Lengkap / Domisili
                            </label>
                            <div class="relative">
                                <MapPin
                                    class="w-4 h-4 absolute left-3 top-3 text-slate-400"
                                />
                                <textarea
                                    v-model="participantData.address"
                                    rows="2"
                                    disabled
                                    readonly
                                    placeholder="-"
                                    class="w-full pl-9 pr-3 py-2 rounded-lg bg-slate-900/80 border border-slate-700/60 text-slate-200 text-xs cursor-not-allowed select-none resize-none"
                                ></textarea>
                            </div>
                        </div>

                        <!-- Enrolled Classes Badge -->
                        <div
                            class="p-3 bg-indigo-950/40 border border-indigo-500/20 rounded-xl space-y-1.5"
                        >
                            <span
                                class="text-[11px] font-bold text-indigo-300 flex items-center gap-1.5"
                            >
                                <BookOpen class="w-3.5 h-3.5" />
                                Kelas yang Terdaftar:
                            </span>
                            <div class="space-y-1">
                                <div
                                    v-for="c in registeredCourses"
                                    :key="c.id"
                                    class="text-xs text-white font-medium bg-white/5 px-2.5 py-1 rounded flex items-center justify-between"
                                >
                                    <span>&bull; {{ c.title }}</span>
                                    <span
                                        v-if="c.batch_name"
                                        class="text-indigo-300 text-[10px]"
                                        >Batch {{ c.batch_name }}</span
                                    >
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 pt-2">
                            <button
                                type="button"
                                @click="step = 1"
                                class="w-1/3 py-2.5 px-3 rounded-xl text-xs font-semibold text-slate-300 bg-white/5 hover:bg-white/10 transition-colors"
                            >
                                Ganti Email
                            </button>
                            <button
                                type="submit"
                                class="w-2/3 flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white bg-emerald-600 hover:bg-emerald-500 transition-all shadow-lg shadow-emerald-600/30 active:scale-95"
                            >
                                <CheckCircle2 class="w-4 h-4" />
                                <span>Konfirmasi & Masuk Dashboard</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>
