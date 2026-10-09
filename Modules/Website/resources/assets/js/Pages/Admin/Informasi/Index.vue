<script setup>
import { ref } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import {
    Save,
    CheckCircle2,
    Plus,
    Trash2,
    GraduationCap,
    Building2,
    Wrench,
    Users,
    MessageSquareQuote,
    Handshake,
    HelpCircle,
    Upload,
    Image as ImageIcon,
    Search,
    X,
} from "lucide-vue-next";
import { RichTextEditor } from "@/Components/ui/rich-text-editor";

const props = defineProps({
    informasi: Object,
});

const itemSearchQuery = ref("");
const isItemMatched = (item) => {
    if (!itemSearchQuery.value) return true;
    const q = itemSearchQuery.value.toLowerCase();
    return Object.values(item).some(
        (val) => typeof val === "string" && val.toLowerCase().includes(q),
    );
};

const parseArr = (val) => {
    if (!val) return [];
    if (Array.isArray(val)) return val;
    try {
        return JSON.parse(val);
    } catch {
        return [];
    }
};

const form = useForm({
    kejuruan: parseArr(props.informasi?.kejuruan),
    gedung_fasilitas: parseArr(props.informasi?.gedung_fasilitas),
    kelas_workshop: parseArr(props.informasi?.kelas_workshop),
    alumni: parseArr(props.informasi?.alumni),
    testimoni: parseArr(props.informasi?.testimoni),
    kerjasama: parseArr(props.informasi?.kerjasama),
    faq: parseArr(props.informasi?.faq),
});

const activeSection = ref("kejuruan");
const uploadingKeys = ref({});

const sections = [
    { id: "kejuruan", label: "Kejuruan", icon: GraduationCap },
    { id: "gedung_fasilitas", label: "Gedung & Fasilitas", icon: Building2 },
    { id: "kelas_workshop", label: "Kelas & Workshop", icon: Wrench },
    { id: "alumni", label: "Alumni", icon: Users },
    { id: "testimoni", label: "Testimoni", icon: MessageSquareQuote },
    { id: "kerjasama", label: "Kerjasama / Mitra", icon: Handshake },
    { id: "faq", label: "FAQ", icon: HelpCircle },
];

const getStorageUrl = (path) => {
    if (!path) return null;
    return path.startsWith("http")
        ? path
        : path.startsWith("/")
          ? path
          : `/storage/${path}`;
};

const isPhotoKey = (key) => {
    return [
        "foto",
        "logo",
        "foto_kejuruan",
        "foto_fasilitas",
        "foto_ruangan",
        "foto_kegiatan_alumni",
        "foto_alumni",
    ].includes(key);
};

const getFieldLabel = (key) => {
    const labels = {
        nama_kejuruan: "Nama Kejuruan Pelatihan",
        deskripsi_kejuruan: "Deskripsi Kejuruan",
        foto_kejuruan: "Foto Kejuruan",
        nama_fasilitas: "Nama Gedung / Fasilitas",
        deskripsi_fasilitas: "Deskripsi Fasilitas",
        foto_fasilitas: "Foto Fasilitas",
        nama_ruangan: "Nama Ruang Kelas / Workshop",
        deskripsi_ruangan: "Deskripsi Ruangan",
        foto_ruangan: "Foto Ruangan",
        tahun_angkatan: "Tahun / Angkatan Kelulusan",
        catatan_alumni: "Catatan / Deskripsi Kegiatan Alumni",
        foto_kegiatan_alumni: "Foto Kegiatan Alumni",
        nama_alumni: "Nama Lengkap Alumni",
        pekerjaan: "Pekerjaan / Instansi Saat Ini",
        isi_testimoni: "Isi Testimoni / Ulasan",
        foto_alumni: "Foto Profil Alumni",
        nama_instansi: "Nama Instansi / Mitra Kerjasama",
        bentuk_kerjasama: "Bentuk Kolaborasi / Kerjasama",
        logo: "Logo Mitra / Instansi",
        pertanyaan: "Pertanyaan",
        jawaban: "Jawaban / Penjelasan",
    };
    return labels[key] || key.replace(/_/g, " ");
};

const addItem = (field) => {
    const templates = {
        kejuruan: {
            nama_kejuruan: "",
            deskripsi_kejuruan: "",
            foto_kejuruan: "",
        },
        gedung_fasilitas: {
            nama_fasilitas: "",
            deskripsi_fasilitas: "",
            foto_fasilitas: "",
        },
        kelas_workshop: {
            nama_ruangan: "",
            deskripsi_ruangan: "",
            foto_ruangan: "",
        },
        alumni: {
            tahun_angkatan: "",
            catatan_alumni: "",
            foto_kegiatan_alumni: "",
        },
        testimoni: {
            nama_alumni: "",
            pekerjaan: "",
            isi_testimoni: "",
            foto_alumni: "",
        },
        kerjasama: { nama_instansi: "", bentuk_kerjasama: "", logo: "" },
        faq: { pertanyaan: "", jawaban: "" },
    };
    form[field] = [...form[field], { ...templates[field] }];
};

const removeItem = (field, index) => {
    form[field] = form[field].filter((_, i) => i !== index);
};

const triggerFileUpload = (sectionId, idx, key) => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const uploadId = `${sectionId}-${idx}-${key}`;
        uploadingKeys.value[uploadId] = true;

        const folderMap = {
            kejuruan: "website/informasi/kejuruan",
            gedung_fasilitas: "website/informasi/fasilitas",
            kelas_workshop: "website/informasi/workshop",
            alumni: "website/informasi/alumni",
            testimoni: "website/informasi/testimoni",
            kerjasama: "website/informasi/kerjasama",
        };
        const targetFolder =
            folderMap[sectionId] || `website/informasi/${sectionId}`;
        const currentItem = form[sectionId][idx] || {};
        const itemName =
            currentItem.nama_kejuruan ||
            currentItem.nama_fasilitas ||
            currentItem.nama_ruangan ||
            currentItem.nama_alumni ||
            currentItem.nama_instansi ||
            sectionId;

        try {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("folder", targetFolder);
            formData.append("name", itemName);

            const res = await window.axios.post(
                "/admin/upload-media",
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                },
            );
            if (res.data?.path || res.data?.url) {
                // Simpan path relatif database murni (website/...)
                form[sectionId][idx][key] = res.data.path || res.data.url;
            }
        } catch (err) {
            console.error("Upload failed", err);
            const errMsg =
                err.response?.data?.errors?.file?.[0] ||
                err.response?.data?.message ||
                "Gagal mengunggah gambar. Pastikan ukuran file tidak melebihi 20MB dan format gambar valid.";
            alert(errMsg);
        } finally {
            uploadingKeys.value[uploadId] = false;
        }
    };
    input.click();
};

const submit = () => {
    form.put("/admin/informasi");
};
</script>

<template>
    <Head title="Informasi Balai" />

    <DashboardLayout>
        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">
                        Informasi Balai
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola data kejuruan, fasilitas, workshop, alumni,
                        kemitraan, dan FAQ
                    </p>
                </div>
                <Button
                    @click="submit"
                    :loading="form.processing"
                    class="w-fit bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20"
                >
                    <Save v-if="!form.processing" class="w-4 h-4 mr-1.5" />
                    {{ form.processing ? "Menyimpan..." : "Simpan Semua" }}
                </Button>
            </div>

            <div
                v-if="form.wasSuccessful"
                class="flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold"
            >
                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                <span>Data informasi balai berhasil diperbarui.</span>
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
                        <Badge
                            :variant="
                                activeSection === s.id ? 'secondary' : 'outline'
                            "
                            :class="[
                                'text-[10px] py-0 px-1.5 font-mono ml-1',
                                activeSection === s.id
                                    ? 'bg-blue-100 text-blue-700 border-0'
                                    : 'bg-white/60 text-slate-500',
                            ]"
                        >
                            {{ form[s.id]?.length ?? 0 }}
                        </Badge>
                    </button>
                </div>

                <!-- Form Card -->
                <Card class="w-full shadow-xs border-slate-200">
                    <template v-for="s in sections" :key="s.id">
                        <div v-if="activeSection === s.id">
                            <CardHeader
                                class="border-b border-slate-100 pb-4 flex flex-row items-center justify-between"
                            >
                                <CardTitle
                                    class="text-sm font-bold text-slate-900 capitalize"
                                >
                                    {{ s.label }}
                                </CardTitle>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="addItem(s.id)"
                                    class="h-8 border-slate-200 text-slate-700 hover:bg-slate-50"
                                >
                                    <Plus class="w-3.5 h-3.5 mr-1" /> Tambah
                                    Data
                                </Button>
                            </CardHeader>

                            <CardContent class="pt-6 space-y-4">
                                <!-- Search inside active category -->
                                <div
                                    v-if="form[s.id].length > 1"
                                    class="relative"
                                >
                                    <Search
                                        class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                                    />
                                    <Input
                                        v-model="itemSearchQuery"
                                        :placeholder="`Cari dalam daftar ${s.label.toLowerCase()}...`"
                                        class="pl-9 pr-8 h-8 text-xs bg-slate-50 border-slate-200"
                                    />
                                    <button
                                        v-if="itemSearchQuery"
                                        type="button"
                                        @click="itemSearchQuery = ''"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                    >
                                        <X class="w-3.5 h-3.5" />
                                    </button>
                                </div>

                                <div
                                    v-if="form[s.id].length === 0"
                                    class="rounded-xl border border-dashed border-slate-200 p-12 text-center text-slate-400 text-xs"
                                >
                                    Belum ada data pada kategori ini. Klik
                                    "Tambah Data" untuk mulai mengisi.
                                </div>

                                <div
                                    v-for="(item, idx) in form[s.id]"
                                    :key="idx"
                                    v-show="isItemMatched(item)"
                                    class="p-4 rounded-xl border border-slate-200 bg-slate-50/60 relative group space-y-3"
                                >
                                    <div
                                        class="flex items-center justify-between border-b border-slate-200 pb-2"
                                    >
                                        <span
                                            class="text-[11px] font-bold text-slate-500 uppercase font-mono"
                                        >
                                            #{{ idx + 1 }} {{ s.label }}
                                        </span>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeItem(s.id, idx)"
                                            class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                        >
                                            <Trash2 class="w-3.5 h-3.5 mr-1" />
                                            Hapus
                                        </Button>
                                    </div>

                                    <div
                                        class="grid grid-cols-1 sm:grid-cols-2 gap-3"
                                    >
                                        <template
                                            v-for="(val, key) in item"
                                            :key="key"
                                        >
                                            <!-- Khusus Kolom Foto / Logo dengan File Uploader (AVIF + simpan original) -->
                                            <div
                                                v-if="isPhotoKey(key)"
                                                class="space-y-1.5 sm:col-span-2"
                                            >
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    {{ getFieldLabel(key) }}
                                                </label>
                                                <div
                                                    class="flex flex-col sm:flex-row items-start sm:items-center gap-3"
                                                >
                                                    <!-- Thumbnail Preview -->
                                                    <div
                                                        v-if="
                                                            form[s.id][idx][key]
                                                        "
                                                        class="relative w-16 h-16 rounded-xl border border-slate-200 bg-white overflow-hidden shrink-0 shadow-2xs group/img"
                                                    >
                                                        <img
                                                            :src="
                                                                getStorageUrl(
                                                                    form[s.id][
                                                                        idx
                                                                    ][key],
                                                                )
                                                            "
                                                            class="w-full h-full object-cover"
                                                            alt="Preview"
                                                        />
                                                        <button
                                                            type="button"
                                                            @click="
                                                                form[s.id][idx][
                                                                    key
                                                                ] = ''
                                                            "
                                                            class="absolute inset-0 bg-slate-900/60 text-white opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition-opacity"
                                                            title="Hapus gambar"
                                                        >
                                                            <Trash2
                                                                class="w-4 h-4 text-rose-300"
                                                            />
                                                        </button>
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="w-16 h-16 rounded-xl border-2 border-dashed border-slate-200 bg-slate-100/50 flex items-center justify-center shrink-0 text-slate-300"
                                                    >
                                                        <ImageIcon
                                                            class="w-6 h-6"
                                                        />
                                                    </div>

                                                    <!-- Controls & Input -->
                                                    <div
                                                        class="flex-1 w-full space-y-1.5"
                                                    >
                                                        <div
                                                            class="flex items-center gap-2"
                                                        >
                                                            <Button
                                                                type="button"
                                                                variant="outline"
                                                                size="sm"
                                                                class="h-8 text-xs font-semibold rounded-xl bg-white border-slate-200 hover:bg-slate-50 text-slate-700"
                                                                :disabled="
                                                                    uploadingKeys[
                                                                        `${s.id}-${idx}-${key}`
                                                                    ]
                                                                "
                                                                @click="
                                                                    triggerFileUpload(
                                                                        s.id,
                                                                        idx,
                                                                        key,
                                                                    )
                                                                "
                                                            >
                                                                <Upload
                                                                    class="w-3.5 h-3.5 mr-1 text-blue-600"
                                                                />
                                                                {{
                                                                    uploadingKeys[
                                                                        `${s.id}-${idx}-${key}`
                                                                    ]
                                                                        ? "Mengonversi ke AVIF..."
                                                                        : "Upload Gambar (AVIF Otomatis)"
                                                                }}
                                                            </Button>
                                                            <span
                                                                v-if="
                                                                    form[s.id][
                                                                        idx
                                                                    ][key]
                                                                "
                                                                class="text-[10px] text-emerald-600 font-medium"
                                                            >
                                                                ✓ File terpasang
                                                            </span>
                                                        </div>
                                                        <Input
                                                            v-model="
                                                                form[s.id][idx][
                                                                    key
                                                                ]
                                                            "
                                                            placeholder="Atau tempel URL gambar / file path..."
                                                            class="bg-white h-8 text-xs"
                                                        />
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Field Deskripsi / Isi / Jawaban / Catatan / Keterangan (Textarea) -->
                                            <div
                                                v-else-if="
                                                    [
                                                        'deskripsi',
                                                        'deskripsi_kejuruan',
                                                        'deskripsi_fasilitas',
                                                        'deskripsi_ruangan',
                                                        'catatan_alumni',
                                                        'isi_testimoni',
                                                        'bentuk_kerjasama',
                                                        'jawaban',
                                                    ].includes(key)
                                                "
                                                class="space-y-1 sm:col-span-2"
                                            >
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    {{ getFieldLabel(key) }}
                                                </label>
                                                <RichTextEditor
                                                    v-model="
                                                        form[s.id][idx][key]
                                                    "
                                                    min-height="120px"
                                                    :placeholder="`Masukkan ${getFieldLabel(key).toLowerCase()} lengkap...`"
                                                    :upload-folder="`website/informasi/${s.id}`"
                                                />
                                            </div>

                                            <!-- Field Teks Biasa -->
                                            <div v-else class="space-y-1">
                                                <label
                                                    class="block text-[10px] font-bold text-slate-600"
                                                >
                                                    {{ getFieldLabel(key) }}
                                                </label>
                                                <Input
                                                    v-model="
                                                        form[s.id][idx][key]
                                                    "
                                                    class="bg-white"
                                                    :placeholder="`Masukkan ${getFieldLabel(key).toLowerCase()}...`"
                                                />
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </CardContent>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </DashboardLayout>
</template>
