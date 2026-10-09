<script setup>
import { ref } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { Badge } from "@/Components/ui/badge";
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
import { RichTextEditor } from "@/Components/ui/rich-text-editor";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    Plus,
    Pencil,
    Trash2,
    ExternalLink,
    Download,
    FileText,
    Upload,
    Link as LinkIcon,
    X,
    Search,
    Loader2,
} from "lucide-vue-next";

const props = defineProps({
    dokumen: Object,
    kategoriList: Array,
    filters: Object,
});

const isDialogOpen = ref(false);
const editItem = ref(null);
const fileInputRef = ref(null);
const selectedFile = ref(null);
const uploadMode = ref("file"); // 'file' or 'url'

// Loading states
const isSubmitting = ref(false);
const deletingId = ref(null);

const searchQuery = ref(props.filters?.search || "");
const filterKategori = ref(props.filters?.kategori || "Semua");
const sortOrder = ref(props.filters?.sort || "latest");
const sortBy = ref(props.filters?.sort_by || "");
const sortDir = ref(props.filters?.sort_dir || "asc");

const handleFilter = () => {
    router.get(
        "/admin/informasi-publik",
        {
            search: searchQuery.value,
            kategori:
                filterKategori.value === "Semua" ? "" : filterKategori.value,
            sort: sortOrder.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const onSort = (column, direction) => {
    sortBy.value = column;
    sortDir.value = direction;
    handleFilter();
};

const KATEGORI_OPTIONS = [
    "Berkala",
    "Serta Merta",
    "Setiap Saat",
    "Dikecualikan",
];

const form = useForm({
    kategori: "",
    nama_dokumen: "",
    file_path: null,
    remove_file_path: false,
    deskripsi: "",
});

const getDocUrl = (path) => {
    if (!path) return null;
    return path.startsWith("http") ? path : `/storage/${path}`;
};

const onFileSelected = (e) => {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 50 * 1024 * 1024) {
            form.errors.file_path = "Ukuran dokumen melebihi batas maksimal 50MB.";
            if (e.target) e.target.value = "";
            return;
        }
        delete form.errors.file_path;
        selectedFile.value = file;
        form.file_path = file;
        form.remove_file_path = false;
    }
};

const removeSelectedFile = () => {
    selectedFile.value = null;
    form.file_path = editItem.value?.file_path ?? "";
    delete form.errors.file_path;
    if (fileInputRef.value) fileInputRef.value.value = "";
};

const removeExistingFile = () => {
    selectedFile.value = null;
    form.file_path = null;
    form.remove_file_path = true;
    delete form.errors.file_path;
    if (fileInputRef.value) fileInputRef.value.value = "";
    if (editItem.value) {
        editItem.value.file_path = null;
    }
};

const openCreate = () => {
    editItem.value = null;
    selectedFile.value = null;
    uploadMode.value = "file";
    form.reset();
    form.clearErrors();
    form.remove_file_path = false;
    isDialogOpen.value = true;
};

const openEdit = (item) => {
    editItem.value = item;
    selectedFile.value = null;
    uploadMode.value = item.file_path?.startsWith("http") ? "url" : "file";
    form.clearErrors();
    form.kategori = item.kategori;
    form.nama_dokumen = item.nama_dokumen;
    form.file_path = item.file_path ?? "";
    form.remove_file_path = false;
    form.deskripsi = item.deskripsi ?? "";
    isDialogOpen.value = true;
};

const closeDialog = () => {
    isDialogOpen.value = false;
    editItem.value = null;
    selectedFile.value = null;
    form.reset();
    form.clearErrors();
};

const submit = () => {
    isSubmitting.value = true;
    form.clearErrors();
    if (editItem.value) {
        form.transform((data) => ({
            ...data,
            _method: "PUT",
        })).post(`/admin/informasi-publik/${editItem.value.id}`, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeDialog(),
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    } else {
        form.post("/admin/informasi-publik", {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => closeDialog(),
            onFinish: () => {
                isSubmitting.value = false;
            },
        });
    }
};

const deleteItem = (item) => {
    if (deletingId.value) return;
    if (!confirm(`Hapus dokumen "${item.nama_dokumen}"?`)) return;
    deletingId.value = item.id;
    router.delete(`/admin/informasi-publik/${item.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
};

const selectKategori = (k) => {
    filterKategori.value = k;
    handleFilter();
};
</script>

<template>
    <Head title="Informasi Publik (PPID)" />

    <DashboardLayout>
        <div class="space-y-6 w-full">
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <h2 class="text-xl font-bold tracking-tight text-slate-900">
                        Informasi Publik (PPID)
                    </h2>
                    <p class="text-xs text-slate-500 mt-0.5">
                        Kelola berkas dan dokumen keterbukaan informasi publik
                        per kategori
                    </p>
                </div>
                <Button
                    @click="openCreate"
                    class="w-fit bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20"
                >
                    <Plus class="w-4 h-4 mr-1.5" /> Tambah Dokumen
                </Button>
            </div>

            <!-- Top Category Tabs (mirip Berita & Galeri) -->
            <div
                class="flex items-center gap-1.5 p-1.5 bg-slate-200/70 rounded-2xl overflow-x-auto w-fit shadow-2xs"
            >
                <button
                    v-for="k in ['Semua', ...KATEGORI_OPTIONS]"
                    :key="k"
                    @click="selectKategori(k)"
                    :class="[
                        'flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl text-xs font-bold transition-all whitespace-nowrap cursor-pointer select-none',
                        filterKategori === k
                            ? 'bg-white text-blue-600 shadow-xs'
                            : 'text-slate-600 hover:text-slate-900 hover:bg-white/50',
                    ]"
                >
                    <span>{{ k }}</span>
                </button>
            </div>

            <!-- Filter & Search Toolbar -->
            <div
                class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-slate-200 shadow-2xs"
            >
                <div class="relative flex-1">
                    <Search
                        class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                    />
                    <Input
                        v-model="searchQuery"
                        @keyup.enter="handleFilter"
                        placeholder="Cari nama dokumen atau deskripsi..."
                        class="pl-9 pr-8 h-9 text-xs"
                    />
                    <button
                        v-if="searchQuery"
                        @click="
                            searchQuery = '';
                            handleFilter();
                        "
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <select
                        v-model="sortOrder"
                        @change="handleFilter"
                        class="h-9 px-3 text-xs font-semibold bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-xl text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                    >
                        <option value="latest">Terbaru</option>
                        <option value="oldest">Terlama</option>
                        <option value="nama_asc">Nama (A-Z)</option>
                        <option value="nama_desc">Nama (Z-A)</option>
                    </select>
                    <Button
                        variant="secondary"
                        size="sm"
                        @click="handleFilter"
                        class="h-9 px-3 text-xs"
                    >
                        Cari
                    </Button>
                </div>
            </div>

            <!-- Table -->
            <div
                class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden w-full"
            >
                <div class="overflow-x-auto w-full">
                    <Table class="w-full">
                        <TableHeader>
                            <TableRow>
                                <TableHead
                                    class="w-12 text-center text-xs font-semibold"
                                    >No</TableHead
                                >
                                <TableHead class="w-36">
                                    <DataTableColumnHeader
                                        title="Kategori"
                                        column="kategori"
                                        :sort-key="sortBy"
                                        :sort-direction="sortDir"
                                        @sort="onSort"
                                    />
                                </TableHead>
                                <TableHead>
                                    <DataTableColumnHeader
                                        title="Nama Dokumen"
                                        column="nama_dokumen"
                                        :sort-key="sortBy"
                                        :sort-direction="sortDir"
                                        @sort="onSort"
                                    />
                                </TableHead>
                                <TableHead class="w-24 text-center"
                                    >Unduhan</TableHead
                                >
                                <TableHead class="text-right">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="!dokumen.data?.length">
                                <TableCell
                                    colspan="5"
                                    class="h-28 text-center text-slate-400 text-xs"
                                >
                                    Belum ada dokumen publik ditemukan.
                                </TableCell>
                            </TableRow>
                            <TableRow
                                v-for="(item, index) in dokumen.data"
                                :key="item.id"
                            >
                                <TableCell
                                    class="text-center font-mono text-[11px] text-slate-400 font-semibold"
                                >
                                    {{
                                        (dokumen.current_page - 1) *
                                            dokumen.per_page +
                                        index +
                                        1
                                    }}
                                </TableCell>
                                <TableCell>
                                    <Badge
                                        variant="secondary"
                                        class="font-semibold text-[10px] bg-slate-100 text-slate-700"
                                    >
                                        {{ item.kategori }}
                                    </Badge>
                                </TableCell>
                                <TableCell>
                                    <div class="flex items-center gap-2.5">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center shrink-0"
                                        >
                                            <FileText class="w-4 h-4" />
                                        </div>
                                        <div>
                                            <div
                                                class="font-semibold text-slate-900 text-xs"
                                            >
                                                {{ item.nama_dokumen }}
                                            </div>
                                            <div
                                                v-if="item.deskripsi"
                                                class="text-[11px] text-slate-400 line-clamp-1"
                                            >
                                                {{ item.deskripsi }}
                                            </div>
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell
                                    class="text-center font-mono text-xs text-slate-500"
                                >
                                    <span
                                        class="inline-flex items-center gap-1"
                                    >
                                        <Download
                                            class="w-3 h-3 text-slate-400"
                                        />
                                        {{ item.jumlah_diunduh ?? 0 }}
                                    </span>
                                </TableCell>
                                <TableCell class="text-right">
                                    <div
                                        class="flex items-center justify-end gap-1.5"
                                    >
                                        <a
                                            v-if="item.file_path"
                                            :href="getDocUrl(item.file_path)"
                                            target="_blank"
                                            class="inline-flex items-center justify-center h-7 px-2.5 text-xs font-semibold rounded-lg text-blue-600 hover:bg-blue-50 transition-colors"
                                        >
                                            <ExternalLink
                                                class="w-3.5 h-3.5 mr-1"
                                            />
                                            Unduh
                                        </a>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="openEdit(item)"
                                            class="h-7 px-2 text-slate-600 hover:text-slate-900 hover:bg-slate-100"
                                        >
                                            <Pencil class="w-3.5 h-3.5 mr-1" />
                                            Edit
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteItem(item)"
                                            :loading="deletingId === item.id"
                                            class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                        >
                                            <Trash2 v-if="deletingId !== item.id" class="w-3.5 h-3.5 mr-1" />
                                            {{ deletingId === item.id ? "Menghapus..." : "Hapus" }}
                                        </Button>
                                    </div>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
                <DataTablePagination :pagination="dokumen" />
            </div>
        </div>

        <!-- MODAL DIALOG -->
        <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
            <DialogContent class="sm:max-w-3xl max-h-[92vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle
                        >{{ editItem ? "Edit" : "Tambah" }} Dokumen
                        PPID</DialogTitle
                    >
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-4 pt-2">
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700"
                            >Kategori Informasi</label
                        >
                        <select
                            v-model="form.kategori"
                            class="flex h-10 w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-600"
                            required
                        >
                            <option value="">Pilih kategori...</option>
                            <option
                                v-for="k in KATEGORI_OPTIONS"
                                :key="k"
                                :value="k"
                            >
                                {{ k }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700"
                            >Nama Dokumen</label
                        >
                        <Input
                            v-model="form.nama_dokumen"
                            placeholder="Contoh: Rencana Kerja dan Anggaran (RKA) 2025"
                            required
                        />
                    </div>

                    <!-- Dokumen File / URL Tabs -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-slate-700"
                                >File Dokumen</label
                            >
                            <div
                                class="flex items-center gap-1 bg-slate-100 p-0.5 rounded-lg text-[10px]"
                            >
                                <button
                                    type="button"
                                    @click="uploadMode = 'file'"
                                    :class="[
                                        'px-2 py-0.5 rounded-md font-semibold transition-all',
                                        uploadMode === 'file'
                                            ? 'bg-white text-slate-900 shadow-2xs'
                                            : 'text-slate-500 hover:text-slate-800',
                                    ]"
                                >
                                    <Upload class="w-2.5 h-2.5 inline mr-1" />
                                    Upload File
                                </button>
                                <button
                                    type="button"
                                    @click="uploadMode = 'url'"
                                    :class="[
                                        'px-2 py-0.5 rounded-md font-semibold transition-all',
                                        uploadMode === 'url'
                                            ? 'bg-white text-slate-900 shadow-2xs'
                                            : 'text-slate-500 hover:text-slate-800',
                                    ]"
                                >
                                    <LinkIcon class="w-2.5 h-2.5 inline mr-1" />
                                    URL Link
                                </button>
                            </div>
                        </div>

                        <!-- Mode Upload File -->
                        <div v-if="uploadMode === 'file'" class="space-y-2">
                            <input
                                ref="fileInputRef"
                                type="file"
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar"
                                class="hidden"
                                id="doc_file_input"
                                @change="onFileSelected"
                            />

                            <!-- Selected File / Existing File -->
                            <div
                                v-if="selectedFile"
                                class="flex items-center justify-between p-3 rounded-xl border border-blue-200 bg-blue-50/50"
                            >
                                <div class="flex items-center gap-2.5 truncate">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-blue-100 text-blue-700 flex items-center justify-center shrink-0"
                                    >
                                        <FileText class="w-4 h-4" />
                                    </div>
                                    <div class="truncate">
                                        <p
                                            class="text-xs font-bold text-slate-800 truncate"
                                        >
                                            {{ selectedFile.name }}
                                        </p>
                                        <p
                                            class="text-[10px] text-slate-500 font-mono"
                                        >
                                            {{
                                                (
                                                    selectedFile.size / 1024
                                                ).toFixed(1)
                                            }}
                                            KB
                                        </p>
                                    </div>
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    @click="removeSelectedFile"
                                    class="h-7 w-7 p-0 text-slate-400 hover:text-rose-600"
                                >
                                    <X class="w-4 h-4" />
                                </Button>
                            </div>

                            <div
                                v-else-if="
                                    editItem?.file_path &&
                                    !editItem?.file_path.startsWith('http')
                                "
                                class="flex items-center justify-between p-3 rounded-xl border border-slate-200 bg-slate-50"
                            >
                                <div class="flex items-center gap-2.5 truncate">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-slate-200 text-slate-700 flex items-center justify-center shrink-0"
                                    >
                                        <FileText class="w-4 h-4" />
                                    </div>
                                    <div class="truncate">
                                        <p
                                            class="text-xs font-semibold text-slate-700 truncate"
                                        >
                                            {{
                                                editItem.file_path
                                                    .split("/")
                                                    .pop()
                                            }}
                                        </p>
                                        <p
                                            class="text-[10px] text-emerald-600 font-medium"
                                        >
                                            Tersimpan di server
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <label
                                        for="doc_file_input"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold rounded-lg bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 cursor-pointer shadow-2xs"
                                    >
                                        <Upload class="w-3 h-3" /> Ganti
                                    </label>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        @click="removeExistingFile"
                                        class="h-7 px-2 text-rose-600 hover:text-rose-700 hover:bg-rose-50"
                                    >
                                        <Trash2 class="w-3.5 h-3.5 mr-1" /> Hapus
                                    </Button>
                                </div>
                            </div>

                            <label
                                v-else
                                for="doc_file_input"
                                class="flex flex-col items-center justify-center p-6 border-2 border-dashed border-slate-200 hover:border-blue-500 rounded-2xl bg-slate-50/50 hover:bg-blue-50/30 transition-colors cursor-pointer text-center"
                            >
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-100/70 text-blue-600 flex items-center justify-center mb-2"
                                >
                                    <Upload class="w-5 h-5" />
                                </div>
                                <p class="text-xs font-bold text-slate-700">
                                    Pilih berkas dari komputer
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">
                                    Format didukung: PDF, DOC, DOCX, XLS, XLSX,
                                    ZIP (Maksimal 50MB)
                                </p>
                            </label>

                            <p
                                v-if="form.errors.file_path"
                                class="text-[11px] text-rose-500 font-medium"
                            >
                                {{ form.errors.file_path }}
                            </p>
                        </div>

                        <!-- Mode URL Eksternal -->
                        <div v-else class="space-y-1">
                            <Input
                                v-model="form.file_path"
                                placeholder="https://drive.google.com/... atau tautan file cloud"
                            />
                            <p class="text-[10px] text-slate-400">
                                Tautan langsung ke file PDF atau Google Drive
                                publik
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700"
                            >Deskripsi / Keterangan Dokumen (Word Editor)</label
                        >
                        <RichTextEditor
                            v-model="form.deskripsi"
                            min-height="160px"
                            placeholder="Keterangan lengkap dokumen seperti di Microsoft Word..."
                            upload-folder="website/informasi-publik/konten"
                        />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeDialog"
                            >Batal</Button
                        >
                        <Button
                            type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white shadow-2xs"
                            :loading="isSubmitting"
                        >
                            {{
                                isSubmitting
                                    ? "Menyimpan..."
                                    : (editItem ? "Perbarui Dokumen" : "Simpan Dokumen")
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>
