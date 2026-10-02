<script setup>
import { ref, computed } from "vue";
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
    Loader2,
    ShieldCheck,
    Eye,
    EyeOff,
} from "lucide-vue-next";

// Steps: 1 = Input Email, 2 = Konfirmasi Profil
const step = ref(1);
const emailInput = ref("");
const isChecking = ref(false);
const isConfirming = ref(false);
const errorMessage = ref("");
const agreedToCommitment = ref(false);
const showSensitiveData = ref(false);
const nikVerificationInput = ref("");
const showNikPrompt = ref(false);
const nikVerificationError = ref("");

const toggleSensitiveData = () => {
    if (showSensitiveData.value) {
        showSensitiveData.value = false;
        showNikPrompt.value = false;
        nikVerificationInput.value = "";
        nikVerificationError.value = "";
        return;
    }
    showNikPrompt.value = true;
    nikVerificationInput.value = "";
    nikVerificationError.value = "";
};

const verifyNikAndReveal = () => {
    if (!nikVerificationInput.value.trim()) {
        nikVerificationError.value = "Silakan masukkan NIK Anda.";
        return;
    }
    if (nikVerificationInput.value.trim() === participantData.value.nik) {
        showSensitiveData.value = true;
        showNikPrompt.value = false;
        nikVerificationError.value = "";
        nikVerificationInput.value = "";
    } else {
        nikVerificationError.value =
            "NIK tidak sesuai dengan data terdaftar. Silakan coba lagi.";
    }
};

const cancelNikVerification = () => {
    showNikPrompt.value = false;
    nikVerificationInput.value = "";
    nikVerificationError.value = "";
};

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

/**
 * Mask/censor sensitive data helpers
 */
const maskEmail = (email) => {
    if (!email) return "-";
    const [local, domain] = email.split("@");
    if (!domain) return "***@***";
    const visibleLocal = local.slice(0, 3);
    const domainParts = domain.split(".");
    const maskedDomain =
        domainParts[0].slice(0, 2) +
        "***." +
        domainParts.slice(1).join(".");
    return `${visibleLocal}***@${maskedDomain}`;
};

const maskNik = (nik) => {
    if (!nik) return "-";
    if (nik.length <= 6) return "******" + nik.slice(-2);
    return nik.slice(0, 4) + "••••••••" + nik.slice(-4);
};

const maskPhone = (phone) => {
    if (!phone) return "-";
    if (phone.length <= 6) return "****" + phone.slice(-2);
    return phone.slice(0, 4) + "••••" + phone.slice(-3);
};

const maskAddress = (address) => {
    if (!address) return "-";
    const words = address.split(" ");
    if (words.length <= 2) return "********";
    return words.slice(0, 2).join(" ") + " ••••••••";
};

const maskedEmail = computed(() =>
    showSensitiveData.value
        ? participantData.value.email
        : maskEmail(participantData.value.email),
);
const maskedNik = computed(() =>
    showSensitiveData.value
        ? participantData.value.nik
        : maskNik(participantData.value.nik),
);
const maskedPhone = computed(() =>
    showSensitiveData.value
        ? participantData.value.phone
        : maskPhone(participantData.value.phone),
);
const maskedAddress = computed(() =>
    showSensitiveData.value
        ? participantData.value.address
        : maskAddress(participantData.value.address),
);
const maskedTransactionCode = computed(() => {
    const code = participantData.value.training_transaction_code;
    if (!code) return "";
    if (showSensitiveData.value) return code;
    if (code.length <= 4) return "••••";
    return code.slice(0, 3) + "••••" + code.slice(-3);
});

const canSubmit = computed(() => agreedToCommitment.value && !isConfirming.value);

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
            agreedToCommitment.value = false;
            showSensitiveData.value = false;
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
    if (!agreedToCommitment.value) return;

    isConfirming.value = true;
    router.post(
        "/lms/login/confirm",
        {
            email: participantData.value.email,
            name: participantData.value.name,
            nik: participantData.value.nik,
            phone: participantData.value.phone,
            address: participantData.value.address,
            gender: participantData.value.gender,
        },
        {
            onFinish: () => {
                isConfirming.value = false;
            },
        },
    );
};
</script>

<template>
    <div
        class="min-h-screen bg-gradient-to-b from-slate-50 to-slate-100 text-slate-800 flex flex-col justify-center py-8 sm:py-12 px-4 sm:px-6 lg:px-8"
    >
        <Head title="Login Siswa LMS - BPVP Pangkep" />

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <div
                class="inline-flex p-3 bg-indigo-50 rounded-2xl ring-1 ring-indigo-100 text-indigo-600 mb-4 shadow-sm"
            >
                <GraduationCap class="w-10 h-10" />
            </div>
            <h2
                class="text-2xl sm:text-3xl font-black tracking-tight text-slate-900"
            >
                LMS BPVP Pangkep
            </h2>
            <p class="mt-2 text-xs sm:text-sm text-slate-500">
                Portal Pembelajaran Mandiri & Presensi Tatap Muka Resmi
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-lg">
            <div
                class="bg-white border border-slate-200 py-8 px-6 sm:px-10 rounded-2xl shadow-xl text-slate-700"
            >
                <!-- STEP 1: INPUT EMAIL -->
                <div v-if="step === 1" class="space-y-6">
                    <div>
                        <span
                            class="text-[11px] font-bold tracking-widest text-indigo-600 uppercase"
                            >Langkah 1 dari 2</span
                        >
                        <h3 class="text-lg font-bold text-slate-900 mt-1">
                            Masukkan Email Terdaftar
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Gunakan alamat email yang Anda gunakan saat
                            mendaftar pelatihan di BPVP Pangkep.
                        </p>
                    </div>

                    <div
                        v-if="errorMessage"
                        class="p-3.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-xs flex items-start gap-2"
                    >
                        <AlertCircle
                            class="w-4 h-4 text-rose-500 shrink-0 mt-0.5"
                        />
                        <span>{{ errorMessage }}</span>
                    </div>

                    <form @submit.prevent="checkEmail" class="space-y-4">
                        <div>
                            <label
                                class="block text-xs font-semibold text-slate-700 mb-1.5"
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
                                    class="w-full pl-10 pr-4 py-3 rounded-xl bg-slate-50 border border-slate-200 text-slate-900 placeholder-slate-400 text-sm focus:bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            :disabled="isChecking"
                            class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all shadow-lg shadow-indigo-600/25 disabled:opacity-50 cursor-pointer"
                        >
                            <Loader2 v-if="isChecking" class="w-4 h-4 animate-spin shrink-0" />
                            <span>{{
                                isChecking ? "Memeriksa Data..." : "Lanjutkan"
                            }}</span>
                            <ArrowRight v-if="!isChecking" class="w-4 h-4" />
                        </button>
                    </form>

                    <div
                        class="pt-4 border-t border-slate-100 text-center text-xs text-slate-500 leading-relaxed"
                    >
                        Belum mendaftar pelatihan? Kunjungi sosial media kami
                        di Instagram untuk mendapatkan pelatihan-pelatihan
                        gratis dari kami
                        <a
                            href="https://instagram.com/bpvppangkep"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="text-indigo-600 font-semibold hover:underline"
                            >@bpvppangkep</a
                        >
                    </div>
                </div>

                <!-- STEP 2: VERIFIKASI IDENTITAS -->
                <div v-else-if="step === 2" class="space-y-5">
                    <div>
                        <span
                            class="text-[11px] font-bold tracking-widest text-emerald-600 uppercase"
                            >Langkah 2 dari 2</span
                        >
                        <h3 class="text-lg font-bold text-slate-900 mt-1">
                            Verifikasi Identitas Anda
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            Pastikan nama dan kelas pelatihan berikut sesuai
                            dengan data Anda. Data sensitif disensor untuk
                            keamanan.
                        </p>
                    </div>

                    <!-- Security Notice -->
                    <div
                        class="p-3 bg-amber-50 border border-amber-200 rounded-xl text-amber-800 text-xs flex items-start gap-2.5"
                    >
                        <ShieldCheck class="w-4 h-4 text-amber-600 shrink-0 mt-0.5" />
                        <span class="leading-relaxed">
                            Data sensitif (NIK, No. HP, alamat, email) telah
                            <strong>disensor otomatis</strong> untuk melindungi
                            privasi Anda. Hanya nama dan kelas pelatihan yang
                            ditampilkan secara lengkap.
                        </span>
                    </div>

                    <form @submit.prevent="confirmProfile" class="space-y-4">
                        <!-- Nama Lengkap — ditampilkan penuh -->
                        <div
                            class="p-4 bg-indigo-50/60 border border-indigo-100 rounded-xl"
                        >
                            <label
                                class="block text-[11px] font-bold text-indigo-700 mb-1.5 uppercase tracking-wide"
                            >
                                <User class="w-3.5 h-3.5 inline -mt-0.5 mr-1" />
                                Nama Lengkap & Gelar
                            </label>
                            <p
                                class="text-base font-bold text-slate-900 leading-snug"
                            >
                                {{ participantData.name }}
                            </p>
                        </div>

                        <!-- Kelas Terdaftar — ditampilkan penuh -->
                        <div
                            class="p-4 bg-emerald-50/60 border border-emerald-100 rounded-xl"
                        >
                            <label
                                class="block text-[11px] font-bold text-emerald-700 mb-2 uppercase tracking-wide"
                            >
                                <BookOpen class="w-3.5 h-3.5 inline -mt-0.5 mr-1" />
                                Kelas Pelatihan Terdaftar
                            </label>
                            <div class="space-y-1.5">
                                <div
                                    v-for="c in registeredCourses"
                                    :key="c.id"
                                    class="text-xs text-slate-800 font-medium bg-white border border-emerald-200 px-3 py-2 rounded-lg flex items-center justify-between shadow-xs"
                                >
                                    <span class="flex items-center gap-1.5">
                                        <CheckCircle2 class="w-3.5 h-3.5 text-emerald-500 shrink-0" />
                                        {{ c.title }}
                                    </span>
                                    <span
                                        v-if="c.batch_name"
                                        class="text-emerald-600 font-semibold text-[10px] bg-emerald-50 px-1.5 py-0.5 rounded"
                                        >Batch {{ c.batch_name }}</span
                                    >
                                </div>
                                <p
                                    v-if="registeredCourses.length === 0"
                                    class="text-xs text-slate-400 italic"
                                >
                                    Belum ada kelas yang terdaftar.
                                </p>
                            </div>
                        </div>

                        <!-- Data Tersensor -->
                        <div
                            class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3"
                        >
                            <div class="flex items-center justify-between">
                                <label
                                    class="text-[11px] font-bold text-slate-500 uppercase tracking-wide"
                                >
                                    <Lock class="w-3.5 h-3.5 inline -mt-0.5 mr-1" />
                                    Data Tersensor
                                </label>
                                <button
                                    type="button"
                                    @click="toggleSensitiveData"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-indigo-600 hover:text-indigo-800 transition-colors cursor-pointer"
                                >
                                    <Eye v-if="!showSensitiveData" class="w-3.5 h-3.5" />
                                    <EyeOff v-else class="w-3.5 h-3.5" />
                                    {{ showSensitiveData ? "Sembunyikan" : "Tampilkan" }}
                                </button>
                            </div>

                            <!-- NIK Verification Prompt -->
                            <div
                                v-if="showNikPrompt && !showSensitiveData"
                                class="p-3 bg-amber-50 border border-amber-200 rounded-lg space-y-2"
                            >
                                <p class="text-[11px] font-semibold text-amber-800">
                                    Masukkan NIK Anda untuk membuka data tersensor:
                                </p>
                                <div
                                    v-if="nikVerificationError"
                                    class="text-[10px] text-rose-600 font-medium flex items-center gap-1"
                                >
                                    <AlertCircle class="w-3 h-3 shrink-0" />
                                    {{ nikVerificationError }}
                                </div>
                                <div class="flex gap-2">
                                    <div class="relative flex-1">
                                        <CreditCard
                                            class="w-3.5 h-3.5 absolute left-2.5 top-2 text-amber-400"
                                        />
                                        <input
                                            v-model="nikVerificationInput"
                                            type="text"
                                            inputmode="numeric"
                                            placeholder="Masukkan 16 digit NIK"
                                            maxlength="16"
                                            class="w-full pl-8 pr-3 py-1.5 rounded-lg bg-white border border-amber-300 text-slate-900 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 placeholder-slate-400"
                                            @keyup.enter="verifyNikAndReveal"
                                        />
                                    </div>
                                    <button
                                        type="button"
                                        @click="verifyNikAndReveal"
                                        class="px-3 py-1.5 rounded-lg text-[10px] font-bold text-white bg-amber-600 hover:bg-amber-700 transition-colors cursor-pointer shrink-0"
                                    >
                                        Verifikasi
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelNikVerification"
                                        class="px-2 py-1.5 rounded-lg text-[10px] font-semibold text-slate-500 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer shrink-0"
                                    >
                                        Batal
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                                <!-- Email -->
                                <div class="space-y-0.5">
                                    <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                                        <Mail class="w-3 h-3" /> Email
                                    </span>
                                    <p class="text-xs text-slate-600 font-mono">
                                        {{ maskedEmail }}
                                    </p>
                                </div>

                                <!-- Kode Transaksi -->
                                <div
                                    v-if="participantData.training_transaction_code"
                                    class="space-y-0.5"
                                >
                                    <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                                        <Hash class="w-3 h-3" /> Kode Transaksi
                                    </span>
                                    <p class="text-xs text-indigo-600 font-mono font-bold">
                                        {{ maskedTransactionCode }}
                                    </p>
                                </div>

                                <!-- NIK -->
                                <div v-if="participantData.nik" class="space-y-0.5">
                                    <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                                        <CreditCard class="w-3 h-3" /> NIK
                                    </span>
                                    <p class="text-xs text-slate-600 font-mono">
                                        {{ maskedNik }}
                                    </p>
                                </div>

                                <!-- No. HP -->
                                <div v-if="participantData.phone" class="space-y-0.5">
                                    <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                                        <Phone class="w-3 h-3" /> No. HP
                                    </span>
                                    <p class="text-xs text-slate-600 font-mono">
                                        {{ maskedPhone }}
                                    </p>
                                </div>

                                <!-- Alamat -->
                                <div
                                    v-if="participantData.address"
                                    class="sm:col-span-2 space-y-0.5"
                                >
                                    <span class="text-[10px] font-semibold text-slate-400 flex items-center gap-1">
                                        <MapPin class="w-3 h-3" /> Alamat
                                    </span>
                                    <p class="text-xs text-slate-600">
                                        {{ maskedAddress }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Pernyataan Komitmen -->
                        <div
                            class="p-4 bg-sky-50 border border-sky-200 rounded-xl"
                        >
                            <label
                                class="flex items-start gap-3 cursor-pointer group"
                            >
                                <input
                                    v-model="agreedToCommitment"
                                    type="checkbox"
                                    class="mt-0.5 w-4 h-4 rounded border-sky-300 text-sky-600 focus:ring-sky-500 shrink-0 cursor-pointer"
                                />
                                <span
                                    class="text-xs text-sky-900 leading-relaxed select-none"
                                >
                                    Saya menyatakan bahwa data di atas adalah
                                    benar milik saya, dan saya
                                    <strong>berkomitmen penuh</strong> untuk
                                    mengikuti serta menyelesaikan seluruh
                                    rangkaian program pelatihan ini dengan
                                    sungguh-sungguh, tepat waktu, dan siap
                                    mengamalkan kompetensi yang diperoleh di
                                    bidang terkait.
                                </span>
                            </label>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex items-center gap-3 pt-1">
                            <button
                                type="button"
                                @click="step = 1"
                                class="w-1/3 py-2.5 px-3 rounded-xl text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors cursor-pointer"
                            >
                                Ganti Email
                            </button>
                            <button
                                type="submit"
                                :disabled="!canSubmit"
                                class="w-2/3 flex items-center justify-center gap-2 py-2.5 px-4 rounded-xl text-xs font-bold text-white transition-all shadow-lg active:scale-95 cursor-pointer"
                                :class="
                                    canSubmit
                                        ? 'bg-emerald-600 hover:bg-emerald-700 shadow-emerald-600/25'
                                        : 'bg-slate-300 shadow-none cursor-not-allowed'
                                "
                            >
                                <Loader2 v-if="isConfirming" class="w-4 h-4 animate-spin shrink-0" />
                                <CheckCircle2 v-else class="w-4 h-4 shrink-0" />
                                <span>{{ isConfirming ? "Menghubungkan..." : "Konfirmasi & Masuk" }}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Footer note -->
            <p class="mt-4 text-center text-[10px] text-slate-400">
                Data dilindungi sesuai kebijakan privasi BPVP Pangkep.
            </p>
        </div>
    </div>
</template>
