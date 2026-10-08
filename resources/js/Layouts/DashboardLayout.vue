<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from "vue";
import { Head, Link, usePage, router, useForm } from "@inertiajs/vue3";
import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    LayoutDashboard,
    Globe,
    Newspaper,
    Building2,
    Scale,
    Link2,
    GraduationCap,
    Users,
    Settings,
    LogOut,
    Menu,
    ChevronLeft,
    ChevronRight,
    ChevronDown,
    ExternalLink,
    Sparkles,
    FileText,
    ClipboardList,
    FileSpreadsheet,
    Calendar,
    BarChart3,
    Copy,
    Check,
    KeyRound,
    Eye,
    EyeOff,
    Lock,
    Loader2,
} from "lucide-vue-next";

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const settings = computed(() => page.props.settings || {});

// --- GLOBAL ACTIVITY LOADING STATE ---
const isNavigating = ref(false);
const activeActionMessage = ref("");
let unregisterStart = null;
let unregisterFinish = null;

onMounted(() => {
    unregisterStart = router.on("start", (event) => {
        isNavigating.value = true;
        const method = (event.detail?.visit?.method || "GET").toUpperCase();
        if (method === "POST" || method === "PUT" || method === "PATCH") {
            activeActionMessage.value = "Menyimpan data & memperbarui...";
        } else if (method === "DELETE") {
            activeActionMessage.value = "Menghapus data...";
        } else {
            activeActionMessage.value = "Memuat data...";
        }
    });

    unregisterFinish = router.on("finish", () => {
        isNavigating.value = false;
        activeActionMessage.value = "";
    });
});

onUnmounted(() => {
    if (unregisterStart) unregisterStart();
    if (unregisterFinish) unregisterFinish();
});

// --- LOGIKA SALIN TAUTAN (COPY TO CLIPBOARD) ---
const copiedHref = ref("");
let copyTimer = null;

const copyToClipboard = (href) => {
    if (typeof window === "undefined") return;
    const fullUrl =
        href.startsWith("http://") || href.startsWith("https://")
            ? href
            : `${window.location.origin}${href}`;

    const setCopied = () => {
        copiedHref.value = href;
        if (copyTimer) clearTimeout(copyTimer);
        copyTimer = setTimeout(() => {
            copiedHref.value = "";
        }, 2200);
    };

    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard
            .writeText(fullUrl)
            .then(setCopied)
            .catch(() => {
                fallbackCopy(fullUrl, setCopied);
            });
    } else {
        fallbackCopy(fullUrl, setCopied);
    }
};

const fallbackCopy = (text, callback) => {
    const textArea = document.createElement("textarea");
    textArea.value = text;
    textArea.style.position = "fixed";
    textArea.style.opacity = "0";
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();
    try {
        document.execCommand("copy");
        callback();
    } catch (err) {
        console.error("Gagal menyalin tautan", err);
    }
    document.body.removeChild(textArea);
};

// --- LOGIKA UBAH PASSWORD PENGGUNA ---
const isPasswordDialogOpen = ref(false);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const passwordSuccessMessage = ref("");

const passwordForm = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const openPasswordDialog = () => {
    passwordForm.reset();
    passwordForm.clearErrors();
    showCurrentPassword.value = false;
    showNewPassword.value = false;
    showConfirmPassword.value = false;
    passwordSuccessMessage.value = "";
    isPasswordDialogOpen.value = true;
};

const handleLogout = () => {
    router.post(
        "/logout",
        {},
        {
            onFinish: () => {
                // Hard reload to completely reset all SPA memory, CSRF tokens, and cookies
                window.location.href = "/login";
            },
        },
    );
};

const closePasswordDialog = () => {
    isPasswordDialogOpen.value = false;
    passwordForm.reset();
    passwordForm.clearErrors();
};

const submitChangePassword = () => {
    passwordSuccessMessage.value = "";
    passwordForm.put("/user/password", {
        preserveScroll: true,
        onSuccess: () => {
            passwordSuccessMessage.value = "Password Anda berhasil diperbarui!";
            passwordForm.reset();
            setTimeout(() => {
                closePasswordDialog();
            }, 1500);
        },
    });
};

const faviconUrl = computed(() => {
    const path = settings.value?.favicon_path;
    if (!path) return "/favicon.ico";
    if (path.startsWith("http://") || path.startsWith("https://")) return path;
    const clean = path.replace(/^\/?storage\//, "").replace(/^\//, "");
    const v = settings.value?.updated_at
        ? new Date(settings.value.updated_at).getTime()
        : Date.now();
    return `/storage/${clean}?v=${v}`;
});

const websiteName = computed(
    () => settings.value?.website_name || "BPVP Pangkep",
);

const userRoles = computed(() => {
    if (Array.isArray(user.value?.roles) && user.value.roles.length > 0) {
        return user.value.roles;
    }
    const roleStr = user.value?.role || "";
    return roleStr
        .split(",")
        .map((r) => r.trim())
        .filter(Boolean);
});

const hasRole = (allowedRoles) => {
    if (userRoles.value.includes("super_admin")) return true;
    if (!allowedRoles || allowedRoles.length === 0) return true;
    return allowedRoles.some((r) => {
        if (userRoles.value.includes(r)) return true;
        if (r === "admin_website" && userRoles.value.includes("admin"))
            return true;
        if (r === "admin" && userRoles.value.includes("admin_website"))
            return true;
        if (r === "admin_shortlink" && userRoles.value.includes("shortlink"))
            return true;
        if (r === "shortlink" && userRoles.value.includes("admin_shortlink"))
            return true;
        if (r === "admin_lms" && userRoles.value.includes("lms")) return true;
        if (r === "lms" && userRoles.value.includes("admin_lms")) return true;
        return false;
    });
};

const hasVisibleItems = (section) => {
    if (!hasRole(section.roles)) return false;
    return section.items.some((i) => hasRole(i.roles));
};

const getSavedSidebarState = () => {
    if (typeof window !== "undefined" && window.localStorage) {
        const saved = localStorage.getItem("bpvp_sidebar_open");
        if (saved !== null) {
            return saved === "true";
        }
    }
    return true;
};

const isSidebarOpen = ref(getSavedSidebarState());
const isMobileSidebarOpen = ref(false);

const setSidebarOpen = (val) => {
    isSidebarOpen.value = val;
    if (typeof window !== "undefined" && window.localStorage) {
        localStorage.setItem("bpvp_sidebar_open", String(val));
    }
};

const activeSubmenu = ref({
    "Modul Website": false,
    "Modul Shortlink": false,
    "Modul Sosmed Hub": false,
    "Modul LMS": false,
});

const isModulWebsiteRoute = computed(() => {
    const url = page.url || "";
    const cleanPath = url.split("?")[0];
    return (
        cleanPath.startsWith("/admin/profil") ||
        cleanPath.startsWith("/admin/informasi") ||
        cleanPath.startsWith("/admin/informasi-publik") ||
        cleanPath.startsWith("/admin/pelayanan") ||
        cleanPath.startsWith("/admin/berita") ||
        cleanPath.startsWith("/admin/jdih") ||
        cleanPath.startsWith("/admin/settings")
    );
});

const isModulShortlinkRoute = computed(() => {
    const url = page.url || "";
    const cleanPath = url.split("?")[0];
    return (
        cleanPath.startsWith("/admin/shortlinks") ||
        cleanPath.startsWith("/shortlink")
    );
});

const isModulSosmedRoute = computed(() => {
    const url = page.url || "";
    const cleanPath = url.split("?")[0];
    return (
        cleanPath.startsWith("/admin/sosmedhub") ||
        cleanPath.startsWith("/sosmed")
    );
});

const isModulLmsRoute = computed(() => {
    const url = page.url || "";
    const cleanPath = url.split("?")[0];
    return cleanPath.startsWith("/admin/lms");
});

const isSubmenuOpen = (menuName) => {
    return Boolean(activeSubmenu.value[menuName]);
};

const isSubmenuActive = (menuName) => {
    if (menuName === "Modul Website") return isModulWebsiteRoute.value;
    if (menuName === "Modul Shortlink") return isModulShortlinkRoute.value;
    if (menuName === "Modul Sosmed Hub") return isModulSosmedRoute.value;
    if (menuName === "Modul LMS") return isModulLmsRoute.value;
    return false;
};

const getActiveModuleFromRoute = () => {
    if (isModulWebsiteRoute.value) return "Modul Website";
    if (isModulShortlinkRoute.value) return "Modul Shortlink";
    if (isModulSosmedRoute.value) return "Modul Sosmed Hub";
    if (isModulLmsRoute.value) return "Modul LMS";
    return null;
};

const syncSubmenusWithRoute = () => {
    const activeMod = getActiveModuleFromRoute();
    activeSubmenu.value["Modul Website"] = activeMod === "Modul Website";
    activeSubmenu.value["Modul Shortlink"] = activeMod === "Modul Shortlink";
    activeSubmenu.value["Modul Sosmed Hub"] = activeMod === "Modul Sosmed Hub";
    activeSubmenu.value["Modul LMS"] = activeMod === "Modul LMS";
};

const toggleSubmenu = (menuName) => {
    const nextState = !activeSubmenu.value[menuName];
    // Accordion: keep only clicked active
    activeSubmenu.value["Modul Website"] = false;
    activeSubmenu.value["Modul Shortlink"] = false;
    activeSubmenu.value["Modul Sosmed Hub"] = false;
    activeSubmenu.value["Modul LMS"] = false;
    activeSubmenu.value[menuName] = nextState;
};

const handleSubmenuClick = (menuName) => {
    if (!isSidebarOpen.value) {
        // In collapsed mode, navigate to the first child link
        const section = navigation.find((s) =>
            s.items.some((i) => i.name === menuName),
        );
        const item = section?.items.find((i) => i.name === menuName);
        if (item?.children?.[0]?.href) {
            router.visit(item.children[0].href);
        }
        return;
    }
    toggleSubmenu(menuName);
};

const currentBasePath = computed(() => (page.url || "").split("?")[0]);

// Immediate watch so initial mount or subsequent Inertia navigation keeps active module standby open
watch(
    currentBasePath,
    () => {
        isMobileSidebarOpen.value = false;
        syncSubmenusWithRoute();
    },
    { immediate: true },
);

const isChildActive = (href) => {
    if (!href) return false;
    const currentUrl = page.url || "";

    // If href contains query parameter (e.g. ?tab=leads)
    if (href.includes("?")) {
        const [hrefPath, hrefQuery] = href.split("?");
        const [currentPath, currentQuery] = currentUrl.split("?");
        if (currentPath !== hrefPath) return false;

        const hrefParams = new URLSearchParams(hrefQuery);
        const currentParams = new URLSearchParams(currentQuery || "");
        for (const [key, value] of hrefParams.entries()) {
            if (currentParams.get(key) !== value) return false;
        }
        return true;
    }

    // Href has no query parameter
    const cleanCurrentPath = currentUrl.split("?")[0].replace(/\/+$/, "");
    const cleanHrefPath = href.split("?")[0].replace(/\/+$/, "");

    // If on /admin/shortlinks?tab=leads or ?tab=settings, do not highlight base /admin/shortlinks
    if (
        currentUrl.includes("tab=") &&
        !currentUrl.includes("tab=shortlinks") &&
        cleanHrefPath === "/admin/shortlinks"
    ) {
        return false;
    }

    // If on /admin/sosmedhub?tab=something, do not highlight base /admin/sosmedhub if href has no tab or different tab
    if (
        currentUrl.includes("tab=") &&
        !currentUrl.includes("tab=kalender") &&
        cleanHrefPath === "/admin/sosmedhub" &&
        !href.includes("tab=")
    ) {
        return false;
    }

    // If on /admin/lms/participants, do not highlight base /admin/lms
    if (
        cleanHrefPath === "/admin/lms" &&
        cleanCurrentPath.startsWith("/admin/lms/participants")
    ) {
        return false;
    }

    if (cleanCurrentPath === cleanHrefPath) {
        return true;
    }
    return cleanCurrentPath.startsWith(cleanHrefPath + "/");
};

const navigation = [
    {
        category: "Utama",
        roles: [],
        items: [
            {
                name: "Dashboard Portal",
                icon: LayoutDashboard,
                href: "/dashboard",
                roles: [],
            },
            {
                name: "Modul Website",
                icon: Globe,
                roles: ["super_admin", "admin_website"],
                children: [
                    {
                        name: "Profil Balai",
                        href: "/admin/profil",
                        icon: Building2,
                    },
                    {
                        name: "Informasi Balai",
                        href: "/admin/informasi",
                        icon: GraduationCap,
                    },
                    {
                        name: "Informasi Publik (PPID)",
                        href: "/admin/informasi-publik",
                        icon: FileText,
                    },
                    {
                        name: "Pelayanan Publik",
                        href: "/admin/pelayanan",
                        icon: ClipboardList,
                    },
                    {
                        name: "Berita & Galeri",
                        href: "/admin/berita",
                        icon: Newspaper,
                    },
                    {
                        name: "Produk Hukum (JDIH)",
                        href: "/admin/jdih",
                        icon: Scale,
                    },
                    {
                        name: "Konfigurasi Website",
                        href: "/admin/settings",
                        icon: Settings,
                    },
                    {
                        name: "Lihat Website",
                        href: "/",
                        icon: Globe,
                        isShortcut: true,
                        external: true,
                        shortcutLabel: "Publik",
                    },
                ],
            },
            {
                name: "Modul Shortlink",
                icon: Link2,
                roles: ["super_admin", "admin_shortlink"],
                children: [
                    {
                        name: "Data Shortlink",
                        href: "/admin/shortlinks",
                        icon: Link2,
                    },
                    {
                        name: "Data Leads Masuk",
                        href: "/admin/shortlinks?tab=leads",
                        icon: Users,
                    },
                    {
                        name: "Integrasi Spreadsheet",
                        href: "/admin/shortlinks?tab=settings",
                        icon: FileSpreadsheet,
                    },
                ],
            },
            {
                name: "Modul Sosmed Hub",
                icon: Sparkles,
                roles: [
                    "super_admin",
                    "medsos_planner",
                    "medsos_editor",
                    "medsos_instruktur",
                    "medsos_admin_platform",
                ],
                children: [
                    {
                        name: "Kalender Konten",
                        href: "/admin/sosmedhub?tab=kalender",
                        icon: Calendar,
                    },
                    {
                        name: "Daftar Konten",
                        href: "/admin/sosmedhub?tab=daftar",
                        icon: FileText,
                    },
                    {
                        name: "Statistik Tim",
                        href: "/admin/sosmedhub?tab=statistik_tim",
                        icon: Users,
                    },
                    {
                        name: "Statistik Medsos",
                        href: "/admin/sosmedhub?tab=statistik_medsos",
                        icon: BarChart3,
                    },
                    {
                        name: "Pengaturan",
                        href: "/admin/sosmedhub?tab=pengaturan",
                        icon: Settings,
                    },
                ],
            },
            {
                name: "Modul LMS",
                icon: GraduationCap,
                roles: ["super_admin", "admin_lms"],
                children: [
                    {
                        name: "Daftar Pelatihan",
                        href: "/admin/lms",
                        roles: ["super_admin", "admin_lms"],
                    },
                    {
                        name: "Data Seluruh Peserta",
                        href: "/admin/lms/participants",
                        roles: ["super_admin"],
                    },
                    {
                        name: "Portal Belajar LMS",
                        href: "/lms",
                        icon: GraduationCap,
                        isShortcut: true,
                        external: true,
                        shortcutLabel: "Siswa",
                    },
                ],
            },
        ],
    },
    {
        category: "Pengaturan Sistem",
        roles: ["super_admin"],
        items: [
            {
                name: "Manajemen Pengguna",
                icon: Users,
                href: "/admin/users",
                roles: ["super_admin"],
            },
        ],
    },
];
</script>

<template>
    <Head>
        <link rel="icon" type="image/x-icon" :href="faviconUrl" />
        <link rel="icon" type="image/png" :href="faviconUrl" />
        <link rel="shortcut icon" :href="faviconUrl" />
        <link rel="apple-touch-icon" :href="faviconUrl" />
    </Head>

    <!-- Floating Global Activity Indicator for all Inertia mutations / visits -->
    <Transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0 -translate-y-2 scale-95"
        enter-to-class="opacity-100 translate-y-0 scale-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100 translate-y-0 scale-100"
        leave-to-class="opacity-0 -translate-y-2 scale-95"
    >
        <div
            v-if="isNavigating"
            class="fixed top-4 right-4 z-[9999] flex items-center gap-2.5 px-4 py-2 bg-slate-900/90 text-white backdrop-blur-md border border-slate-700/80 rounded-2xl shadow-xl text-xs font-semibold pointer-events-none tracking-wide"
        >
            <Loader2 class="w-4 h-4 animate-spin text-blue-400 shrink-0" />
            <span>{{ activeActionMessage || "Sedang memproses..." }}</span>
        </div>
    </Transition>

    <div
        class="h-screen w-screen overflow-hidden bg-slate-50 flex font-sans text-slate-900 antialiased selection:bg-blue-600 selection:text-white"
    >
        <!-- MOBILE BACKDROP -->
        <div
            v-if="isMobileSidebarOpen"
            @click="isMobileSidebarOpen = false"
            class="fixed inset-0 bg-slate-950/40 backdrop-blur-xs z-40 lg:hidden"
        ></div>

        <!-- SIDEBAR -->
        <aside
            :class="[
                'fixed lg:static inset-y-0 left-0 z-50 flex flex-col h-full bg-white border-r border-slate-200 shadow-sm transition-all duration-300 ease-in-out shrink-0 select-none',
                isSidebarOpen ? 'w-64' : 'w-20',
                isMobileSidebarOpen
                    ? 'translate-x-0'
                    : '-translate-x-full lg:translate-x-0',
            ]"
        >
            <!-- BRAND / LOGO AREA -->
            <div
                class="h-16 flex items-center px-4 border-b border-slate-100 shrink-0"
                :class="isSidebarOpen ? 'justify-between' : 'justify-center'"
            >
                <template v-if="isSidebarOpen">
                    <Link
                        href="/dashboard"
                        class="flex items-center gap-2.5 min-w-0 group"
                    >
                        <img
                            v-if="faviconUrl"
                            :src="faviconUrl"
                            class="w-8 h-8 rounded-xl object-contain shrink-0 bg-white p-0.5 border border-slate-200/80 shadow-2xs group-hover:scale-105 transition-transform"
                            alt="Logo"
                        />
                        <div class="flex flex-col min-w-0">
                            <span
                                class="font-black text-sm tracking-tight text-slate-900 block leading-tight group-hover:text-blue-600 transition-colors truncate"
                            >
                                {{ websiteName }}
                            </span>
                            <span
                                class="text-[10px] font-bold text-blue-600 uppercase tracking-wider block mt-0.5"
                            >
                                Portal Terpadu
                            </span>
                        </div>
                    </Link>

                    <button
                        type="button"
                        @click.stop="setSidebarOpen(false)"
                        class="flex w-8 h-8 rounded-lg items-center justify-center text-slate-400 hover:text-slate-700 hover:bg-slate-100 transition-colors cursor-pointer shrink-0"
                        title="Ciutkan Sidebar"
                    >
                        <ChevronLeft class="w-4 h-4" />
                    </button>
                </template>

                <template v-else>
                    <button
                        type="button"
                        @click.stop="setSidebarOpen(true)"
                        class="flex w-10 h-10 rounded-xl items-center justify-center hover:bg-blue-50 transition-colors cursor-pointer group"
                        :title="`Buka Navigasi Penuh (${websiteName})`"
                    >
                        <img
                            v-if="faviconUrl"
                            :src="faviconUrl"
                            class="w-7 h-7 rounded-lg object-contain group-hover:scale-110 transition-transform"
                            alt="Logo"
                        />
                        <ChevronRight
                            v-else
                            class="w-5 h-5 text-slate-600 group-hover:text-blue-600"
                        />
                    </button>
                </template>
            </div>

            <!-- NAVIGATION ITEMS (SCROLLABLE INDEPENDENTLY) -->
            <nav class="flex-1 min-h-0 overflow-y-auto px-3 py-3 space-y-5">
                <template v-for="(section, secIdx) in navigation" :key="secIdx">
                    <div v-if="hasVisibleItems(section)" class="space-y-1">
                        <div
                            v-show="isSidebarOpen"
                            class="px-3 text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5 font-mono"
                        >
                            {{ section.category }}
                        </div>
                        <div
                            v-if="!isSidebarOpen && secIdx > 0"
                            class="w-8 h-px bg-slate-200 mx-auto my-2"
                        ></div>

                        <template
                            v-for="(item, itemIdx) in section.items"
                            :key="itemIdx"
                        >
                            <div v-if="hasRole(item.roles)">
                                <!-- SUBMENU (ACCORDION) -->
                                <div v-if="item.children" class="space-y-0.5">
                                    <button
                                        type="button"
                                        @click="handleSubmenuClick(item.name)"
                                        :title="!isSidebarOpen ? item.name : ''"
                                        :class="[
                                            'w-full flex items-center rounded-xl text-xs font-semibold transition-all cursor-pointer select-none',
                                            isSidebarOpen
                                                ? 'justify-between px-3 py-2.5'
                                                : 'justify-center p-2.5',
                                            isSubmenuOpen(item.name) ||
                                            isSubmenuActive(item.name)
                                                ? 'bg-slate-100/90 text-slate-900 font-bold'
                                                : 'text-slate-600 hover:bg-slate-100/60 hover:text-slate-900',
                                        ]"
                                    >
                                        <div
                                            :class="[
                                                'flex items-center',
                                                isSidebarOpen
                                                    ? 'gap-3 min-w-0'
                                                    : 'justify-center',
                                            ]"
                                        >
                                            <component
                                                :is="item.icon"
                                                :class="[
                                                    'w-4 h-4 shrink-0 transition-colors',
                                                    isSubmenuOpen(item.name) ||
                                                    isSubmenuActive(item.name)
                                                        ? 'text-blue-600'
                                                        : 'text-slate-400',
                                                ]"
                                            />
                                            <span
                                                v-show="isSidebarOpen"
                                                class="truncate"
                                                >{{ item.name }}</span
                                            >
                                        </div>
                                        <ChevronDown
                                            v-show="isSidebarOpen"
                                            :class="[
                                                'w-3.5 h-3.5 text-slate-400 transition-transform duration-200 shrink-0',
                                                isSubmenuOpen(item.name)
                                                    ? 'rotate-180 text-blue-600'
                                                    : '',
                                            ]"
                                        />
                                    </button>

                                    <div
                                        v-show="
                                            !isSidebarOpen ||
                                            isSubmenuOpen(item.name)
                                        "
                                        :class="[
                                            isSidebarOpen
                                                ? 'pl-4 pr-1 py-1 space-y-1'
                                                : 'space-y-1 py-1',
                                        ]"
                                    >
                                        <template
                                            v-for="(
                                                child, childIdx
                                            ) in item.children"
                                            :key="childIdx"
                                        >
                                            <!-- KHUSUS ITEM SHORTCUT (ADA TOMBOL KLIK & SALIN) -->
                                            <div
                                                v-if="
                                                    child.isShortcut &&
                                                    (!child.roles ||
                                                        hasRole(child.roles))
                                                "
                                                :class="[
                                                    'group/shortcut flex items-center rounded-xl text-xs transition-all select-none border border-slate-200/90 bg-slate-50/80 hover:bg-blue-50/60 hover:border-blue-300',
                                                    isSidebarOpen
                                                        ? 'justify-between px-2.5 py-1.5 gap-1.5'
                                                        : 'justify-center p-2',
                                                ]"
                                            >
                                                <a
                                                    :href="child.href"
                                                    target="_blank"
                                                    :title="`Buka ${child.name} di tab baru`"
                                                    class="flex items-center gap-2 min-w-0 flex-1 text-slate-700 hover:text-blue-600 transition-colors cursor-pointer"
                                                >
                                                    <component
                                                        v-if="child.icon"
                                                        :is="child.icon"
                                                        class="w-3.5 h-3.5 shrink-0 text-blue-600"
                                                    />
                                                    <span
                                                        v-show="isSidebarOpen"
                                                        class="truncate font-semibold text-[11px]"
                                                        >{{ child.name }}</span
                                                    >
                                                    <span
                                                        v-if="
                                                            child.shortcutLabel &&
                                                            isSidebarOpen
                                                        "
                                                        class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-100/90 text-blue-700 uppercase tracking-tight shrink-0 font-mono"
                                                    >
                                                        {{
                                                            child.shortcutLabel
                                                        }}
                                                    </span>
                                                </a>

                                                <!-- Tombol Aksi: Salin & Klik (Buka) -->
                                                <div
                                                    v-show="isSidebarOpen"
                                                    class="flex items-center gap-1 shrink-0"
                                                >
                                                    <!-- Tombol Salin Tautan -->
                                                    <button
                                                        type="button"
                                                        @click.stop.prevent="
                                                            copyToClipboard(
                                                                child.href,
                                                            )
                                                        "
                                                        class="p-1 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-white transition-all cursor-pointer relative"
                                                        :title="
                                                            copiedHref ===
                                                            child.href
                                                                ? 'Tautan Berhasil Disalin!'
                                                                : 'Salin Tautan'
                                                        "
                                                    >
                                                        <Check
                                                            v-if="
                                                                copiedHref ===
                                                                child.href
                                                            "
                                                            class="w-3.5 h-3.5 text-emerald-600"
                                                        />
                                                        <Copy
                                                            v-else
                                                            class="w-3.5 h-3.5"
                                                        />

                                                        <!-- Tooltip Feedback Tersalin -->
                                                        <span
                                                            v-if="
                                                                copiedHref ===
                                                                child.href
                                                            "
                                                            class="absolute -top-7 right-0 text-[10px] font-bold px-1.5 py-0.5 rounded bg-slate-900 text-white shadow-xs whitespace-nowrap z-50 pointer-events-none"
                                                        >
                                                            Tersalin!
                                                        </span>
                                                    </button>

                                                    <!-- Tombol Klik Buka Halaman -->
                                                    <a
                                                        :href="child.href"
                                                        target="_blank"
                                                        class="p-1 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-white transition-all cursor-pointer"
                                                        title="Buka di Tab Baru"
                                                    >
                                                        <ExternalLink
                                                            class="w-3.5 h-3.5"
                                                        />
                                                    </a>
                                                </div>
                                            </div>

                                            <!-- ITEM MENU STANDAR -->
                                            <Link
                                                v-else-if="
                                                    !child.roles ||
                                                    hasRole(child.roles)
                                                "
                                                :href="child.href"
                                                :title="child.name"
                                                :class="[
                                                    'flex items-center rounded-xl text-xs font-semibold transition-all select-none',
                                                    isSidebarOpen
                                                        ? 'gap-2.5 px-3 py-2'
                                                        : 'justify-center p-2.5',
                                                    isChildActive(child.href)
                                                        ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-md shadow-blue-500/20'
                                                        : 'text-slate-600 hover:text-blue-600 hover:bg-blue-50/70',
                                                ]"
                                            >
                                                <component
                                                    v-if="child.icon"
                                                    :is="child.icon"
                                                    :class="[
                                                        'w-4 h-4 shrink-0 transition-colors',
                                                        isChildActive(
                                                            child.href,
                                                        )
                                                            ? 'text-white'
                                                            : 'text-slate-400',
                                                    ]"
                                                />
                                                <span
                                                    v-show="isSidebarOpen"
                                                    class="truncate"
                                                    >{{ child.name }}</span
                                                >
                                            </Link>
                                        </template>
                                    </div>
                                </div>

                                <!-- EXTERNAL LINK -->
                                <a
                                    v-else-if="item.external"
                                    :href="item.href"
                                    target="_blank"
                                    :title="!isSidebarOpen ? item.name : ''"
                                    :class="[
                                        'flex items-center rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100 hover:text-slate-900 transition-all select-none',
                                        isSidebarOpen
                                            ? 'justify-between px-3 py-2'
                                            : 'justify-center p-2.5',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'flex items-center',
                                            isSidebarOpen
                                                ? 'gap-3 min-w-0'
                                                : 'justify-center',
                                        ]"
                                    >
                                        <component
                                            :is="item.icon"
                                            class="w-4 h-4 text-slate-400 shrink-0"
                                        />
                                        <span
                                            v-show="isSidebarOpen"
                                            class="truncate"
                                            >{{ item.name }}</span
                                        >
                                    </div>
                                    <ExternalLink
                                        v-show="isSidebarOpen"
                                        class="w-3.5 h-3.5 text-slate-400 shrink-0"
                                    />
                                </a>

                                <!-- SINGLE ROUTE LINK -->
                                <Link
                                    v-else
                                    :href="item.href"
                                    :title="!isSidebarOpen ? item.name : ''"
                                    :class="[
                                        'flex items-center rounded-xl text-xs font-semibold transition-all select-none',
                                        isSidebarOpen
                                            ? 'justify-between px-3 py-2'
                                            : 'justify-center p-2.5',
                                        isChildActive(item.href)
                                            ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-bold shadow-md shadow-blue-500/20'
                                            : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                                    ]"
                                >
                                    <div
                                        :class="[
                                            'flex items-center',
                                            isSidebarOpen
                                                ? 'gap-3 min-w-0'
                                                : 'justify-center',
                                        ]"
                                    >
                                        <component
                                            :is="item.icon"
                                            :class="[
                                                'w-4 h-4 shrink-0 transition-colors',
                                                isChildActive(item.href)
                                                    ? 'text-white'
                                                    : 'text-slate-400',
                                            ]"
                                        />
                                        <span
                                            v-show="isSidebarOpen"
                                            class="truncate"
                                            >{{ item.name }}</span
                                        >
                                    </div>
                                    <span
                                        v-if="item.badge && isSidebarOpen"
                                        class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700 shrink-0"
                                    >
                                        {{ item.badge }}
                                    </span>
                                </Link>
                            </div>
                        </template>
                    </div>
                </template>
            </nav>

            <!-- UNIFIED USER IDENTITY & LOGOUT FOOTER (ALWAYS PINNED AT BOTTOM) -->
            <div
                class="p-3 border-t border-slate-200/80 bg-slate-50/70 shrink-0 mt-auto"
            >
                <div
                    v-if="isSidebarOpen"
                    class="p-2 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between gap-2"
                >
                    <!-- Tombol Trigger Ubah Password (Klik User Profile) -->
                    <button
                        type="button"
                        @click="openPasswordDialog"
                        class="flex items-center gap-2.5 min-w-0 flex-1 p-1 -m-0.5 rounded-xl hover:bg-slate-100/80 transition-all cursor-pointer group text-left"
                        title="Klik untuk ubah password akun Anda"
                    >
                        <Avatar
                            class="h-9 w-9 ring-2 ring-blue-500/20 group-hover:ring-blue-500/50 shadow-xs shrink-0 transition-all"
                        >
                            <AvatarFallback
                                class="bg-gradient-to-tr from-blue-600 to-indigo-600 text-white text-xs font-black group-hover:scale-105 transition-transform"
                            >
                                {{
                                    (user?.name || "U")
                                        .substring(0, 1)
                                        .toUpperCase()
                                }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 flex-1">
                            <div
                                class="font-bold text-xs text-slate-900 truncate group-hover:text-blue-600 transition-colors flex items-center gap-1"
                                :title="user?.name"
                            >
                                <span class="truncate">{{
                                    user?.name || "Pengguna"
                                }}</span>
                                <KeyRound
                                    class="w-3 h-3 text-slate-400 group-hover:text-blue-600 shrink-0 opacity-0 group-hover:opacity-100 transition-opacity"
                                />
                            </div>
                            <div
                                class="text-[10px] text-slate-500 font-medium truncate flex items-center gap-1.5 mt-0.5"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"
                                ></span>
                                <span
                                    class="capitalize font-semibold text-blue-600 truncate"
                                    >{{ user?.role || "user" }}</span
                                >
                            </div>
                        </div>
                    </button>

                    <!-- Integrated Logout Button -->
                    <button
                        type="button"
                        @click="handleLogout"
                        class="p-2 rounded-xl text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer shrink-0"
                        title="Keluar / Logout"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>

                <!-- Collapsed view -->
                <div v-else class="flex flex-col items-center gap-2 p-1">
                    <button
                        type="button"
                        @click="openPasswordDialog"
                        class="rounded-full ring-2 ring-blue-500/20 hover:ring-blue-500/60 p-0.5 transition-all cursor-pointer group"
                        title="Klik untuk ubah kata sandi"
                    >
                        <Avatar
                            class="h-9 w-9 shadow-xs"
                            :title="user?.name || 'Pengguna'"
                        >
                            <AvatarFallback
                                class="bg-gradient-to-tr from-blue-600 to-indigo-600 text-white text-xs font-black group-hover:scale-105 transition-transform"
                            >
                                {{
                                    (user?.name || "U")
                                        .substring(0, 1)
                                        .toUpperCase()
                                }}
                            </AvatarFallback>
                        </Avatar>
                    </button>
                    <button
                        type="button"
                        @click="handleLogout"
                        class="p-2 rounded-xl text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer"
                        title="Keluar / Logout"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- MAIN CONTENT AREA -->
        <div class="flex-1 h-full flex flex-col min-w-0 overflow-hidden">
            <!-- MOBILE TOPBAR (HANYA MUNCUL DI LAYAR MOBILE UNTUK TOMBOL MENU) -->
            <header
                class="lg:hidden h-14 shrink-0 bg-white/90 backdrop-blur-md border-b border-slate-200/80 px-4 flex items-center justify-between sticky top-0 z-30 shadow-2xs"
            >
                <div class="flex items-center gap-2.5">
                    <button
                        @click="isMobileSidebarOpen = true"
                        class="p-2 rounded-xl text-slate-600 hover:bg-slate-100 cursor-pointer"
                        title="Buka Navigasi"
                    >
                        <Menu class="w-5 h-5" />
                    </button>
                    <img
                        v-if="faviconUrl"
                        :src="faviconUrl"
                        class="w-6 h-6 rounded-md object-contain shrink-0"
                        alt="Logo"
                    />
                    <span class="text-xs font-bold text-slate-800">{{
                        websiteName
                    }}</span>
                </div>
            </header>

            <!-- MAIN BODY (SCROLLS INDEPENDENTLY) -->
            <main class="flex-1 min-h-0 overflow-y-auto p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>

        <!-- DIALOG UBAH PASSWORD PENGGUNA -->
        <Dialog :open="isPasswordDialogOpen" @update:open="closePasswordDialog">
            <DialogContent class="sm:max-w-[430px] p-6">
                <DialogHeader>
                    <DialogTitle
                        class="text-base font-bold text-slate-900 flex items-center gap-2"
                    >
                        <div
                            class="w-8 h-8 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center border border-blue-100"
                        >
                            <KeyRound class="w-4 h-4" />
                        </div>
                        <span>Ubah Kata Sandi Akun</span>
                    </DialogTitle>
                    <DialogDescription class="text-xs text-slate-500 mt-1">
                        Masukkan password lama Anda, lalu buat password baru dan
                        ulangi verifikasi 2 kali.
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="passwordSuccessMessage"
                    class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold flex items-center gap-2"
                >
                    <Check class="w-4 h-4 text-emerald-600 shrink-0" />
                    <span>{{ passwordSuccessMessage }}</span>
                </div>

                <form
                    @submit.prevent="submitChangePassword"
                    class="space-y-4 py-2"
                >
                    <!-- Kolom 1: Password Lama -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 block"
                            >Password Lama
                            <span class="text-rose-500">*</span></label
                        >
                        <div class="relative">
                            <Input
                                :type="
                                    showCurrentPassword ? 'text' : 'password'
                                "
                                v-model="passwordForm.current_password"
                                placeholder="Masukkan password saat ini"
                                class="pr-10 text-xs"
                                :class="
                                    passwordForm.errors.current_password
                                        ? 'border-rose-400 focus-visible:ring-rose-400'
                                        : ''
                                "
                                required
                            />
                            <button
                                type="button"
                                @click="
                                    showCurrentPassword = !showCurrentPassword
                                "
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff
                                    v-if="showCurrentPassword"
                                    class="w-4 h-4"
                                />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p
                            v-if="passwordForm.errors.current_password"
                            class="text-[11px] text-rose-500 font-medium"
                        >
                            {{ passwordForm.errors.current_password }}
                        </p>
                    </div>

                    <!-- Kolom 2: Password Baru -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 block"
                            >Password Baru
                            <span class="text-rose-500">*</span></label
                        >
                        <div class="relative">
                            <Input
                                :type="showNewPassword ? 'text' : 'password'"
                                v-model="passwordForm.password"
                                placeholder="Minimal 8 karakter"
                                class="pr-10 text-xs"
                                :class="
                                    passwordForm.errors.password
                                        ? 'border-rose-400 focus-visible:ring-rose-400'
                                        : ''
                                "
                                required
                            />
                            <button
                                type="button"
                                @click="showNewPassword = !showNewPassword"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff
                                    v-if="showNewPassword"
                                    class="w-4 h-4"
                                />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p
                            v-if="passwordForm.errors.password"
                            class="text-[11px] text-rose-500 font-medium"
                        >
                            {{ passwordForm.errors.password }}
                        </p>
                    </div>

                    <!-- Kolom 3: Konfirmasi Password Baru (Verifikasi 2x) -->
                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-slate-700 block"
                            >Konfirmasi Password Baru
                            <span class="text-rose-500">*</span></label
                        >
                        <div class="relative">
                            <Input
                                :type="
                                    showConfirmPassword ? 'text' : 'password'
                                "
                                v-model="passwordForm.password_confirmation"
                                placeholder="Ketik ulang password baru Anda"
                                class="pr-10 text-xs"
                                :class="
                                    passwordForm.errors.password_confirmation
                                        ? 'border-rose-400 focus-visible:ring-rose-400'
                                        : ''
                                "
                                required
                            />
                            <button
                                type="button"
                                @click="
                                    showConfirmPassword = !showConfirmPassword
                                "
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 cursor-pointer"
                                tabindex="-1"
                            >
                                <EyeOff
                                    v-if="showConfirmPassword"
                                    class="w-4 h-4"
                                />
                                <Eye v-else class="w-4 h-4" />
                            </button>
                        </div>
                        <p
                            v-if="passwordForm.errors.password_confirmation"
                            class="text-[11px] text-rose-500 font-medium"
                        >
                            {{ passwordForm.errors.password_confirmation }}
                        </p>
                    </div>

                    <DialogFooter
                        class="pt-3 border-t border-slate-100 flex items-center justify-end gap-2"
                    >
                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="closePasswordDialog"
                            :disabled="passwordForm.processing"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            size="sm"
                            class="bg-blue-600 hover:bg-blue-700 text-white"
                            :loading="passwordForm.processing"
                        >
                            {{ passwordForm.processing ? "Menyimpan..." : "Perbarui Password" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
