<script setup>
import { ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
import { RichTextEditor } from "@/Components/ui/rich-text-editor";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import {
    Save,
    CheckCircle2,
    FileText,
    CheckSquare,
    GitFork,
    ClipboardList,
    GraduationCap,
    Briefcase,
    BarChart3,
    Upload,
    ExternalLink,
    Plus,
    Trash2,
    Image as ImageIcon,
    Eye,
    EyeOff,
    Check,
} from "lucide-vue-next";

const props = defineProps({
    pelayanan: Object,
});

const getStorageUrl = (path) => {
    if (!path) return null;
    return path.startsWith("http")
        ? path
        : path.startsWith("/")
          ? path
          : `/storage/${path}`;
};

const isImageFile = (path) => {
    if (!path) return false;
    const ext = path.split(".").pop().toLowerCase();
    return ["jpg", "jpeg", "png", "webp", "gif", "avif"].includes(ext);
};

const isPdfFile = (path) => {
    if (!path) return false;
    return path.split(".").pop().toLowerCase() === "pdf";
};

const previewFiles = ref({});
const toggleFilePreview = (key) => {
    previewFiles.value[key] = !previewFiles.value[key];
};

const insertedKey = ref(null);
const insertFileToEditor = (type, idx) => {
    const item =
        type === "maklumat"
            ? form.maklumat_pelayanan[idx]
            : form.standar_pelayanan[idx];
    const filePath =
        type === "maklumat" ? item?.file_maklumat : item?.file_standar;
    if (!filePath) return;

    const url = getStorageUrl(filePath);
    const fileName = getFileName(filePath);
    const isPdf = isPdfFile(filePath);
    const badgeLabel = isPdf ? "PDF" : "DOKUMEN";

    const insertSnippet = `<p><br></p><p><a href="${url}" target="_blank" class="inline-flex items-center gap-2.5 px-4 py-2.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-xl text-xs font-bold text-blue-700 transition-all shadow-2xs"><span class="w-6 h-6 rounded-lg bg-blue-600 text-white inline-flex items-center justify-center text-[10px] font-mono shrink-0">${badgeLabel}</span><span>Unduh Berkas Lampiran: ${fileName}</span></a></p><p><br></p>`;

    if (type === "maklumat") {
        item.keterangan_maklumat =
            (item.keterangan_maklumat || "") + insertSnippet;
    } else {
        item.keterangan_standar =
            (item.keterangan_standar || "") + insertSnippet;
    }

    insertedKey.value = `${type}-${idx}`;
    setTimeout(() => {
        if (insertedKey.value === `${type}-${idx}`) {
            insertedKey.value = null;
        }
    }, 2000);
};

const getFileName = (path) => {
    if (!path) return "";
    return path.split("/").pop().split("\\").pop();
};

const parseMaklumat = (val) => {
    if (!val) {
        return [
            {
                judul_maklumat: "Maklumat Pelayanan Tahun 2026",
                file_maklumat: "",
                keterangan_maklumat: "",
            },
        ];
    }
    let list = val;
    if (typeof val === "string") {
        try {
            list = JSON.parse(val);
        } catch {
            return [
                {
                    judul_maklumat: "Maklumat Pelayanan",
                    file_maklumat: "",
                    keterangan_maklumat: val,
                },
            ];
        }
    }
    if (Array.isArray(list) && list.length > 0) {
        return list.map((item) => ({
            judul_maklumat: item.judul_maklumat ?? "",
            file_maklumat: item.file_maklumat ?? "",
            keterangan_maklumat: item.keterangan_maklumat ?? "",
        }));
    }
    return [
        {
            judul_maklumat: "Maklumat Pelayanan",
            file_maklumat: "",
            keterangan_maklumat: "",
        },
    ];
};

const parseStandar = (val) => {
    if (!val) {
        return [
            {
                judul_standar: "Standar Pelayanan Balai",
                file_standar: "",
                keterangan_standar: "",
            },
        ];
    }
    let list = val;
    if (typeof val === "string") {
        try {
            list = JSON.parse(val);
        } catch {
            return [
                {
                    judul_standar: "Standar Pelayanan",
                    file_standar: "",
                    keterangan_standar: val,
                },
            ];
        }
    }
    if (Array.isArray(list) && list.length > 0) {
        return list.map((item) => ({
            judul_standar: item.judul_standar ?? "",
            file_standar: item.file_standar ?? "",
            keterangan_standar: item.keterangan_standar ?? "",
        }));
    }
    return [
        {
            judul_standar: "Standar Pelayanan",
            file_standar: "",
            keterangan_standar: "",
        },
    ];
};

const alurPreview = ref(getStorageUrl(props.pelayanan?.foto_alur_pelayanan));
const uploadingFiles = ref({});

const form = useForm({
    maklumat_pelayanan: parseMaklumat(props.pelayanan?.maklumat_pelayanan),
    standar_pelayanan: parseStandar(props.pelayanan?.standar_pelayanan),
    foto_alur_pelayanan: null,
    deskripsi_alur_pelayanan: props.pelayanan?.deskripsi_alur_pelayanan ?? "",
    survey_kepuasan_masyarakat:
        props.pelayanan?.survey_kepuasan_masyarakat ?? "",
    survey_kebutuhan_pelatihan:
        props.pelayanan?.survey_kebutuhan_pelatihan ?? "",
    survey_kebekerjaan: props.pelayanan?.survey_kebekerjaan ?? "",
    indeks_kepuasan_masyarakat:
        props.pelayanan?.indeks_kepuasan_masyarakat ?? "",
});

const onAlurSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.foto_alur_pelayanan = file;
        alurPreview.value = URL.createObjectURL(file);
    }
};

const activeSection = ref("maklumat");
const sections = [
    { id: "maklumat", label: "Maklumat Pelayanan", icon: FileText },
    { id: "standar", label: "Standar Pelayanan", icon: CheckSquare },
    { id: "alur", label: "Alur Pelayanan", icon: GitFork },
    { id: "survey_kepuasan", label: "Survey Kepuasan", icon: ClipboardList },
    { id: "survey_kebutuhan", label: "Survey Kebutuhan", icon: GraduationCap },
    { id: "survey_kebekerjaan", label: "Survey Kebekerjaan", icon: Briefcase },
    { id: "indeks", label: "Indeks Kepuasan", icon: BarChart3 },
];

const addMaklumat = () => {
    form.maklumat_pelayanan.push({
        judul_maklumat: "",
        file_maklumat: "",
        keterangan_maklumat: "",
    });
};

const removeMaklumat = (index) => {
    if (form.maklumat_pelayanan.length > 1) {
        form.maklumat_pelayanan.splice(index, 1);
    }
};

const addStandar = () => {
    form.standar_pelayanan.push({
        judul_standar: "",
        file_standar: "",
        keterangan_standar: "",
    });
};

const removeStandar = (index) => {
    if (form.standar_pelayanan.length > 1) {
        form.standar_pelayanan.splice(index, 1);
    }
};

const triggerFileUpload = (type, index) => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = ".pdf,image/*,.doc,.docx";
    input.onchange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const uploadKey = `${type}-${index}`;
        uploadingFiles.value[uploadKey] = true;

        const folder =
            type === "maklumat"
                ? "website/pelayanan/maklumat"
                : "website/pelayanan/standar";
        const currentItem =
            type === "maklumat"
                ? form.maklumat_pelayanan[index]
                : form.standar_pelayanan[index];
        const itemName =
            (type === "maklumat"
                ? currentItem.judul_maklumat
                : currentItem.judul_standar) || type;

        try {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("folder", folder);
            formData.append("name", itemName);

            const res = await window.axios.post(
                "/admin/upload-media",
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                },
            );

            if (res.data?.path || res.data?.url) {
                const savedPath = res.data.path || res.data.url;
                if (type === "maklumat") {
                    form.maklumat_pelayanan[index].file_maklumat = savedPath;
                } else {
                    form.standar_pelayanan[index].file_standar = savedPath;
                }
            }
        } catch (err) {
            console.error("Upload failed", err);
            alert(
                "Gagal mengunggah file lampiran. Pastikan ukuran file tidak melebihi 20MB.",
            );
        } finally {
            uploadingFiles.value[uploadKey] = false;
        }
    };
    input.click();
};

const submit = () => {
    router.post(
        "/admin/pelayanan",
        {
            _method: "PUT",
            maklumat_pelayanan: form.maklumat_pelayanan,
            standar_pelayanan: form.standar_pelayanan,
            foto_alur_pelayanan: form.foto_alur_pelayanan,
            deskripsi_alur_pelayanan: form.deskripsi_alur_pelayanan,
            survey_kepuasan_masyarakat: form.survey_kepuasan_masyarakat,
            survey_kebutuhan_pelatihan: form.survey_kebutuhan_pelatihan,
            survey_kebekerjaan: form.survey_kebekerjaan,
            indeks_kepuasan_masyarakat: form.indeks_kepuasan_masyarakat,
        },
        {
            forceFormData: true,
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <Head title="Pelayanan Publik & Survey" />

    <DashboardLayout>
        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <h2
                        class="text-xl font-extrabold tracking-tight text-slate-900"
                    >
                        Pelayanan Publik & Survey
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola maklumat, standar pelayanan beserta file lampiran
                        PDF, bagan alur, serta instrumen survey
                    </p>
                </div>
                <Button
                    @click="submit"
                    :disabled="form.processing"
                    class="w-fit bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20"
                >
                    <Save class="w-4 h-4 mr-1.5" />
                    {{ form.processing ? "Menyimpan..." : "Simpan Perubahan" }}
                </Button>
            </div>

            <div
                v-if="form.wasSuccessful"
                class="flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold"
            >
                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                <span>Data pelayanan publik berhasil diperbarui.</span>
            </div>

            <div class="space-y-4">
                <!-- Top Tabs Navigation -->
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
                            v-if="s.id === 'maklumat'"
                            variant="secondary"
                            :class="[
                                'text-[10px] py-0 px-1.5 font-mono ml-0.5',
                                activeSection === s.id
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'bg-white/60 text-slate-500',
                            ]"
                        >
                            {{ form.maklumat_pelayanan.length }}
                        </Badge>
                        <Badge
                            v-else-if="s.id === 'standar'"
                            variant="secondary"
                            :class="[
                                'text-[10px] py-0 px-1.5 font-mono ml-0.5',
                                activeSection === s.id
                                    ? 'bg-blue-100 text-blue-700'
                                    : 'bg-white/60 text-slate-500',
                            ]"
                        >
                            {{ form.standar_pelayanan.length }}
                        </Badge>
                    </button>
                </div>

                <!-- Form Card -->
                <Card class="w-full shadow-xs border-slate-200">
                    <CardHeader
                        class="border-b border-slate-100 pb-4 flex flex-row items-center justify-between"
                    >
                        <div>
                            <CardTitle
                                class="text-sm font-bold text-slate-900 capitalize"
                            >
                                {{
                                    sections.find((s) => s.id === activeSection)
                                        ?.label
                                }}
                            </CardTitle>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Atur rincian teks, judul, serta lampiran dokumen
                                resmi untuk bagian ini
                            </p>
                        </div>
                        <Button
                            v-if="activeSection === 'maklumat'"
                            size="sm"
                            variant="outline"
                            @click="addMaklumat"
                            class="h-8 border-slate-200 text-slate-700 hover:bg-slate-50"
                        >
                            <Plus class="w-3.5 h-3.5 mr-1 text-blue-600" />
                            Tambah Maklumat
                        </Button>
                        <Button
                            v-else-if="activeSection === 'standar'"
                            size="sm"
                            variant="outline"
                            @click="addStandar"
                            class="h-8 border-slate-200 text-slate-700 hover:bg-slate-50"
                        >
                            <Plus class="w-3.5 h-3.5 mr-1 text-blue-600" />
                            Tambah Standar
                        </Button>
                    </CardHeader>
                    <CardContent class="pt-6 space-y-6">
                        <!-- Maklumat Pelayanan -->
                        <div
                            v-if="activeSection === 'maklumat'"
                            class="space-y-6"
                        >
                            <div
                                v-for="(item, idx) in form.maklumat_pelayanan"
                                :key="idx"
                                class="p-5 rounded-2xl border border-slate-200/80 bg-slate-50/50 space-y-4"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-slate-200/70 pb-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-xs font-black text-slate-800 uppercase tracking-wider font-mono"
                                        >
                                            #{{ idx + 1 }} MAKLUMAT PELAYANAN
                                        </span>
                                        <Badge
                                            v-if="item.file_maklumat"
                                            class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold border-0"
                                        >
                                            Lampiran Ada
                                        </Badge>
                                    </div>
                                    <Button
                                        v-if="
                                            form.maklumat_pelayanan.length > 1
                                        "
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeMaklumat(idx)"
                                        class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 mr-1" />
                                        Hapus
                                    </Button>
                                </div>

                                <!-- Judul Maklumat -->
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                    >
                                        Judul Maklumat Pelayanan
                                    </label>
                                    <Input
                                        v-model="item.judul_maklumat"
                                        class="bg-white"
                                        placeholder="Contoh: Maklumat Pelayanan Tahun 2026"
                                    />
                                </div>

                                <!-- File Lampiran Maklumat (PDF / Gambar) -->
                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-bold text-slate-700 block"
                                    >
                                        File Lampiran Dokumen Maklumat (PDF /
                                        Dokumen Resmi)
                                    </label>

                                    <!-- Jika file sudah terpasang -->
                                    <div
                                        v-if="item.file_maklumat"
                                        class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 space-y-3"
                                    >
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                        >
                                            <div
                                                class="flex items-center gap-3 min-w-0"
                                            >
                                                <div
                                                    class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs"
                                                >
                                                    <FileText
                                                        v-if="
                                                            !isImageFile(
                                                                item.file_maklumat,
                                                            )
                                                        "
                                                        class="w-5 h-5"
                                                    />
                                                    <ImageIcon
                                                        v-else
                                                        class="w-5 h-5"
                                                    />
                                                </div>
                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <span
                                                            class="text-xs font-bold text-slate-800 truncate block"
                                                        >
                                                            {{
                                                                getFileName(
                                                                    item.file_maklumat,
                                                                )
                                                            }}
                                                        </span>
                                                        <Badge
                                                            variant="secondary"
                                                            class="bg-blue-100 text-blue-700 text-[10px] font-semibold border-0 shrink-0"
                                                        >
                                                            {{
                                                                item.file_maklumat.endsWith(
                                                                    ".pdf",
                                                                )
                                                                    ? "PDF Document"
                                                                    : "File Terlampir"
                                                            }}
                                                        </Badge>
                                                    </div>
                                                    <p
                                                        class="text-[11px] text-slate-500 font-mono truncate mt-0.5"
                                                        :title="
                                                            item.file_maklumat
                                                        "
                                                    >
                                                        {{ item.file_maklumat }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div
                                                class="flex flex-wrap items-center gap-2 shrink-0"
                                            >
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        toggleFilePreview(
                                                            `maklumat-${idx}`,
                                                        )
                                                    "
                                                    :class="[
                                                        'h-8 text-xs font-semibold rounded-xl transition-all',
                                                        previewFiles[
                                                            `maklumat-${idx}`
                                                        ]
                                                            ? 'bg-blue-600 text-white hover:bg-blue-700 border-blue-600'
                                                            : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                                                    ]"
                                                >
                                                    <component
                                                        :is="
                                                            previewFiles[
                                                                `maklumat-${idx}`
                                                            ]
                                                                ? EyeOff
                                                                : Eye
                                                        "
                                                        class="w-3.5 h-3.5 mr-1"
                                                    />
                                                    {{
                                                        previewFiles[
                                                            `maklumat-${idx}`
                                                        ]
                                                            ? "Tutup Preview"
                                                            : "Pratinjau PDF"
                                                    }}
                                                </Button>

                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        insertFileToEditor(
                                                            'maklumat',
                                                            idx,
                                                        )
                                                    "
                                                    class="h-8 text-xs font-semibold rounded-xl bg-white border-slate-200 hover:bg-slate-50 text-slate-700"
                                                    title="Sisipkan tautan unduh berkas lampiran ini langsung ke dalam Editor di bawah"
                                                >
                                                    <Check
                                                        v-if="
                                                            insertedKey ===
                                                            `maklumat-${idx}`
                                                        "
                                                        class="w-3.5 h-3.5 mr-1 text-emerald-600"
                                                    />
                                                    <FileText
                                                        v-else
                                                        class="w-3.5 h-3.5 mr-1 text-blue-600"
                                                    />
                                                    {{
                                                        insertedKey ===
                                                        `maklumat-${idx}`
                                                            ? "Tersisip ke Editor!"
                                                            : "Sisipkan ke Editor"
                                                    }}
                                                </Button>

                                                <a
                                                    :href="
                                                        getStorageUrl(
                                                            item.file_maklumat,
                                                        )
                                                    "
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
                                                >
                                                    <ExternalLink
                                                        class="w-3.5 h-3.5"
                                                    />
                                                    <span>Unduh Dokumen</span>
                                                </a>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        triggerFileUpload(
                                                            'maklumat',
                                                            idx,
                                                        )
                                                    "
                                                    :disabled="
                                                        uploadingFiles[
                                                            `maklumat-${idx}`
                                                        ]
                                                    "
                                                    class="h-8 text-xs font-semibold bg-white border-slate-200 hover:bg-slate-50 text-slate-700"
                                                >
                                                    <Upload
                                                        class="w-3.5 h-3.5 mr-1 text-blue-600"
                                                    />
                                                    {{
                                                        uploadingFiles[
                                                            `maklumat-${idx}`
                                                        ]
                                                            ? "Mengunggah..."
                                                            : "Ganti File"
                                                    }}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="
                                                        item.file_maklumat = ''
                                                    "
                                                    class="h-8 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                                    title="Hapus file lampiran"
                                                >
                                                    <Trash2
                                                        class="w-3.5 h-3.5 mr-1"
                                                    />
                                                    Hapus
                                                </Button>
                                            </div>
                                        </div>

                                        <!-- Expandable Preview Container -->
                                        <div
                                            v-if="
                                                previewFiles[`maklumat-${idx}`]
                                            "
                                            class="pt-3 border-t border-blue-200/80 space-y-2 animate-in fade-in duration-200"
                                        >
                                            <div
                                                class="flex items-center justify-between text-xs font-bold text-slate-700 px-1"
                                            >
                                                <span
                                                    >Pratinjau Dokumen
                                                    Lampiran:</span
                                                >
                                                <span
                                                    class="text-[11px] text-slate-500 font-mono"
                                                    >{{
                                                        getFileName(
                                                            item.file_maklumat,
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                            <div
                                                class="w-full bg-slate-900/5 rounded-xl border border-blue-200 overflow-hidden min-h-[450px] h-[550px]"
                                            >
                                                <iframe
                                                    v-if="
                                                        isPdfFile(
                                                            item.file_maklumat,
                                                        )
                                                    "
                                                    :src="
                                                        getStorageUrl(
                                                            item.file_maklumat,
                                                        ) + '#toolbar=1'
                                                    "
                                                    class="w-full h-full border-0 rounded-xl bg-white"
                                                    title="Pratinjau PDF Maklumat"
                                                ></iframe>
                                                <div
                                                    v-else-if="
                                                        isImageFile(
                                                            item.file_maklumat,
                                                        )
                                                    "
                                                    class="w-full h-full flex items-center justify-center p-4 bg-white"
                                                >
                                                    <img
                                                        :src="
                                                            getStorageUrl(
                                                                item.file_maklumat,
                                                            )
                                                        "
                                                        class="max-h-[500px] w-auto object-contain rounded-lg shadow-xs"
                                                    />
                                                </div>
                                                <div
                                                    v-else
                                                    class="p-8 text-center text-xs text-slate-500 bg-white h-full flex flex-col items-center justify-center"
                                                >
                                                    <p>
                                                        Format dokumen ini tidak
                                                        mendukung pratinjau
                                                        langsung di dalam
                                                        browser.
                                                    </p>
                                                    <a
                                                        :href="
                                                            getStorageUrl(
                                                                item.file_maklumat,
                                                            )
                                                        "
                                                        target="_blank"
                                                        class="mt-2 text-blue-600 font-bold underline"
                                                        >Klik di sini untuk
                                                        mengunduh dokumen</a
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Jika file belum ada / ingin upload baru -->
                                    <div v-else class="space-y-2">
                                        <div
                                            @click="
                                                triggerFileUpload(
                                                    'maklumat',
                                                    idx,
                                                )
                                            "
                                            class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl cursor-pointer bg-white hover:bg-blue-50/30 transition-all text-center group"
                                        >
                                            <div
                                                class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform"
                                            >
                                                <Upload class="w-5 h-5" />
                                            </div>
                                            <span
                                                class="text-xs font-bold text-slate-700"
                                            >
                                                {{
                                                    uploadingFiles[
                                                        `maklumat-${idx}`
                                                    ]
                                                        ? "Sedang Mengunggah Dokumen..."
                                                        : "Klik untuk Unggah Dokumen Lampiran (PDF / Gambar / Dokumen)"
                                                }}
                                            </span>
                                            <span
                                                class="text-[10px] text-slate-400 mt-0.5"
                                            >
                                                Format: PDF, PNG, JPG, WEBP,
                                                DOCX (Maksimal 20MB)
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Input
                                                v-model="item.file_maklumat"
                                                placeholder="Atau tempel path relatif file (contoh: website/pelayanan/maklumat/...)"
                                                class="bg-white h-8 text-xs font-mono"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Isi / Teks Maklumat (RichTextEditor) -->
                                <div class="space-y-1.5">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <label
                                            class="text-xs font-bold text-slate-700"
                                        >
                                            Isi & Teks Komitmen Maklumat
                                            Pelayanan (Word Editor)
                                        </label>
                                        <span
                                            class="text-[10px] text-slate-400"
                                        >
                                            Mendukung gambar banner, tabel,
                                            perataan teks, dan styling dokumen
                                        </span>
                                    </div>
                                    <RichTextEditor
                                        v-model="item.keterangan_maklumat"
                                        min-height="320px"
                                        placeholder="Teks komitmen maklumat pelayanan publik balai seperti di Microsoft Word..."
                                        upload-folder="website/pelayanan/maklumat"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Standar Pelayanan -->
                        <div
                            v-if="activeSection === 'standar'"
                            class="space-y-6"
                        >
                            <div
                                v-for="(item, idx) in form.standar_pelayanan"
                                :key="idx"
                                class="p-5 rounded-2xl border border-slate-200/80 bg-slate-50/50 space-y-4"
                            >
                                <div
                                    class="flex items-center justify-between border-b border-slate-200/70 pb-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <span
                                            class="text-xs font-black text-slate-800 uppercase tracking-wider font-mono"
                                        >
                                            #{{ idx + 1 }} STANDAR PELAYANAN
                                        </span>
                                        <Badge
                                            v-if="item.file_standar"
                                            class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold border-0"
                                        >
                                            Lampiran Ada
                                        </Badge>
                                    </div>
                                    <Button
                                        v-if="form.standar_pelayanan.length > 1"
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeStandar(idx)"
                                        class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 mr-1" />
                                        Hapus
                                    </Button>
                                </div>

                                <!-- Judul Standar -->
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                    >
                                        Judul Standar Pelayanan
                                    </label>
                                    <Input
                                        v-model="item.judul_standar"
                                        class="bg-white"
                                        placeholder="Contoh: Standar Pelayanan Publik Balai Pelatihan Vokasi"
                                    />
                                </div>

                                <!-- File Lampiran Standar (PDF / Gambar) -->
                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-bold text-slate-700 block"
                                    >
                                        File Lampiran Dokumen Standar (PDF /
                                        Dokumen Resmi)
                                    </label>

                                    <!-- Jika file sudah terpasang -->
                                    <div
                                        v-if="item.file_standar"
                                        class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 space-y-3"
                                    >
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                                        >
                                            <div
                                                class="flex items-center gap-3 min-w-0"
                                            >
                                                <div
                                                    class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs"
                                                >
                                                    <FileText
                                                        v-if="
                                                            !isImageFile(
                                                                item.file_standar,
                                                            )
                                                        "
                                                        class="w-5 h-5"
                                                    />
                                                    <ImageIcon
                                                        v-else
                                                        class="w-5 h-5"
                                                    />
                                                </div>
                                                <div class="min-w-0">
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <span
                                                            class="text-xs font-bold text-slate-800 truncate block"
                                                        >
                                                            {{
                                                                getFileName(
                                                                    item.file_standar,
                                                                )
                                                            }}
                                                        </span>
                                                        <Badge
                                                            variant="secondary"
                                                            class="bg-blue-100 text-blue-700 text-[10px] font-semibold border-0 shrink-0"
                                                        >
                                                            {{
                                                                item.file_standar.endsWith(
                                                                    ".pdf",
                                                                )
                                                                    ? "PDF Document"
                                                                    : "File Terlampir"
                                                            }}
                                                        </Badge>
                                                    </div>
                                                    <p
                                                        class="text-[11px] text-slate-500 font-mono truncate mt-0.5"
                                                        :title="
                                                            item.file_standar
                                                        "
                                                    >
                                                        {{ item.file_standar }}
                                                    </p>
                                                </div>
                                            </div>

                                            <div
                                                class="flex flex-wrap items-center gap-2 shrink-0"
                                            >
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        toggleFilePreview(
                                                            `standar-${idx}`,
                                                        )
                                                    "
                                                    :class="[
                                                        'h-8 text-xs font-semibold rounded-xl transition-all',
                                                        previewFiles[
                                                            `standar-${idx}`
                                                        ]
                                                            ? 'bg-blue-600 text-white hover:bg-blue-700 border-blue-600'
                                                            : 'bg-white border-slate-200 text-slate-700 hover:bg-slate-50',
                                                    ]"
                                                >
                                                    <component
                                                        :is="
                                                            previewFiles[
                                                                `standar-${idx}`
                                                            ]
                                                                ? EyeOff
                                                                : Eye
                                                        "
                                                        class="w-3.5 h-3.5 mr-1"
                                                    />
                                                    {{
                                                        previewFiles[
                                                            `standar-${idx}`
                                                        ]
                                                            ? "Tutup Preview"
                                                            : "Pratinjau PDF"
                                                    }}
                                                </Button>

                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        insertFileToEditor(
                                                            'standar',
                                                            idx,
                                                        )
                                                    "
                                                    class="h-8 text-xs font-semibold rounded-xl bg-white border-slate-200 hover:bg-slate-50 text-slate-700"
                                                    title="Sisipkan tautan unduh berkas lampiran ini langsung ke dalam Editor di bawah"
                                                >
                                                    <Check
                                                        v-if="
                                                            insertedKey ===
                                                            `standar-${idx}`
                                                        "
                                                        class="w-3.5 h-3.5 mr-1 text-emerald-600"
                                                    />
                                                    <FileText
                                                        v-else
                                                        class="w-3.5 h-3.5 mr-1 text-blue-600"
                                                    />
                                                    {{
                                                        insertedKey ===
                                                        `standar-${idx}`
                                                            ? "Tersisip ke Editor!"
                                                            : "Sisipkan ke Editor"
                                                    }}
                                                </Button>

                                                <a
                                                    :href="
                                                        getStorageUrl(
                                                            item.file_standar,
                                                        )
                                                    "
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
                                                >
                                                    <ExternalLink
                                                        class="w-3.5 h-3.5"
                                                    />
                                                    <span>Unduh Dokumen</span>
                                                </a>
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        triggerFileUpload(
                                                            'standar',
                                                            idx,
                                                        )
                                                    "
                                                    :disabled="
                                                        uploadingFiles[
                                                            `standar-${idx}`
                                                        ]
                                                    "
                                                    class="h-8 text-xs font-semibold bg-white border-slate-200 hover:bg-slate-50 text-slate-700"
                                                >
                                                    <Upload
                                                        class="w-3.5 h-3.5 mr-1 text-blue-600"
                                                    />
                                                    {{
                                                        uploadingFiles[
                                                            `standar-${idx}`
                                                        ]
                                                            ? "Mengunggah..."
                                                            : "Ganti File"
                                                    }}
                                                </Button>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="sm"
                                                    @click="
                                                        item.file_standar = ''
                                                    "
                                                    class="h-8 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                                    title="Hapus file lampiran"
                                                >
                                                    <Trash2
                                                        class="w-3.5 h-3.5 mr-1"
                                                    />
                                                    Hapus
                                                </Button>
                                            </div>
                                        </div>

                                        <!-- Expandable Preview Container -->
                                        <div
                                            v-if="
                                                previewFiles[`standar-${idx}`]
                                            "
                                            class="pt-3 border-t border-blue-200/80 space-y-2 animate-in fade-in duration-200"
                                        >
                                            <div
                                                class="flex items-center justify-between text-xs font-bold text-slate-700 px-1"
                                            >
                                                <span
                                                    >Pratinjau Dokumen
                                                    Lampiran:</span
                                                >
                                                <span
                                                    class="text-[11px] text-slate-500 font-mono"
                                                    >{{
                                                        getFileName(
                                                            item.file_standar,
                                                        )
                                                    }}</span
                                                >
                                            </div>
                                            <div
                                                class="w-full bg-slate-900/5 rounded-xl border border-blue-200 overflow-hidden min-h-[450px] h-[550px]"
                                            >
                                                <iframe
                                                    v-if="
                                                        isPdfFile(
                                                            item.file_standar,
                                                        )
                                                    "
                                                    :src="
                                                        getStorageUrl(
                                                            item.file_standar,
                                                        ) + '#toolbar=1'
                                                    "
                                                    class="w-full h-full border-0 rounded-xl bg-white"
                                                    title="Pratinjau PDF Standar"
                                                ></iframe>
                                                <div
                                                    v-else-if="
                                                        isImageFile(
                                                            item.file_standar,
                                                        )
                                                    "
                                                    class="w-full h-full flex items-center justify-center p-4 bg-white"
                                                >
                                                    <img
                                                        :src="
                                                            getStorageUrl(
                                                                item.file_standar,
                                                            )
                                                        "
                                                        class="max-h-[500px] w-auto object-contain rounded-lg shadow-xs"
                                                    />
                                                </div>
                                                <div
                                                    v-else
                                                    class="p-8 text-center text-xs text-slate-500 bg-white h-full flex flex-col items-center justify-center"
                                                >
                                                    <p>
                                                        Format dokumen ini tidak
                                                        mendukung pratinjau
                                                        langsung di dalam
                                                        browser.
                                                    </p>
                                                    <a
                                                        :href="
                                                            getStorageUrl(
                                                                item.file_standar,
                                                            )
                                                        "
                                                        target="_blank"
                                                        class="mt-2 text-blue-600 font-bold underline"
                                                        >Klik di sini untuk
                                                        mengunduh dokumen</a
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Jika file belum ada / ingin upload baru -->
                                    <div v-else class="space-y-2">
                                        <div
                                            @click="
                                                triggerFileUpload(
                                                    'standar',
                                                    idx,
                                                )
                                            "
                                            class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl cursor-pointer bg-white hover:bg-blue-50/30 transition-all text-center group"
                                        >
                                            <div
                                                class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center mb-2 group-hover:scale-110 transition-transform"
                                            >
                                                <Upload class="w-5 h-5" />
                                            </div>
                                            <span
                                                class="text-xs font-bold text-slate-700"
                                            >
                                                {{
                                                    uploadingFiles[
                                                        `standar-${idx}`
                                                    ]
                                                        ? "Sedang Mengunggah Dokumen..."
                                                        : "Klik untuk Unggah Dokumen Standar (PDF / Dokumen / Gambar)"
                                                }}
                                            </span>
                                            <span
                                                class="text-[10px] text-slate-400 mt-0.5"
                                            >
                                                Format: PDF, PNG, JPG, WEBP,
                                                DOCX (Maksimal 20MB)
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <Input
                                                v-model="item.file_standar"
                                                placeholder="Atau tempel path relatif file (contoh: website/pelayanan/standar/...)"
                                                class="bg-white h-8 text-xs font-mono"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <!-- Isi / Teks Standar (RichTextEditor) -->
                                <div class="space-y-1.5">
                                    <div
                                        class="flex items-center justify-between"
                                    >
                                        <label
                                            class="text-xs font-bold text-slate-700"
                                        >
                                            Isi & Teks Standar Pelayanan (Word
                                            Editor)
                                        </label>
                                        <span
                                            class="text-[10px] text-slate-400"
                                        >
                                            Mendukung tabel komparasi, gambar
                                            panduan, dan format resmi
                                        </span>
                                    </div>
                                    <RichTextEditor
                                        v-model="item.keterangan_standar"
                                        min-height="320px"
                                        placeholder="Teks standar pelayanan publik balai seperti di Microsoft Word..."
                                        upload-folder="website/pelayanan/standar"
                                    />
                                </div>
                            </div>
                        </div>

                        <!-- Alur Pelayanan -->
                        <div v-if="activeSection === 'alur'" class="space-y-4">
                            <div class="space-y-2">
                                <label
                                    class="text-xs font-bold text-slate-700 block"
                                >
                                    Bagan Gambar Alur Pelayanan
                                </label>

                                <div
                                    v-if="alurPreview"
                                    class="space-y-3 p-4 rounded-2xl border border-slate-200 bg-slate-50"
                                >
                                    <img
                                        :src="alurPreview"
                                        class="max-h-64 rounded-xl border border-slate-200 object-contain bg-white p-2"
                                        alt="Alur"
                                    />
                                    <label
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer shadow-2xs"
                                    >
                                        <Upload
                                            class="w-3.5 h-3.5 text-blue-600"
                                        />
                                        <span>Ganti Gambar Alur</span>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="onAlurSelected"
                                        />
                                    </label>
                                </div>

                                <label
                                    v-else
                                    class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl cursor-pointer bg-slate-50 hover:bg-blue-50/30 transition-colors"
                                >
                                    <Upload
                                        class="w-8 h-8 text-blue-600 mb-2"
                                    />
                                    <span
                                        class="text-xs font-bold text-slate-700"
                                    >
                                        Unggah Gambar Bagan Alur Pelayanan
                                    </span>
                                    <span class="text-[10px] text-slate-400"
                                        >PNG, JPG, WEBP</span
                                    >
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="onAlurSelected"
                                    />
                                </label>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700">
                                    Deskripsi Alur Pelayanan (Word Editor)
                                </label>
                                <RichTextEditor
                                    v-model="form.deskripsi_alur_pelayanan"
                                    min-height="200px"
                                    placeholder="Penjelasan tahapan alur pelayanan seperti di Microsoft Word..."
                                    upload-folder="website/pelayanan/alur-deskripsi"
                                />
                            </div>
                        </div>

                        <!-- Survey Kepuasan -->
                        <div
                            v-if="activeSection === 'survey_kepuasan'"
                            class="space-y-1.5"
                        >
                            <label class="text-xs font-bold text-slate-700">
                                URL / Tautan Survey Kepuasan Masyarakat
                            </label>
                            <Input
                                v-model="form.survey_kepuasan_masyarakat"
                                placeholder="https://forms.google.com/..."
                            />
                        </div>

                        <!-- Survey Kebutuhan -->
                        <div
                            v-if="activeSection === 'survey_kebutuhan'"
                            class="space-y-1.5"
                        >
                            <label class="text-xs font-bold text-slate-700">
                                URL / Tautan Survey Kebutuhan Pelatihan
                            </label>
                            <Input
                                v-model="form.survey_kebutuhan_pelatihan"
                                placeholder="https://forms.google.com/..."
                            />
                        </div>

                        <!-- Survey Kebekerjaan -->
                        <div
                            v-if="activeSection === 'survey_kebekerjaan'"
                            class="space-y-1.5"
                        >
                            <label class="text-xs font-bold text-slate-700">
                                URL / Tautan Survey Kebekerjaan Alumni
                            </label>
                            <Input
                                v-model="form.survey_kebekerjaan"
                                placeholder="https://forms.google.com/..."
                            />
                        </div>

                        <!-- Indeks Kepuasan -->
                        <div
                            v-if="activeSection === 'indeks'"
                            class="space-y-1.5"
                        >
                            <label class="text-xs font-bold text-slate-700">
                                Ringkasan / Hasil Indeks Kepuasan Masyarakat
                                (Word Editor)
                            </label>
                            <RichTextEditor
                                v-model="form.indeks_kepuasan_masyarakat"
                                min-height="250px"
                                placeholder="Nilai IKM atau grafik indeks kepuasan seperti di Microsoft Word..."
                                upload-folder="website/pelayanan/ikm"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </DashboardLayout>
</template>
