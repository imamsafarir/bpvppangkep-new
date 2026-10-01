<script setup>
import { ref, watch } from "vue";
import { Head, useForm } from "@inertiajs/vue3";
import axios from "axios";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import {
    Save,
    CheckCircle2,
    Globe,
    Phone,
    Share2,
    Megaphone,
    Upload,
    Images,
    Plus,
    Trash2,
} from "lucide-vue-next";

const props = defineProps({
    settings: Object,
});

const getStorageUrl = (path) => {
    if (!path) return null;
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    const clean = path.replace(/^\/?storage\//, "").replace(/^\//, "");
    return `/storage/${clean}`;
};

const logoPreview = ref(getStorageUrl(props.settings?.logo_path));
const faviconPreview = ref(
    props.settings?.favicon_path
        ? `${getStorageUrl(props.settings.favicon_path)}?v=${Date.now()}`
        : null,
);
const popupPreview = ref(getStorageUrl(props.settings?.popup_image_path));

const parseSliders = (val) => {
    if (!val) return [];
    let raw = val;
    if (typeof raw === "string") {
        try {
            raw = JSON.parse(raw);
        } catch {
            return [];
        }
        if (typeof raw === "string") {
            try {
                raw = JSON.parse(raw);
            } catch {}
        }
    }
    if (!Array.isArray(raw)) return [];
    return raw.map((item, idx) => {
        if (typeof item === "string") {
            return {
                title: `Slide Banner #${idx + 1}`,
                subtitle: "",
                image_url: item,
                cta_text: "Daftar Pelatihan",
                cta_link: "/informasi/kejuruan",
            };
        }
        return {
            title: item?.title ?? `Slide Banner #${idx + 1}`,
            subtitle: item?.subtitle ?? "",
            image_url: item?.image_url ?? item?.image ?? "",
            cta_text: item?.cta_text ?? "Daftar Pelatihan",
            cta_link: item?.cta_link ?? "/informasi/kejuruan",
        };
    });
};

const form = useForm({
    website_name: props.settings?.website_name ?? "",
    logo_path: null,
    favicon_path: null,
    email: props.settings?.email ?? "",
    whatsapp_number: props.settings?.whatsapp_number ?? "",
    phone_number: props.settings?.phone_number ?? "",
    address: props.settings?.address ?? "",
    google_maps_embed: props.settings?.google_maps_embed ?? "",
    facebook_url: props.settings?.facebook_url ?? "",
    instagram_url: props.settings?.instagram_url ?? "",
    youtube_url: props.settings?.youtube_url ?? "",
    tiktok_url: props.settings?.tiktok_url ?? "",
    is_popup_active: Boolean(props.settings?.is_popup_active),
    popup_image_path: null,
    popup_redirect_url: props.settings?.popup_redirect_url ?? "",
    is_running_text_active: Boolean(props.settings?.is_running_text_active),
    running_text_content: props.settings?.running_text_content ?? "",
    sliders: parseSliders(props.settings?.sliders),
});

watch(
    () => props.settings,
    (newSettings) => {
        if (newSettings) {
            if (newSettings.logo_path) {
                logoPreview.value = getStorageUrl(newSettings.logo_path);
            }
            if (newSettings.favicon_path) {
                faviconPreview.value = `${getStorageUrl(newSettings.favicon_path)}?v=${Date.now()}`;
            }
            if (newSettings.popup_image_path) {
                popupPreview.value = getStorageUrl(
                    newSettings.popup_image_path,
                );
            }
            if (newSettings.sliders) {
                form.sliders = parseSliders(newSettings.sliders);
            }
        }
    },
    { deep: true },
);

const onLogoSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.logo_path = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const onFaviconSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.favicon_path = file;
        faviconPreview.value = URL.createObjectURL(file);
    }
};

const onPopupSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.popup_image_path = file;
        popupPreview.value = URL.createObjectURL(file);
    }
};

const addSlider = () => {
    form.sliders.push({
        title: `Slide Banner #${form.sliders.length + 1}`,
        subtitle: "",
        image_url: "",
        cta_text: "Daftar Pelatihan",
        cta_link: "/informasi/kejuruan",
    });
};

const removeSlider = (index) => {
    form.sliders.splice(index, 1);
};

const uploadingSliderIdx = ref(null);

const triggerSliderUpload = (sIdx) => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        uploadingSliderIdx.value = sIdx;
        try {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("folder", "website/settings/sliders");
            formData.append(
                "name",
                form.sliders[sIdx]?.title || `slider-${sIdx + 1}`,
            );
            const res = await axios.post("/admin/upload-media", formData, {
                headers: { "Content-Type": "multipart/form-data" },
            });
            if (res.data?.path || res.data?.url) {
                form.sliders[sIdx].image_url = res.data.path || res.data.url;
            }
        } catch (err) {
            console.error("Upload failed", err);
            alert(
                "Gagal mengunggah banner. Pastikan ukuran file tidak melebihi 10MB.",
            );
        } finally {
            uploadingSliderIdx.value = null;
        }
    };
    input.click();
};

const activeSection = ref("umum");
const sections = [
    { id: "umum", label: "Identitas Website", icon: Globe },
    { id: "sliders", label: "Banner Hero Slider", icon: Images },
    { id: "kontak", label: "Kontak & Alamat", icon: Phone },
    { id: "sosmed", label: "Media Sosial", icon: Share2 },
    { id: "popup", label: "Popup & Pengumuman", icon: Megaphone },
];

const submit = () => {
    form.transform((data) => ({
        ...data,
        _method: "PUT",
    })).post("/admin/settings", {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: (page) => {
            const updated = page.props?.settings || props.settings;
            if (updated?.logo_path) {
                logoPreview.value = getStorageUrl(updated.logo_path);
            }
            if (updated?.favicon_path) {
                faviconPreview.value = `${getStorageUrl(updated.favicon_path)}?v=${Date.now()}`;
            }
            if (updated?.popup_image_path) {
                popupPreview.value = getStorageUrl(updated.popup_image_path);
            }
            if (updated?.sliders) {
                form.sliders = parseSliders(updated.sliders);
            }
            form.logo_path = null;
            form.favicon_path = null;
            form.popup_image_path = null;
        },
    });
};
</script>

<template>
    <Head title="Konfigurasi Website" />

    <DashboardLayout>
        <div class="space-y-6">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <h2
                        class="text-xl font-extrabold tracking-tight text-slate-900"
                    >
                        Konfigurasi Website
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Pengaturan identitas, logo, slider banner beranda,
                        kontak resmi, dan pengumuman popup
                    </p>
                </div>
                <Button
                    @click="submit"
                    :loading="form.processing"
                    class="w-fit bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20"
                >
                    <Save v-if="!form.processing" class="w-4 h-4 mr-1.5" />
                    {{ form.processing ? "Menyimpan..." : "Simpan Pengaturan" }}
                </Button>
            </div>

            <div
                v-if="form.wasSuccessful || $page.props.flash?.success"
                class="flex items-center gap-2 px-4 py-3 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs font-semibold"
            >
                <CheckCircle2 class="w-4 h-4 text-emerald-600 shrink-0" />
                <span>{{
                    $page.props.flash?.success ||
                    "Pengaturan website berhasil disimpan ke database."
                }}</span>
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

                <!-- Form Card -->
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
                        <!-- Identitas -->
                        <template v-if="activeSection === 'umum'">
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Nama Website Resmi</label
                                >
                                <Input
                                    v-model="form.website_name"
                                    placeholder="BPVP Pangkajene dan Kepulauan"
                                />
                            </div>

                            <div
                                class="grid grid-cols-1 sm:grid-cols-2 gap-6 pt-2"
                            >
                                <!-- Upload Logo -->
                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-bold text-slate-700 block"
                                        >Logo Website</label
                                    >
                                    <div
                                        v-if="logoPreview"
                                        class="p-4 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-4"
                                    >
                                        <img
                                            :src="logoPreview"
                                            class="h-12 w-auto object-contain bg-white p-2 rounded-xl border border-slate-200"
                                            alt="Logo"
                                        />
                                        <label
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer shadow-2xs"
                                        >
                                            <Upload class="w-3.5 h-3.5" />
                                            <span>Ganti Logo</span>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                @change="onLogoSelected"
                                            />
                                        </label>
                                    </div>
                                    <label
                                        v-else
                                        class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl cursor-pointer bg-slate-50 hover:bg-blue-50/30 transition-colors"
                                    >
                                        <Upload
                                            class="w-6 h-6 text-blue-600 mb-1"
                                        />
                                        <span
                                            class="text-xs font-bold text-slate-700"
                                            >Unggah Logo</span
                                        >
                                        <span class="text-[10px] text-slate-400"
                                            >PNG transparan disarankan</span
                                        >
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="onLogoSelected"
                                        />
                                    </label>
                                </div>

                                <!-- Upload Favicon -->
                                <div class="space-y-2">
                                    <label
                                        class="text-xs font-bold text-slate-700 block"
                                        >Favicon Browser</label
                                    >
                                    <div
                                        v-if="faviconPreview"
                                        class="p-4 rounded-2xl border border-slate-200 bg-slate-50 flex items-center gap-4"
                                    >
                                        <img
                                            :src="faviconPreview"
                                            class="h-10 w-10 object-contain bg-white p-1 rounded-xl border border-slate-200"
                                            alt="Favicon"
                                        />
                                        <label
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 bg-white text-xs font-bold text-slate-700 hover:bg-slate-50 cursor-pointer shadow-2xs"
                                        >
                                            <Upload class="w-3.5 h-3.5" />
                                            <span>Ganti Favicon</span>
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                @change="onFaviconSelected"
                                            />
                                        </label>
                                    </div>
                                    <label
                                        v-else
                                        class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl cursor-pointer bg-slate-50 hover:bg-blue-50/30 transition-colors"
                                    >
                                        <Upload
                                            class="w-6 h-6 text-blue-600 mb-1"
                                        />
                                        <span
                                            class="text-xs font-bold text-slate-700"
                                            >Unggah Favicon</span
                                        >
                                        <span class="text-[10px] text-slate-400"
                                            >PNG atau ICO ukuran 32x32</span
                                        >
                                        <input
                                            type="file"
                                            accept="image/*"
                                            class="hidden"
                                            @change="onFaviconSelected"
                                        />
                                    </label>
                                </div>
                            </div>
                        </template>

                        <!-- Sliders Hero Banner -->
                        <template v-if="activeSection === 'sliders'">
                            <div class="flex items-center justify-between pb-2">
                                <p class="text-xs text-slate-500">
                                    Banner gambar carousel yang tampil di
                                    halaman beranda utama
                                </p>
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="addSlider"
                                    class="h-8"
                                >
                                    <Plus class="w-3.5 h-3.5 mr-1" /> Tambah
                                    Slide Banner
                                </Button>
                            </div>

                            <div
                                v-if="form.sliders.length === 0"
                                class="rounded-xl border border-dashed border-slate-200 p-8 text-center text-slate-400 text-xs"
                            >
                                Belum ada banner slider. Klik "Tambah Slide
                                Banner".
                            </div>

                            <div
                                v-for="(slide, sIdx) in form.sliders"
                                :key="sIdx"
                                class="p-4 rounded-2xl border border-slate-200 bg-slate-50/60 space-y-3"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-bold text-slate-700"
                                        >Slide Banner #{{ sIdx + 1 }}</span
                                    >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="removeSlider(sIdx)"
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
                                            >Judul Headline</label
                                        >
                                        <Input
                                            v-model="slide.title"
                                            class="bg-white"
                                            placeholder="Pelatihan Vokasi Siap Kerja..."
                                        />
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-bold text-slate-600"
                                            >Sub-judul / Deskripsi</label
                                        >
                                        <Input
                                            v-model="slide.subtitle"
                                            class="bg-white"
                                            placeholder="Tingkatkan kompetensi Anda bersama kami..."
                                        />
                                    </div>
                                    <div class="space-y-1.5 sm:col-span-2">
                                        <label
                                            class="text-[10px] font-bold text-slate-600"
                                            >Foto / Banner Slide</label
                                        >
                                        <div
                                            class="flex flex-col sm:flex-row items-start sm:items-center gap-3"
                                        >
                                            <div
                                                v-if="slide.image_url"
                                                class="relative w-28 h-16 rounded-xl border border-slate-200 bg-slate-900/5 overflow-hidden shrink-0 shadow-2xs group/img"
                                            >
                                                <img
                                                    :src="
                                                        getStorageUrl(
                                                            slide.image_url,
                                                        )
                                                    "
                                                    class="w-full h-full object-cover"
                                                    alt="Banner Preview"
                                                />
                                                <button
                                                    type="button"
                                                    @click="
                                                        slide.image_url = ''
                                                    "
                                                    class="absolute inset-0 bg-slate-900/60 text-white opacity-0 group-hover/img:opacity-100 flex items-center justify-center transition-opacity cursor-pointer"
                                                    title="Hapus banner"
                                                >
                                                    <Trash2
                                                        class="w-4 h-4 text-rose-300"
                                                    />
                                                </button>
                                            </div>
                                            <div
                                                v-else
                                                class="w-28 h-16 rounded-xl border-2 border-dashed border-slate-200 bg-slate-100/50 flex items-center justify-center shrink-0 text-slate-300"
                                            >
                                                <Images class="w-6 h-6" />
                                            </div>
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
                                                            uploadingSliderIdx ===
                                                            sIdx
                                                        "
                                                        @click="
                                                            triggerSliderUpload(
                                                                sIdx,
                                                            )
                                                        "
                                                    >
                                                        <Upload
                                                            class="w-3.5 h-3.5 mr-1 text-blue-600"
                                                        />
                                                        {{
                                                            uploadingSliderIdx ===
                                                            sIdx
                                                                ? "Mengunggah..."
                                                                : "Upload Banner dari Komputer"
                                                        }}
                                                    </Button>
                                                    <span
                                                        v-if="slide.image_url"
                                                        class="text-[10px] text-emerald-600 font-medium"
                                                        >✓ Terpasang</span
                                                    >
                                                </div>
                                                <Input
                                                    v-model="slide.image_url"
                                                    class="bg-white h-8 text-xs"
                                                    placeholder="Atau masukkan URL /storage/... atau https://..."
                                                />
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="text-[10px] font-bold text-slate-600"
                                            >Link Tombol (URL)</label
                                        >
                                        <Input
                                            v-model="slide.cta_link"
                                            class="bg-white"
                                            placeholder="/informasi/kejuruan"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Kontak -->
                        <template v-if="activeSection === 'kontak'">
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >Email Resmi</label
                                    >
                                    <Input
                                        v-model="form.email"
                                        type="email"
                                        placeholder="humas@bpvppangkep.id"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >No. WhatsApp Layanan</label
                                    >
                                    <Input
                                        v-model="form.whatsapp_number"
                                        placeholder="628123456789"
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >No. Telepon Kantor</label
                                    >
                                    <Input
                                        v-model="form.phone_number"
                                        placeholder="(0410) 12345"
                                    />
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Alamat Lengkap Balai</label
                                >
                                <Textarea
                                    v-model="form.address"
                                    rows="3"
                                    placeholder="Jl. Poros Makassar - Parepare KM 75..."
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-slate-700"
                                    >Embed Google Maps (URL / iframe src)</label
                                >
                                <Input
                                    v-model="form.google_maps_embed"
                                    placeholder="https://maps.google.com/..."
                                />
                            </div>
                        </template>

                        <!-- Media Sosial -->
                        <template v-if="activeSection === 'sosmed'">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >URL Facebook Resmi</label
                                    >
                                    <Input
                                        v-model="form.facebook_url"
                                        placeholder="https://facebook.com/..."
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >URL Instagram Resmi</label
                                    >
                                    <Input
                                        v-model="form.instagram_url"
                                        placeholder="https://instagram.com/..."
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >URL Channel YouTube</label
                                    >
                                    <Input
                                        v-model="form.youtube_url"
                                        placeholder="https://youtube.com/..."
                                    />
                                </div>
                                <div class="space-y-1.5">
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >URL Akun TikTok</label
                                    >
                                    <Input
                                        v-model="form.tiktok_url"
                                        placeholder="https://tiktok.com/@..."
                                    />
                                </div>
                            </div>
                        </template>

                        <!-- Popup & Running Text -->
                        <template v-if="activeSection === 'popup'">
                            <!-- Popup Modal Banner -->
                            <div
                                class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 space-y-4"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="popup_active"
                                        v-model="form.is_popup_active"
                                        class="h-4 w-4 rounded-sm border-slate-300 text-blue-600 focus:ring-blue-600"
                                    />
                                    <label
                                        for="popup_active"
                                        class="text-xs font-bold text-slate-800 cursor-pointer select-none"
                                    >
                                        Aktifkan Banner Modal Pop-up di Halaman
                                        Beranda
                                    </label>
                                </div>

                                <div
                                    v-if="form.is_popup_active"
                                    class="space-y-4 pt-2"
                                >
                                    <div class="space-y-2">
                                        <label
                                            class="text-xs font-bold text-slate-700 block"
                                            >Gambar Pop-up</label
                                        >
                                        <div
                                            v-if="popupPreview"
                                            class="p-4 rounded-xl border border-slate-200 bg-white flex items-center gap-4"
                                        >
                                            <img
                                                :src="popupPreview"
                                                class="h-20 w-auto object-cover rounded-lg border border-slate-200"
                                                alt="Popup"
                                            />
                                            <label
                                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-slate-300 bg-slate-50 text-xs font-bold text-slate-700 hover:bg-slate-100 cursor-pointer shadow-2xs"
                                            >
                                                <Upload class="w-3.5 h-3.5" />
                                                <span>Ganti Gambar</span>
                                                <input
                                                    type="file"
                                                    accept="image/*"
                                                    class="hidden"
                                                    @change="onPopupSelected"
                                                />
                                            </label>
                                        </div>
                                        <label
                                            v-else
                                            class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-300 hover:border-blue-500 rounded-2xl cursor-pointer bg-white transition-colors"
                                        >
                                            <Upload
                                                class="w-6 h-6 text-blue-600 mb-1"
                                            />
                                            <span
                                                class="text-xs font-bold text-slate-700"
                                                >Unggah Banner Pop-up</span
                                            >
                                            <input
                                                type="file"
                                                accept="image/*"
                                                class="hidden"
                                                @change="onPopupSelected"
                                            />
                                        </label>
                                    </div>

                                    <div class="space-y-1.5">
                                        <label
                                            class="text-xs font-bold text-slate-700"
                                            >URL Pengalihan Saat Banner
                                            Diklik</label
                                        >
                                        <Input
                                            v-model="form.popup_redirect_url"
                                            placeholder="https://..."
                                        />
                                    </div>
                                </div>
                            </div>

                            <!-- Running Text Banner -->
                            <div
                                class="rounded-2xl border border-slate-200 bg-slate-50/80 p-5 space-y-4"
                            >
                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="running_active"
                                        v-model="form.is_running_text_active"
                                        class="h-4 w-4 rounded-sm border-slate-300 text-blue-600 focus:ring-blue-600"
                                    />
                                    <label
                                        for="running_active"
                                        class="text-xs font-bold text-slate-800 cursor-pointer select-none"
                                    >
                                        Aktifkan Banner Running Text di Atas
                                        Navbar
                                    </label>
                                </div>

                                <div
                                    v-if="form.is_running_text_active"
                                    class="space-y-1.5 pt-2"
                                >
                                    <label
                                        class="text-xs font-bold text-slate-700"
                                        >Teks Pesan Berjalan</label
                                    >
                                    <Textarea
                                        v-model="form.running_text_content"
                                        rows="3"
                                        placeholder="Pemberitahuan pendaftaran pelatihan dibuka..."
                                    />
                                </div>
                            </div>
                        </template>
                    </CardContent>
                </Card>
            </div>
        </div>
    </DashboardLayout>
</template>
