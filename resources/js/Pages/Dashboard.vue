<script setup>
import { computed } from "vue";
import { Head, Link, usePage } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter,
} from "@/Components/ui/card";
import { Badge } from "@/Components/ui/badge";
import { Button } from "@/Components/ui/button";
import {
    Globe,
    Instagram,
    Link2,
    Users,
    ArrowRight,
    Mail,
    AtSign,
    Sparkles,
    Shield,
    Newspaper,
    FileText,
    MousePointerClick,
    ExternalLink,
    CheckCircle2,
    Lock,
} from "lucide-vue-next";

const props = defineProps({
    stats: {
        type: Object,
        default: () => ({
            total_berita: 0,
            total_galeri: 0,
            total_dokumen: 0,
            total_shortlinks: 0,
            total_clicks: 0,
            total_users: 0,
        }),
    },
});

const page = usePage();
const user = computed(() => page.props.auth?.user || {});

const userRoles = computed(() => {
    const authUser = user.value;
    if (
        authUser?.roles &&
        Array.isArray(authUser.roles) &&
        authUser.roles.length > 0
    ) {
        return authUser.roles;
    }
    const roleStr = authUser?.role || "";
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

// Modul-modul dengan identitas warna hidup (color themes)
const quickAccessApps = [
    {
        title: "Website & Profil Balai",
        desc: "Kelola artikel berita, foto galeri, sambutan pimpinan, PPID, dan informasi kejuruan.",
        icon: Globe,
        href: "/admin/berita",
        badge: "Modul Website",
        roles: ["super_admin", "admin_website", "admin"],
        accentColor: "border-t-blue-500",
        iconBg: "bg-blue-50 text-blue-600 border-blue-100",
        badgeVariant: "info",
        badgeText: "Website Balai",
    },
    {
        title: "Modul Sosmed Hub",
        desc: "Perencanaan materi, alur produksi video/grafis, editorial review, dan publikasi media sosial resmi BPVP.",
        icon: Sparkles,
        href: "/admin/sosmedhub",
        badge: "Modul Sosmed Hub",
        roles: [
            "super_admin",
            "medsos_planner",
            "medsos_editor",
            "medsos_instruktur",
            "medsos_admin_platform",
        ],
        accentColor: "border-t-pink-500",
        iconBg: "bg-pink-50 text-pink-600 border-pink-100",
        badgeVariant: "destructive",
        badgeText: "Media Sosial",
    },
    {
        title: "Modul Shortlink",
        desc: "Pemendek tautan resmi BPVP dengan form penangkapan data (lead) dan sinkronisasi spreadsheet.",
        icon: Link2,
        href: "/admin/shortlinks",
        badge: "Modul Shortlink",
        roles: ["super_admin", "admin_shortlink", "shortlink"],
        accentColor: "border-t-emerald-500",
        iconBg: "bg-emerald-50 text-emerald-600 border-emerald-100",
        badgeVariant: "success",
        badgeText: "Shortlink & Leads",
    },
    {
        title: "Manajemen Pengguna",
        desc: "Konfigurasi akun pegawai, penugasan hak akses role portal, dan log keamanan.",
        icon: Users,
        href: "/admin/users",
        badge: "Sistem",
        roles: ["super_admin"],
        accentColor: "border-t-amber-500",
        iconBg: "bg-amber-50 text-amber-600 border-amber-100",
        badgeVariant: "secondary",
        badgeText: "Super Admin",
    },
];
</script>

<template>
    <Head title="Dashboard Terpadu" />

    <DashboardLayout>
        <div class="space-y-8 max-w-7xl mx-auto">
            <!-- 1. HERO BANNER VIBRANT GRADIENT -->
            <div
                class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-blue-950 to-indigo-950 text-white p-6 sm:p-8 md:p-10 shadow-xl border border-blue-900/40"
            >
                <!-- Decorative Glows -->
                <div
                    class="absolute -right-20 -top-20 w-80 h-80 bg-blue-500/20 rounded-full blur-3xl pointer-events-none"
                ></div>
                <div
                    class="absolute -left-20 -bottom-20 w-80 h-80 bg-indigo-500/15 rounded-full blur-3xl pointer-events-none"
                ></div>

                <div
                    class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6"
                >
                    <div class="space-y-3 max-w-2xl">
                        <div
                            class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-blue-500/15 border border-blue-400/20 text-xs font-semibold text-blue-300"
                        >
                            <span
                                class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"
                            ></span>
                            <span>Portal Terpadu BPVP Pangkep</span>
                        </div>

                        <h2
                            class="text-2xl sm:text-3xl lg:text-4xl font-black tracking-tight text-white leading-tight"
                        >
                            Selamat Datang, {{ user?.name || "Pengguna" }}!
                        </h2>
                        <p
                            class="text-xs sm:text-sm text-slate-300 leading-relaxed font-normal"
                        >
                            Portal sistem terpadu untuk pengelolaan website
                            resmi, media sosial, publikasi data, dan aplikasi
                            internal balai.
                        </p>

                        <div
                            class="pt-2 flex flex-wrap items-center gap-2.5 text-xs font-mono"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-xl border border-white/10 text-slate-200"
                            >
                                <Mail class="w-3.5 h-3.5 text-blue-400" />
                                {{ user?.email }}
                            </span>
                            <span
                                v-if="user?.username"
                                class="inline-flex items-center gap-1.5 bg-white/10 backdrop-blur-sm px-3 py-1.5 rounded-xl border border-white/10 text-slate-200"
                            >
                                <AtSign class="w-3.5 h-3.5 text-blue-400" />
                                {{ user?.username }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 bg-blue-600/30 text-blue-200 px-3 py-1.5 rounded-xl border border-blue-400/20 font-bold uppercase tracking-wider text-[11px]"
                            >
                                <Shield class="w-3.5 h-3.5 text-blue-300" />
                                {{ user?.role || "user" }}
                            </span>
                        </div>
                    </div>

                    <!-- Quick Action Button in Banner -->
                    <div class="shrink-0 flex sm:flex-col gap-2">
                        <a
                            href="/"
                            target="_blank"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-500 text-white text-xs font-bold shadow-lg shadow-blue-600/30 transition-all hover:scale-105 cursor-pointer"
                        >
                            <Globe class="w-4 h-4" />
                            <span>Buka Website</span>
                            <ExternalLink class="w-3.5 h-3.5 opacity-80" />
                        </a>
                    </div>
                </div>
            </div>

            <!-- 2. QUICK METRIC STAT CARDS -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- Stat 1: Berita -->
                <div
                    class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex items-center justify-between"
                >
                    <div class="space-y-1">
                        <span
                            class="text-xs font-bold text-slate-500 uppercase tracking-wider block"
                        >
                            Berita & Publikasi
                        </span>
                        <div
                            class="text-2xl font-black text-slate-900 tracking-tight"
                        >
                            {{ stats.total_berita }}
                            <span class="text-xs font-semibold text-slate-400"
                                >Artikel</span
                            >
                        </div>
                        <span
                            class="text-[11px] text-blue-600 font-semibold block"
                        >
                            + {{ stats.total_galeri }} Foto Kegiatan
                        </span>
                    </div>
                    <div
                        class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 flex items-center justify-center shrink-0"
                    >
                        <Newspaper class="w-6 h-6" />
                    </div>
                </div>

                <!-- Stat 2: Dokumen -->
                <div
                    class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex items-center justify-between"
                >
                    <div class="space-y-1">
                        <span
                            class="text-xs font-bold text-slate-500 uppercase tracking-wider block"
                        >
                            Dokumen & JDIH
                        </span>
                        <div
                            class="text-2xl font-black text-slate-900 tracking-tight"
                        >
                            {{ stats.total_dokumen }}
                            <span class="text-xs font-semibold text-slate-400"
                                >Berkas</span
                            >
                        </div>
                        <span
                            class="text-[11px] text-purple-600 font-semibold block"
                        >
                            PPID & Produk Hukum
                        </span>
                    </div>
                    <div
                        class="w-12 h-12 rounded-2xl bg-purple-50 text-purple-600 border border-purple-100 flex items-center justify-center shrink-0"
                    >
                        <FileText class="w-6 h-6" />
                    </div>
                </div>

                <!-- Stat 3: Shortlinks -->
                <div
                    class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex items-center justify-between"
                >
                    <div class="space-y-1">
                        <span
                            class="text-xs font-bold text-slate-500 uppercase tracking-wider block"
                        >
                            Shortlinks Aktif
                        </span>
                        <div
                            class="text-2xl font-black text-slate-900 tracking-tight"
                        >
                            {{ stats.total_shortlinks }}
                            <span class="text-xs font-semibold text-slate-400"
                                >Link</span
                            >
                        </div>
                        <span
                            class="text-[11px] text-emerald-600 font-semibold block"
                        >
                            {{ stats.total_clicks }} Total Klik Pengunjung
                        </span>
                    </div>
                    <div
                        class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 border border-emerald-100 flex items-center justify-center shrink-0"
                    >
                        <Link2 class="w-6 h-6" />
                    </div>
                </div>

                <!-- Stat 4: Users -->
                <div
                    class="rounded-2xl bg-white p-5 border border-slate-200/80 shadow-xs hover:shadow-md transition-shadow flex items-center justify-between"
                >
                    <div class="space-y-1">
                        <span
                            class="text-xs font-bold text-slate-500 uppercase tracking-wider block"
                        >
                            Pengguna Sistem
                        </span>
                        <div
                            class="text-2xl font-black text-slate-900 tracking-tight"
                        >
                            {{ stats.total_users }}
                            <span class="text-xs font-semibold text-slate-400"
                                >Akun</span
                            >
                        </div>
                        <span
                            class="text-[11px] text-amber-600 font-semibold block"
                        >
                            Pegawai & Administrator
                        </span>
                    </div>
                    <div
                        class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 border border-amber-100 flex items-center justify-center shrink-0"
                    >
                        <Users class="w-6 h-6" />
                    </div>
                </div>
            </div>

            <!-- 3. MODUL APLIKASI (VIBRANT CARDS GRID) -->
            <div class="space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3
                            class="text-lg font-bold text-slate-900 tracking-tight flex items-center gap-2"
                        >
                            <span>Modul & Aplikasi Terpadu</span>
                            <span
                                class="text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700"
                            >
                                Aktif
                            </span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Pilih modul kerja yang ingin Anda kelola sesuai hak
                            akses Anda
                        </p>
                    </div>
                </div>

                <div
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5"
                >
                    <template v-for="(app, idx) in quickAccessApps" :key="idx">
                        <!-- KARTU AKTIF / DAPAT DIAKSES -->
                        <div
                            v-if="hasRole(app.roles)"
                            :class="[
                                'rounded-3xl border border-slate-200/80 bg-white p-6 shadow-xs hover:shadow-xl hover:shadow-slate-200/50 hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between group relative overflow-hidden border-t-4',
                                app.accentColor,
                            ]"
                        >
                            <div class="space-y-4">
                                <div class="flex items-start justify-between">
                                    <div
                                        :class="[
                                            'w-12 h-12 rounded-2xl flex items-center justify-center transition-transform group-hover:scale-110 duration-200 shadow-2xs border',
                                            app.iconBg,
                                        ]"
                                    >
                                        <component
                                            :is="app.icon"
                                            class="w-6 h-6"
                                        />
                                    </div>
                                    <Badge
                                        :variant="app.badgeVariant"
                                        class="text-[11px] font-semibold py-0.5 px-2.5"
                                    >
                                        {{ app.badgeText }}
                                    </Badge>
                                </div>

                                <div class="space-y-1.5">
                                    <h4
                                        class="font-extrabold text-slate-900 text-base group-hover:text-blue-600 transition-colors leading-snug"
                                    >
                                        {{ app.title }}
                                    </h4>
                                    <p
                                        class="text-xs text-slate-500 leading-relaxed font-normal"
                                    >
                                        {{ app.desc }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="pt-5 mt-4 border-t border-slate-100 flex items-center justify-between"
                            >
                                <Link
                                    :href="app.href"
                                    class="inline-flex items-center gap-2 text-xs font-bold text-slate-800 group-hover:text-blue-600 transition-colors"
                                >
                                    <span>Buka Modul</span>
                                    <ArrowRight
                                        class="w-4 h-4 text-slate-400 group-hover:text-blue-600 group-hover:translate-x-1.5 transition-all"
                                    />
                                </Link>
                                <span
                                    class="text-[10px] text-slate-400 font-mono"
                                >
                                    Kelola &rsaquo;
                                </span>
                            </div>
                        </div>

                        <!-- KARTU TERKUNCI (GAMBAR KUNCI KARENA TIDAK ADA AKSES) -->
                        <div
                            v-else
                            class="rounded-3xl border border-slate-200 bg-slate-50/80 p-6 shadow-2xs flex flex-col justify-between relative overflow-hidden border-t-4 border-t-slate-300 select-none opacity-85"
                        >
                            <!-- Watermark Gambar Kunci Besar di Latar Belakang -->
                            <div
                                class="absolute -right-3 -bottom-3 text-slate-200/50 pointer-events-none"
                            >
                                <Lock class="w-24 h-24 stroke-[1.2]" />
                            </div>

                            <div class="space-y-4 relative z-10">
                                <div class="flex items-start justify-between">
                                    <div
                                        class="relative w-12 h-12 rounded-2xl bg-slate-200/70 text-slate-400 border border-slate-300/60 flex items-center justify-center shadow-2xs"
                                    >
                                        <component
                                            :is="app.icon"
                                            class="w-6 h-6 opacity-40"
                                        />
                                        <!-- Gambar Kunci Badge di Sudut Icon -->
                                        <div
                                            class="absolute -bottom-1.5 -right-1.5 w-5 h-5 rounded-full bg-amber-500 text-white flex items-center justify-center shadow-xs ring-2 ring-white"
                                            title="Modul Terkunci"
                                        >
                                            <Lock class="w-3 h-3" />
                                        </div>
                                    </div>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-slate-200/90 text-slate-600 border border-slate-300/80 text-[11px] font-bold shadow-2xs"
                                    >
                                        <Lock class="w-3 h-3 text-amber-600" />
                                        Terkunci
                                    </span>
                                </div>

                                <div class="space-y-1.5">
                                    <h4
                                        class="font-extrabold text-slate-600 text-base leading-snug"
                                    >
                                        {{ app.title }}
                                    </h4>
                                    <p
                                        class="text-xs text-slate-400 leading-relaxed font-normal"
                                    >
                                        {{ app.desc }}
                                    </p>
                                </div>
                            </div>

                            <div
                                class="pt-5 mt-4 border-t border-slate-200/80 flex items-center justify-between relative z-10"
                            >
                                <div
                                    class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-400"
                                >
                                    <Lock class="w-3.5 h-3.5 text-slate-400" />
                                    <span>Tidak Ada Akses</span>
                                </div>
                                <span
                                    class="text-[10px] text-slate-400 font-medium bg-slate-200/60 px-2 py-0.5 rounded border border-slate-300/60"
                                >
                                    Hubungi Super Admin
                                </span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
