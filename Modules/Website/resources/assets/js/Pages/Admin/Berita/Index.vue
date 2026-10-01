<script setup>
import { ref, computed } from "vue";
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
    RotateCcw,
    ExternalLink,
    CheckSquare,
    Eye,
    Tag,
    ZoomIn,
    ChevronDown,
    ChevronUp,
    Loader2,
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

// Loading states
const isSubmitting = ref(false);
const isBulkDeletingBerita = ref(false);
const isBulkDeletingGaleri = ref(false);
const deletingId = ref(null);

// Search, Sort & Per-Page filters
const searchBerita = ref(props.filters?.search_berita || "");
const sortBerita = ref(props.filters?.sort_berita || "latest");
const sortByBerita = ref(props.filters?.sort_by_berita || "");
const sortDirBerita = ref(props.filters?.sort_dir_berita || "asc");
const perPageBerita = ref(props.filters?.per_page_berita || 10);

const searchGaleri = ref(props.filters?.search_galeri || "");
const sortGaleri = ref(props.filters?.sort_galeri || "latest");
const sortByGaleri = ref(props.filters?.sort_by_galeri || "");
const sortDirGaleri = ref(props.filters?.sort_dir_galeri || "asc");
const perPageGaleri = ref(props.filters?.per_page_galeri || 12);

const handleBeritaFilter = () => {
    router.get(
        "/admin/berita",
        {
            search_berita: searchBerita.value,
            sort_berita: sortBerita.value,
            sort_by_berita: sortByBerita.value,
            sort_dir_berita: sortDirBerita.value,
            per_page_berita: perPageBerita.value,
            search_galeri: searchGaleri.value,
            sort_galeri: sortGaleri.value,
            sort_by_galeri: sortByGaleri.value,
            sort_dir_galeri: sortDirGaleri.value,
            per_page_galeri: perPageGaleri.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetBeritaFilter = () => {
    searchBerita.value = "";
    sortBerita.value = "latest";
    sortByBerita.value = "";
    sortDirBerita.value = "asc";
    perPageBerita.value = 10;
    handleBeritaFilter();
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
            per_page_berita: perPageBerita.value,
            search_galeri: searchGaleri.value,
            sort_galeri: sortGaleri.value,
            sort_by_galeri: sortByGaleri.value,
            sort_dir_galeri: sortDirGaleri.value,
            per_page_galeri: perPageGaleri.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetGaleriFilter = () => {
    searchGaleri.value = "";
    sortGaleri.value = "latest";
    sortByGaleri.value = "";
    sortDirGaleri.value = "asc";
    perPageGaleri.value = 12;
    handleGaleriFilter();
};

const onSortGaleri = (column, direction) => {
    sortByGaleri.value = column;
    sortDirGaleri.value = direction;
    handleGaleriFilter();
};

// --- LOGIKA PARSING DAN TOGGLE TAGS DINAMIS ---
const parseTags = (tags) => {
    if (!tags) return [];
    if (Array.isArray(tags)) {
        return tags.map((t) => String(t).trim()).filter(Boolean);
    }
    if (typeof tags === "string") {
        if (tags.startsWith("[") && tags.endsWith("]")) {
            try {
                const parsed = JSON.parse(tags);
                if (Array.isArray(parsed)) {
                    return parsed.map((t) => String(t).trim()).filter(Boolean);
                }
            } catch (e) {}
        }
        return tags
            .split(",")
            .map((t) => t.trim())
            .filter(Boolean);
    }
    return [String(tags).trim()].filter(Boolean);
};

const expandedTags = ref(new Set());

const toggleExpandTags = (id) => {
    const next = new Set(expandedTags.value);
    if (next.has(id)) {
        next.delete(id);
    } else {
        next.add(id);
    }
    expandedTags.value = next;
};

const filterByTag = (tag) => {
    searchBerita.value = tag;
    handleBeritaFilter();
};

// --- BULK SELECTION & BULK DELETE ---
const selectedBeritaIds = ref([]);
const selectedGaleriIds = ref([]);

const isAllBeritaSelected = computed(() => {
    const list = props.berita?.data || [];
    if (!list.length) return false;
    return list.every((item) => selectedBeritaIds.value.includes(item.id));
});

const isSomeBeritaSelected = computed(() => {
    const list = props.berita?.data || [];
    return selectedBeritaIds.value.length > 0 && !isAllBeritaSelected.value;
});

const toggleSelectAllBerita = () => {
    const list = props.berita?.data || [];
    if (isAllBeritaSelected.value) {
        selectedBeritaIds.value = [];
    } else {
        selectedBeritaIds.value = list.map((item) => item.id);
    }
};

const toggleSelectBerita = (id) => {
    const idx = selectedBeritaIds.value.indexOf(id);
    if (idx > -1) {
        selectedBeritaIds.value.splice(idx, 1);
    } else {
        selectedBeritaIds.value.push(id);
    }
};

const bulkDeleteBerita = () => {
    if (!selectedBeritaIds.value.length || isBulkDeletingBerita.value) return;
    if (
        !confirm(
            `Apakah Anda yakin ingin menghapus ${selectedBeritaIds.value.length} berita terpilih?`,
        )
    ) {
        return;
    }

    isBulkDeletingBerita.value = true;
    router.post(
        "/admin/berita/bulk-delete",
        { ids: selectedBeritaIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedBeritaIds.value = [];
            },
            onFinish: () => {
                isBulkDeletingBerita.value = false;
            },
        },
    );
};

const isAllGaleriSelected = computed(() => {
    const list = props.galeri?.data || [];
    if (!list.length) return false;
    return list.every((item) => selectedGaleriIds.value.includes(item.id));
});

const isSomeGaleriSelected = computed(() => {
    const list = props.galeri?.data || [];
    return selectedGaleriIds.value.length > 0 && !isAllGaleriSelected.value;
});

const toggleSelectAllGaleri = () => {
    const list = props.galeri?.data || [];
    if (isAllGaleriSelected.value) {
        selectedGaleriIds.value = [];
    } else {
        selectedGaleriIds.value = list.map((item) => item.id);
    }
};

const toggleSelectGaleri = (id) => {
    const idx = selectedGaleriIds.value.indexOf(id);
    if (idx > -1) {
        selectedGaleriIds.value.splice(idx, 1);
    } else {
        selectedGaleriIds.value.push(id);
    }
};

const bulkDeleteGaleri = () => {
    if (!selectedGaleriIds.value.length || isBulkDeletingGaleri.value) return;
    if (
        !confirm(
            `Apakah Anda yakin ingin menghapus ${selectedGaleriIds.value.length} foto galeri terpilih?`,
        )
    ) {
        return;
    }

    isBulkDeletingGaleri.value = true;
    router.post(
        "/admin/berita/bulk-delete",
        { ids: selectedGaleriIds.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedGaleriIds.value = [];
            },
            onFinish: () => {
                isBulkDeletingGaleri.value = false;
            },
        },
    );
};

// --- MODAL ZOOM FOTO ---
const isZoomOpen = ref(false);
const zoomImageUrl = ref("");
const zoomImageTitle = ref("");

const openZoom = (url, title = "") => {
    if (!url) return;
    zoomImageUrl.value = url;
    zoomImageTitle.value = title;
    isZoomOpen.value = true;
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
    isSubmitting.value = true;
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
                onFinish: () => {
                    isSubmitting.value = false;
                },
            },
        );
    } else {
        form.post("/admin/berita", {
            forceFormData: true,
            onSuccess: () => closeDialog(),
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    }
};

const deleteItem = (item) => {
    if (deletingId.value) return;
    if (!confirm(`Hapus "${item.judul_berita || item.keterangan_galeri}"?`))
        return;
    deletingId.value = item.id;
    router.delete(`/admin/berita/${item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
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
                    class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs"
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
                    <div class="flex items-center flex-wrap gap-2">
                        <!-- Per-Page Selector -->
                        <div
                            class="flex items-center gap-1.5 text-xs text-slate-500 font-medium"
                        >
                            <span class="hidden sm:inline">Baris:</span>
                            <select
                                v-model.number="perPageBerita"
                                @change="handleBeritaFilter"
                                class="h-9 px-2.5 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                            >
                                <option :value="10">10 / hal</option>
                                <option :value="25">25 / hal</option>
                                <option :value="50">50 / hal</option>
                                <option :value="100">100 / hal</option>
                            </select>
                        </div>

                        <!-- Sort Selector -->
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

                        <!-- Cari Button -->
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="handleBeritaFilter"
                            class="h-9 px-3 text-xs gap-1.5"
                        >
                            <Search class="w-3.5 h-3.5" />
                            <span>Cari</span>
                        </Button>

                        <!-- Reset Filter Button -->
                        <Button
                            v-if="
                                searchBerita ||
                                sortBerita !== 'latest' ||
                                sortByBerita ||
                                perPageBerita !== 10
                            "
                            variant="outline"
                            size="sm"
                            @click="resetBeritaFilter"
                            title="Reset Semua Filter"
                            class="h-9 px-2.5 text-xs text-slate-600 hover:text-slate-900 border-dashed"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            <span class="hidden sm:inline ml-1">Reset</span>
                        </Button>
                    </div>
                </div>

                <!-- Bulk Action Bar: Berita -->
                <div
                    v-if="selectedBeritaIds.length > 0"
                    class="flex items-center justify-between gap-3 px-4 py-2.5 bg-blue-50/90 border border-blue-200 rounded-2xl animate-in fade-in slide-in-from-top-1 duration-200"
                >
                    <div class="flex items-center gap-2">
                        <CheckSquare class="w-4 h-4 text-blue-600" />
                        <span class="text-xs font-bold text-blue-900">
                            {{ selectedBeritaIds.length }} berita terpilih
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="selectedBeritaIds = []"
                            class="h-8 px-2.5 text-xs text-blue-700 hover:bg-blue-100"
                        >
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="bulkDeleteBerita"
                            :loading="isBulkDeletingBerita"
                            class="h-8 px-3 text-xs gap-1.5 shadow-xs bg-rose-600 hover:bg-rose-700 text-white"
                        >
                            <Trash2 v-if="!isBulkDeletingBerita" class="w-3.5 h-3.5" />
                            <span>{{ isBulkDeletingBerita ? "Menghapus..." : "Hapus Terpilih" }}</span>
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
                                    <TableHead class="w-10 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="isAllBeritaSelected"
                                            :indeterminate.prop="
                                                isSomeBeritaSelected
                                            "
                                            @change="toggleSelectAllBerita"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4"
                                            title="Pilih Semua di Halaman Ini"
                                        />
                                    </TableHead>
                                    <TableHead
                                        class="w-12 text-center text-xs font-semibold"
                                        >No</TableHead
                                    >
                                    <TableHead class="w-24">Cover</TableHead>
                                    <TableHead class="min-w-[260px]">
                                        <DataTableColumnHeader
                                            title="Judul Berita"
                                            column="judul_berita"
                                            :sort-key="sortByBerita"
                                            :sort-direction="sortDirBerita"
                                            @sort="onSortBerita"
                                        />
                                    </TableHead>
                                    <TableHead
                                        class="min-w-[200px] max-w-[320px]"
                                    >
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
                                        colspan="7"
                                        class="h-24 text-center text-slate-400"
                                    >
                                        Tidak ada data berita ditemukan.
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="(item, index) in berita.data"
                                    :key="item.id"
                                    :class="{
                                        'bg-blue-50/40':
                                            selectedBeritaIds.includes(item.id),
                                    }"
                                >
                                    <TableCell class="text-center">
                                        <input
                                            type="checkbox"
                                            :checked="
                                                selectedBeritaIds.includes(
                                                    item.id,
                                                )
                                            "
                                            @change="
                                                toggleSelectBerita(item.id)
                                            "
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4"
                                        />
                                    </TableCell>
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
                                        <div
                                            v-if="item.file_foto"
                                            @click="
                                                openZoom(
                                                    getImageUrl(item.file_foto),
                                                    item.judul_berita,
                                                )
                                            "
                                            class="relative group/thumb h-11 w-18 rounded-lg overflow-hidden border border-slate-200 shadow-2xs cursor-zoom-in bg-slate-100 flex-shrink-0"
                                            title="Klik untuk memperbesar gambar"
                                        >
                                            <img
                                                :src="
                                                    getImageUrl(item.file_foto)
                                                "
                                                class="h-full w-full object-cover transition-transform duration-200 group-hover/thumb:scale-105"
                                                alt="Cover"
                                            />
                                            <div
                                                class="absolute inset-0 bg-slate-900/35 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center text-white"
                                            >
                                                <ZoomIn
                                                    class="w-4 h-4 drop-shadow"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="h-11 w-18 rounded-lg bg-slate-100 border border-slate-200/60 flex items-center justify-center text-slate-400"
                                        >
                                            <ImageIcon class="w-4 h-4" />
                                        </div>
                                    </TableCell>
                                    <TableCell class="align-middle">
                                        <div class="space-y-1">
                                            <p
                                                class="font-bold text-slate-900 text-sm leading-snug line-clamp-2"
                                                :title="item.judul_berita"
                                            >
                                                {{ item.judul_berita }}
                                            </p>
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <a
                                                    :href="`/berita-informasi/berita/${item.id}`"
                                                    target="_blank"
                                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:text-blue-800 hover:underline"
                                                    title="Buka artikel di tab baru"
                                                >
                                                    <span>Lihat Artikel</span>
                                                    <ExternalLink
                                                        class="w-3 h-3"
                                                    />
                                                </a>
                                            </div>
                                        </div>
                                    </TableCell>
                                    <TableCell class="align-middle">
                                        <!-- Kotak Tags Dinamis -->
                                        <div
                                            v-if="parseTags(item.tags).length"
                                            class="flex flex-wrap items-center gap-1.5 max-w-[320px]"
                                        >
                                            <button
                                                v-for="(
                                                    tag, tIdx
                                                ) in expandedTags.has(item.id)
                                                    ? parseTags(item.tags)
                                                    : parseTags(
                                                          item.tags,
                                                      ).slice(0, 2)"
                                                :key="tIdx"
                                                type="button"
                                                @click="filterByTag(tag)"
                                                :title="`Cari berita dengan tag: ${tag}`"
                                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] font-medium bg-blue-50 text-blue-700 border border-blue-200/80 hover:bg-blue-100 hover:border-blue-300 transition-colors cursor-pointer group/tag"
                                            >
                                                <Tag
                                                    class="w-2.5 h-2.5 text-blue-500 group-hover/tag:text-blue-700"
                                                />
                                                <span
                                                    class="truncate max-w-[120px]"
                                                    >{{ tag }}</span
                                                >
                                            </button>

                                            <button
                                                v-if="
                                                    parseTags(item.tags)
                                                        .length > 2
                                                "
                                                type="button"
                                                @click="
                                                    toggleExpandTags(item.id)
                                                "
                                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 border border-slate-200 transition-colors cursor-pointer"
                                            >
                                                <span
                                                    v-if="
                                                        !expandedTags.has(
                                                            item.id,
                                                        )
                                                    "
                                                    >+{{
                                                        parseTags(item.tags)
                                                            .length - 2
                                                    }}
                                                    lainnya</span
                                                >
                                                <span
                                                    v-else
                                                    class="text-[10px] text-slate-500"
                                                    >Tutup</span
                                                >
                                                <ChevronDown
                                                    v-if="
                                                        !expandedTags.has(
                                                            item.id,
                                                        )
                                                    "
                                                    class="w-3 h-3"
                                                />
                                                <ChevronUp
                                                    v-else
                                                    class="w-3 h-3"
                                                />
                                            </button>
                                        </div>
                                        <span
                                            v-else
                                            class="text-xs text-slate-400 italic"
                                            >-</span
                                        >
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
                                                :loading="deletingId === item.id"
                                                class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                            >
                                                <Trash2
                                                    v-if="deletingId !== item.id"
                                                    class="w-3.5 h-3.5 mr-1"
                                                />
                                                {{ deletingId === item.id ? "Menghapus..." : "Hapus" }}
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
                <!-- Filter & Search Toolbar: Galeri -->
                <div
                    class="flex flex-col lg:flex-row items-stretch lg:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs"
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
                    <div class="flex items-center flex-wrap gap-2">
                        <!-- Per-Page Selector Galeri -->
                        <div
                            class="flex items-center gap-1.5 text-xs text-slate-500 font-medium"
                        >
                            <span class="hidden sm:inline">Baris:</span>
                            <select
                                v-model.number="perPageGaleri"
                                @change="handleGaleriFilter"
                                class="h-9 px-2.5 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                            >
                                <option :value="12">12 / hal</option>
                                <option :value="24">24 / hal</option>
                                <option :value="48">48 / hal</option>
                                <option :value="96">96 / hal</option>
                            </select>
                        </div>

                        <!-- Sort Selector Galeri -->
                        <select
                            v-model="sortGaleri"
                            @change="handleGaleriFilter"
                            class="h-9 px-3 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                        >
                            <option value="latest">Terbaru</option>
                            <option value="oldest">Terlama</option>
                        </select>

                        <!-- Cari Button -->
                        <Button
                            variant="secondary"
                            size="sm"
                            @click="handleGaleriFilter"
                            class="h-9 px-3 text-xs gap-1.5"
                        >
                            <Search class="w-3.5 h-3.5" />
                            <span>Cari</span>
                        </Button>

                        <!-- Reset Filter Button -->
                        <Button
                            v-if="
                                searchGaleri ||
                                sortGaleri !== 'latest' ||
                                sortByGaleri ||
                                perPageGaleri !== 12
                            "
                            variant="outline"
                            size="sm"
                            @click="resetGaleriFilter"
                            title="Reset Semua Filter"
                            class="h-9 px-2.5 text-xs text-slate-600 hover:text-slate-900 border-dashed"
                        >
                            <RotateCcw class="w-3.5 h-3.5" />
                            <span class="hidden sm:inline ml-1">Reset</span>
                        </Button>
                    </div>
                </div>

                <!-- Bulk Action Bar: Galeri -->
                <div
                    v-if="selectedGaleriIds.length > 0"
                    class="flex items-center justify-between gap-3 px-4 py-2.5 bg-blue-50/90 border border-blue-200 rounded-2xl animate-in fade-in slide-in-from-top-1 duration-200"
                >
                    <div class="flex items-center gap-2">
                        <CheckSquare class="w-4 h-4 text-blue-600" />
                        <span class="text-xs font-bold text-blue-900">
                            {{ selectedGaleriIds.length }} foto terpilih
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="selectedGaleriIds = []"
                            class="h-8 px-2.5 text-xs text-blue-700 hover:bg-blue-100"
                        >
                            Batal
                        </Button>
                        <Button
                            variant="destructive"
                            size="sm"
                            @click="bulkDeleteGaleri"
                            :loading="isBulkDeletingGaleri"
                            class="h-8 px-3 text-xs gap-1.5 shadow-xs bg-rose-600 hover:bg-rose-700 text-white"
                        >
                            <Trash2 v-if="!isBulkDeletingGaleri" class="w-3.5 h-3.5" />
                            <span>{{ isBulkDeletingGaleri ? "Menghapus..." : "Hapus Terpilih" }}</span>
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
                                    <TableHead class="w-10 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="isAllGaleriSelected"
                                            :indeterminate.prop="
                                                isSomeGaleriSelected
                                            "
                                            @change="toggleSelectAllGaleri"
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4"
                                            title="Pilih Semua di Halaman Ini"
                                        />
                                    </TableHead>
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
                                        colspan="6"
                                        class="h-24 text-center text-slate-400"
                                    >
                                        Tidak ada foto galeri ditemukan.
                                    </TableCell>
                                </TableRow>
                                <TableRow
                                    v-for="(item, index) in galeri.data"
                                    :key="item.id"
                                    :class="{
                                        'bg-blue-50/40':
                                            selectedGaleriIds.includes(item.id),
                                    }"
                                >
                                    <TableCell class="text-center">
                                        <input
                                            type="checkbox"
                                            :checked="
                                                selectedGaleriIds.includes(
                                                    item.id,
                                                )
                                            "
                                            @change="
                                                toggleSelectGaleri(item.id)
                                            "
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 cursor-pointer w-4 h-4"
                                        />
                                    </TableCell>
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
                                        <div
                                            v-if="item.file_foto"
                                            @click="
                                                openZoom(
                                                    getImageUrl(item.file_foto),
                                                    item.keterangan_galeri,
                                                )
                                            "
                                            class="relative group/thumb h-12 w-20 rounded-xl overflow-hidden border border-slate-200 shadow-2xs cursor-zoom-in bg-slate-100 flex-shrink-0"
                                            title="Klik untuk memperbesar gambar"
                                        >
                                            <img
                                                :src="
                                                    getImageUrl(item.file_foto)
                                                "
                                                class="h-full w-full object-cover transition-transform duration-200 group-hover/thumb:scale-105"
                                                alt="Foto"
                                            />
                                            <div
                                                class="absolute inset-0 bg-slate-900/35 opacity-0 group-hover/thumb:opacity-100 transition-opacity flex items-center justify-center text-white"
                                            >
                                                <ZoomIn
                                                    class="w-4 h-4 drop-shadow"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="h-12 w-20 rounded-xl bg-slate-100 border border-slate-200/60 flex items-center justify-center text-slate-400"
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
                                                :loading="deletingId === item.id"
                                                class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                            >
                                                <Trash2
                                                    v-if="deletingId !== item.id"
                                                    class="w-3.5 h-3.5 mr-1"
                                                />
                                                {{ deletingId === item.id ? "Menghapus..." : "Hapus" }}
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

        <!-- MODAL ZOOM FOTO LIGHTBOX -->
        <Dialog :open="isZoomOpen" @update:open="isZoomOpen = $event">
            <DialogContent
                class="sm:max-w-3xl p-4 overflow-hidden bg-slate-950/95 border-slate-800 text-white"
            >
                <DialogHeader class="mb-2">
                    <DialogTitle
                        class="text-sm font-semibold text-slate-200 truncate"
                    >
                        {{ zoomImageTitle || "Preview Foto" }}
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
                        class="inline-flex items-center gap-1 text-blue-400 hover:text-blue-300 font-medium"
                    >
                        <ExternalLink class="w-3.5 h-3.5" />
                        <span>Buka Tab Baru</span>
                    </a>
                </div>
            </DialogContent>
        </Dialog>

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
                            :loading="isSubmitting || form.processing"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                        >
                            {{
                                (isSubmitting || form.processing)
                                    ? "Menyimpan..."
                                    : (editItem ? "Perbarui Data" : "Simpan Data")
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>
