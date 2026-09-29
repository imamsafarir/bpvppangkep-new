<script setup>
import { ref, computed } from "vue";
import { Head, useForm, router, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
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
import {
    Plus,
    Pencil,
    Trash2,
    Shield,
    User as UserIcon,
    Search,
    X,
    Check,
    Download,
    KeyRound,
    UserCheck,
    UserX,
    CheckSquare,
    Square,
    Users,
    ShieldCheck,
    RotateCcw,
    FileSpreadsheet,
    Building2,
    Calendar,
    ArrowUpDown,
} from "lucide-vue-next";

const props = defineProps({
    users: Object,
    stats: Object,
    availableRoles: Array,
    filters: Object,
});

const page = usePage();
const currentAuthId = computed(() => page.props.auth?.user?.id);

// --- SEARCH, FILTERS & SORTING ---
const searchQuery = ref(props.filters?.search || "");
const roleFilter = ref(props.filters?.role || "");
const statusFilter = ref(props.filters?.status || "");
const perPage = ref(props.filters?.per_page || 10);
const sortBy = ref(props.filters?.sort_by || "id");
const sortDir = ref(props.filters?.sort_dir || "desc");

const handleFilter = () => {
    router.get(
        "/admin/users",
        {
            search: searchQuery.value,
            role: roleFilter.value,
            status: statusFilter.value,
            per_page: perPage.value,
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

const resetFilter = () => {
    searchQuery.value = "";
    roleFilter.value = "";
    statusFilter.value = "";
    perPage.value = 10;
    sortBy.value = "id";
    sortDir.value = "desc";
    handleFilter();
};

// --- MULTI-SELECT & BULK ACTIONS ---
const selectedUserIds = ref([]);

const isAllSelected = computed(() => {
    const list = props.users?.data || [];
    return list.length > 0 && selectedUserIds.value.length === list.length;
});

const toggleSelectAll = () => {
    const list = props.users?.data || [];
    if (isAllSelected.value) {
        selectedUserIds.value = [];
    } else {
        selectedUserIds.value = list.map((u) => u.id);
    }
};

const toggleSelectUser = (id) => {
    const idx = selectedUserIds.value.indexOf(id);
    if (idx > -1) {
        selectedUserIds.value.splice(idx, 1);
    } else {
        selectedUserIds.value.push(id);
    }
};

const executeBulkAction = (action) => {
    if (!selectedUserIds.value.length) return;

    if (action === "delete") {
        if (
            !confirm(
                `Yakin ingin menghapus ${selectedUserIds.value.length} pengguna terpilih secara permanen?`,
            )
        ) {
            return;
        }
    }

    router.post(
        "/admin/users/bulk",
        {
            action: action,
            ids: selectedUserIds.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedUserIds.value = [];
            },
        },
    );
};

const exportSelectedUsers = () => {
    if (!selectedUserIds.value.length) return;
    window.location.href = `/admin/users/export?ids=${selectedUserIds.value.join(",")}`;
};

const exportAllUsers = () => {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append("search", searchQuery.value);
    if (roleFilter.value) params.append("role", roleFilter.value);
    if (statusFilter.value) params.append("status", statusFilter.value);
    window.location.href = `/admin/users/export?${params.toString()}`;
};

const downloadTemplate = () => {
    window.location.href = "/admin/users/template";
};

// --- QUICK TOGGLE STATUS ---
const toggleUserStatus = (user) => {
    if (user.id === currentAuthId.value) {
        alert("Tidak dapat menonaktifkan akun sendiri.");
        return;
    }
    router.patch(
        `/admin/users/${user.id}/toggle`,
        {},
        { preserveScroll: true },
    );
};

// --- ROLES DEFINITIONS & BADGES ---
const defaultRolesList = [
    {
        id: "super_admin",
        label: "Super Admin",
        desc: "Akses penuh ke seluruh sistem dan konfigurasi",
        badge_color: "default",
    },
    {
        id: "admin_website",
        label: "Admin Website",
        desc: "Kelola website balai, profil, informasi publik, dan berita",
        badge_color: "info",
    },
    {
        id: "admin_shortlink",
        label: "Admin Shortlink",
        desc: "Pengelola pemendek tautan resmi balai dan form leads",
        badge_color: "success",
    },
    {
        id: "medsos_instruktur",
        label: "Medsos Instruktur",
        desc: "Pengusul materi pelatihan kejuruan dan narasumber",
        badge_color: "secondary",
    },
    {
        id: "medsos_planner",
        label: "Medsos Planner",
        desc: "Perencanaan ide konten dan penjadwalan medsos",
        badge_color: "primary",
    },
    {
        id: "medsos_editor",
        label: "Medsos Editor",
        desc: "Produksi multimedia, editing video dan desain visual",
        badge_color: "warning",
    },
    {
        id: "medsos_admin_platform",
        label: "Medsos Admin Platform",
        desc: "Publisher akun resmi medsos balai dan review tayang",
        badge_color: "outline",
    },
    {
        id: "user",
        label: "Pengguna Biasa",
        desc: "Akses standar pegawai / pengguna umum",
        badge_color: "zinc",
    },
];

const roleOptions = computed(() => {
    return props.availableRoles && props.availableRoles.length > 0
        ? props.availableRoles
        : defaultRolesList;
});

const getRoleLabel = (roleId) => {
    const found = roleOptions.value.find((r) => r.id === roleId);
    return found ? found.label : roleId;
};

const getUserRoleList = (user) => {
    if (user.roles && Array.isArray(user.roles) && user.roles.length > 0) {
        return user.roles;
    }
    if (user.role) {
        return user.role
            .split(",")
            .map((r) => r.trim())
            .filter(Boolean);
    }
    return ["user"];
};

const getRoleBadgeVariant = (role) => {
    if (role === "super_admin") return "default";
    if (role === "admin_website" || role === "admin") return "info";
    if (role === "admin_shortlink" || role === "shortlink") return "success";
    if (role === "medsos_planner") return "primary";
    if (role === "medsos_editor") return "warning";
    if (role === "medsos_instruktur") return "secondary";
    if (role === "medsos_admin_platform") return "outline";
    if (role.startsWith("medsos_")) return "secondary";
    return "outline";
};

// --- FORM CREATE & EDIT MODAL ---
const isDialogOpen = ref(false);
const editItem = ref(null);

const form = useForm({
    name: "",
    username: "",
    email: "",
    password: "",
    roles: ["user"],
    role: "user",
    room_or_desk: "",
    is_active: true,
});

const openCreate = () => {
    editItem.value = null;
    form.reset();
    form.roles = ["user"];
    form.role = "user";
    form.is_active = true;
    isDialogOpen.value = true;
};

const openEdit = (user) => {
    editItem.value = user;
    form.name = user.name;
    form.username = user.username ?? "";
    form.email = user.email;
    form.password = "";

    const userRoles = getUserRoleList(user);
    form.roles = userRoles.length > 0 ? [...userRoles] : ["user"];
    form.role = form.roles.join(",");

    form.room_or_desk = user.room_or_desk ?? "";
    form.is_active = !!user.is_active;
    isDialogOpen.value = true;
};

const toggleRole = (roleId) => {
    const idx = form.roles.indexOf(roleId);
    if (idx > -1) {
        if (form.roles.length > 1) {
            form.roles.splice(idx, 1);
        } else {
            form.roles = ["user"];
        }
    } else {
        if (form.roles.length === 1 && form.roles[0] === "user") {
            form.roles = [roleId];
        } else {
            form.roles.push(roleId);
        }
    }
    form.role = form.roles.join(",");
};

const selectAllRoles = () => {
    form.roles = roleOptions.value.map((r) => r.id);
    form.role = form.roles.join(",");
};

const resetRoles = () => {
    form.roles = ["user"];
    form.role = "user";
};

const closeDialog = () => {
    isDialogOpen.value = false;
    editItem.value = null;
    form.reset();
};

const submit = () => {
    form.role = form.roles.join(",");
    if (editItem.value) {
        form.put(`/admin/users/${editItem.value.id}`, {
            onSuccess: () => closeDialog(),
        });
    } else {
        form.post("/admin/users", { onSuccess: () => closeDialog() });
    }
};

const deleteUser = (user) => {
    if (user.id === currentAuthId.value) {
        alert("Tidak dapat menghapus akun sendiri.");
        return;
    }
    if (!confirm(`Hapus pengguna "${user.name}"?`)) return;
    router.delete(`/admin/users/${user.id}`);
};

// --- RESET PASSWORD MODAL ---
const isResetPasswordOpen = ref(false);
const resetPasswordUser = ref(null);
const resetPasswordForm = useForm({
    password: "",
    password_confirmation: "",
});

const openResetPassword = (user) => {
    resetPasswordUser.value = user;
    resetPasswordForm.reset();
    isResetPasswordOpen.value = true;
};

const submitResetPassword = () => {
    if (!resetPasswordUser.value) return;
    resetPasswordForm.patch(
        `/admin/users/${resetPasswordUser.value.id}/reset-password`,
        {
            preserveScroll: true,
            onSuccess: () => {
                isResetPasswordOpen.value = false;
                resetPasswordUser.value = null;
                resetPasswordForm.reset();
            },
        },
    );
};
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <DashboardLayout>
        <div class="space-y-6">
            <!-- Header Section -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <div class="flex items-center gap-2.5">
                        <div
                            class="h-10 w-10 rounded-2xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shadow-2xs"
                        >
                            <Users class="w-5 h-5" />
                        </div>
                        <div>
                            <h2
                                class="text-xl font-bold tracking-tight text-zinc-900"
                            >
                                Manajemen Pengguna
                            </h2>
                            <p class="text-xs text-zinc-500">
                                Kelola hak akses multi-role, aktivasi akun
                                pegawai, reset password, dan ekspor data
                                pengguna
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-wrap items-center gap-2">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="downloadTemplate"
                        class="h-9 text-xs border-zinc-200 text-zinc-700 hover:bg-zinc-50 shadow-2xs font-semibold"
                        title="Download Template Format CSV untuk Import Pengguna"
                    >
                        <FileSpreadsheet
                            class="w-3.5 h-3.5 mr-1.5 text-blue-600"
                        />
                        Download Template
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="exportAllUsers"
                        class="h-9 text-xs border-zinc-200 text-zinc-700 hover:bg-zinc-50 shadow-2xs font-semibold"
                    >
                        <Download class="w-3.5 h-3.5 mr-1.5 text-emerald-600" />
                        Export Excel / CSV
                    </Button>
                    <Button
                        @click="openCreate"
                        class="h-9 text-xs bg-blue-600 hover:bg-blue-700 text-white shadow-xs font-semibold"
                    >
                        <Plus class="w-4 h-4 mr-1.5" />
                        Tambah Pengguna
                    </Button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3.5">
                <div
                    class="bg-white p-4 rounded-2xl border border-zinc-200/80 shadow-2xs flex items-center gap-3"
                >
                    <div
                        class="w-10 h-10 rounded-xl bg-zinc-100 flex items-center justify-center text-zinc-600 shrink-0"
                    >
                        <Users class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-zinc-500">
                            Total Pengguna
                        </div>
                        <div class="text-xl font-bold text-zinc-900 mt-0.5">
                            {{ stats?.total || 0 }}
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white p-4 rounded-2xl border border-emerald-100 shadow-2xs flex items-center gap-3"
                >
                    <div
                        class="w-10 h-10 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0"
                    >
                        <UserCheck class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-emerald-700">
                            Akun Aktif
                        </div>
                        <div class="text-xl font-bold text-emerald-700 mt-0.5">
                            {{ stats?.active || 0 }}
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white p-4 rounded-2xl border border-rose-100 shadow-2xs flex items-center gap-3"
                >
                    <div
                        class="w-10 h-10 rounded-xl bg-rose-50 border border-rose-100 flex items-center justify-center text-rose-600 shrink-0"
                    >
                        <UserX class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-rose-700">
                            Akun Nonaktif
                        </div>
                        <div class="text-xl font-bold text-rose-700 mt-0.5">
                            {{ stats?.inactive || 0 }}
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white p-4 rounded-2xl border border-blue-100 shadow-2xs flex items-center gap-3"
                >
                    <div
                        class="w-10 h-10 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0"
                    >
                        <ShieldCheck class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-[11px] font-medium text-blue-700">
                            Administrator
                        </div>
                        <div class="text-xl font-bold text-blue-700 mt-0.5">
                            {{ stats?.admins || 0 }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- BULK ACTIONS FLOATING / TOP BAR -->
            <div
                v-if="selectedUserIds.length > 0"
                class="flex flex-wrap items-center justify-between gap-3 p-3 bg-blue-50/90 border border-blue-200 rounded-2xl shadow-xs animate-in fade-in duration-200"
            >
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center justify-center h-6 px-2.5 rounded-full bg-blue-600 text-white text-xs font-bold font-mono"
                    >
                        {{ selectedUserIds.length }}
                    </span>
                    <span class="text-xs font-semibold text-blue-900">
                        Pengguna terpilih
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-1.5">
                    <Button
                        variant="outline"
                        size="sm"
                        @click="executeBulkAction('activate')"
                        class="h-8 text-xs bg-white text-emerald-700 hover:bg-emerald-50 border-emerald-300"
                    >
                        <UserCheck class="w-3.5 h-3.5 mr-1" />
                        Aktifkan Terpilih
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="executeBulkAction('deactivate')"
                        class="h-8 text-xs bg-white text-amber-700 hover:bg-amber-50 border-amber-300"
                    >
                        <UserX class="w-3.5 h-3.5 mr-1" />
                        Nonaktifkan Terpilih
                    </Button>
                    <Button
                        variant="outline"
                        size="sm"
                        @click="exportSelectedUsers"
                        class="h-8 text-xs bg-white text-blue-700 hover:bg-blue-50 border-blue-300"
                    >
                        <Download class="w-3.5 h-3.5 mr-1" />
                        Export Terpilih
                    </Button>
                    <Button
                        variant="destructive"
                        size="sm"
                        @click="executeBulkAction('delete')"
                        class="h-8 text-xs bg-rose-600 hover:bg-rose-700 text-white"
                    >
                        <Trash2 class="w-3.5 h-3.5 mr-1" />
                        Hapus Terpilih
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        @click="selectedUserIds = []"
                        class="h-8 text-xs text-zinc-600 hover:text-zinc-900"
                    >
                        Batal
                    </Button>
                </div>
            </div>

            <!-- Filter & Search Toolbar -->
            <div
                class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-zinc-200 shadow-2xs"
            >
                <!-- Search Box -->
                <div class="relative flex-1">
                    <Search
                        class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"
                    />
                    <Input
                        v-model="searchQuery"
                        @keyup.enter="handleFilter"
                        placeholder="Cari nama pengguna, username, email, atau ruangan..."
                        class="pl-9 pr-8 h-9 text-xs"
                    />
                    <button
                        v-if="searchQuery"
                        @click="
                            searchQuery = '';
                            handleFilter();
                        "
                        type="button"
                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600"
                    >
                        <X class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Dropdown Filters -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Filter Role -->
                    <select
                        v-model="roleFilter"
                        @change="handleFilter"
                        class="h-9 px-3 text-xs font-semibold bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                    >
                        <option value="">Semua Role</option>
                        <option
                            v-for="r in roleOptions"
                            :key="r.id"
                            :value="r.id"
                        >
                            {{ r.label }}
                        </option>
                    </select>

                    <!-- Filter Status -->
                    <select
                        v-model="statusFilter"
                        @change="handleFilter"
                        class="h-9 px-3 text-xs font-semibold bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                    >
                        <option value="">Semua Status</option>
                        <option value="active">Hanya Aktif</option>
                        <option value="inactive">Hanya Nonaktif</option>
                    </select>

                    <!-- Per Page -->
                    <select
                        v-model="perPage"
                        @change="handleFilter"
                        class="h-9 px-2.5 text-xs font-semibold bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none cursor-pointer"
                    >
                        <option :value="10">10 / hal</option>
                        <option :value="25">25 / hal</option>
                        <option :value="50">50 / hal</option>
                        <option :value="100">100 / hal</option>
                    </select>

                    <Button
                        variant="secondary"
                        size="sm"
                        @click="handleFilter"
                        class="h-9 px-3 text-xs font-semibold"
                    >
                        Cari
                    </Button>

                    <Button
                        v-if="
                            searchQuery ||
                            roleFilter ||
                            statusFilter ||
                            perPage !== 10
                        "
                        variant="ghost"
                        size="sm"
                        @click="resetFilter"
                        class="h-9 px-2 text-xs text-rose-600 hover:bg-rose-50"
                        title="Reset Filter"
                    >
                        <RotateCcw class="w-3.5 h-3.5 mr-1" />
                        Reset
                    </Button>
                </div>
            </div>

            <!-- Table Card -->
            <div
                class="rounded-2xl border border-zinc-200/80 bg-white shadow-xs overflow-hidden"
            >
                <Table>
                    <TableHeader>
                        <TableRow class="bg-zinc-50/70">
                            <!-- Checkbox Select All Column -->
                            <TableHead class="w-10 text-center px-2">
                                <button
                                    type="button"
                                    @click="toggleSelectAll"
                                    class="text-zinc-500 hover:text-zinc-900 focus:outline-none"
                                    title="Pilih Semua di Halaman Ini"
                                >
                                    <CheckSquare
                                        v-if="isAllSelected"
                                        class="w-4 h-4 text-blue-600"
                                    />
                                    <Square
                                        v-else
                                        class="w-4 h-4 text-zinc-400"
                                    />
                                </button>
                            </TableHead>

                            <!-- NOMOR DI PALING KIRI -->
                            <TableHead
                                class="w-12 text-center text-xs font-semibold text-zinc-600"
                            >
                                No
                            </TableHead>

                            <!-- Nama Pengguna -->
                            <TableHead>
                                <DataTableColumnHeader
                                    title="Nama Pengguna"
                                    column="name"
                                    :sort-key="sortBy"
                                    :sort-direction="sortDir"
                                    @sort="onSort"
                                />
                            </TableHead>

                            <!-- Username -->
                            <TableHead>
                                <DataTableColumnHeader
                                    title="Username"
                                    column="username"
                                    :sort-key="sortBy"
                                    :sort-direction="sortDir"
                                    @sort="onSort"
                                />
                            </TableHead>

                            <!-- Email Resmi -->
                            <TableHead>
                                <DataTableColumnHeader
                                    title="Email Resmi"
                                    column="email"
                                    :sort-key="sortBy"
                                    :sort-direction="sortDir"
                                    @sort="onSort"
                                />
                            </TableHead>

                            <!-- Role Akses -->
                            <TableHead>
                                <DataTableColumnHeader
                                    title="Role Akses (Multi)"
                                    column="role"
                                    :sort-key="sortBy"
                                    :sort-direction="sortDir"
                                    @sort="onSort"
                                />
                            </TableHead>

                            <!-- Status Akun -->
                            <TableHead class="w-32 text-center">
                                <DataTableColumnHeader
                                    title="Status Akun"
                                    column="is_active"
                                    :sort-key="sortBy"
                                    :sort-direction="sortDir"
                                    align="center"
                                    @sort="onSort"
                                />
                            </TableHead>

                            <!-- Terdaftar -->
                            <TableHead class="w-28">
                                <DataTableColumnHeader
                                    title="Terdaftar"
                                    column="created_at"
                                    :sort-key="sortBy"
                                    :sort-direction="sortDir"
                                    @sort="onSort"
                                />
                            </TableHead>

                            <!-- Aksi -->
                            <TableHead class="text-right">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>

                    <TableBody>
                        <TableRow v-if="!users.data?.length">
                            <TableCell
                                colspan="9"
                                class="h-28 text-center text-zinc-400"
                            >
                                <div
                                    class="flex flex-col items-center justify-center gap-1.5"
                                >
                                    <Users class="w-6 h-6 text-zinc-300" />
                                    <span
                                        >Tidak ada pengguna yang cocok dengan
                                        kriteria pencarian.</span
                                    >
                                    <button
                                        v-if="
                                            searchQuery ||
                                            roleFilter ||
                                            statusFilter
                                        "
                                        @click="resetFilter"
                                        class="text-xs text-blue-600 hover:underline font-semibold mt-1"
                                    >
                                        Bersihkan filter pencarian
                                    </button>
                                </div>
                            </TableCell>
                        </TableRow>

                        <TableRow
                            v-for="(user, idx) in users.data"
                            :key="user.id"
                            class="hover:bg-zinc-50/70 transition"
                            :class="
                                selectedUserIds.includes(user.id)
                                    ? 'bg-blue-50/40'
                                    : ''
                            "
                        >
                            <!-- Row Checkbox -->
                            <TableCell class="text-center px-2">
                                <button
                                    type="button"
                                    @click="toggleSelectUser(user.id)"
                                    class="text-zinc-500 hover:text-zinc-900 focus:outline-none"
                                >
                                    <CheckSquare
                                        v-if="selectedUserIds.includes(user.id)"
                                        class="w-4 h-4 text-blue-600"
                                    />
                                    <Square
                                        v-else
                                        class="w-4 h-4 text-zinc-300 hover:text-zinc-400"
                                    />
                                </button>
                            </TableCell>

                            <!-- NOMOR DI PALING KIRI -->
                            <TableCell
                                class="text-center font-mono text-[11px] text-zinc-500 font-semibold w-12"
                            >
                                {{
                                    (users.current_page - 1) * users.per_page +
                                    idx +
                                    1
                                }}
                            </TableCell>

                            <!-- Nama & Ruangan -->
                            <TableCell>
                                <div class="flex items-center gap-2.5">
                                    <Avatar
                                        class="h-8 w-8 border border-zinc-200"
                                    >
                                        <AvatarFallback
                                            class="bg-blue-50 text-blue-700 font-bold text-xs"
                                        >
                                            {{
                                                (user.name || "U")
                                                    .substring(0, 1)
                                                    .toUpperCase()
                                            }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <div
                                            class="font-bold text-zinc-900 text-xs flex items-center gap-1.5"
                                        >
                                            {{ user.name }}
                                            <span
                                                v-if="user.id === currentAuthId"
                                                class="px-1.5 py-0.2 rounded text-[9px] bg-zinc-100 text-zinc-600 font-mono"
                                            >
                                                Anda
                                            </span>
                                        </div>
                                        <div
                                            v-if="user.room_or_desk"
                                            class="text-[10px] text-zinc-400 flex items-center gap-1 mt-0.5"
                                        >
                                            <Building2
                                                class="w-3 h-3 text-zinc-300"
                                            />
                                            {{ user.room_or_desk }}
                                        </div>
                                    </div>
                                </div>
                            </TableCell>

                            <!-- Username -->
                            <TableCell
                                class="font-mono text-zinc-600 text-[11px]"
                            >
                                {{ user.username }}
                            </TableCell>

                            <!-- Email -->
                            <TableCell class="text-zinc-600 text-[11px]">
                                {{ user.email }}
                            </TableCell>

                            <!-- Role Multi-Badge -->
                            <TableCell>
                                <div
                                    class="flex flex-wrap items-center gap-1 max-w-[260px]"
                                >
                                    <Badge
                                        v-for="rId in getUserRoleList(user)"
                                        :key="rId"
                                        :variant="getRoleBadgeVariant(rId)"
                                        class="text-[10px] py-0 px-2 font-medium"
                                    >
                                        {{ getRoleLabel(rId) }}
                                    </Badge>
                                </div>
                            </TableCell>

                            <!-- Status & Quick Toggle -->
                            <TableCell class="text-center">
                                <div
                                    class="flex items-center justify-center gap-1.5"
                                >
                                    <Badge
                                        :variant="
                                            user.is_active
                                                ? 'success'
                                                : 'destructive'
                                        "
                                        class="text-[10px] py-0 px-2"
                                    >
                                        {{
                                            user.is_active
                                                ? "Aktif"
                                                : "Nonaktif"
                                        }}
                                    </Badge>
                                    <!-- Quick Switch Toggle -->
                                    <button
                                        type="button"
                                        @click="toggleUserStatus(user)"
                                        :title="
                                            user.is_active
                                                ? 'Klik untuk nonaktifkan'
                                                : 'Klik untuk aktifkan'
                                        "
                                        class="p-1 rounded hover:bg-zinc-100 text-zinc-400 hover:text-zinc-700 transition"
                                    >
                                        <UserX
                                            v-if="user.is_active"
                                            class="w-3.5 h-3.5 text-zinc-400 hover:text-rose-600"
                                        />
                                        <UserCheck
                                            v-else
                                            class="w-3.5 h-3.5 text-zinc-400 hover:text-emerald-600"
                                        />
                                    </button>
                                </div>
                            </TableCell>

                            <!-- Terdaftar -->
                            <TableCell
                                class="text-[11px] text-zinc-500 font-mono"
                            >
                                {{
                                    user.created_at
                                        ? user.created_at.substring(0, 10)
                                        : "-"
                                }}
                            </TableCell>

                            <!-- Aksi -->
                            <TableCell class="text-right">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="openResetPassword(user)"
                                        class="h-7 px-2 text-zinc-500 hover:text-amber-700 hover:bg-amber-50"
                                        title="Reset Password Cepat"
                                    >
                                        <KeyRound class="w-3.5 h-3.5 mr-1" />
                                        Sandi
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="openEdit(user)"
                                        class="h-7 px-2 text-zinc-600 hover:text-blue-600 hover:bg-blue-50"
                                        title="Edit Pengguna"
                                    >
                                        <Pencil class="w-3.5 h-3.5 mr-1" /> Edit
                                    </Button>

                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="deleteUser(user)"
                                        :disabled="user.id === currentAuthId"
                                        class="h-7 px-2 text-rose-500 hover:text-rose-700 hover:bg-rose-50 disabled:opacity-30"
                                        title="Hapus Pengguna"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination Footer -->
                <div
                    v-if="users.links"
                    class="p-3.5 border-t border-zinc-200/80 bg-zinc-50/50 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs text-zinc-500"
                >
                    <div>
                        Menampilkan
                        <strong class="text-zinc-800">{{
                            users.from || 0
                        }}</strong>
                        -
                        <strong class="text-zinc-800">{{
                            users.to || 0
                        }}</strong>
                        dari
                        <strong class="text-zinc-800">{{
                            users.total || 0
                        }}</strong>
                        pengguna
                    </div>
                    <DataTablePagination :links="users.links" />
                </div>
            </div>
        </div>

        <!-- MODAL: TAMBAH / EDIT PENGGUNA -->
        <Dialog :open="isDialogOpen" @update:open="closeDialog">
            <DialogContent
                class="sm:max-w-[580px] p-6 max-h-[90vh] overflow-y-auto"
            >
                <DialogHeader>
                    <DialogTitle class="text-base font-bold text-zinc-900">
                        {{
                            editItem ? "Edit Pengguna" : "Tambah Pengguna Baru"
                        }}
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submit" class="space-y-4 py-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-zinc-700"
                                >Nama Lengkap</label
                            >
                            <Input
                                v-model="form.name"
                                placeholder="Contoh: Budi Santoso"
                            />
                            <span
                                v-if="form.errors.name"
                                class="text-rose-500 text-[10px]"
                            >
                                {{ form.errors.name }}
                            </span>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-zinc-700"
                                >Username</label
                            >
                            <Input
                                v-model="form.username"
                                placeholder="Username login..."
                            />
                            <span
                                v-if="form.errors.username"
                                class="text-rose-500 text-[10px]"
                            >
                                {{ form.errors.username }}
                            </span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-zinc-700"
                                >Email Resmi</label
                            >
                            <Input
                                v-model="form.email"
                                type="email"
                                placeholder="user@bpvppangkep.id"
                            />
                            <span
                                v-if="form.errors.email"
                                class="text-rose-500 text-[10px]"
                            >
                                {{ form.errors.email }}
                            </span>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-zinc-700"
                                >Ruangan / Meja (Opsional)</label
                            >
                            <Input
                                v-model="form.room_or_desk"
                                placeholder="Contoh: Gedung A / R. Kejuruan"
                            />
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-700">
                            Password
                            <span
                                v-if="editItem"
                                class="font-normal text-zinc-400"
                            >
                                (kosongkan jika tidak ingin mengubah)
                            </span>
                        </label>
                        <Input
                            v-model="form.password"
                            type="password"
                            placeholder="Minimal 8 karakter..."
                        />
                        <span
                            v-if="form.errors.password"
                            class="text-rose-500 text-[10px]"
                        >
                            {{ form.errors.password }}
                        </span>
                    </div>

                    <!-- Role Akses Multi-Select -->
                    <div class="space-y-2 pt-2 border-t border-zinc-100">
                        <div class="flex items-center justify-between">
                            <div>
                                <label
                                    class="text-xs font-bold text-zinc-800 flex items-center gap-1.5"
                                >
                                    <Shield class="w-3.5 h-3.5 text-blue-600" />
                                    Role Akses Pengguna (Bisa Pilih Lebih dari
                                    1)
                                </label>
                                <p class="text-[11px] text-zinc-500">
                                    Tentukan peran dan hak akses kerja pengguna
                                    dalam portal sistem
                                </p>
                            </div>
                            <div class="flex items-center gap-2 text-[11px]">
                                <button
                                    type="button"
                                    @click="selectAllRoles"
                                    class="text-blue-600 font-semibold hover:underline"
                                >
                                    Pilih Semua
                                </button>
                                <span class="text-zinc-300">|</span>
                                <button
                                    type="button"
                                    @click="resetRoles"
                                    class="text-zinc-500 hover:underline"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>

                        <!-- Checkbox Cards Grid -->
                        <div
                            class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-52 overflow-y-auto p-1.5 border border-zinc-200/80 rounded-xl bg-zinc-50/50"
                        >
                            <div
                                v-for="r in roleOptions"
                                :key="r.id"
                                @click="toggleRole(r.id)"
                                class="flex items-start gap-2.5 p-2 rounded-lg border cursor-pointer transition-all select-none"
                                :class="
                                    form.roles.includes(r.id)
                                        ? 'bg-blue-50 border-blue-400 text-blue-900 shadow-2xs'
                                        : 'bg-white border-zinc-200 hover:border-zinc-300 text-zinc-700'
                                "
                            >
                                <div class="mt-0.5">
                                    <div
                                        class="w-4 h-4 rounded border flex items-center justify-center transition-colors"
                                        :class="
                                            form.roles.includes(r.id)
                                                ? 'bg-blue-600 border-blue-600 text-white'
                                                : 'border-zinc-300 bg-white'
                                        "
                                    >
                                        <Check
                                            v-if="form.roles.includes(r.id)"
                                            class="w-3 h-3 stroke-[3]"
                                        />
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div
                                        class="flex items-center justify-between gap-1"
                                    >
                                        <span
                                            class="text-xs font-semibold truncate"
                                            >{{ r.label }}</span
                                        >
                                    </div>
                                    <p
                                        class="text-[10px] text-zinc-500 line-clamp-1 mt-0.5 leading-tight"
                                    >
                                        {{ r.desc }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <span
                            v-if="form.errors.roles || form.errors.role"
                            class="text-rose-500 text-[10px] block"
                        >
                            {{ form.errors.roles || form.errors.role }}
                        </span>
                    </div>

                    <!-- Status Akun Aktif -->
                    <div class="flex items-center gap-2.5 pt-1">
                        <input
                            type="checkbox"
                            id="user_active"
                            v-model="form.is_active"
                            class="h-4 w-4 rounded-sm border-zinc-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label
                            for="user_active"
                            class="text-xs font-bold text-zinc-800 cursor-pointer select-none"
                        >
                            Akun Aktif (Dapat Login ke Portal)
                        </label>
                    </div>

                    <DialogFooter class="pt-3 border-t border-zinc-100">
                        <Button
                            type="button"
                            variant="outline"
                            @click="closeDialog"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="form.processing"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold"
                        >
                            {{
                                editItem
                                    ? "Simpan Perubahan"
                                    : "Simpan Pengguna"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- MODAL: RESET PASSWORD CEPAT -->
        <Dialog
            :open="isResetPasswordOpen"
            @update:open="isResetPasswordOpen = false"
        >
            <DialogContent class="sm:max-w-[420px] p-6">
                <DialogHeader>
                    <DialogTitle
                        class="text-base font-bold text-zinc-900 flex items-center gap-2"
                    >
                        <KeyRound class="w-4 h-4 text-amber-600" />
                        Reset Password Pengguna
                    </DialogTitle>
                </DialogHeader>

                <p class="text-xs text-zinc-500 mt-1">
                    Atur kata sandi baru untuk akun
                    <strong class="text-zinc-900">{{
                        resetPasswordUser?.name
                    }}</strong>
                    ({{ resetPasswordUser?.email }}).
                </p>

                <form
                    @submit.prevent="submitResetPassword"
                    class="space-y-3.5 py-3"
                >
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-700"
                            >Password Baru</label
                        >
                        <Input
                            v-model="resetPasswordForm.password"
                            type="password"
                            placeholder="Minimal 8 karakter..."
                        />
                        <span
                            v-if="resetPasswordForm.errors.password"
                            class="text-rose-500 text-[10px]"
                        >
                            {{ resetPasswordForm.errors.password }}
                        </span>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-700"
                            >Konfirmasi Password Baru</label
                        >
                        <Input
                            v-model="resetPasswordForm.password_confirmation"
                            type="password"
                            placeholder="Ulangi password baru..."
                        />
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isResetPasswordOpen = false"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="resetPasswordForm.processing"
                            class="bg-amber-600 hover:bg-amber-700 text-white font-semibold"
                        >
                            Simpan Password Baru
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>
