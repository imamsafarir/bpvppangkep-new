<script setup>
import { ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { RichTextEditor } from "@/Components/ui/rich-text-editor";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import {
    Save,
    CheckCircle2,
    User,
    FileText,
    Compass,
    ListChecks,
    Shield,
    Network,
    Upload,
    X,
    Users,
    Plus,
    Trash2,
} from "lucide-vue-next";

const props = defineProps({
    profil: Object,
});

const chiefPhotoPreview = ref(
    props.profil?.chief_photo_path
        ? props.profil.chief_photo_path.startsWith("http")
            ? props.profil.chief_photo_path
            : `/storage/${props.profil.chief_photo_path}`
        : null,
);

const strukturPreview = ref(
    props.profil?.struktur_organisasi
        ? props.profil.struktur_organisasi.startsWith("http")
            ? props.profil.struktur_organisasi
            : `/storage/${props.profil.struktur_organisasi}`
        : null,
);

const isPdf = (path) => {
    if (!path) return false;
    return path.split("?")[0].split(".").pop().toLowerCase() === "pdf";
};

const parsePejabat = (val) => {
    if (!val) return [];
    let parsed = val;
    if (typeof val === "string") {
        try {
            parsed = JSON.parse(val);
        } catch {
            return [];
        }
    }
    if (!Array.isArray(parsed)) return [];
    return parsed.map((item) => ({
        nama: item.nama ?? "",
        nip: item.nip ?? "",
        jabatan: item.jabatan ?? "",
        foto: item.foto ?? "",
        riwayat_jabatan: Array.isArray(item.riwayat_jabatan)
            ? item.riwayat_jabatan.map((r) => ({
                  tahun: r.tahun ?? "",
                  nama_jabatan: r.nama_jabatan ?? "",
              }))
            : [],
    }));
};

const form = useForm({
    chief_name: props.profil?.chief_name ?? "",
    chief_nip: props.profil?.chief_nip ?? "",
    chief_photo_path: null,
    sambutan_kepala: props.profil?.sambutan_kepala ?? "",
    tentang_kami: props.profil?.tentang_kami ?? "",
    ppid: props.profil?.ppid ?? "",
    tugas_fungsi: props.profil?.tugas_fungsi ?? "",
    visi_misi: props.profil?.visi_misi ?? "",
    struktur_organisasi: null,
    pejabat_struktural: parsePejabat(props.profil?.pejabat_struktural),
});

const removeChiefPhoto = ref(false);
const removeStruktur = ref(false);

const onChiefPhotoSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 10 * 1024 * 1024) {
            alert("Ukuran foto kepala balai tidak boleh melebihi 10MB.");
            return;
        }
        form.chief_photo_path = file;
        removeChiefPhoto.value = false;
        chiefPhotoPreview.value = URL.createObjectURL(file);
    }
};

const clearChiefPhoto = () => {
    form.chief_photo_path = null;
    chiefPhotoPreview.value = null;
    removeChiefPhoto.value = true;
};

const onStrukturSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 20 * 1024 * 1024) {
            alert("Ukuran bagan struktur organisasi tidak boleh melebihi 20MB.");
            return;
        }
        form.struktur_organisasi = file;
        removeStruktur.value = false;
        strukturPreview.value = URL.createObjectURL(file);
    }
};

const clearStruktur = () => {
    form.struktur_organisasi = null;
    strukturPreview.value = null;
    removeStruktur.value = true;
};

const addPejabat = () => {
    form.pejabat_struktural.push({
        nama: "",
        nip: "",
        jabatan: "",
        foto: "",
        riwayat_jabatan: [],
    });
};

const removePejabat = (index) => {
    form.pejabat_struktural.splice(index, 1);
};

const addRiwayat = (pejabatIdx) => {
    if (!form.pejabat_struktural[pejabatIdx].riwayat_jabatan) {
        form.pejabat_struktural[pejabatIdx].riwayat_jabatan = [];
    }
    form.pejabat_struktural[pejabatIdx].riwayat_jabatan.push({
        tahun: "",
        nama_jabatan: "",
    });
};

const removeRiwayat = (pejabatIdx, riwayatIdx) => {
    form.pejabat_struktural[pejabatIdx].riwayat_jabatan.splice(riwayatIdx, 1);
};

const uploadingPejabatIdx = ref(null);

const getStorageUrl = (path) => {
    if (!path) return null;
    return path.startsWith("http")
        ? path
        : path.startsWith("/")
          ? path
          : `/storage/${path}`;
};

const triggerPejabatUpload = (idx) => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        uploadingPejabatIdx.value = idx;
        try {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("folder", "website/profil/pejabat");
            formData.append(
                "name",
                form.pejabat_struktural[idx]?.nama || "pejabat",
            );
            const res = await window.axios.post(
                "/admin/upload-media",
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                },
            );
            if (res.data?.path || res.data?.url) {
                form.pejabat_struktural[idx].foto =
                    res.data.path || res.data.url;
            }
        } catch (err) {
            console.error("Upload failed", err);
            const errMsg =
                err.response?.data?.errors?.file?.[0] ||
                err.response?.data?.message ||
                "Gagal mengunggah foto. Pastikan ukuran file tidak melebihi 10MB dan format gambar valid.";
            alert(errMsg);
        } finally {
            uploadingPejabatIdx.value = null;
        }
    };
    input.click();
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: "PUT",
        remove_chief_photo_path: removeChiefPhoto.value ? 1 : 0,
        remove_struktur_organisasi: removeStruktur.value ? 1 : 0,
    })).post("/admin/profil", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            removeChiefPhoto.value = false;
            removeStruktur.value = false;
        },
    });
};

const activeSection = ref("kepala");
const sections = [
    { id: "kepala", label: "Kepala Balai", icon: User },
    { id: "sambutan", label: "Sambutan", icon: FileText },
    { id: "tentang", label: "Tentang Kami", icon: FileText },
    { id: "visi", label: "Visi & Misi", icon: Compass },
    { id: "tugas", label: "Tugas & Fungsi", icon: ListChecks },
    { id: "ppid", label: "PPID Balai", icon: Shield },
    { id: "struktur", label: "Struktur Organisasi", icon: Network },
    { id: "pejabat", label: "Pejabat Struktural", icon: Users },
];
</script>

<template>
    <Head title="Profil Balai" />

    <DashboardLayout>
        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <h2
                        class="text-xl font-extrabold tracking-tight text-slate-900"
                    >
                        Profil Balai
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola data pimpinan, foto profil, sambutan, visi misi,
                        dan struktur organisasi
                    </p>
                </div>
                <Button
                    @click="submit"
                    :loading="form.processing"
                    class="w-fit bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20"
                >
                    <Save v-if="!form.processing" class="w-4 h-4 mr-1.5" />
                    {{ form.processing ? "Menyimpan..." : "Simpan Perubahan" }}
                </Button>
            </div>

            <div
                v-if="form.wasSuccessful"
                class="flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold"
            >
                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                <span>Perubahan profil berhasil disimpan ke database.</span>
            </div>

            <div class="space-y-4">
                <!-- Top Tabs Navigation (mirip Berita & Galeri) -->
                <div
                    class="flex items-center gap-1.5 p-1.5 bg-slate-200/70 rounded-2xl overflow-x-auto w-full max-w-full shadow-2xs"
                >
                    <button
                        v-for="s in sections"
                        :key="s.id"
                        @click="activeSection = s.id"
                        :class="[
                            'flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer select-none shrink-0',
                            activeSection === s.id
                                ? 'bg-white text-blue-600 shadow-xs'
                                : 'text-slate-600 hover:text-slate-900 hover:bg-white/50',
                        ]"
                    >
                        <component :is="s.icon" class="w-3.5 h-3.5" />
                        <span>{{ s.label }}</span>
                    </button>
                </div>

                <!-- Form Card Content -->
                <Card class="w-full shadow-xs border-slate-200">
                    <CardHeader class="border-b border-slate-100 pb-4">
                        <CardTitle
                            class="text-sm font-bold text-slate-900 capitalize"
                        >
                            {{
                                sections.find((s) => s.id === activeSection)
                                    ?.label
                            }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="pt-6 space-y-5">
                        <!-- Kepala Balai -->
                        <template v-if="activeSection === 'kepala'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >Nama Lengkap Kepala Balai</label
                                    >
                                    <Input
                                        v-model="form.chief_name"
                                        placeholder="Nama dan gelar lengkap..."
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >NIP Pimpinan</label
                                    >
                                    <Input
                                        v-model="form.chief_nip"
                                        placeholder="19XXXXXXXXXXXX..."
                                    />
                                </div>
                            </div>

                            <!-- Upload Foto Kepala -->
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-bold text-slate-700 block"
                                    >Foto Resmi Kepala Balai</label
                                >

                                <div
                                    v-if="chiefPhotoPreview"
                                    class="flex items-center gap-4 p-4 rounded-2xl border border-slate-200 bg-slate-50/50"
                                >
                                    <img
                                        :src="chiefPhotoPreview"
                                        class="h-28 w-24 object-cover rounded-xl border border-slate-200 shadow-xs"
                                        alt="Foto Kepala"
                                    />
                                    <div class="space-y-2">
                                        <div
                                            class="text-xs font-bold text-slate-800"
                                        >
                                            Foto Terpasang
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <label
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer shadow-2xs"
                                            >
                                                <Upload class="w-3.5 h-3.5 text-blue-600" />
                                                <span>Ganti Foto</span>
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    class="hidden"
                                                    @change="onChiefPhotoSelected"
                                                />
                                            </label>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="clearChiefPhoto"
                                                class="h-8 px-2.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-xl"
                                            >
                                                <Trash2 class="w-3.5 h-3.5 mr-1" />
                                                Hapus
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 bg-slate-50/50 hover:bg-blue-50/30 p-6 text-center transition-colors cursor-pointer relative"
                                >
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                                        @change="onChiefPhotoSelected"
                                    />
                                    <div
                                        class="flex flex-col items-center justify-center space-y-2 pointer-events-none"
                                    >
                                        <div
                                            class="w-10 h-10 rounded-xl bg-blue-100/70 text-blue-600 flex items-center justify-center"
                                        >
                                            <Upload class="w-5 h-5" />
                                        </div>
                                        <div
                                            class="text-xs font-bold text-slate-700"
                                        >
                                            Klik untuk memilih foto kepala balai
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            Format PNG, JPG, JPEG, WEBP (Maks. 10MB)
                                        </div>
                                    </div>
                                </div>

                                <p
                                    v-if="form.errors.chief_photo_path"
                                    class="text-xs text-rose-500 font-medium mt-1"
                                >
                                    {{ form.errors.chief_photo_path }}
                                </p>
                            </div>
                        </template>

                        <!-- Sambutan -->
                        <template v-if="activeSection === 'sambutan'">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Teks Sambutan Kepala Balai (Word
                                    Editor)</label
                                >
                                <RichTextEditor
                                    v-model="form.sambutan_kepala"
                                    min-height="320px"
                                    placeholder="Tuliskan kata sambutan kepala balai lengkap seperti di Microsoft Word..."
                                    upload-folder="website/profil/sambutan"
                                />
                            </div>
                        </template>

                        <!-- Tentang Kami -->
                        <template v-if="activeSection === 'tentang'">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Sejarah & Tentang Kami (Word Editor)</label
                                >
                                <RichTextEditor
                                    v-model="form.tentang_kami"
                                    min-height="320px"
                                    placeholder="Sejarah berdirinya balai, profil umum, wilayah kerja seperti di Microsoft Word..."
                                    upload-folder="website/profil/tentang"
                                />
                            </div>
                        </template>

                        <!-- Visi & Misi -->
                        <template v-if="activeSection === 'visi'">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Visi & Butir-Butir Misi Balai (Word
                                    Editor)</label
                                >
                                <RichTextEditor
                                    v-model="form.visi_misi"
                                    min-height="320px"
                                    placeholder="Visi balai dan misi kementerian ketenagakerjaan seperti di Microsoft Word..."
                                    upload-folder="website/profil/visi-misi"
                                />
                            </div>
                        </template>

                        <!-- Tugas & Fungsi -->
                        <template v-if="activeSection === 'tugas'">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Tugas Pokok & Fungsi (Word Editor)</label
                                >
                                <RichTextEditor
                                    v-model="form.tugas_fungsi"
                                    min-height="320px"
                                    placeholder="Dasar regulasi dan butir tupoksi balai seperti di Microsoft Word..."
                                    upload-folder="website/profil/tugas-fungsi"
                                />
                            </div>
                        </template>

                        <!-- PPID -->
                        <template v-if="activeSection === 'ppid'">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Layanan Informasi PPID (Word Editor)</label
                                >
                                <RichTextEditor
                                    v-model="form.ppid"
                                    min-height="320px"
                                    placeholder="Keterangan alur permohonan informasi publik PPID seperti di Microsoft Word..."
                                    upload-folder="website/profil/ppid"
                                />
                            </div>
                        </template>

                        <!-- Struktur Organisasi -->
                        <template v-if="activeSection === 'struktur'">
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-bold text-slate-700 block"
                                    >Bagan Struktur Organisasi</label
                                >

                                <div
                                    v-if="strukturPreview"
                                    class="space-y-3 p-4 rounded-2xl border border-slate-200 bg-slate-50/50"
                                >
                                    <iframe
                                        v-if="isPdf(strukturPreview)"
                                        :src="strukturPreview + '#toolbar=0&navpanes=0&scrollbar=0&view=FitH'"
                                        class="h-80 w-full rounded-xl border border-slate-200 bg-white"
                                        title="Bagan Struktur PDF"
                                    ></iframe>
                                    <img
                                        v-else
                                        :src="strukturPreview"
                                        class="max-h-72 w-full object-contain rounded-xl border border-slate-200 bg-white p-2"
                                        alt="Bagan Struktur"
                                    />
                                    <div class="flex items-center gap-2">
                                        <label
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer shadow-2xs"
                                        >
                                            <Upload
                                                class="w-3.5 h-3.5 text-blue-600"
                                            />
                                            <span>Ganti Bagan (Gambar / PDF)</span>
                                            <input
                                                type="file"
                                                accept="image/*,application/pdf"
                                                class="hidden"
                                                @change="onStrukturSelected"
                                            />
                                        </label>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="clearStruktur"
                                            class="h-8 px-2.5 text-rose-600 hover:text-rose-700 hover:bg-rose-50 rounded-xl"
                                        >
                                            <Trash2 class="w-3.5 h-3.5 mr-1" />
                                            Hapus Bagan
                                        </Button>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 bg-slate-50/50 hover:bg-blue-50/30 p-8 text-center transition-colors cursor-pointer relative"
                                >
                                    <input
                                        type="file"
                                        accept="image/*,application/pdf"
                                        class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                                        @change="onStrukturSelected"
                                    />
                                    <div
                                        class="flex flex-col items-center justify-center space-y-2 pointer-events-none"
                                    >
                                        <div
                                            class="w-10 h-10 rounded-xl bg-blue-100/70 text-blue-600 flex items-center justify-center"
                                        >
                                            <Upload class="w-5 h-5" />
                                        </div>
                                        <div
                                            class="text-xs font-bold text-slate-700"
                                        >
                                            Unggah file bagan struktur
                                            organisasi (Gambar / PDF)
                                        </div>
                                        <div class="text-[10px] text-slate-400">
                                            Format PNG, JPG, JPEG, WEBP, PDF (Maks. 20MB)
                                        </div>
                                    </div>
                                </div>

                                <p
                                    v-if="form.errors.struktur_organisasi"
                                    class="text-xs text-rose-500 font-medium mt-1"
                                >
                                    {{ form.errors.struktur_organisasi }}
                                </p>
                            </div>
                        </template>

                        <!-- Pejabat Struktural -->
                        <template v-if="activeSection === 'pejabat'">
                            <div class="flex items-center justify-between pb-2">
                                <p class="text-xs text-slate-500">
                                    Daftar pejabat struktural / koordinator
                                    bidang di BPVP Pangkep
                                </p>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="addPejabat"
                                    class="h-8"
                                >
                                    <Plus class="w-3.5 h-3.5 mr-1" /> Tambah
                                    Pejabat
                                </Button>
                            </div>

                            <div
                                v-if="form.pejabat_struktural.length === 0"
                                class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-400 text-xs"
                            >
                                Belum ada daftar pejabat struktural.
                            </div>

                            <div
                                v-for="(p, idx) in form.pejabat_struktural"
                                :key="idx"
                                class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-bold text-slate-700"
                                        >Pejabat #{{ idx + 1 }}</span
                                    >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="removePejabat(idx)"
                                        class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 mr-1" />
                                        Hapus
                                    </Button>
                                </div>
                                <div
                                    class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                >
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-bold text-slate-600"
                                            >Nama Lengkap & Gelar</label
                                        >
                                        <Input
                                            v-model="p.nama"
                                            class="bg-white"
                                            placeholder="Nama..."
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-bold text-slate-600"
                                            >Jabatan</label
                                        >
                                        <Input
                                            v-model="p.jabatan"
                                            class="bg-white"
                                            placeholder="Kepala Bagian / Sub Koordinator..."
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-bold text-slate-600"
                                            >NIP</label
                                        >
                                        <Input
                                            v-model="p.nip"
                                            class="bg-white"
                                            placeholder="NIP..."
                                        />
                                    </div>
                                    <div class="space-y-1 sm:col-span-2">
                                        <label
                                            class="text-[10px] font-bold text-slate-600"
                                            >Foto Pejabat</label
                                        >
                                        <div class="flex items-center gap-3">
                                            <div
                                                v-if="p.foto"
                                                class="relative w-12 h-12 rounded-xl border border-slate-200 bg-white overflow-hidden shrink-0 shadow-2xs group/img"
                                            >
                                                <img
                                                    :src="getStorageUrl(p.foto)"
                                                    class="w-full h-full object-cover"
                                                />
                                                <button
                                                    type="button"
                                                    @click="p.foto = ''"
                                                    class="absolute inset-0 bg-slate-900/60 text-white opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition-opacity"
                                                    title="Hapus foto"
                                                >
                                                    <Trash2
                                                        class="w-3.5 h-3.5 text-rose-300"
                                                    />
                                                </button>
                                            </div>
                                            <div
                                                v-else
                                                class="w-12 h-12 rounded-xl border-2 border-dashed border-slate-200 bg-slate-100/50 flex items-center justify-center shrink-0 text-slate-300"
                                            >
                                                <User class="w-5 h-5" />
                                            </div>
                                            <div class="flex-1 space-y-1">
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        class="h-7 text-xs font-semibold rounded-lg bg-white border-slate-200 hover:bg-slate-50 text-slate-700"
                                                        :disabled="
                                                            uploadingPejabatIdx ===
                                                            idx
                                                        "
                                                        @click="
                                                            triggerPejabatUpload(
                                                                idx,
                                                            )
                                                        "
                                                    >
                                                        <Upload
                                                            class="w-3 h-3 mr-1 text-blue-600"
                                                        />
                                                        {{
                                                            uploadingPejabatIdx ===
                                                            idx
                                                                ? "Mengunggah..."
                                                                : "Upload Foto"
                                                        }}
                                                    </Button>
                                                    <span
                                                        v-if="p.foto"
                                                        class="text-[10px] text-emerald-600 font-medium"
                                                        >✓ Terpasang</span
                                                    >
                                                </div>
                                                <Input
                                                    v-model="p.foto"
                                                    class="bg-white h-7 text-xs"
                                                    placeholder="Atau tempel tautan URL foto..."
                                                />
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Riwayat Jabatan / Rekam Jejak Karier -->
                                    <div
                                        class="space-y-2 sm:col-span-2 pt-2 border-t border-slate-200/80"
                                    >
                                        <div
                                            class="flex items-center justify-between"
                                        >
                                            <label
                                                class="text-[11px] font-bold text-slate-700"
                                            >
                                                Riwayat Jabatan & Rekam Jejak
                                                Karier (Muncul di Modal Pejabat)
                                            </label>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                @click="addRiwayat(idx)"
                                                class="h-6 text-[10px] px-2 border-slate-200 hover:bg-slate-100"
                                            >
                                                <Plus
                                                    class="w-3 h-3 mr-1 text-blue-600"
                                                />
                                                Tambah Riwayat
                                            </Button>
                                        </div>

                                        <div
                                            v-if="
                                                !p.riwayat_jabatan ||
                                                p.riwayat_jabatan.length === 0
                                            "
                                            class="text-[11px] text-slate-400 italic bg-white p-2.5 rounded-lg border border-dashed border-slate-200"
                                        >
                                            Belum ada rekam jejak karier yang
                                            ditambahkan.
                                        </div>

                                        <div v-else class="space-y-1.5">
                                            <div
                                                v-for="(
                                                    rw, rIdx
                                                ) in p.riwayat_jabatan"
                                                :key="rIdx"
                                                class="flex items-center gap-2 bg-white p-2 rounded-lg border border-slate-200 shadow-2xs"
                                            >
                                                <Input
                                                    v-model="rw.tahun"
                                                    placeholder="Tahun (misal: 2021 - 2025)"
                                                    class="h-7 text-xs w-44 shrink-0 font-mono"
                                                />
                                                <Input
                                                    v-model="rw.nama_jabatan"
                                                    placeholder="Nama Jabatan / Pengalaman..."
                                                    class="h-7 text-xs flex-1"
                                                />
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="
                                                        removeRiwayat(idx, rIdx)
                                                    "
                                                    class="h-7 px-1.5 text-rose-500 hover:text-rose-700 hover:bg-rose-50"
                                                    title="Hapus riwayat ini"
                                                >
                                                    <Trash2
                                                        class="w-3.5 h-3.5"
                                                    />
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </CardContent>
                </Card>
            </div>
        </div>
    </DashboardLayout>
</template>
