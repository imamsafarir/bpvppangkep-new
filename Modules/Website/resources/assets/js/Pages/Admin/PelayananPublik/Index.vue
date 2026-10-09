<script setup>
import { ref, computed } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
import { RichTextEditor } from "@/Components/ui/rich-text-editor";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
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
    ArrowUp,
    ArrowDown,
    Copy,
    ChevronDown,
    ChevronUp,
    ZoomIn,
    X,
    Sparkles,
    HelpCircle,
    RotateCcw,
    Link as LinkIcon,
} from "lucide-vue-next";

const props = defineProps({
    pelayanan: Object,
});

const page = usePage();

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
    const clean = path.split("?")[0].split("#")[0];
    const ext = clean.split(".").pop().toLowerCase();
    return ["jpg", "jpeg", "png", "webp", "gif", "avif"].includes(ext);
};

const isPdfFile = (path) => {
    if (!path) return false;
    const clean = path.split("?")[0].split("#")[0];
    return clean.split(".").pop().toLowerCase() === "pdf";
};

const getFileName = (path) => {
    if (!path) return "";
    return path.split("/").pop().split("\\").pop();
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

const parseMaklumat = (val) => {
    if (!val) {
        return [
            {
                judul_maklumat: "Maklumat Pelayanan",
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

// Alur Pelayanan State
const alurFile = ref(props.pelayanan?.foto_alur_pelayanan || "");
const alurPreview = ref(getStorageUrl(props.pelayanan?.foto_alur_pelayanan));
const removeFotoAlur = ref(false);
const uploadingFiles = ref({});

const isAlurPdf = computed(() => {
    if (form.foto_alur_pelayanan instanceof File) {
        return (
            form.foto_alur_pelayanan.type === "application/pdf" ||
            form.foto_alur_pelayanan.name.toLowerCase().endsWith(".pdf")
        );
    }
    return isPdfFile(alurFile.value);
});

const onAlurSelected = (e) => {
    const file = e.target.files?.[0];
    if (file) {
        form.foto_alur_pelayanan = file;
        removeFotoAlur.value = false;
        alurFile.value = file.name;
        alurPreview.value = URL.createObjectURL(file);
    }
};

const clearAlurFile = () => {
    form.foto_alur_pelayanan = null;
    alurFile.value = "";
    alurPreview.value = null;
    removeFotoAlur.value = true;
};

// Form state
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

// Sections configuration
const activeSection = ref("maklumat");
const sections = [
    {
        id: "maklumat",
        label: "Maklumat Pelayanan",
        icon: FileText,
        publicUrl: "/pelayanan-publik/maklumat",
    },
    {
        id: "standar",
        label: "Standar Pelayanan",
        icon: CheckSquare,
        publicUrl: "/pelayanan-publik/standar-pelayanan",
    },
    {
        id: "alur",
        label: "Alur Pelayanan",
        icon: GitFork,
        publicUrl: "/pelayanan-publik/alur-pelayanan",
    },
    {
        id: "survey_kepuasan",
        label: "Survey Kepuasan",
        icon: ClipboardList,
        publicUrl: "/pelayanan-publik/survey-kepuasan",
    },
    {
        id: "survey_kebutuhan",
        label: "Survey Kebutuhan",
        icon: GraduationCap,
        publicUrl: "/pelayanan-publik/survey-kebutuhan",
    },
    {
        id: "survey_kebekerjaan",
        label: "Survey Kebekerjaan",
        icon: Briefcase,
        publicUrl: "/pelayanan-publik/survey-kebekerjaan",
    },
    {
        id: "indeks",
        label: "Indeks Kepuasan",
        icon: BarChart3,
        publicUrl: "/pelayanan-publik/indeks-kepuasan",
    },
];

const currentSection = computed(() => {
    return sections.find((s) => s.id === activeSection.value) || sections[0];
});

// Maklumat controls
const collapsedMaklumat = ref({});
const toggleMaklumatCollapse = (idx) => {
    collapsedMaklumat.value[idx] = !collapsedMaklumat.value[idx];
};

const addMaklumat = () => {
    form.maklumat_pelayanan.push({
        judul_maklumat: "",
        file_maklumat: "",
        keterangan_maklumat: "",
    });
};

const removeMaklumat = (index) => {
    if (form.maklumat_pelayanan.length > 1) {
        if (confirm("Apakah Anda yakin ingin menghapus poin maklumat ini?")) {
            form.maklumat_pelayanan.splice(index, 1);
        }
    } else {
        form.maklumat_pelayanan[0] = {
            judul_maklumat: "",
            file_maklumat: "",
            keterangan_maklumat: "",
        };
    }
};

const moveMaklumat = (idx, direction) => {
    const targetIdx = idx + direction;
    if (targetIdx < 0 || targetIdx >= form.maklumat_pelayanan.length) return;
    const item = form.maklumat_pelayanan.splice(idx, 1)[0];
    form.maklumat_pelayanan.splice(targetIdx, 0, item);
};

const duplicateMaklumat = (idx) => {
    const source = form.maklumat_pelayanan[idx];
    form.maklumat_pelayanan.splice(idx + 1, 0, {
        judul_maklumat: source.judul_maklumat
            ? `${source.judul_maklumat} (Salinan)`
            : "Maklumat Pelayanan (Salinan)",
        file_maklumat: source.file_maklumat,
        keterangan_maklumat: source.keterangan_maklumat,
    });
};

// Standar controls
const collapsedStandar = ref({});
const toggleStandarCollapse = (idx) => {
    collapsedStandar.value[idx] = !collapsedStandar.value[idx];
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
        if (
            confirm("Apakah Anda yakin ingin menghapus standar pelayanan ini?")
        ) {
            form.standar_pelayanan.splice(index, 1);
        }
    } else {
        form.standar_pelayanan[0] = {
            judul_standar: "",
            file_standar: "",
            keterangan_standar: "",
        };
    }
};

const moveStandar = (idx, direction) => {
    const targetIdx = idx + direction;
    if (targetIdx < 0 || targetIdx >= form.standar_pelayanan.length) return;
    const item = form.standar_pelayanan.splice(idx, 1)[0];
    form.standar_pelayanan.splice(targetIdx, 0, item);
};

const duplicateStandar = (idx) => {
    const source = form.standar_pelayanan[idx];
    form.standar_pelayanan.splice(idx + 1, 0, {
        judul_standar: source.judul_standar
            ? `${source.judul_standar} (Salinan)`
            : "Standar Pelayanan (Salinan)",
        file_standar: source.file_standar,
        keterangan_standar: source.keterangan_standar,
    });
};

// Generic File Upload Trigger
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
            const errMsg =
                err.response?.data?.errors?.file?.[0] ||
                err.response?.data?.message ||
                "Gagal mengunggah file lampiran. Pastikan ukuran file tidak melebihi 20MB dan format didukung.";
            alert(errMsg);
        } finally {
            uploadingFiles.value[uploadKey] = false;
        }
    };
    input.click();
};

// Survey URL cleaner & helper
const cleanSurveyUrl = (val) => {
    if (!val) return "";
    let trimmed = val.trim();
    if (trimmed.includes("<iframe")) {
        const match = trimmed.match(/src=["']([^"']+)["']/i);
        if (match && match[1]) return match[1];
    }
    return trimmed;
};

const onSurveyUrlInput = (key, event) => {
    const val = event.target.value;
    const cleaned = cleanSurveyUrl(val);
    form[key] = cleaned;
};

// Survey Live Preview toggles
const previewSurveys = ref({
    survey_kepuasan: false,
    survey_kebutuhan: false,
    survey_kebekerjaan: false,
});

const toggleSurveyPreview = (key) => {
    previewSurveys.value[key] = !previewSurveys.value[key];
};

// Template Generators
const insertAlurTemplate = () => {
    const template = `
<h3>Tahapan & Mekanisme Alur Pelayanan Publik</h3>
<ol>
    <li><strong>Penerimaan Berkas & Permohonan:</strong> Pemohon mendatangi meja Pelayanan Terpadu Satu Pintu (PTSP) atau mengisi formulir registrasi online balai.</li>
    <li><strong>Verifikasi Dokumen:</strong> Petugas loket memeriksa kelengkapan berkas administrasi pemohon (estimasi: 10 - 15 menit).</li>
    <li><strong>Pemrosesan Teknis:</strong> Tim teknis atau seksi penyelenggara memproses permohonan sesuai SOP yang berlaku.</li>
    <li><strong>Penyerahan Hasil Layanan:</strong> Pemohon menerima dokumen, sertifikat, atau produk layanan resmi yang telah disahkan.</li>
    <li><strong>Pengisian Kuesioner Survey (IKM):</strong> Pemohon mengisi instrumen evaluasi kepuasan masyarakat melalui loket digital.</li>
</ol>
`;
    form.deskripsi_alur_pelayanan =
        (form.deskripsi_alur_pelayanan || "") + template;
};

const insertIkmTableTemplate = () => {
    const year = new Date().getFullYear();
    const template = `
<div class="my-6 overflow-hidden rounded-2xl border border-slate-200 shadow-xs bg-white">
    <div style="background-color: #2563eb; color: #ffffff; padding: 14px; font-weight: bold; font-size: 15px; text-align: center;">
        REKAPITULASI INDEKS KEPUASAN MASYARAKAT (IKM) TAHUN ${year}
    </div>
    <div style="padding: 16px;">
        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 13px;" border="1" cellpadding="8">
            <thead>
                <tr style="background-color: #f1f5f9; font-weight: bold;">
                    <th style="padding: 10px; border: 1px solid #cbd5e1; width: 8%; text-align: center;">No</th>
                    <th style="padding: 10px; border: 1px solid #cbd5e1;">Unsur Pelayanan (PermenPAN-RB No. 14/2017)</th>
                    <th style="padding: 10px; border: 1px solid #cbd5e1; width: 22%; text-align: center;">Nilai Rata-rata (NRR)</th>
                    <th style="padding: 10px; border: 1px solid #cbd5e1; width: 20%; text-align: center;">Mutu Pelayanan</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">1</td>
                    <td style="border: 1px solid #cbd5e1;">Persyaratan Pelayanan</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3.68</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">2</td>
                    <td style="border: 1px solid #cbd5e1;">Kemudahan Prosedur dan Alur</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3.62</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3</td>
                    <td style="border: 1px solid #cbd5e1;">Kecepatan Waktu Pelayanan</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3.58</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">4</td>
                    <td style="border: 1px solid #cbd5e1;">Kesesuaian Tarif / Biaya (Gratis)</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">4.00</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">5</td>
                    <td style="border: 1px solid #cbd5e1;">Kesesuaian Produk Spesifikasi Pelayanan</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3.72</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">6</td>
                    <td style="border: 1px solid #cbd5e1;">Kompetensi dan Kemampuan Petugas</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3.70</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">7</td>
                    <td style="border: 1px solid #cbd5e1;">Perilaku dan Kesopanan Petugas</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3.76</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">8</td>
                    <td style="border: 1px solid #cbd5e1;">Kualitas Sarana dan Prasarana Pelayanan</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3.65</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">9</td>
                    <td style="border: 1px solid #cbd5e1;">Penanganan Pengaduan dan Saran</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1;">3.71</td>
                    <td style="text-align: center; border: 1px solid #cbd5e1; font-weight: bold; color: #16a34a;">Sangat Baik (A)</td>
                </tr>
                <tr style="background-color: #f8fafc; font-weight: bold;">
                    <td colspan="2" style="padding: 12px; border: 1px solid #cbd5e1; text-align: right;">NILAI IKM (SKOR KONVERSI):</td>
                    <td style="padding: 12px; border: 1px solid #cbd5e1; text-align: center; color: #2563eb; font-size: 16px;">91.80</td>
                    <td style="padding: 12px; border: 1px solid #cbd5e1; text-align: center; font-size: 15px; color: #16a34a;">A (SANGAT BAIK)</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
`;
    form.indeks_kepuasan_masyarakat =
        (form.indeks_kepuasan_masyarakat || "") + template;
};

// Lightbox modal state
const isZoomOpen = ref(false);
const zoomImageUrl = ref("");
const zoomImageTitle = ref("");

const openZoom = (url, title = "") => {
    if (!url) return;
    zoomImageUrl.value = url;
    zoomImageTitle.value = title;
    isZoomOpen.value = true;
};

// Dismissible success message state
const showSuccessNotice = ref(true);

// Submit handler
const submit = () => {
    showSuccessNotice.value = true;
    form.transform((data) => ({
        ...data,
        _method: "PUT",
        remove_foto_alur_pelayanan: removeFotoAlur.value ? 1 : 0,
    })).post("/admin/pelayanan", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            removeFotoAlur.value = false;
        },
    });
};
</script>

<template>
    <Head title="Pelayanan Publik & Survey" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header Bar -->
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
                        Kelola maklumat pelayanan, standar pelayanan publik (PDF
                        & naskah), bagan alur, tautan survei, dan laporan indeks
                        IKM.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <Button
                        @click="submit"
                        :loading="form.processing"
                        class="bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 gap-1.5"
                    >
                        <Save v-if="!form.processing" class="w-4 h-4" />
                        <span>{{
                            form.processing
                                ? "Menyimpan..."
                                : "Simpan Perubahan"
                        }}</span>
                    </Button>
                </div>
            </div>

            <!-- Flash & Success Alert -->
            <div
                v-if="
                    showSuccessNotice &&
                    (form.wasSuccessful || page.props.flash?.success)
                "
                class="flex items-center justify-between gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold animate-in fade-in"
            >
                <div class="flex items-center gap-2">
                    <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{{
                        page.props.flash?.success ||
                        "Data pelayanan publik berhasil diperbarui."
                    }}</span>
                </div>
                <button
                    type="button"
                    @click="showSuccessNotice = false"
                    class="text-emerald-600 hover:text-emerald-800 p-1"
                >
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>

            <div class="space-y-4">
                <!-- Top Tabs Navigation Switcher -->
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

                        <!-- Maklumat Count Badge -->
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

                        <!-- Standar Count Badge -->
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

                        <!-- Alur Badge -->
                        <Badge
                            v-else-if="
                                s.id === 'alur' &&
                                (alurFile || form.foto_alur_pelayanan)
                            "
                            variant="secondary"
                            class="text-[9px] py-0 px-1.5 bg-emerald-100 text-emerald-700 font-semibold border-0 ml-0.5"
                        >
                            Bagan Ada
                        </Badge>

                        <!-- Survey Badges -->
                        <Badge
                            v-else-if="
                                [
                                    'survey_kepuasan',
                                    'survey_kebutuhan',
                                    'survey_kebekerjaan',
                                ].includes(s.id) &&
                                form[
                                    s.id === 'survey_kepuasan'
                                        ? 'survey_kepuasan_masyarakat'
                                        : s.id === 'survey_kebutuhan'
                                          ? 'survey_kebutuhan_pelatihan'
                                          : 'survey_kebekerjaan'
                                ]
                            "
                            variant="secondary"
                            class="text-[9px] py-0 px-1.5 bg-blue-100 text-blue-700 font-semibold border-0 ml-0.5"
                        >
                            Tersambung
                        </Badge>
                    </button>
                </div>

                <!-- Main Form Card -->
                <Card class="w-full shadow-xs border-slate-200">
                    <CardHeader
                        class="border-b border-slate-100 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <div>
                            <CardTitle
                                class="text-sm font-bold text-slate-900 capitalize flex items-center gap-2"
                            >
                                <component
                                    :is="currentSection.icon"
                                    class="w-4 h-4 text-blue-600"
                                />
                                <span>{{ currentSection.label }}</span>
                            </CardTitle>
                            <p class="text-xs text-slate-400 mt-0.5">
                                Atur dokumen resmi, teks komitmen, dan tautan
                                formulir pada bagian ini
                            </p>
                        </div>

                        <div class="flex items-center flex-wrap gap-2">
                            <!-- Shortcut Lihat Halaman Publik -->
                            <a
                                :href="currentSection.publicUrl"
                                target="_blank"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-xl bg-slate-100 hover:bg-blue-50 text-slate-700 hover:text-blue-700 border border-slate-200 transition-colors shadow-2xs"
                                title="Buka pratinjau halaman publik di tab baru"
                            >
                                <ExternalLink class="w-3.5 h-3.5" />
                                <span>Lihat Halaman Publik</span>
                            </a>

                            <!-- Tambah Maklumat Button -->
                            <Button
                                v-if="activeSection === 'maklumat'"
                                size="sm"
                                variant="outline"
                                @click="addMaklumat"
                                class="h-8 border-slate-200 text-slate-700 hover:bg-slate-50 gap-1"
                            >
                                <Plus class="w-3.5 h-3.5 text-blue-600" />
                                <span>Tambah Maklumat</span>
                            </Button>

                            <!-- Tambah Standar Button -->
                            <Button
                                v-else-if="activeSection === 'standar'"
                                size="sm"
                                variant="outline"
                                @click="addStandar"
                                class="h-8 border-slate-200 text-slate-700 hover:bg-slate-50 gap-1"
                            >
                                <Plus class="w-3.5 h-3.5 text-blue-600" />
                                <span>Tambah Standar</span>
                            </Button>
                        </div>
                    </CardHeader>

                    <CardContent class="pt-6 space-y-6">
                        <!-- ═══════════════════════════════════════════════════ -->
                        <!-- 1. MAKLUMAT PELAYANAN -->
                        <!-- ═══════════════════════════════════════════════════ -->
                        <div
                            v-if="activeSection === 'maklumat'"
                            class="space-y-6"
                        >
                            <div
                                v-for="(item, idx) in form.maklumat_pelayanan"
                                :key="idx"
                                class="rounded-2xl border border-slate-200/80 bg-slate-50/50 overflow-hidden shadow-2xs transition-all"
                            >
                                <!-- Item Header Bar -->
                                <div
                                    class="flex items-center justify-between p-4 bg-slate-100/60 border-b border-slate-200/70"
                                >
                                    <div
                                        class="flex items-center gap-2.5 min-w-0"
                                    >
                                        <span
                                            class="text-xs font-black text-slate-800 uppercase tracking-wider font-mono bg-white px-2 py-0.5 rounded-lg border border-slate-200 shrink-0"
                                        >
                                            #{{ idx + 1 }}
                                        </span>
                                        <span
                                            class="text-xs font-bold text-slate-800 truncate"
                                        >
                                            {{
                                                item.judul_maklumat ||
                                                "Maklumat Pelayanan Baru"
                                            }}
                                        </span>
                                        <Badge
                                            v-if="item.file_maklumat"
                                            class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold border-0 shrink-0 hidden sm:inline-flex"
                                        >
                                            Lampiran Ada
                                        </Badge>
                                        <Badge
                                            v-if="item.keterangan_maklumat"
                                            class="bg-blue-100 text-blue-700 text-[10px] font-semibold border-0 shrink-0 hidden md:inline-flex"
                                        >
                                            Teks Ada
                                        </Badge>
                                    </div>

                                    <div
                                        class="flex items-center gap-1 shrink-0"
                                    >
                                        <!-- Move Up -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            :disabled="idx === 0"
                                            @click="moveMaklumat(idx, -1)"
                                            class="h-7 w-7 p-0 text-slate-500 hover:text-slate-800"
                                            title="Geser ke Atas"
                                        >
                                            <ArrowUp class="w-3.5 h-3.5" />
                                        </Button>

                                        <!-- Move Down -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            :disabled="
                                                idx ===
                                                form.maklumat_pelayanan.length -
                                                    1
                                            "
                                            @click="moveMaklumat(idx, 1)"
                                            class="h-7 w-7 p-0 text-slate-500 hover:text-slate-800"
                                            title="Geser ke Bawah"
                                        >
                                            <ArrowDown class="w-3.5 h-3.5" />
                                        </Button>

                                        <!-- Duplicate -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="duplicateMaklumat(idx)"
                                            class="h-7 w-7 p-0 text-slate-500 hover:text-blue-600"
                                            title="Duplikat Maklumat Ini"
                                        >
                                            <Copy class="w-3.5 h-3.5" />
                                        </Button>

                                        <!-- Collapse Toggle -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="toggleMaklumatCollapse(idx)"
                                            class="h-7 w-7 p-0 text-slate-500 hover:text-slate-800"
                                            :title="
                                                collapsedMaklumat[idx]
                                                    ? 'Buka Rincian'
                                                    : 'Tutup Rincian'
                                            "
                                        >
                                            <ChevronDown
                                                v-if="!collapsedMaklumat[idx]"
                                                class="w-3.5 h-3.5"
                                            />
                                            <ChevronUp
                                                v-else
                                                class="w-3.5 h-3.5"
                                            />
                                        </Button>

                                        <!-- Delete -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeMaklumat(idx)"
                                            class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 ml-1"
                                            title="Hapus Maklumat Ini"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Item Body -->
                                <div
                                    v-show="!collapsedMaklumat[idx]"
                                    class="p-5 space-y-4"
                                >
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
                                            placeholder="Contoh: Maklumat Pelayanan Balai Tahun 2026"
                                        />
                                    </div>

                                    <!-- File Lampiran Maklumat (PDF / Gambar) -->
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-bold text-slate-700 block"
                                        >
                                            File Lampiran Dokumen Maklumat (PDF
                                            / Dokumen Resmi)
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
                                                        class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs cursor-pointer"
                                                        @click="
                                                            isImageFile(
                                                                item.file_maklumat,
                                                            )
                                                                ? openZoom(
                                                                      getStorageUrl(
                                                                          item.file_maklumat,
                                                                      ),
                                                                      item.judul_maklumat,
                                                                  )
                                                                : toggleFilePreview(
                                                                      `maklumat-${idx}`,
                                                                  )
                                                        "
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
                                                                    isPdfFile(
                                                                        item.file_maklumat,
                                                                    )
                                                                        ? "PDF Document"
                                                                        : isImageFile(
                                                                                item.file_maklumat,
                                                                            )
                                                                          ? "Foto/Gambar"
                                                                          : "Dokumen Resmi"
                                                                }}
                                                            </Badge>
                                                        </div>
                                                        <p
                                                            class="text-[11px] text-slate-500 font-mono truncate mt-0.5"
                                                            :title="
                                                                item.file_maklumat
                                                            "
                                                        >
                                                            {{
                                                                item.file_maklumat
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex flex-wrap items-center gap-2 shrink-0"
                                                >
                                                    <!-- Pratinjau Toggle -->
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
                                                                : isPdfFile(
                                                                        item.file_maklumat,
                                                                    )
                                                                  ? "Pratinjau PDF"
                                                                  : "Pratinjau File"
                                                        }}
                                                    </Button>

                                                    <!-- Zoom Gambar jika image -->
                                                    <Button
                                                        v-if="
                                                            isImageFile(
                                                                item.file_maklumat,
                                                            )
                                                        "
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        @click="
                                                            openZoom(
                                                                getStorageUrl(
                                                                    item.file_maklumat,
                                                                ),
                                                                item.judul_maklumat,
                                                            )
                                                        "
                                                        class="h-8 text-xs font-semibold rounded-xl bg-white border-slate-200 hover:bg-slate-50 text-slate-700"
                                                    >
                                                        <ZoomIn
                                                            class="w-3.5 h-3.5 mr-1 text-blue-600"
                                                        />
                                                        <span>Zoom Foto</span>
                                                    </Button>

                                                    <!-- Sisipkan ke Editor -->
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

                                                    <!-- Unduh Dokumen -->
                                                    <a
                                                        :href="
                                                            getStorageUrl(
                                                                item.file_maklumat,
                                                            )
                                                        "
                                                        target="_blank"
                                                        download
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
                                                    >
                                                        <ExternalLink
                                                            class="w-3.5 h-3.5"
                                                        />
                                                        <span>Unduh</span>
                                                    </a>

                                                    <!-- Ganti File -->
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

                                                    <!-- Hapus File -->
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="
                                                            item.file_maklumat =
                                                                ''
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
                                                    previewFiles[
                                                        `maklumat-${idx}`
                                                    ]
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
                                                            class="max-h-[500px] w-auto object-contain rounded-lg shadow-xs cursor-zoom-in"
                                                            @click="
                                                                openZoom(
                                                                    getStorageUrl(
                                                                        item.file_maklumat,
                                                                    ),
                                                                    item.judul_maklumat,
                                                                )
                                                            "
                                                        />
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="p-8 text-center text-xs text-slate-500 bg-white h-full flex flex-col items-center justify-center"
                                                    >
                                                        <p>
                                                            Format dokumen ini
                                                            tidak mendukung
                                                            pratinjau langsung
                                                            di dalam browser.
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

                                        <!-- Dropzone Upload if no file -->
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
                                            <div
                                                class="flex items-center gap-2"
                                            >
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
                                                Mendukung banner, tabel,
                                                perataan teks, dan styling
                                                dokumen
                                            </span>
                                        </div>
                                        <RichTextEditor
                                            v-model="item.keterangan_maklumat"
                                            min-height="280px"
                                            placeholder="Tuliskan teks komitmen maklumat pelayanan publik balai seperti di Microsoft Word..."
                                            upload-folder="website/pelayanan/maklumat"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════════════ -->
                        <!-- 2. STANDAR PELAYANAN -->
                        <!-- ═══════════════════════════════════════════════════ -->
                        <div
                            v-if="activeSection === 'standar'"
                            class="space-y-6"
                        >
                            <div
                                v-for="(item, idx) in form.standar_pelayanan"
                                :key="idx"
                                class="rounded-2xl border border-slate-200/80 bg-slate-50/50 overflow-hidden shadow-2xs transition-all"
                            >
                                <!-- Item Header Bar -->
                                <div
                                    class="flex items-center justify-between p-4 bg-slate-100/60 border-b border-slate-200/70"
                                >
                                    <div
                                        class="flex items-center gap-2.5 min-w-0"
                                    >
                                        <span
                                            class="text-xs font-black text-slate-800 uppercase tracking-wider font-mono bg-white px-2 py-0.5 rounded-lg border border-slate-200 shrink-0"
                                        >
                                            #{{ idx + 1 }}
                                        </span>
                                        <span
                                            class="text-xs font-bold text-slate-800 truncate"
                                        >
                                            {{
                                                item.judul_standar ||
                                                "Standar Pelayanan Baru"
                                            }}
                                        </span>
                                        <Badge
                                            v-if="item.file_standar"
                                            class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold border-0 shrink-0 hidden sm:inline-flex"
                                        >
                                            Lampiran Ada
                                        </Badge>
                                        <Badge
                                            v-if="item.keterangan_standar"
                                            class="bg-blue-100 text-blue-700 text-[10px] font-semibold border-0 shrink-0 hidden md:inline-flex"
                                        >
                                            Teks Ada
                                        </Badge>
                                    </div>

                                    <div
                                        class="flex items-center gap-1 shrink-0"
                                    >
                                        <!-- Move Up -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            :disabled="idx === 0"
                                            @click="moveStandar(idx, -1)"
                                            class="h-7 w-7 p-0 text-slate-500 hover:text-slate-800"
                                            title="Geser ke Atas"
                                        >
                                            <ArrowUp class="w-3.5 h-3.5" />
                                        </Button>

                                        <!-- Move Down -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            :disabled="
                                                idx ===
                                                form.standar_pelayanan.length -
                                                    1
                                            "
                                            @click="moveStandar(idx, 1)"
                                            class="h-7 w-7 p-0 text-slate-500 hover:text-slate-800"
                                            title="Geser ke Bawah"
                                        >
                                            <ArrowDown class="w-3.5 h-3.5" />
                                        </Button>

                                        <!-- Duplicate -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="duplicateStandar(idx)"
                                            class="h-7 w-7 p-0 text-slate-500 hover:text-blue-600"
                                            title="Duplikat Standar Ini"
                                        >
                                            <Copy class="w-3.5 h-3.5" />
                                        </Button>

                                        <!-- Collapse Toggle -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="toggleStandarCollapse(idx)"
                                            class="h-7 w-7 p-0 text-slate-500 hover:text-slate-800"
                                            :title="
                                                collapsedStandar[idx]
                                                    ? 'Buka Rincian'
                                                    : 'Tutup Rincian'
                                            "
                                        >
                                            <ChevronDown
                                                v-if="!collapsedStandar[idx]"
                                                class="w-3.5 h-3.5"
                                            />
                                            <ChevronUp
                                                v-else
                                                class="w-3.5 h-3.5"
                                            />
                                        </Button>

                                        <!-- Delete -->
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            @click="removeStandar(idx)"
                                            class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50 ml-1"
                                            title="Hapus Standar Ini"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                    </div>
                                </div>

                                <!-- Item Body -->
                                <div
                                    v-show="!collapsedStandar[idx]"
                                    class="p-5 space-y-4"
                                >
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
                                            placeholder="Contoh: Standar Pelayanan Publik Pelatihan Vokasi"
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
                                                        class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs cursor-pointer"
                                                        @click="
                                                            isImageFile(
                                                                item.file_standar,
                                                            )
                                                                ? openZoom(
                                                                      getStorageUrl(
                                                                          item.file_standar,
                                                                      ),
                                                                      item.judul_standar,
                                                                  )
                                                                : toggleFilePreview(
                                                                      `standar-${idx}`,
                                                                  )
                                                        "
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
                                                                    isPdfFile(
                                                                        item.file_standar,
                                                                    )
                                                                        ? "PDF Document"
                                                                        : isImageFile(
                                                                                item.file_standar,
                                                                            )
                                                                          ? "Foto/Gambar"
                                                                          : "Dokumen Resmi"
                                                                }}
                                                            </Badge>
                                                        </div>
                                                        <p
                                                            class="text-[11px] text-slate-500 font-mono truncate mt-0.5"
                                                            :title="
                                                                item.file_standar
                                                            "
                                                        >
                                                            {{
                                                                item.file_standar
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex flex-wrap items-center gap-2 shrink-0"
                                                >
                                                    <!-- Pratinjau Toggle -->
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
                                                                : isPdfFile(
                                                                        item.file_standar,
                                                                    )
                                                                  ? "Pratinjau PDF"
                                                                  : "Pratinjau File"
                                                        }}
                                                    </Button>

                                                    <!-- Zoom Gambar jika image -->
                                                    <Button
                                                        v-if="
                                                            isImageFile(
                                                                item.file_standar,
                                                            )
                                                        "
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        @click="
                                                            openZoom(
                                                                getStorageUrl(
                                                                    item.file_standar,
                                                                ),
                                                                item.judul_standar,
                                                            )
                                                        "
                                                        class="h-8 text-xs font-semibold rounded-xl bg-white border-slate-200 hover:bg-slate-50 text-slate-700"
                                                    >
                                                        <ZoomIn
                                                            class="w-3.5 h-3.5 mr-1 text-blue-600"
                                                        />
                                                        <span>Zoom Foto</span>
                                                    </Button>

                                                    <!-- Sisipkan ke Editor -->
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

                                                    <!-- Unduh Dokumen -->
                                                    <a
                                                        :href="
                                                            getStorageUrl(
                                                                item.file_standar,
                                                            )
                                                        "
                                                        target="_blank"
                                                        download
                                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-bold transition-all shadow-xs cursor-pointer"
                                                    >
                                                        <ExternalLink
                                                            class="w-3.5 h-3.5"
                                                        />
                                                        <span>Unduh</span>
                                                    </a>

                                                    <!-- Ganti File -->
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

                                                    <!-- Hapus File -->
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="sm"
                                                        @click="
                                                            item.file_standar =
                                                                ''
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
                                                    previewFiles[
                                                        `standar-${idx}`
                                                    ]
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
                                                            class="max-h-[500px] w-auto object-contain rounded-lg shadow-xs cursor-zoom-in"
                                                            @click="
                                                                openZoom(
                                                                    getStorageUrl(
                                                                        item.file_standar,
                                                                    ),
                                                                    item.judul_standar,
                                                                )
                                                            "
                                                        />
                                                    </div>
                                                    <div
                                                        v-else
                                                        class="p-8 text-center text-xs text-slate-500 bg-white h-full flex flex-col items-center justify-center"
                                                    >
                                                        <p>
                                                            Format dokumen ini
                                                            tidak mendukung
                                                            pratinjau langsung
                                                            di dalam browser.
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

                                        <!-- Dropzone Upload if no file -->
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
                                            <div
                                                class="flex items-center gap-2"
                                            >
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
                                                Isi & Teks Standar Pelayanan
                                                (Word Editor)
                                            </label>
                                            <span
                                                class="text-[10px] text-slate-400"
                                            >
                                                Mendukung tabel komparasi,
                                                gambar panduan, dan format resmi
                                            </span>
                                        </div>
                                        <RichTextEditor
                                            v-model="item.keterangan_standar"
                                            min-height="280px"
                                            placeholder="Tuliskan teks standar operasional pelayanan publik seperti di Microsoft Word..."
                                            upload-folder="website/pelayanan/standar"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════════════ -->
                        <!-- 3. ALUR PELAYANAN (Gambar atau Dokumen PDF) -->
                        <!-- ═══════════════════════════════════════════════════ -->
                        <div v-if="activeSection === 'alur'" class="space-y-6">
                            <!-- Bagan Alur Prosedur -->
                            <div class="space-y-2">
                                <div class="flex items-center justify-between">
                                    <label
                                        class="text-xs font-bold text-slate-700 block"
                                    >
                                        Bagan Prosedur Alur Pelayanan (Gambar
                                        atau Dokumen PDF)
                                    </label>
                                    <span class="text-[10px] text-slate-400">
                                        Dapat berupa gambar diagram
                                        (PNG/JPG/WEBP) atau file dokumen (PDF)
                                    </span>
                                </div>

                                <!-- Jika bagan sudah terpasang -->
                                <div
                                    v-if="alurPreview"
                                    class="p-4 rounded-2xl border border-slate-200 bg-slate-50 space-y-4"
                                >
                                    <!-- Jika format PDF -->
                                    <div
                                        v-if="isAlurPdf"
                                        class="p-4 rounded-xl bg-white border border-slate-200/80 shadow-2xs space-y-3"
                                    >
                                        <div
                                            class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3"
                                        >
                                            <div
                                                class="flex items-center gap-3"
                                            >
                                                <div
                                                    class="w-10 h-10 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm shrink-0"
                                                >
                                                    PDF
                                                </div>
                                                <div>
                                                    <span
                                                        class="text-xs font-bold text-slate-800 block"
                                                    >
                                                        Bagan Alur Prosedur
                                                        Pelayanan (PDF)
                                                    </span>
                                                    <span
                                                        class="text-[11px] text-slate-400 font-mono"
                                                    >
                                                        {{
                                                            getFileName(
                                                                alurFile,
                                                            )
                                                        }}
                                                    </span>
                                                </div>
                                            </div>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Button
                                                    type="button"
                                                    variant="outline"
                                                    size="sm"
                                                    @click="
                                                        toggleFilePreview(
                                                            'alur-pdf',
                                                        )
                                                    "
                                                    class="h-8 text-xs font-semibold rounded-xl bg-white border-slate-200 hover:bg-slate-50"
                                                >
                                                    <component
                                                        :is="
                                                            previewFiles[
                                                                'alur-pdf'
                                                            ]
                                                                ? EyeOff
                                                                : Eye
                                                        "
                                                        class="w-3.5 h-3.5 mr-1"
                                                    />
                                                    {{
                                                        previewFiles["alur-pdf"]
                                                            ? "Tutup Preview"
                                                            : "Pratinjau PDF"
                                                    }}
                                                </Button>
                                                <a
                                                    :href="alurPreview"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 shadow-2xs"
                                                >
                                                    <ExternalLink
                                                        class="w-3.5 h-3.5"
                                                    />
                                                    <span>Buka Tab Baru</span>
                                                </a>
                                            </div>
                                        </div>

                                        <!-- Embed Iframe Preview jika ditoggle -->
                                        <div
                                            v-if="previewFiles['alur-pdf']"
                                            class="w-full bg-slate-900/5 rounded-xl border border-slate-200 overflow-hidden min-h-[450px] h-[550px]"
                                        >
                                            <iframe
                                                :src="
                                                    alurPreview + '#toolbar=1'
                                                "
                                                class="w-full h-full border-0 rounded-xl bg-white"
                                                title="Pratinjau Bagan Alur PDF"
                                            ></iframe>
                                        </div>
                                    </div>

                                    <!-- Jika format Gambar -->
                                    <div
                                        v-else
                                        class="relative group rounded-xl border border-slate-200 bg-white p-3 shadow-2xs flex items-center justify-center max-h-[380px] overflow-hidden"
                                    >
                                        <img
                                            :src="alurPreview"
                                            class="max-h-[350px] w-auto object-contain rounded-lg transition-transform group-hover:scale-105 cursor-zoom-in"
                                            alt="Bagan Alur"
                                            @click="
                                                openZoom(
                                                    alurPreview,
                                                    'Bagan Alur Pelayanan',
                                                )
                                            "
                                        />
                                        <div
                                            class="absolute inset-0 bg-slate-950/30 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none"
                                        >
                                            <div
                                                class="px-3 py-1.5 rounded-xl bg-white text-slate-800 text-xs font-bold shadow-md flex items-center gap-1.5"
                                            >
                                                <ZoomIn
                                                    class="w-3.5 h-3.5 text-blue-600"
                                                />
                                                <span
                                                    >Klik untuk
                                                    Memperbesar</span
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Action buttons for Alur File -->
                                    <div
                                        class="flex items-center justify-between pt-1"
                                    >
                                        <span
                                            class="text-[11px] text-slate-400"
                                        >
                                            {{
                                                alurFile
                                                    ? getFileName(alurFile)
                                                    : "Bagan alur terpasang"
                                            }}
                                        </span>
                                        <div class="flex items-center gap-2">
                                            <label
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-200 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer shadow-2xs transition-colors"
                                            >
                                                <Upload
                                                    class="w-3.5 h-3.5 text-blue-600"
                                                />
                                                <span
                                                    >Ganti Bagan (PDF /
                                                    Gambar)</span
                                                >
                                                <input
                                                    type="file"
                                                    accept=".pdf,image/*"
                                                    class="hidden"
                                                    @change="onAlurSelected"
                                                />
                                            </label>
                                            <Button
                                                type="button"
                                                variant="ghost"
                                                size="sm"
                                                @click="clearAlurFile"
                                                class="h-8 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                            >
                                                <Trash2
                                                    class="w-3.5 h-3.5 mr-1"
                                                />
                                                <span>Hapus Bagan</span>
                                            </Button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dropzone Picker if no alur file -->
                                <label
                                    v-else
                                    class="flex flex-col items-center justify-center p-8 border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl cursor-pointer bg-slate-50 hover:bg-blue-50/30 transition-all text-center group"
                                >
                                    <div
                                        class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-2.5 group-hover:scale-110 transition-transform shadow-2xs"
                                    >
                                        <Upload class="w-6 h-6" />
                                    </div>
                                    <span
                                        class="text-xs font-bold text-slate-700"
                                    >
                                        Unggah Dokumen Bagan Prosedur Alur
                                        Pelayanan (PDF / Gambar)
                                    </span>
                                    <span
                                        class="text-[10px] text-slate-400 mt-1"
                                    >
                                        Format: Dokumen PDF, Gambar PNG, JPG,
                                        JPEG, WEBP (Maksimal 20MB)
                                    </span>
                                    <input
                                        type="file"
                                        accept=".pdf,image/*"
                                        class="hidden"
                                        @change="onAlurSelected"
                                    />
                                </label>

                                <p
                                    v-if="form.errors.foto_alur_pelayanan"
                                    class="text-xs text-rose-500 font-medium mt-1"
                                >
                                    {{ form.errors.foto_alur_pelayanan }}
                                </p>
                            </div>

                            <!-- Teks Deskripsi Alur Pelayanan -->
                            <div class="space-y-1.5">
                                <div class="flex items-center justify-between">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                    >
                                        Penjelasan Rinci Tahapan Alur Pelayanan
                                        (Word Editor)
                                    </label>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="insertAlurTemplate"
                                        class="h-7 text-[11px] font-semibold text-blue-600 hover:text-blue-700 hover:bg-blue-50 border-blue-200 gap-1"
                                    >
                                        <Sparkles class="w-3 h-3" />
                                        <span>Sisipkan Contoh Tahapan</span>
                                    </Button>
                                </div>
                                <RichTextEditor
                                    v-model="form.deskripsi_alur_pelayanan"
                                    min-height="240px"
                                    placeholder="Penjelasan tahapan alur pelayanan seperti di Microsoft Word..."
                                    upload-folder="website/pelayanan/alur-deskripsi"
                                />
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════════════ -->
                        <!-- 4. SURVEY KEPUASAN MASYARAKAT -->
                        <!-- ═══════════════════════════════════════════════════ -->
                        <div
                            v-if="activeSection === 'survey_kepuasan'"
                            class="space-y-6"
                        >
                            <div
                                class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 flex items-start gap-3"
                            >
                                <HelpCircle
                                    class="w-5 h-5 text-blue-600 shrink-0 mt-0.5"
                                />
                                <div class="text-xs text-slate-600 space-y-1">
                                    <p class="font-bold text-blue-900">
                                        Panduan Integrasi Survey Kepuasan:
                                    </p>
                                    <p>
                                        Tempelkan tautan publik formulir
                                        kuesioner dari
                                        <strong>Google Forms</strong> atau
                                        <strong>Microsoft Forms</strong>. Anda
                                        juga dapat langsung menempelkan kode
                                        semat HTML (embed iframe), sistem akan
                                        mengekstrak URL secara otomatis.
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700">
                                    URL / Tautan Kuesioner Survey Kepuasan
                                    Masyarakat
                                </label>
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <LinkIcon
                                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                                        />
                                        <Input
                                            :value="
                                                form.survey_kepuasan_masyarakat
                                            "
                                            @input="
                                                onSurveyUrlInput(
                                                    'survey_kepuasan_masyarakat',
                                                    $event,
                                                )
                                            "
                                            placeholder="https://forms.google.com/... atau https://forms.office.com/..."
                                            class="pl-9 pr-8"
                                        />
                                        <button
                                            v-if="
                                                form.survey_kepuasan_masyarakat
                                            "
                                            type="button"
                                            @click="
                                                form.survey_kepuasan_masyarakat =
                                                    ''
                                            "
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                    <a
                                        v-if="form.survey_kepuasan_masyarakat"
                                        :href="form.survey_kepuasan_masyarakat"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all shrink-0"
                                    >
                                        <ExternalLink class="w-3.5 h-3.5" />
                                        <span>Uji Buka</span>
                                    </a>
                                    <Button
                                        v-if="form.survey_kepuasan_masyarakat"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="
                                            toggleSurveyPreview(
                                                'survey_kepuasan',
                                            )
                                        "
                                        class="h-9 px-3 text-xs font-semibold rounded-xl bg-white border-slate-200 shrink-0"
                                    >
                                        <component
                                            :is="
                                                previewSurveys.survey_kepuasan
                                                    ? EyeOff
                                                    : Eye
                                            "
                                            class="w-3.5 h-3.5 mr-1"
                                        />
                                        {{
                                            previewSurveys.survey_kepuasan
                                                ? "Tutup Preview"
                                                : "Pratinjau di Panel"
                                        }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Live Iframe Preview -->
                            <div
                                v-if="
                                    previewSurveys.survey_kepuasan &&
                                    form.survey_kepuasan_masyarakat
                                "
                                class="rounded-2xl border border-slate-200 overflow-hidden bg-slate-50 min-h-[550px] space-y-2 p-2"
                            >
                                <div
                                    class="px-2 py-1 text-xs font-bold text-slate-600 flex items-center justify-between"
                                >
                                    <span>Pratinjau Tampilan Formulir:</span>
                                    <span
                                        class="text-[11px] text-slate-400 font-mono"
                                        >{{
                                            form.survey_kepuasan_masyarakat
                                        }}</span
                                    >
                                </div>
                                <iframe
                                    :src="form.survey_kepuasan_masyarakat"
                                    class="w-full h-[650px] border-0 rounded-xl bg-white"
                                    title="Pratinjau Survey Kepuasan"
                                ></iframe>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════════════ -->
                        <!-- 5. SURVEY KEBUTUHAN PELATIHAN -->
                        <!-- ═══════════════════════════════════════════════════ -->
                        <div
                            v-if="activeSection === 'survey_kebutuhan'"
                            class="space-y-6"
                        >
                            <div
                                class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 flex items-start gap-3"
                            >
                                <HelpCircle
                                    class="w-5 h-5 text-blue-600 shrink-0 mt-0.5"
                                />
                                <div class="text-xs text-slate-600 space-y-1">
                                    <p class="font-bold text-blue-900">
                                        Panduan Integrasi Survey Kebutuhan:
                                    </p>
                                    <p>
                                        Formulir ini digunakan calon peserta
                                        pelatihan dan dunia industri untuk
                                        mengusulkan kejuruan atau materi
                                        pelatihan vokasi baru.
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700">
                                    URL / Tautan Survey Kebutuhan Pelatihan
                                </label>
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <LinkIcon
                                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                                        />
                                        <Input
                                            :value="
                                                form.survey_kebutuhan_pelatihan
                                            "
                                            @input="
                                                onSurveyUrlInput(
                                                    'survey_kebutuhan_pelatihan',
                                                    $event,
                                                )
                                            "
                                            placeholder="https://forms.google.com/... atau https://forms.office.com/..."
                                            class="pl-9 pr-8"
                                        />
                                        <button
                                            v-if="
                                                form.survey_kebutuhan_pelatihan
                                            "
                                            type="button"
                                            @click="
                                                form.survey_kebutuhan_pelatihan =
                                                    ''
                                            "
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                    <a
                                        v-if="form.survey_kebutuhan_pelatihan"
                                        :href="form.survey_kebutuhan_pelatihan"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all shrink-0"
                                    >
                                        <ExternalLink class="w-3.5 h-3.5" />
                                        <span>Uji Buka</span>
                                    </a>
                                    <Button
                                        v-if="form.survey_kebutuhan_pelatihan"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="
                                            toggleSurveyPreview(
                                                'survey_kebutuhan',
                                            )
                                        "
                                        class="h-9 px-3 text-xs font-semibold rounded-xl bg-white border-slate-200 shrink-0"
                                    >
                                        <component
                                            :is="
                                                previewSurveys.survey_kebutuhan
                                                    ? EyeOff
                                                    : Eye
                                            "
                                            class="w-3.5 h-3.5 mr-1"
                                        />
                                        {{
                                            previewSurveys.survey_kebutuhan
                                                ? "Tutup Preview"
                                                : "Pratinjau di Panel"
                                        }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Live Iframe Preview -->
                            <div
                                v-if="
                                    previewSurveys.survey_kebutuhan &&
                                    form.survey_kebutuhan_pelatihan
                                "
                                class="rounded-2xl border border-slate-200 overflow-hidden bg-slate-50 min-h-[550px] space-y-2 p-2"
                            >
                                <div
                                    class="px-2 py-1 text-xs font-bold text-slate-600 flex items-center justify-between"
                                >
                                    <span>Pratinjau Tampilan Formulir:</span>
                                    <span
                                        class="text-[11px] text-slate-400 font-mono"
                                        >{{
                                            form.survey_kebutuhan_pelatihan
                                        }}</span
                                    >
                                </div>
                                <iframe
                                    :src="form.survey_kebutuhan_pelatihan"
                                    class="w-full h-[650px] border-0 rounded-xl bg-white"
                                    title="Pratinjau Survey Kebutuhan"
                                ></iframe>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════════════ -->
                        <!-- 6. SURVEY KEBEKERJAAN ALUMNI -->
                        <!-- ═══════════════════════════════════════════════════ -->
                        <div
                            v-if="activeSection === 'survey_kebekerjaan'"
                            class="space-y-6"
                        >
                            <div
                                class="p-4 rounded-2xl bg-blue-50/60 border border-blue-200/80 flex items-start gap-3"
                            >
                                <HelpCircle
                                    class="w-5 h-5 text-blue-600 shrink-0 mt-0.5"
                                />
                                <div class="text-xs text-slate-600 space-y-1">
                                    <p class="font-bold text-blue-900">
                                        Panduan Integrasi Tracer Study Alumni:
                                    </p>
                                    <p>
                                        Formulir ini digunakan untuk pelacakan
                                        jejak alumni (Tracer Study) terkait
                                        status kebekerjaan, wirausaha, atau
                                        studi lanjutan pasca pelatihan.
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <label class="text-xs font-bold text-slate-700">
                                    URL / Tautan Survey Kebekerjaan Alumni
                                    (Tracer Study)
                                </label>
                                <div class="flex items-center gap-2">
                                    <div class="relative flex-1">
                                        <LinkIcon
                                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                                        />
                                        <Input
                                            :value="form.survey_kebekerjaan"
                                            @input="
                                                onSurveyUrlInput(
                                                    'survey_kebekerjaan',
                                                    $event,
                                                )
                                            "
                                            placeholder="https://forms.google.com/... atau https://forms.office.com/..."
                                            class="pl-9 pr-8"
                                        />
                                        <button
                                            v-if="form.survey_kebekerjaan"
                                            type="button"
                                            @click="
                                                form.survey_kebekerjaan = ''
                                            "
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                                        >
                                            <X class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                    <a
                                        v-if="form.survey_kebekerjaan"
                                        :href="form.survey_kebekerjaan"
                                        target="_blank"
                                        class="inline-flex items-center gap-1.5 px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-bold transition-all shrink-0"
                                    >
                                        <ExternalLink class="w-3.5 h-3.5" />
                                        <span>Uji Buka</span>
                                    </a>
                                    <Button
                                        v-if="form.survey_kebekerjaan"
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        @click="
                                            toggleSurveyPreview(
                                                'survey_kebekerjaan',
                                            )
                                        "
                                        class="h-9 px-3 text-xs font-semibold rounded-xl bg-white border-slate-200 shrink-0"
                                    >
                                        <component
                                            :is="
                                                previewSurveys.survey_kebekerjaan
                                                    ? EyeOff
                                                    : Eye
                                            "
                                            class="w-3.5 h-3.5 mr-1"
                                        />
                                        {{
                                            previewSurveys.survey_kebekerjaan
                                                ? "Tutup Preview"
                                                : "Pratinjau di Panel"
                                        }}
                                    </Button>
                                </div>
                            </div>

                            <!-- Live Iframe Preview -->
                            <div
                                v-if="
                                    previewSurveys.survey_kebekerjaan &&
                                    form.survey_kebekerjaan
                                "
                                class="rounded-2xl border border-slate-200 overflow-hidden bg-slate-50 min-h-[550px] space-y-2 p-2"
                            >
                                <div
                                    class="px-2 py-1 text-xs font-bold text-slate-600 flex items-center justify-between"
                                >
                                    <span>Pratinjau Tampilan Formulir:</span>
                                    <span
                                        class="text-[11px] text-slate-400 font-mono"
                                        >{{ form.survey_kebekerjaan }}</span
                                    >
                                </div>
                                <iframe
                                    :src="form.survey_kebekerjaan"
                                    class="w-full h-[650px] border-0 rounded-xl bg-white"
                                    title="Pratinjau Survey Kebekerjaan"
                                ></iframe>
                            </div>
                        </div>

                        <!-- ═══════════════════════════════════════════════════ -->
                        <!-- 7. INDEKS KEPUASAN MASYARAKAT (IKM) -->
                        <!-- ═══════════════════════════════════════════════════ -->
                        <div
                            v-if="activeSection === 'indeks'"
                            class="space-y-4"
                        >
                            <div
                                class="flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                            >
                                <div>
                                    <label
                                        class="text-xs font-bold text-slate-700 block"
                                    >
                                        Ringkasan & Laporan Hasil Nilai IKM
                                        (Word Editor)
                                    </label>
                                    <span class="text-[10px] text-slate-400">
                                        Cantumkan infografis, rincian 9 unsur
                                        pelayanan, nilai konversi, dan predikat
                                        mutu
                                    </span>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    @click="insertIkmTableTemplate"
                                    class="h-8 text-xs font-semibold text-blue-600 hover:text-blue-700 hover:bg-blue-50 border-blue-200 gap-1.5"
                                >
                                    <Sparkles class="w-3.5 h-3.5" />
                                    <span
                                        >Sisipkan Template Tabel IKM Resmi</span
                                    >
                                </Button>
                            </div>

                            <RichTextEditor
                                v-model="form.indeks_kepuasan_masyarakat"
                                min-height="380px"
                                placeholder="Tuliskan laporan nilai IKM atau infografis seperti di Microsoft Word..."
                                upload-folder="website/pelayanan/ikm"
                            />
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <!-- Global Lightbox Zoom Modal -->
        <Dialog :open="isZoomOpen" @update:open="isZoomOpen = $event">
            <DialogContent
                class="sm:max-w-3xl p-4 overflow-hidden bg-slate-950/95 border-slate-800 text-white"
            >
                <DialogHeader class="mb-2">
                    <DialogTitle
                        class="text-sm font-semibold text-slate-200 truncate"
                    >
                        {{ zoomImageTitle || "Preview Gambar" }}
                    </DialogTitle>
                </DialogHeader>
                <div
                    class="relative w-full max-h-[75vh] flex items-center justify-center bg-black/60 rounded-xl overflow-hidden"
                >
                    <img
                        :src="zoomImageUrl"
                        alt="Zoom Preview"
                        class="max-h-[75vh] w-auto max-w-full object-contain rounded-lg"
                    />
                </div>
                <div
                    class="flex items-center justify-between pt-2 text-xs text-slate-400"
                >
                    <span class="truncate max-w-md">{{ zoomImageTitle }}</span>
                    <a
                        :href="zoomImageUrl"
                        target="_blank"
                        download
                        class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300 font-medium"
                    >
                        <ExternalLink class="w-3.5 h-3.5" />
                        <span>Buka Ukuran Penuh</span>
                    </a>
                </div>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>
