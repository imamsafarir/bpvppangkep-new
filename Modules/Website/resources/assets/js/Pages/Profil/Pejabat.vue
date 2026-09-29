<script setup>
import { ref, computed } from "vue";
import { Head, Link } from "@inertiajs/vue3";
import AppLayout from "../../Layouts/AppLayout.vue";

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    profil: { type: Object, default: () => ({}) },
});

const openModal = ref(false);
const selectedPejabat = ref(null);

const pejabatList = computed(() => {
    const raw = props.profil?.pejabat_struktural;
    if (!raw) return [];
    if (typeof raw === "string") {
        try {
            return JSON.parse(raw);
        } catch {
            return [];
        }
    }
    return Array.isArray(raw) ? raw : [];
});

const openPejabatModal = (pejabat) => {
    selectedPejabat.value = pejabat;
    openModal.value = true;
};

const closeModal = () => {
    openModal.value = false;
    selectedPejabat.value = null;
};
</script>

<template>
    <Head title="Profil Pejabat Struktural" />
    <AppLayout :settings="settings">
        <main class="pt-32 pb-16 min-h-screen">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav
                    class="flex items-center gap-2 text-xs font-semibold text-slate-400 mb-8 uppercase tracking-wider select-none"
                >
                    <Link href="/" class="hover:text-blue-600 transition-colors"
                        >Home</Link
                    >
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-slate-500">Profil</span>
                    <i class="fas fa-chevron-right text-[9px]"></i>
                    <span class="text-blue-600">Pejabat Struktural</span>
                </nav>

                <div class="space-y-8">
                    <div class="border-b border-slate-200 pb-5">
                        <span
                            class="text-xs font-bold text-blue-600 uppercase tracking-widest block mb-1"
                            >Manajemen Balai</span
                        >
                        <h1
                            class="text-2xl sm:text-3xl font-black text-slate-900 tracking-tight"
                        >
                            Profil Pejabat Struktural
                        </h1>
                        <p class="text-xs text-slate-500 mt-1">
                            Klik pada foto pejabat untuk melihat riwayat karier
                            dan rekam jejak jabatan.
                        </p>
                    </div>

                    <div
                        class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6"
                    >
                        <template v-if="pejabatList.length > 0">
                            <div
                                v-for="(pejabat, index) in pejabatList"
                                :key="index"
                                @click="openPejabatModal(pejabat)"
                                class="bg-white rounded-2xl overflow-hidden border border-slate-200/60 shadow-xs p-4 text-center flex flex-col items-center group cursor-pointer hover:border-blue-300 hover:shadow-md transition-all"
                            >
                                <div
                                    class="w-full aspect-[3/4] rounded-xl overflow-hidden bg-slate-100 mb-3.5 border border-slate-100 shadow-inner relative group"
                                >
                                    <img
                                        v-if="pejabat.foto"
                                        :src="'/storage/' + pejabat.foto"
                                        :alt="'Foto ' + pejabat.nama"
                                        class="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-300"
                                    />
                                    <div
                                        v-else
                                        class="w-full h-full flex flex-col items-center justify-center text-slate-300 gap-1 py-12"
                                    >
                                        <i class="fas fa-user text-3xl"></i>
                                        <span
                                            class="text-[10px] text-slate-400 font-medium"
                                            >Tanpa Foto</span
                                        >
                                    </div>

                                    <div
                                        class="absolute inset-0 bg-blue-900/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
                                    >
                                        <span
                                            class="bg-white text-slate-900 text-[10px] font-bold px-3 py-1.5 rounded-full shadow-sm"
                                        >
                                            <i class="fas fa-eye mr-1"></i>
                                            Lihat Riwayat
                                        </span>
                                    </div>
                                </div>

                                <div class="leading-tight">
                                    <h4
                                        class="font-extrabold text-slate-900 text-xs sm:text-sm tracking-tight line-clamp-2 min-h-[2.5rem] flex items-center justify-center"
                                    >
                                        {{ pejabat.nama }}
                                    </h4>
                                    <p
                                        class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mt-1 block truncate"
                                    >
                                        {{ pejabat.jabatan }}
                                    </p>
                                </div>
                            </div>
                        </template>

                        <div
                            v-else
                            class="col-span-full bg-white rounded-2xl border border-slate-100 p-12 text-center text-slate-400 italic"
                        >
                            <i
                                class="fas fa-users-slash text-4xl text-slate-300 block mb-2"
                            ></i>
                            Daftar nama pejabat struktural belum dimasukkan di
                            panel admin.
                        </div>
                    </div>
                </div>

                <!-- MODAL -->
                <Teleport to="body">
                    <div
                        v-if="openModal && selectedPejabat"
                        class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs"
                    >
                        <div
                            class="bg-white rounded-3xl max-w-lg w-full overflow-hidden shadow-2xl border border-slate-100 transform transition-all"
                        >
                            <div
                                class="p-6 border-b border-slate-100 flex justify-between items-start bg-slate-50"
                            >
                                <div>
                                    <h3
                                        class="text-base font-black text-slate-900 tracking-tight"
                                    >
                                        {{ selectedPejabat.nama }}
                                    </h3>
                                    <p
                                        class="text-xs font-bold text-blue-600 uppercase tracking-wider mt-0.5"
                                    >
                                        {{ selectedPejabat.jabatan }}
                                    </p>
                                </div>
                                <button
                                    @click="closeModal"
                                    class="text-slate-400 hover:text-slate-600 p-1 cursor-pointer"
                                >
                                    <i class="fas fa-times text-lg"></i>
                                </button>
                            </div>

                            <div
                                class="p-6 space-y-4 max-h-[60vh] overflow-y-auto"
                            >
                                <h4
                                    class="text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-50 pb-2"
                                >
                                    <i
                                        class="fas fa-history mr-1 text-blue-600"
                                    ></i>
                                    Rekam Jejak Karier
                                </h4>

                                <div
                                    class="relative border-l-2 border-slate-100 pl-4 ml-2 space-y-4 py-2"
                                >
                                    <template
                                        v-if="
                                            selectedPejabat.riwayat_jabatan &&
                                            selectedPejabat.riwayat_jabatan
                                                .length > 0
                                        "
                                    >
                                        <div
                                            v-for="(
                                                riwayat, index
                                            ) in selectedPejabat.riwayat_jabatan"
                                            :key="index"
                                            class="relative mb-4 last:mb-0"
                                        >
                                            <div
                                                class="absolute -left-[21px] top-1.5 w-2.5 h-2.5 rounded-full bg-blue-600 border-2 border-white ring-4 ring-blue-50"
                                            ></div>
                                            <span
                                                class="inline-block bg-slate-100 text-slate-700 text-[10px] font-mono font-bold px-2 py-0.5 rounded-md"
                                            >
                                                {{ riwayat.tahun }}
                                            </span>
                                            <p
                                                class="text-xs font-semibold text-slate-800 mt-1"
                                            >
                                                {{ riwayat.nama_jabatan }}
                                            </p>
                                        </div>
                                    </template>
                                    <p
                                        v-else
                                        class="text-xs text-slate-400 italic text-center py-4"
                                    >
                                        Belum ada data riwayat jabatan yang
                                        dimasukkan.
                                    </p>
                                </div>
                            </div>

                            <div
                                class="p-4 bg-slate-50 border-t border-slate-100 text-right"
                            >
                                <button
                                    @click="closeModal"
                                    class="px-4 py-2 bg-slate-900 text-white font-bold rounded-xl text-xs active:scale-95 transition-all cursor-pointer"
                                >
                                    Tutup Halaman
                                </button>
                            </div>
                        </div>
                    </div>
                </Teleport>
            </div>
        </main>
    </AppLayout>
</template>
