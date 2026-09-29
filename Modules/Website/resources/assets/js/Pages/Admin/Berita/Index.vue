<script setup>
import { ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
import { RichTextEditor } from "@/Components/ui/rich-text-editor";
import {
    Table,
    TableHeader,
    TableBody,
    TableHead,
    TableRow,
    TableCell,
    DataTablePagination,
    DataTableColumnHeader,
} from "@/Components/ui/table";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Tabs, TabsList, TabsTrigger } from "@/Components/ui/tabs";
import {
    Plus,
    Pencil,
    Trash2,
    Newspaper,
    Image as ImageIcon,
    Upload,
    X,
    Search,
    ArrowUpDown,
    Filter,
} from "lucide-vue-next";

const props = defineProps({
    berita: Object,
    galeri: Object,
    filters: Object,
});

const activeTab = ref("berita");
const isDialogOpen = ref(false);
const editItem = ref(null);
const previewImage = ref(null);

// Search & Sort filters
const searchBerita = ref(props.filters?.search_berita || "");
const sortBerita = ref(props.filters?.sort_berita || "latest");
const sortByBerita = ref(props.filters?.sort_by_berita || "");
const sortDirBerita = ref(props.filters?.sort_dir_berita || "asc");

const searchGaleri = ref(props.filters?.search_galeri || "");
const sortGaleri = ref(props.filters?.sort_galeri || "latest");
const sortByGaleri = ref(props.filters?.sort_by_galeri || "");
const sortDirGaleri = ref(props.filters?.sort_dir_galeri || "asc");

const handleBeritaFilter = () => {
    router.get(
        "/admin/berita",
        {
            search_berita: searchBerita.value,
            sort_berita: sortBerita.value,
            sort_by_berita: sortByBerita.value,
            sort_dir_berita: sortDirBerita.value,
            search_galeri: searchGaleri.value,
            sort_galeri: sortGaleri.value,
            sort_by_galeri: sortByGaleri.value,
            sort_dir_galeri: sortDirGaleri.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const onSortBerita = (column, direction) => {
    sortByBerita.value = column;
    sortDirBerita.value = direction;
    handleBeritaFilter();
};

const handleGaleriFilter = () => {
    router.get(
        "/admin/berita",
        {
            search_berita: searchBerita.value,
            sort_berita: sortBerita.value,
            sort_by_berita: sortByBerita.value,
            sort_dir_berita: sortDirBerita.value,
            search_galeri: searchGaleri.value,
            sort_galeri: sortGaleri.value,
            sort_by_galeri: sortByGaleri.value,
            sort_dir_galeri: sortDirGaleri.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const onSortGaleri = (column, direction) => {
    sortByGaleri.value = column;
    sortDirGaleri.value = direction;
    handleGaleriFilter();
};

const form = useForm({
    _method: "POST",
    jenis: "berita",
    judul_berita: "",
    tags: "",
    konten_berita: "",
    file_foto: null,
    keterangan_galeri: "",
});

const openCreate = (jenis) => {
    editItem.value = null;
    previewImage.value = null;
    form.reset();
    form._method = "POST";
    form.jenis = jenis;
    form.file_foto = null;
    isDialogOpen.value = true;
};

const openEdit = (item) => {
    editItem.value = item;
    form.reset();
    form._method = "POST"; // using POST to handle multipart with spoofing
    form.jenis = item.jenis;
    form.judul_berita = item.judul_berita ?? "";
    form.tags = Array.isArray(item.tags)
        ? item.tags.join(", ")
        : (item.tags ?? "");
    form.konten_berita = item.konten_berita ?? "";
    form.keterangan_galeri = item.keterangan_galeri ?? "";
    form.file_foto = null;

    if (item.file_foto) {
        previewImage.value = getImageUrl(item.file_foto);
    } else {
        previewImage.value = null;
    }

    isDialogOpen.value = true;
};

const closeDialog = () => {
    isDialogOpen.value = false;
    editItem.value = null;
    previewImage.value = null;
    form.reset();
};

const onFileSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.file_foto = file;
        previewImage.value = URL.createObjectURL(file);
    }
};

const removeSelectedFile = () => {
    form.file_foto = null;
    previewImage.value = null;
};

const submit = () => {
    if (editItem.value) {
        // Send as POST with _method spoofing for PHP file upload support
        router.post(
            `/admin/berita/${editItem.value.id}`,
            {
                _method: "PUT",
                jenis: form.jenis,
                judul_berita: form.judul_berita,
                tags: form.tags,
                konten_berita: form.konten_berita,
                keterangan_galeri: form.keterangan_galeri,
                file_foto: form.file_foto,
            },
            {
                forceFormData: true,
                onSuccess: () => closeDialog(),
            },
        );
    } else {
        form.post("/admin/berita", {
            forceFormData: true,
            onSuccess: () => closeDialog(),
        });
    }
};

const deleteItem = (item) => {
    if (!confirm(`Hapus "${item.judul_berita || item.keterangan_galeri}"?`))
        return;
    router.delete(`/admin/berita/${item.id}`);
};

const getImageUrl = (val) => {
    if (!val) return "";
    let path = val;
    if (Array.isArray(val)) {
        path = val[0] || "";
    } else if (typeof val === "string" && val.startsWith("[")) {
        try {
            const arr = JSON.parse(val);
            path = arr[0] || "";
        } catch {
            path = val;
        }
    }
    if (!path) return "";
    return path.startsWith("http")
        ? path
        : path.startsWith("/storage")
          ? path
          : `/storage/${path}`;
};
</script>

<template>
    <Head title="Manajemen Berita & Galeri" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <h2
                        class="text-xl font-extrabold tracking-tight text-slate-900"
                    >
                        Berita & Galeri
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola publikasi artikel berita, foto headline, dan
                        dokumentasi galeri balai
                    </p>
                </div>
                <Button
                    @click="openCreate(activeTab)"
                    class="w-fit bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20"
                >
                    <Plus class="w-4 h-4 mr-1.5" />
                    Tambah {{ activeTab === "berita" ? "Berita" : "Galeri" }}
                </Button>
            </div>

            <!-- Tabs Switcher -->
            <Tabs v-model="activeTab" class="w-fit">
                <TabsList class="bg-slate-200/70">
                    <TabsTrigger
                        value="berita"
                        class="gap-2 data-[state=active]:bg-white data-[state=active]:text-blue-600 font-bold"
                    >
                        <Newspaper class="w-3.5 h-3.5" />
                        <span>Katalog Berita</span>
                    </TabsTrigger>
                    <TabsTrigger
                        value="galeri"
                        class="gap-2 data-[state=active]:bg-white data-[state=active]:text-blue-600 font-bold"
                    >
                        <ImageIcon class="w-3.5 h-3.5" />
                        <span>Galeri Foto</span>
                    </TabsTrigger>
                </TabsList>
            </Tabs>

            <!-- Table: Berita -->
            <div v-if="activeTab === 'berita'" class="space-y-3">
                <!-- Filter & Search Toolbar -->
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs"
                >
                    <div class="relative flex-1">
                        <Search
                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                        />
                        <Input
                            v-model="searchBerita"
                            @keyup.enter="handleBeritaFilter"
                            placeholder="Cari judul berita, tags, atau konten artikel..."
                            class="pl-9 pr-8 h-9 text-xs"
                        />
                        <button
                            v-if="searchBerita"
                            @click="
                                searchBerita = '';
                                handleBeritaFilter();
                            "
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="sortBerita"
                            @change="handleBeritaFilter"
                            class="h-9 px-3 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                        >
                            <option value="latest">Terbaru</option>
                            <option value="oldest">Terlama</option>
                            <option value="title_asc">Judul (A-Z)</option>
                            <option value="title_desc">Judul (Z-A)</option>
                        </select>
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="handleBeritaFilter"
                            class="h-9 px-3 text-xs"
                        >
                            Cari
                        </Button>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden w-full"
                >
                    <div class="overflow-x-auto w-full">
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead
                                        class="w-12 text-center text-xs font-semibold"
                                        >No</TableHead
                                    >
                                    <TableHead class="w-20">Cover</TableHead>
                                    <TableHead class="min-w-[240px]">
                                        <DataTableColumnHeader
                                            title="Judul Berita"
                                            column="judul_berita"
                                            :sort-key="sortByBerita"
                                            :sort-direction="sortDirBerita"
                                            @sort="onSortBerita"
                                        />
                                    </TableHead>
                                    <TableHead class="w-44">
                                        <DataTableColumnHeader
                                            title="Tags"
                                            column="tags"
                                            :sort-key="sortByBerita"
                                            :sort-direction="sortDirBerita"
                                            @sort="onSortBerita"
                                        />
                                    </TableHead>
                                    <TableHead class="w-32">
                                        <DataTableColumnHeader
                                            title="Tanggal"
                                            column="created_at"
                                            :sort-key="sortByBerita"
                                            :sort-direction="sortDirBerita"
                                            @sort="onSortBerita"
                                        />
                                    </TableHead>
                                    <TableHead class="text-right w-28"
                                        >Aksi</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="!berita.data?.length">
                                    <TableCell
                                        colspan="6"
                                        class="h-24 text-center text-slate-400"
                                    >
                                        Tidak ada data berita ditemukan.
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="(item, index) in berita.data"
                                    :key="item.id"
                                >
                                    <TableCell
                                        class="text-center font-mono text-[11px] text-slate-400 font-semibold"
                                    >
                                        {{
                                            (berita.current_page - 1) *
                                                berita.per_page +
                                            index +
                                            1
                                        }}
                                    </TableCell>
                                    <TableCell>
                                        <img
                                            v-if="item.file_foto"
                                            :src="getImageUrl(item.file_foto)"
                                            class="h-10 w-16 object-cover rounded-lg border border-slate-200 shadow-2xs"
                                            alt="Foto"
                                        />
                                        <div
                                            v-else
                                            class="h-10 w-16 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 text-xs"
                                        >
                                            <ImageIcon class="w-4 h-4" />
                                        </div>
                                    </TableCell>
                                    <TableCell
                                        class="font-bold text-slate-900 max-w-sm truncate"
                                    >
                                        {{ item.judul_berita }}
                                    </TableCell>
                                    <TableCell>
                                        <Badge
                                            variant="secondary"
                                            class="text-[10px] font-semibold text-blue-700 bg-blue-50 border-blue-200"
                                        >
                                            {{
                                                Array.isArray(item.tags)
                                                    ? item.tags.join(", ")
                                                    : item.tags || "Berita"
                                            }}
                                        </Badge>
                                    </TableCell>
                                    <TableCell
                                        class="text-slate-500 font-mono text-[11px]"
                                    >
                                        {{
                                            new Date(
                                                item.created_at,
                                            ).toLocaleDateString("id-ID", {
                                                day: "numeric",
                                                month: "short",
                                                year: "numeric",
                                            })
                                        }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div
                                            class="flex items-center justify-end gap-1.5"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openEdit(item)"
                                                class="h-7 px-2 text-slate-600 hover:text-blue-600 hover:bg-blue-50"
                                            >
                                                <Pencil
                                                    class="w-3.5 h-3.5 mr-1"
                                                />
                                                Edit
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="deleteItem(item)"
                                                class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                            >
                                                <Trash2
                                                    class="w-3.5 h-3.5 mr-1"
                                                />
                                                Hapus
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <DataTablePagination :pagination="berita" />
                </div>
            </div>

            <!-- Table: Galeri -->
            <div v-if="activeTab === 'galeri'" class="space-y-3">
                <!-- Filter & Search Toolbar -->
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs"
                >
                    <div class="relative flex-1">
                        <Search
                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                        />
                        <Input
                            v-model="searchGaleri"
                            @keyup.enter="handleGaleriFilter"
                            placeholder="Cari keterangan dokumentasi kegiatan..."
                            class="pl-9 pr-8 h-9 text-xs"
                        />
                        <button
                            v-if="searchGaleri"
                            @click="
                                searchGaleri = '';
                                handleGaleriFilter();
                            "
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <div class="flex items-center gap-2">
                        <select
                            v-model="sortGaleri"
                            @change="handleGaleriFilter"
                            class="h-9 px-3 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                        >
                            <option value="latest">Terbaru</option>
                            <option value="oldest">Terlama</option>
                        </select>
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="handleGaleriFilter"
                            class="h-9 px-3 text-xs"
                        >
                            Cari
                        </Button>
                    </div>
                </div>

                <div
                    class="rounded-2xl border border-slate-200 bg-white shadow-xs overflow-hidden w-full"
                >
                    <div class="overflow-x-auto w-full">
                        <Table class="w-full">
                            <TableHeader>
                                <TableRow>
                                    <TableHead
                                        class="w-12 text-center text-xs font-semibold"
                                        >No</TableHead
                                    >
                                    <TableHead class="w-24">Foto</TableHead>
                                    <TableHead class="min-w-[240px]">
                                        <DataTableColumnHeader
                                            title="Keterangan Foto"
                                            column="keterangan_galeri"
                                            :sort-key="sortByGaleri"
                                            :sort-direction="sortDirGaleri"
                                            @sort="onSortGaleri"
                                        />
                                    </TableHead>
                                    <TableHead class="w-36">
                                        <DataTableColumnHeader
                                            title="Tanggal"
                                            column="created_at"
                                            :sort-key="sortByGaleri"
                                            :sort-direction="sortDirGaleri"
                                            @sort="onSortGaleri"
                                        />
                                    </TableHead>
                                    <TableHead class="text-right w-28"
                                        >Aksi</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="!galeri.data?.length">
                                    <TableCell
                                        colspan="5"
                                        class="h-24 text-center text-slate-400"
                                    >
                                        Tidak ada foto galeri ditemukan.
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="(item, index) in galeri.data"
                                    :key="item.id"
                                >
                                    <TableCell
                                        class="text-center font-mono text-[11px] text-slate-400 font-semibold"
                                    >
                                        {{
                                            (galeri.current_page - 1) *
                                                galeri.per_page +
                                            index +
                                            1
                                        }}
                                    </TableCell>
                                    <TableCell>
                                        <img
                                            v-if="item.file_foto"
                                            :src="getImageUrl(item.file_foto)"
                                            class="h-12 w-20 object-cover rounded-xl border border-slate-200 shadow-2xs"
                                            alt="Foto"
                                        />
                                        <div
                                            v-else
                                            class="h-12 w-20 rounded-xl bg-slate-100 flex items-center justify-center text-slate-400"
                                        >
                                            <ImageIcon class="w-5 h-5" />
                                        </div>
                                    </TableCell>
                                    <TableCell class="font-bold text-slate-900">
                                        {{ item.keterangan_galeri }}
                                    </TableCell>
                                    <TableCell
                                        class="text-slate-500 font-mono text-[11px]"
                                    >
                                        {{
                                            new Date(
                                                item.created_at,
                                            ).toLocaleDateString("id-ID", {
                                                day: "numeric",
                                                month: "short",
                                                year: "numeric",
                                            })
                                        }}
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <div
                                            class="flex items-center justify-end gap-1.5"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openEdit(item)"
                                                class="h-7 px-2 text-slate-600 hover:text-blue-600 hover:bg-blue-50"
                                            >
                                                <Pencil
                                                    class="w-3.5 h-3.5 mr-1"
                                                />
                                                Edit
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="deleteItem(item)"
                                                class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                            >
                                                <Trash2
                                                    class="w-3.5 h-3.5 mr-1"
                                                />
                                                Hapus
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <DataTablePagination :pagination="galeri" />
                </div>
            </div>
        </div>

        <!-- SHADCN DIALOG / MODAL DENGAN UPLOAD FOTO & WORD-STYLE EDITOR -->
        <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
            <DialogContent class="sm:max-w-4xl max-h-[92vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>
                        {{ editItem ? "Edit" : "Tambah" }}
                        {{ form.jenis === "berita" ? "Berita" : "Galeri" }}
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-4 pt-2">
                    <div v-if="form.jenis === 'berita'" class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700"
                            >Judul Berita</label
                        >
                        <Input
                            v-model="form.judul_berita"
                            placeholder="Masukkan judul artikel..."
                        />
                    </div>

                    <div v-if="form.jenis === 'berita'" class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700"
                            >Tags (pisahkan koma)</label
                        >
                        <Input
                            v-model="form.tags"
                            placeholder="pelatihan, kejuruan, balai"
                        />
                    </div>

                    <div v-if="form.jenis === 'berita'" class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700"
                            >Konten Berita (Word-like Editor)</label
                        >
                        <RichTextEditor
                            v-model="form.konten_berita"
                            placeholder="Tuliskan isi artikel lengkap di sini seperti di Microsoft Word..."
                            upload-folder="website/berita/konten"
                        />
                    </div>

                    <div v-if="form.jenis === 'galeri'" class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700"
                            >Keterangan Galeri</label
                        >
                        <Input
                            v-model="form.keterangan_galeri"
                            placeholder="Keterangan dokumentasi kegiatan..."
                        />
                    </div>

                    <!-- FOTO UPLOAD ZONE -->
                    <div class="space-y-2">
                        <label class="text-xs font-bold text-slate-700 block">
                            Foto / Gambar
                            {{
                                form.jenis === "berita"
                                    ? "Cover Artikel"
                                    : "Dokumentasi"
                            }}
                        </label>

                        <!-- Preview Box -->
                        <div
                            v-if="previewImage"
                            class="relative rounded-2xl overflow-hidden border border-slate-200 bg-slate-50 w-full aspect-video flex items-center justify-center group"
                        >
                            <img
                                :src="previewImage"
                                class="w-full h-full object-cover"
                                alt="Preview"
                            />
                            <div
                                class="absolute inset-0 bg-slate-950/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2"
                            >
                                <label
                                    class="px-3 py-1.5 rounded-xl bg-white text-slate-800 text-xs font-bold cursor-pointer hover:bg-slate-100 shadow-md"
                                >
                                    Ganti Foto
                                    <input
                                        type="file"
                                        accept="image/*"
                                        class="hidden"
                                        @change="onFileSelected"
                                    />
                                </label>
                                <button
                                    type="button"
                                    @click="removeSelectedFile"
                                    class="p-1.5 rounded-xl bg-rose-600 text-white hover:bg-rose-700 shadow-md cursor-pointer"
                                >
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        <!-- Dropzone Picker if no image -->
                        <div
                            v-else
                            class="rounded-2xl border-2 border-dashed border-slate-300 hover:border-blue-500 bg-slate-50/50 hover:bg-blue-50/30 p-6 text-center transition-colors cursor-pointer relative"
                        >
                            <input
                                type="file"
                                accept="image/*"
                                class="absolute inset-0 opacity-0 cursor-pointer w-full h-full"
                                @change="onFileSelected"
                            />
                            <div
                                class="flex flex-col items-center justify-center space-y-2 pointer-events-none"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-100/70 text-blue-600 flex items-center justify-center"
                                >
                                    <Upload class="w-5 h-5" />
                                </div>
                                <div class="text-xs font-bold text-slate-700">
                                    Pilih foto dari komputer atau seret ke sini
                                </div>
                                <div class="text-[10px] text-slate-400">
                                    PNG, JPG, JPEG, WEBP (Maksimal 5MB)
                                </div>
                            </div>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeDialog"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            {{
                                form.processing ? "Menyimpan..." : "Simpan Data"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>
