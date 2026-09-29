<script setup>
import { ref, onMounted, onUnmounted } from "vue";
import { Link } from "@inertiajs/vue3";
import { triggerInstallPrompt } from "@/pwa";

const props = defineProps({
    settings: {
        type: Object,
        default: () => ({}),
    },
    isHome: {
        type: Boolean,
        default: false,
    },
});

const isScrolled = ref(false);
const openMenu = ref(null);
const mobileMenu = ref(false);

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

const navigationMenu = {
    Profil: {
        type: "dropdown",
        key: "profil",
        links: [
            { label: "Sambutan Kepala", url: "/profil/sambutan-kepala" },
            { label: "Tentang Kami", url: "/profil/tentang-kami" },
            { label: "PPID", url: "/profil/ppid-pelayanan" },
            { label: "Visi & Misi", url: "/profil/visi-misi" },
            { label: "Tugas & Fungsi", url: "/profil/tugas-fungsi" },
            {
                label: "Struktur Organisasi",
                url: "/profil/struktur-organisasi",
            },
            { label: "Pejabat Struktural", url: "/profil/pejabat-struktural" },
        ],
    },
    Informasi: {
        type: "dropdown",
        key: "informasi",
        links: [
            { label: "Kejuruan", url: "/informasi/kejuruan" },
            { label: "Gedung & Fasilitas", url: "/informasi/gedung-fasilitas" },
            {
                label: "Ruang Kelas & Workshop",
                url: "/informasi/ruang-kelas-workshop",
            },
            { label: "Alumni", url: "/informasi/alumni" },
            { label: "Testimoni", url: "/informasi/testimoni" },
        ],
    },
    "Informasi Publik": {
        type: "dropdown",
        key: "infopublik",
        links: [
            { label: "Informasi Berkala", url: "/informasi-publik/berkala" },
            {
                label: "Informasi Serta Merta",
                url: "/informasi-publik/serta-merta",
            },
            {
                label: "Informasi Setiap Saat",
                url: "/informasi-publik/setiap-saat",
            },
        ],
    },
    "Pelayanan Publik": {
        type: "dropdown",
        key: "pelayanan",
        links: [
            { label: "Maklumat Pelayanan", url: "/pelayanan-publik/maklumat" },
            {
                label: "Standar Pelayanan Publik",
                url: "/pelayanan-publik/standar-pelayanan",
            },
            {
                label: "Alur Pelayanan",
                url: "/pelayanan-publik/alur-pelayanan",
            },
            {
                label: "Survey Kepuasan Masyarakat",
                url: "/pelayanan-publik/survey-kepuasan",
            },
            {
                label: "Survey Kebutuhan Pelatihan",
                url: "/pelayanan-publik/survey-kebutuhan",
            },
            {
                label: "Survey Kebekerjaan",
                url: "/pelayanan-publik/survey-kebekerjaan",
            },
            {
                label: "Indeks Kepuasan Masyarakat",
                url: "/pelayanan-publik/indeks-kepuasan",
            },
        ],
    },
    Berita: {
        type: "dropdown",
        key: "berita",
        links: [
            { label: "Berita", url: "/berita-informasi/daftar-berita" },
            {
                label: "Galeri Kegiatan",
                url: "/berita-informasi/galeri-kegiatan",
            },
        ],
    },
    JDIH: {
        type: "link",
        url: "/jdih",
    },
};

onMounted(() => {
    isScrolled.value = window.scrollY > 20;
    window.addEventListener("scroll", handleScroll);
});

onUnmounted(() => {
    window.removeEventListener("scroll", handleScroll);
});
</script>

<template>
    <header
        id="navbar-container"
        class="fixed top-0 left-0 z-40 w-full transition-all duration-500"
    >
        <!-- Running Text -->
        <div
            v-if="
                settings?.is_running_text_active &&
                settings?.running_text_content
            "
            class="relative z-50 w-full select-none overflow-hidden border-b border-white/10 py-2.5 text-white shadow-sm"
            style="background-color: #1b446f"
        >
            <div
                class="animate-marquee items-center whitespace-nowrap text-xs font-medium uppercase tracking-wide flex"
            >
                <span class="mx-4 shrink-0 font-bold text-amber-300"
                    >⚠️ INFO TERKINI:</span
                >
                <span class="shrink-0 pr-10">{{
                    settings.running_text_content
                }}</span>
            </div>
        </div>

        <!-- Navbar Utama -->
        <div
            id="navbar-main"
            class="w-full border-b py-3.5 transition-all duration-500"
            :class="
                isHome
                    ? isScrolled
                        ? 'border-amber-400/30 bg-[#15406a] shadow-md'
                        : 'border-transparent bg-transparent shadow-none'
                    : 'border-amber-400/30 bg-[#15406a] shadow-md'
            "
        >
            <div
                class="mx-auto flex w-full max-w-[1700px] items-center justify-between gap-3 px-3 sm:px-4 lg:px-5 xl:px-6"
            >
                <!-- Logo & Brand -->
                <Link
                    href="/"
                    class="flex shrink-0 select-none items-center gap-2 text-white"
                >
                    <img
                        :src="
                            settings?.logo_path
                                ? `/storage/${settings.logo_path}`
                                : 'https://kemnaker.go.id/assets/images/logo.png'
                        "
                        :alt="settings?.website_name ?? 'BPVP Pangkep'"
                        class="h-9 w-auto shrink-0 object-contain xl:h-10"
                    />
                    <div class="shrink-0 leading-tight">
                        <span
                            class="block whitespace-nowrap text-sm font-black tracking-tight drop-shadow-md sm:text-base 2xl:text-lg"
                        >
                            {{ settings?.website_name ?? "BPVP PANGKEP" }}
                        </span>
                        <span
                            class="block whitespace-nowrap text-[8px] font-bold uppercase tracking-wider text-amber-400 sm:text-[9px]"
                        >
                            KEMNAKER RI
                        </span>
                    </div>
                </Link>

                <!-- Desktop Nav -->
                <nav
                    class="ml-auto hidden min-w-0 flex-1 flex-nowrap items-center justify-end gap-2 whitespace-nowrap text-[11px] font-semibold text-white lg:flex xl:gap-3 xl:text-xs 2xl:gap-5 2xl:text-sm"
                >
                    <Link
                        href="/"
                        class="shrink-0 whitespace-nowrap text-white/90 drop-shadow-sm transition-colors duration-200 hover:text-amber-400"
                    >
                        Beranda
                    </Link>

                    <template
                        v-for="(menu, title) in navigationMenu"
                        :key="title"
                    >
                        <!-- Dropdown -->
                        <div
                            v-if="menu.type === 'dropdown'"
                            class="relative shrink-0 py-2"
                            @mouseenter="openMenu = menu.key"
                            @mouseleave="openMenu = null"
                        >
                            <button
                                type="button"
                                @click="
                                    openMenu =
                                        openMenu === menu.key ? null : menu.key
                                "
                                class="flex shrink-0 cursor-pointer items-center gap-1 whitespace-nowrap text-white/90 drop-shadow-sm transition-colors duration-200 hover:text-amber-400 focus:outline-none"
                            >
                                <span>{{ title }}</span>
                                <svg
                                    class="h-3.5 w-3.5 shrink-0 transition-transform duration-200"
                                    :class="
                                        openMenu === menu.key
                                            ? 'rotate-180 text-amber-400'
                                            : ''
                                    "
                                    fill="none"
                                    stroke="currentColor"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        stroke-width="2.5"
                                        d="M19 9l-7 7-7-7"
                                    />
                                </svg>
                            </button>

                            <!-- Dropdown Menu -->
                            <div
                                v-show="openMenu === menu.key"
                                class="absolute left-0 mt-2 min-w-[200px] rounded-xl bg-white p-2 shadow-2xl border border-slate-100 text-slate-800 z-50 text-xs font-medium"
                            >
                                <Link
                                    v-for="link in menu.links"
                                    :key="link.url"
                                    :href="link.url"
                                    @click="openMenu = null"
                                    class="block rounded-lg px-3 py-2 text-slate-700 hover:bg-amber-50 hover:text-amber-700 transition"
                                >
                                    {{ link.label }}
                                </Link>
                            </div>
                        </div>

                        <!-- Single Link -->
                        <Link
                            v-else
                            :href="menu.url"
                            class="shrink-0 whitespace-nowrap text-white/90 drop-shadow-sm transition-colors duration-200 hover:text-amber-400"
                        >
                            {{ title }}
                        </Link>
                    </template>

                    <!-- Install Super APP Quick Button (Desktop) -->
                    <button
                        @click="triggerInstallPrompt"
                        type="button"
                        class="hidden xl:inline-flex items-center gap-1.5 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-900 font-bold text-[11px] px-3 py-1.5 rounded-full shadow-sm transition active:scale-95 cursor-pointer shrink-0 ml-2"
                        title="Pasang BPVP Pangkep - Super APP"
                    >
                        <i class="fas fa-download text-[10px]"></i>
                        <span>Super APP</span>
                    </button>
                </nav>

                <!-- Mobile Menu Button -->
                <button
                    @click="mobileMenu = !mobileMenu"
                    type="button"
                    class="lg:hidden p-2 rounded-lg text-white hover:bg-white/10 cursor-pointer"
                >
                    <i
                        :class="mobileMenu ? 'fas fa-times' : 'fas fa-bars'"
                        class="text-xl"
                    ></i>
                </button>
            </div>
        </div>

        <!-- Mobile Drawer -->
        <div
            v-if="mobileMenu"
            class="lg:hidden bg-[#15406a] border-b border-white/10 p-4 text-white text-sm"
        >
            <Link
                href="/"
                @click="mobileMenu = false"
                class="block py-2 font-bold hover:text-amber-400"
                >Beranda</Link
            >
            <div
                v-for="(menu, title) in navigationMenu"
                :key="title"
                class="border-t border-white/10 py-2"
            >
                <template v-if="menu.type === 'dropdown'">
                    <span
                        class="block text-xs font-bold uppercase tracking-wider text-amber-400 mb-1"
                        >{{ title }}</span
                    >
                    <Link
                        v-for="link in menu.links"
                        :key="link.url"
                        :href="link.url"
                        @click="mobileMenu = false"
                        class="block pl-3 py-1.5 text-xs text-white/80 hover:text-white"
                    >
                        {{ link.label }}
                    </Link>
                </template>
                <Link
                    v-else
                    :href="menu.url"
                    @click="mobileMenu = false"
                    class="block font-bold hover:text-amber-400"
                    >{{ title }}</Link
                >
            </div>

            <!-- Install Button inside mobile drawer -->
            <div class="pt-3 mt-2 border-t border-white/10">
                <button
                    @click="
                        mobileMenu = false;
                        triggerInstallPrompt();
                    "
                    type="button"
                    class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 to-amber-500 hover:from-amber-300 hover:to-amber-400 text-slate-900 font-bold text-xs py-2.5 px-4 rounded-xl shadow transition active:scale-95 cursor-pointer"
                >
                    <i class="fas fa-mobile-alt"></i>
                    <span>Pasang BPVP Pangkep - Super APP</span>
                </button>
            </div>
        </div>
    </header>
</template>

<style scoped>
.animate-marquee {
    display: inline-flex;
    width: max-content;
    min-width: 100%;
    padding-left: 100%;
    animation: marqueeAnimation 50s linear infinite;
}

@keyframes marqueeAnimation {
    0% {
        transform: translate3d(0, 0, 0);
    }
    100% {
        transform: translate3d(-100%, 0, 0);
    }
}
</style>
