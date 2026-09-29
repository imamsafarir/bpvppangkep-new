<script setup>
import { ref, computed, watch } from "vue";
import { Head, Link, usePage, router } from "@inertiajs/vue3";
import { Avatar, AvatarFallback } from "@/Components/ui/avatar";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import PwaInstallPrompt from "@/Components/PwaInstallPrompt.vue";
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
} from "lucide-vue-next";

const page = usePage();
const user = computed(() => page.props.auth?.user || {});
const settings = computed(() => page.props.settings || {});

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

const isSubmenuOpen = (menuName) => {
    return Boolean(activeSubmenu.value[menuName]);
};

const isSubmenuActive = (menuName) => {
    if (menuName === "Modul Website") return isModulWebsiteRoute.value;
    if (menuName === "Modul Shortlink") return isModulShortlinkRoute.value;
    if (menuName === "Modul Sosmed Hub") return isModulSosmedRoute.value;
    return false;
};

const getActiveModuleFromRoute = () => {
    if (isModulWebsiteRoute.value) return "Modul Website";
    if (isModulShortlinkRoute.value) return "Modul Shortlink";
    if (isModulSosmedRoute.value) return "Modul Sosmed Hub";
    return null;
};

const syncSubmenusWithRoute = () => {
    const activeMod = getActiveModuleFromRoute();
    activeSubmenu.value["Modul Website"] = activeMod === "Modul Website";
    activeSubmenu.value["Modul Shortlink"] = activeMod === "Modul Shortlink";
    activeSubmenu.value["Modul Sosmed Hub"] = activeMod === "Modul Sosmed Hub";
};

const toggleSubmenu = (menuName) => {
    const nextState = !activeSubmenu.value[menuName];
    // Accordion: keep only clicked active
    activeSubmenu.value["Modul Website"] = false;
    activeSubmenu.value["Modul Shortlink"] = false;
    activeSubmenu.value["Modul Sosmed Hub"] = false;
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
                name: "Lihat Website",
                icon: ExternalLink,
                href: "/",
                external: true,
                roles: [],
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
                                        <Link
                                            v-for="(
                                                child, childIdx
                                            ) in item.children"
                                            :key="childIdx"
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
                                                    isChildActive(child.href)
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
                    class="p-2.5 rounded-2xl bg-white border border-slate-200/90 shadow-2xs flex items-center justify-between gap-2.5"
                >
                    <div class="flex items-center gap-2.5 min-w-0 flex-1">
                        <Avatar
                            class="h-9 w-9 ring-2 ring-blue-500/20 shadow-xs shrink-0"
                        >
                            <AvatarFallback
                                class="bg-gradient-to-tr from-blue-600 to-indigo-600 text-white text-xs font-black"
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
                                class="font-bold text-xs text-slate-900 truncate"
                                :title="user?.name"
                            >
                                {{ user?.name || "Pengguna" }}
                            </div>
                            <div
                                class="text-[10px] text-slate-500 font-medium truncate flex items-center gap-1.5 mt-0.5"
                            >
                                <span
                                    class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"
                                ></span>
                                <span
                                    class="capitalize font-semibold text-blue-600"
                                    >{{ user?.role || "user" }}</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Integrated Logout Button -->
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="p-2 rounded-xl text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer shrink-0"
                        title="Keluar / Logout"
                    >
                        <LogOut class="w-4 h-4" />
                    </Link>
                </div>

                <!-- Collapsed view -->
                <div v-else class="flex flex-col items-center gap-2 p-1">
                    <Avatar
                        class="h-9 w-9 ring-2 ring-blue-500/20 shadow-xs"
                        :title="user?.name || 'Pengguna'"
                    >
                        <AvatarFallback
                            class="bg-gradient-to-tr from-blue-600 to-indigo-600 text-white text-xs font-black"
                        >
                            {{
                                (user?.name || "U")
                                    .substring(0, 1)
                                    .toUpperCase()
                            }}
                        </AvatarFallback>
                    </Avatar>
                    <Link
                        href="/logout"
                        method="post"
                        as="button"
                        class="p-2 rounded-xl text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition-colors cursor-pointer"
                        title="Keluar / Logout"
                    >
                        <LogOut class="w-4 h-4" />
                    </Link>
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

        <!-- PWA Install Prompt & Update Handler -->
        <PwaInstallPrompt />
    </div>
</template>
