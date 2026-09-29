<script setup>
import { ref, computed } from "vue";
import { Head, useForm, router } from "@inertiajs/vue3";
import DashboardLayout from "@/Layouts/DashboardLayout.vue";
import QRCode from "qrcode";
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
    Copy,
    Check,
    MousePointerClick,
    ExternalLink,
    Search,
    X,
    QrCode,
    Download,
    Upload,
    Link2,
    Users,
    CheckCircle2,
    RefreshCw,
    FileSpreadsheet,
    ArrowRight,
    PhoneCall,
    Mail,
    Code2,
    CheckSquare,
    Square,
    AlertCircle,
    FileDown,
    FileUp,
    Save,
    Send,
    Loader2,
    Sparkles,
} from "lucide-vue-next";

const props = defineProps({
    stats: Object,
    currentTab: { type: String, default: "shortlinks" },
    shortlinks: Object,
    leads: Object,
    allShortlinks: Array,
    filters: Object,
    settings: Object,
});

const activeTab = ref(props.currentTab || "shortlinks");

// Switch tab
const switchTab = (tab) => {
    activeTab.value = tab;
    selectedShortlinkIds.value = [];
    selectedLeadIds.value = [];
    router.get(
        "/admin/shortlinks",
        {
            tab: tab,
            search: searchQuery.value,
            status: statusFilter.value,
            capture: captureFilter.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
            lead_search: leadSearchQuery.value,
            lead_shortlink_id: leadShortlinkFilter.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

// --- BULLETPROOF CLIPBOARD COPY (WORKS OVER HTTP & HTTPS) ---
const copyFeedback = ref({
    type: null,
    id: null,
});

const copyToClipboard = async (text, type = "general", id = null) => {
    let success = false;
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
            success = true;
        }
    } catch {
        success = false;
    }

    if (!success) {
        try {
            const textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed";
            textArea.style.left = "-999999px";
            textArea.style.top = "-999999px";
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            success = document.execCommand("copy");
            textArea.remove();
        } catch (err) {
            console.error("Fallback copy failed", err);
            success = false;
        }
    }

    if (success) {
        copyFeedback.value = { type, id };
        setTimeout(() => {
            if (
                copyFeedback.value.type === type &&
                copyFeedback.value.id === id
            ) {
                copyFeedback.value = { type: null, id: null };
            }
        }, 2200);
    }
};

const getFullShortlink = (code) => {
    if (typeof window !== "undefined" && window.location) {
        return `${window.location.origin}/s/${code}`;
    }
    return `/s/${code}`;
};

// --- SHORTLINKS TAB STATE ---
const isDialogOpen = ref(false);
const isQrDialogOpen = ref(false);
const isImportDialogOpen = ref(false);
const activeQrShortlink = ref(null);
const qrSvg = ref("");
const qrDataUrl = ref("");
const isQrLoading = ref(false);
const editItem = ref(null);

const searchQuery = ref(props.filters?.search || "");
const statusFilter = ref(props.filters?.status ?? "");
const captureFilter = ref(props.filters?.capture ?? "");
const sortBy = ref(props.filters?.sort_by || "");
const sortDir = ref(props.filters?.sort_dir || "desc");

// Selected shortlinks
const selectedShortlinkIds = ref([]);
const isAllShortlinksSelected = computed(() => {
    const list = props.shortlinks?.data || [];
    return list.length > 0 && selectedShortlinkIds.value.length === list.length;
});

const toggleSelectAllShortlinks = () => {
    const list = props.shortlinks?.data || [];
    if (isAllShortlinksSelected.value) {
        selectedShortlinkIds.value = [];
    } else {
        selectedShortlinkIds.value = list.map((item) => item.id);
    }
};

const toggleSelectShortlink = (id) => {
    const index = selectedShortlinkIds.value.indexOf(id);
    if (index > -1) {
        selectedShortlinkIds.value.splice(index, 1);
    } else {
        selectedShortlinkIds.value.push(id);
    }
};

// Bulk Actions on Shortlinks
const bulkShortlinksAction = (action) => {
    if (!selectedShortlinkIds.value.length) return;
    if (action === "delete") {
        if (
            !confirm(
                `Hapus ${selectedShortlinkIds.value.length} shortlink yang dipilih beserta semua leads terkait?`,
            )
        )
            return;
    }

    router.post(
        "/admin/shortlinks/bulk",
        {
            action: action,
            ids: selectedShortlinkIds.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedShortlinkIds.value = [];
            },
        },
    );
};

const exportSelectedShortlinks = () => {
    const ids = selectedShortlinkIds.value.join(",");
    window.open(`/admin/shortlinks/export/shortlinks?ids=${ids}`, "_blank");
};

const exportAllShortlinks = () => {
    const params = new URLSearchParams();
    if (searchQuery.value) params.append("search", searchQuery.value);
    if (statusFilter.value !== "") params.append("status", statusFilter.value);
    window.open(
        `/admin/shortlinks/export/shortlinks?${params.toString()}`,
        "_blank",
    );
};

const handleFilterShortlinks = () => {
    selectedShortlinkIds.value = [];
    router.get(
        "/admin/shortlinks",
        {
            tab: "shortlinks",
            search: searchQuery.value,
            status: statusFilter.value,
            capture: captureFilter.value,
            sort_by: sortBy.value,
            sort_dir: sortDir.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const onSort = (column, direction) => {
    sortBy.value = column;
    sortDir.value = direction;
    handleFilterShortlinks();
};

const form = useForm({
    pegawai_name: "",
    code: "",
    destination_url: "",
    is_capture_active: false,
    capture_fields: ["nama", "whatsapp"],
    custom_title: "",
    custom_description: "",
    custom_button_text: "",
    spreadsheet_webhook_url: "",
    is_active: true,
});

const openCreate = () => {
    editItem.value = null;
    form.reset();
    form.capture_fields = ["nama", "whatsapp"];
    form.is_active = true;
    form.is_capture_active = false;
    isDialogOpen.value = true;
};

const openEdit = (item) => {
    editItem.value = item;
    form.pegawai_name = item.pegawai_name;
    form.code = item.code;
    form.destination_url = item.destination_url;
    form.is_capture_active = !!item.is_capture_active;
    form.capture_fields =
        Array.isArray(item.capture_fields) && item.capture_fields.length > 0
            ? [...item.capture_fields]
            : ["nama", "whatsapp"];
    form.custom_title = item.custom_title ?? "";
    form.custom_description = item.custom_description ?? "";
    form.custom_button_text = item.custom_button_text ?? "";
    form.spreadsheet_webhook_url = item.spreadsheet_webhook_url ?? "";
    form.is_active = !!item.is_active;
    isDialogOpen.value = true;
};

const closeDialog = () => {
    isDialogOpen.value = false;
    editItem.value = null;
    form.reset();
};

const submitShortlink = () => {
    if (editItem.value) {
        form.put(`/admin/shortlinks/${editItem.value.id}`, {
            onSuccess: () => closeDialog(),
        });
    } else {
        form.post("/admin/shortlinks", {
            onSuccess: () => closeDialog(),
        });
    }
};

const toggleField = (field) => {
    const idx = form.capture_fields.indexOf(field);
    if (idx > -1) {
        if (form.capture_fields.length > 1) {
            form.capture_fields.splice(idx, 1);
        }
    } else {
        form.capture_fields.push(field);
    }
};

const toggleActive = (item) => {
    router.patch(
        `/admin/shortlinks/${item.id}/toggle`,
        {},
        { preserveScroll: true },
    );
};

const deleteShortlink = (item) => {
    if (
        !confirm(
            `Hapus shortlink "/s/${item.code}" beserta seluruh data lead terkait?`,
        )
    )
        return;
    router.delete(`/admin/shortlinks/${item.id}`, { preserveScroll: true });
};

// --- QR CODE GENERATION & DOWNLOAD ---
const openQrModal = async (item) => {
    activeQrShortlink.value = item;
    isQrDialogOpen.value = true;
    isQrLoading.value = true;
    qrSvg.value = "";
    qrDataUrl.value = "";
    const target = getFullShortlink(item.code);

    try {
        const qrLib =
            QRCode && typeof QRCode.toString === "function"
                ? QRCode
                : QRCode &&
                    QRCode.default &&
                    typeof QRCode.default.toString === "function"
                  ? QRCode.default
                  : QRCode;

        // 1. Generate local vector SVG (works 100% without canvas or CORS)
        if (qrLib && typeof qrLib.toString === "function") {
            const svgContent = await qrLib.toString(target, {
                type: "svg",
                margin: 2,
                width: 260,
                color: {
                    dark: "#0f172a",
                    light: "#ffffff",
                },
            });
            qrSvg.value = svgContent;
        }

        // 2. Generate PNG toDataURL for direct PNG download
        if (qrLib && typeof qrLib.toDataURL === "function") {
            try {
                const pngData = await qrLib.toDataURL(target, {
                    width: 360,
                    margin: 2,
                    color: {
                        dark: "#0f172a",
                        light: "#ffffff",
                    },
                });
                if (pngData) {
                    qrDataUrl.value = pngData;
                }
            } catch (pngErr) {
                console.warn("Canvas PNG generation fallback to SVG", pngErr);
            }
        }
    } catch (err) {
        console.error("Local QR Code Error:", err);
    }

    // 3. Fallback online QR API if both failed
    if (!qrSvg.value && !qrDataUrl.value) {
        qrDataUrl.value = `https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=10&data=${encodeURIComponent(target)}`;
    }

    isQrLoading.value = false;
};

const downloadQr = () => {
    if (!activeQrShortlink.value) return;
    const filename = `qr-${activeQrShortlink.value.code}.png`;

    if (qrDataUrl.value && qrDataUrl.value.startsWith("data:image/png")) {
        const a = document.createElement("a");
        a.href = qrDataUrl.value;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        return;
    }

    if (qrSvg.value) {
        const canvas = document.createElement("canvas");
        canvas.width = 400;
        canvas.height = 400;
        const ctx = canvas.getContext("2d");
        const img = new Image();
        const svgBlob = new Blob([qrSvg.value], {
            type: "image/svg+xml;charset=utf-8",
        });
        const url = URL.createObjectURL(svgBlob);
        img.onload = () => {
            ctx.fillStyle = "#ffffff";
            ctx.fillRect(0, 0, 400, 400);
            ctx.drawImage(img, 0, 0, 400, 400);
            URL.revokeObjectURL(url);
            const a = document.createElement("a");
            a.href = canvas.toDataURL("image/png");
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        };
        img.src = url;
        return;
    }

    // Fallback if canvas/local fails
    const target = getFullShortlink(activeQrShortlink.value.code);
    window.open(
        `https://api.qrserver.com/v1/create-qr-code/?size=500x500&data=${encodeURIComponent(target)}`,
        "_blank",
    );
};

// --- IMPORT EXCEL / CSV ---
const importForm = useForm({
    file: null,
});

const onFileImportChange = (e) => {
    importForm.file = e.target.files[0] || null;
};

const submitImport = () => {
    if (!importForm.file) return;
    importForm.post("/admin/shortlinks/import", {
        onSuccess: () => {
            isImportDialogOpen.value = false;
            importForm.reset();
        },
    });
};

// --- LEADS TAB STATE ---
const leadSearchQuery = ref(props.filters?.lead_search || "");
const leadShortlinkFilter = ref(props.filters?.lead_shortlink_id || "");
const selectedLeadIds = ref([]);

const isAllLeadsSelected = computed(() => {
    const list = props.leads?.data || [];
    return list.length > 0 && selectedLeadIds.value.length === list.length;
});

const toggleSelectAllLeads = () => {
    const list = props.leads?.data || [];
    if (isAllLeadsSelected.value) {
        selectedLeadIds.value = [];
    } else {
        selectedLeadIds.value = list.map((l) => l.id);
    }
};

const toggleSelectLead = (id) => {
    const index = selectedLeadIds.value.indexOf(id);
    if (index > -1) {
        selectedLeadIds.value.splice(index, 1);
    } else {
        selectedLeadIds.value.push(id);
    }
};

const bulkLeadsAction = (action) => {
    if (!selectedLeadIds.value.length) return;
    if (action === "delete") {
        if (
            !confirm(
                `Hapus ${selectedLeadIds.value.length} data leads yang dipilih?`,
            )
        )
            return;
    }

    router.post(
        "/admin/shortlinks/leads/bulk",
        {
            action: action,
            ids: selectedLeadIds.value,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                selectedLeadIds.value = [];
            },
        },
    );
};

const handleFilterLeads = () => {
    selectedLeadIds.value = [];
    router.get(
        "/admin/shortlinks",
        {
            tab: "leads",
            lead_search: leadSearchQuery.value,
            lead_shortlink_id: leadShortlinkFilter.value,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const filterLeadsByShortlink = (shortlinkId) => {
    activeTab.value = "leads";
    leadShortlinkFilter.value = String(shortlinkId);
    handleFilterLeads();
};

const deleteLead = (lead) => {
    if (!confirm(`Hapus lead dari "${lead.nama || "Pengunjung"}"?`)) return;
    router.delete(`/admin/shortlinks/leads/${lead.id}`, {
        preserveScroll: true,
    });
};

const formatWaNumber = (phone) => {
    if (!phone) return "";
    let clean = phone.replace(/[^0-9]/g, "");
    if (clean.startsWith("0")) {
        clean = "62" + clean.substring(1);
    }
    return clean;
};

const exportLeadsCsv = () => {
    const params = new URLSearchParams();
    if (leadShortlinkFilter.value)
        params.append("shortlink_id", leadShortlinkFilter.value);
    if (leadSearchQuery.value) params.append("search", leadSearchQuery.value);
    window.open(
        `/admin/shortlinks/export/leads?${params.toString()}`,
        "_blank",
    );
};

// --- SETTINGS TAB ---
const googleFormulaLeads = computed(() => {
    return props.settings?.feed_csv_url
        ? `=IMPORTDATA("${props.settings.feed_csv_url}")`
        : "";
});

const googleFormulaShortlinks = computed(() => {
    return props.settings?.shortlinks_feed_csv_url
        ? `=IMPORTDATA("${props.settings.shortlinks_feed_csv_url}")`
        : "";
});

const regenerateToken = () => {
    if (
        !confirm(
            "Regenerasi token akan memutus integrasi spreadsheet lama sampai link diperbarui. Lanjutkan?",
        )
    )
        return;
    router.post(
        "/admin/shortlinks/settings/regenerate-token",
        {},
        { preserveScroll: true },
    );
};

// Webhook Google Sheets State & Actions
const webhookForm = useForm({
    spreadsheet_webhook_url: props.settings?.spreadsheet_webhook_url || "",
});

const isTestingWebhook = ref(false);
const webhookTestResult = ref(null);

const saveWebhook = () => {
    webhookForm.post("/admin/shortlinks/settings/webhook", {
        preserveScroll: true,
        onSuccess: () => {
            webhookTestResult.value = null;
        },
    });
};

const testWebhook = async () => {
    if (!webhookForm.spreadsheet_webhook_url) {
        alert(
            "Silakan masukkan URL Webhook Google Apps Script terlebih dahulu.",
        );
        return;
    }

    isTestingWebhook.value = true;
    webhookTestResult.value = null;

    try {
        const csrfToken =
            document
                .querySelector('meta[name="csrf-token"]')
                ?.getAttribute("content") || "";
        const res = await fetch("/admin/shortlinks/settings/test-webhook", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": csrfToken,
                Accept: "application/json",
            },
            body: JSON.stringify({
                url: webhookForm.spreadsheet_webhook_url,
            }),
        });

        const data = await res.json();
        webhookTestResult.value = {
            success: res.ok && data.success,
            message:
                data.message ||
                (res.ok
                    ? "Tes webhook berhasil terkirim!"
                    : "Gagal menguji webhook."),
        };
    } catch (err) {
        webhookTestResult.value = {
            success: false,
            message:
                "Terjadi kesalahan saat pengujian: " + (err.message || err),
        };
    } finally {
        isTestingWebhook.value = false;
    }
};

const appsScriptCode = `function doPost(e) {
  try {
    var sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
    
    // Otomatis buat header kolom jika sheet masih kosong
    if (sheet.getLastRow() === 0) {
      sheet.appendRow([
        "Waktu Masuk",
        "Event",
        "Kode Shortlink",
        "Nama Pegawai",
        "Nama Lengkap",
        "Nomor WhatsApp",
        "Email",
        "IP Address"
      ]);
      sheet.getRange(1, 1, 1, 8).setFontWeight("bold");
    }

    // Ambil payload JSON yang dikirim sistem Shortlink BPVP
    var payload = JSON.parse(e.postData.contents);

    sheet.appendRow([
      new Date(),
      payload.event || "new_lead",
      payload.code || "-",
      payload.pegawai_name || "-",
      payload.nama || "-",
      payload.whatsapp ? "'" + payload.whatsapp : "-",
      payload.email || "-",
      payload.ip_address || "-"
    ]);

    return ContentService.createTextOutput(JSON.stringify({
      status: "success",
      message: "Data berhasil dicatat ke Google Sheets"
    })).setMimeType(ContentService.MimeType.JSON);

  } catch (error) {
    return ContentService.createTextOutput(JSON.stringify({
      status: "error",
      message: error.toString()
    })).setMimeType(ContentService.MimeType.JSON);
  }
}

function doGet(e) {
  return ContentService.createTextOutput(JSON.stringify({
    status: "active",
    service: "Webhook Shortlink BPVP Pangkep"
  })).setMimeType(ContentService.MimeType.JSON);
}`;
</script>

<template>
    <Head title="Shortlink & Leads - BPVP Pangkep" />

    <DashboardLayout>
        <div class="space-y-6 w-full">
            <!-- Header Banner -->
            <div
                class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white p-5 rounded-2xl border border-zinc-200/80 shadow-2xs"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                            <Link2 class="w-5 h-5" />
                        </div>
                        <h1
                            class="text-xl font-bold tracking-tight text-zinc-900"
                        >
                            Modul Shortlink & Leads
                        </h1>
                    </div>
                    <p class="text-xs text-zinc-500 mt-1 pl-1">
                        Kelola tautan pendek resmi BPVP Pangkep, formulir
                        capture pengunjung, template impor/ekspor excel, dan
                        integrasi Google Sheets.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <template v-if="activeTab === 'shortlinks'">
                        <a
                            href="/admin/shortlinks/template/download"
                            class="inline-flex items-center justify-center h-9 px-3 text-xs font-semibold rounded-xl border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50 shadow-2xs transition-colors"
                            title="Unduh format template Excel / CSV resmi untuk membuat shortlink dalam jumlah banyak"
                        >
                            <FileSpreadsheet
                                class="w-3.5 h-3.5 mr-1.5 text-emerald-600"
                            />
                            Template Excel / CSV
                        </a>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="isImportDialogOpen = true"
                            class="h-9 text-xs border-zinc-200 text-zinc-700 hover:bg-zinc-50"
                        >
                            <FileUp class="w-3.5 h-3.5 mr-1.5 text-blue-600" />
                            Import Excel / CSV
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="exportAllShortlinks"
                            class="h-9 text-xs border-zinc-200 text-zinc-700 hover:bg-zinc-50"
                        >
                            <FileDown
                                class="w-3.5 h-3.5 mr-1.5 text-indigo-600"
                            />
                            Export Excel / CSV
                        </Button>
                        <Button
                            @click="openCreate"
                            class="h-9 text-xs bg-blue-600 hover:bg-blue-700 text-white shadow-xs font-semibold"
                        >
                            <Plus class="w-4 h-4 mr-1.5" />
                            Buat Shortlink
                        </Button>
                    </template>

                    <template v-else-if="activeTab === 'leads'">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="exportLeadsCsv"
                            class="h-9 text-xs border-emerald-300 text-emerald-700 hover:bg-emerald-50"
                        >
                            <Download class="w-3.5 h-3.5 mr-1.5" />
                            Export Leads Excel / CSV
                        </Button>
                    </template>
                </div>
            </div>

            <!-- Top Stat Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3.5">
                <div
                    class="bg-white p-4 rounded-2xl border border-zinc-200/80 shadow-2xs flex items-center gap-3.5"
                >
                    <div
                        class="w-11 h-11 rounded-xl bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 shrink-0"
                    >
                        <Link2 class="w-5 h-5" />
                    </div>
                    <div>
                        <div
                            class="text-2xl font-bold tracking-tight text-zinc-900"
                        >
                            {{ stats?.total_shortlinks ?? 0 }}
                        </div>
                        <div class="text-[11px] font-medium text-zinc-500">
                            Total Shortlink
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white p-4 rounded-2xl border border-zinc-200/80 shadow-2xs flex items-center gap-3.5"
                >
                    <div
                        class="w-11 h-11 rounded-xl bg-purple-50 border border-purple-100 flex items-center justify-center text-purple-600 shrink-0"
                    >
                        <MousePointerClick class="w-5 h-5" />
                    </div>
                    <div>
                        <div
                            class="text-2xl font-bold tracking-tight text-zinc-900"
                        >
                            {{
                                (stats?.total_clicks ?? 0).toLocaleString(
                                    "id-ID",
                                )
                            }}
                        </div>
                        <div class="text-[11px] font-medium text-zinc-500">
                            Total Kunjungan / Klik
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white p-4 rounded-2xl border border-zinc-200/80 shadow-2xs flex items-center gap-3.5"
                >
                    <div
                        class="w-11 h-11 rounded-xl bg-emerald-50 border border-emerald-100 flex items-center justify-center text-emerald-600 shrink-0"
                    >
                        <Users class="w-5 h-5" />
                    </div>
                    <div>
                        <div
                            class="text-2xl font-bold tracking-tight text-zinc-900"
                        >
                            {{
                                (stats?.total_leads ?? 0).toLocaleString(
                                    "id-ID",
                                )
                            }}
                        </div>
                        <div class="text-[11px] font-medium text-zinc-500">
                            Leads Kontak Masuk
                        </div>
                    </div>
                </div>

                <div
                    class="bg-white p-4 rounded-2xl border border-zinc-200/80 shadow-2xs flex items-center gap-3.5"
                >
                    <div
                        class="w-11 h-11 rounded-xl bg-amber-50 border border-amber-100 flex items-center justify-center text-amber-600 shrink-0"
                    >
                        <CheckCircle2 class="w-5 h-5" />
                    </div>
                    <div>
                        <div
                            class="text-2xl font-bold tracking-tight text-zinc-900"
                        >
                            {{ stats?.active_shortlinks ?? 0 }}
                        </div>
                        <div class="text-[11px] font-medium text-zinc-500">
                            Tautan Aktif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Navigation Tabs -->
            <div class="flex items-center gap-2 border-b border-zinc-200 pb-2">
                <button
                    type="button"
                    @click="switchTab('shortlinks')"
                    :class="[
                        'px-4 py-2 text-xs font-semibold rounded-xl transition-all flex items-center gap-2 cursor-pointer',
                        activeTab === 'shortlinks'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'bg-white hover:bg-zinc-100 text-zinc-600 border border-zinc-200',
                    ]"
                >
                    <Link2 class="w-4 h-4" />
                    Daftar Shortlink
                    <span
                        :class="
                            activeTab === 'shortlinks'
                                ? 'bg-blue-500 text-white'
                                : 'bg-zinc-100 text-zinc-600'
                        "
                        class="px-1.5 py-0.5 rounded-full text-[10px]"
                    >
                        {{ stats?.total_shortlinks ?? 0 }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="switchTab('leads')"
                    :class="[
                        'px-4 py-2 text-xs font-semibold rounded-xl transition-all flex items-center gap-2 cursor-pointer',
                        activeTab === 'leads'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'bg-white hover:bg-zinc-100 text-zinc-600 border border-zinc-200',
                    ]"
                >
                    <Users class="w-4 h-4" />
                    Data Leads Pengunjung
                    <span
                        :class="
                            activeTab === 'leads'
                                ? 'bg-blue-500 text-white'
                                : 'bg-zinc-100 text-zinc-600'
                        "
                        class="px-1.5 py-0.5 rounded-full text-[10px]"
                    >
                        {{ stats?.total_leads ?? 0 }}
                    </span>
                </button>

                <button
                    type="button"
                    @click="switchTab('settings')"
                    :class="[
                        'px-4 py-2 text-xs font-semibold rounded-xl transition-all flex items-center gap-2 cursor-pointer',
                        activeTab === 'settings'
                            ? 'bg-blue-600 text-white shadow-xs'
                            : 'bg-white hover:bg-zinc-100 text-zinc-600 border border-zinc-200',
                    ]"
                >
                    <FileSpreadsheet class="w-4 h-4" />
                    Integrasi Google Sheets
                </button>
            </div>

            <!-- ========================================== -->
            <!-- TAB 1: DAFTAR SHORTLINK                    -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'shortlinks'" class="space-y-4">
                <!-- Banner Unduh Template & Buat Banyak Shortlink -->
                <div
                    class="bg-linear-to-r from-blue-50/90 via-sky-50/60 to-indigo-50/80 border border-blue-200/80 p-4 rounded-2xl flex flex-col sm:flex-row sm:items-center justify-between gap-3.5 shadow-2xs"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-xs"
                        >
                            <FileSpreadsheet class="w-5 h-5" />
                        </div>
                        <div>
                            <div class="font-bold text-sm text-blue-950">
                                Ingin Membuat Shortlink dalam Jumlah Banyak?
                            </div>
                            <div class="text-xs text-blue-800/85 mt-0.5">
                                Unduh format template resmi Excel/CSV, isi
                                daftar tautan & pegawai, lalu unggah file
                                melalui tombol Import.
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a
                            href="/admin/shortlinks/template/download"
                            class="inline-flex items-center gap-1.5 h-8.5 px-3.5 rounded-xl text-xs font-bold bg-white text-blue-700 border border-blue-200 hover:bg-blue-100/70 transition-colors shadow-2xs cursor-pointer"
                        >
                            <Download class="w-3.5 h-3.5 text-blue-600" />
                            Download Template Excel
                        </a>
                        <Button
                            variant="default"
                            size="sm"
                            @click="isImportDialogOpen = true"
                            class="h-8.5 text-xs bg-blue-600 hover:bg-blue-700 text-white font-semibold cursor-pointer shadow-xs"
                        >
                            <FileUp class="w-3.5 h-3.5 mr-1" />
                            Unggah & Impor File
                        </Button>
                    </div>
                </div>

                <!-- Search & Filters Toolbar -->
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-zinc-200/80 shadow-2xs"
                >
                    <div class="relative flex-1">
                        <Search
                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"
                        />
                        <Input
                            v-model="searchQuery"
                            @keyup.enter="handleFilterShortlinks"
                            placeholder="Cari kode (misal: v7zWr), nama pegawai, atau URL..."
                            class="pl-9 pr-8 h-9 text-xs"
                        />
                        <button
                            v-if="searchQuery"
                            @click="
                                searchQuery = '';
                                handleFilterShortlinks();
                            "
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 cursor-pointer"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="statusFilter"
                            @change="handleFilterShortlinks"
                            class="h-9 px-3 text-xs font-medium bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                        >
                            <option value="">Semua Status</option>
                            <option value="1">Aktif</option>
                            <option value="0">Nonaktif</option>
                        </select>

                        <select
                            v-model="captureFilter"
                            @change="handleFilterShortlinks"
                            class="h-9 px-3 text-xs font-medium bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                        >
                            <option value="">Semua Tipe Form</option>
                            <option value="1">Dengan Form Capture</option>
                            <option value="0">Langsung Redirect</option>
                        </select>

                        <Button
                            variant="secondary"
                            size="sm"
                            @click="handleFilterShortlinks"
                            class="h-9 px-3 text-xs"
                        >
                            Filter
                        </Button>
                    </div>
                </div>

                <!-- FLOATING / SELECTION ACTION BAR -->
                <div
                    v-if="selectedShortlinkIds.length > 0"
                    class="bg-blue-50 border border-blue-200 text-blue-900 px-4 py-2.5 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-xs animate-in fade-in duration-200"
                >
                    <div
                        class="flex items-center gap-2 text-xs font-bold text-blue-900"
                    >
                        <CheckSquare class="w-4 h-4 text-blue-600" />
                        <span
                            >{{ selectedShortlinkIds.length }} shortlink
                            dipilih</span
                        >
                    </div>

                    <div class="flex items-center gap-1.5 flex-wrap">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="bulkShortlinksAction('activate')"
                            class="h-8 text-xs bg-white text-emerald-700 border-emerald-200 hover:bg-emerald-50"
                        >
                            Aktifkan
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="bulkShortlinksAction('deactivate')"
                            class="h-8 text-xs bg-white text-amber-700 border-amber-200 hover:bg-amber-50"
                        >
                            Nonaktifkan
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="exportSelectedShortlinks"
                            class="h-8 text-xs bg-white text-zinc-700 border-zinc-200 hover:bg-zinc-50"
                        >
                            <Download class="w-3 h-3 mr-1" />
                            Export Terpilih
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            @click="bulkShortlinksAction('delete')"
                            class="h-8 text-xs bg-white text-rose-700 border-rose-200 hover:bg-rose-50"
                        >
                            <Trash2 class="w-3 h-3 mr-1" />
                            Hapus
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="selectedShortlinkIds = []"
                            class="h-8 text-xs text-zinc-600 hover:text-zinc-900"
                        >
                            Batal
                        </Button>
                    </div>
                </div>

                <!-- Shortlinks Table -->
                <div
                    class="rounded-2xl border border-zinc-200/80 bg-white shadow-2xs overflow-hidden w-full"
                >
                    <div class="overflow-x-auto w-full">
                        <Table class="w-full">
                            <TableHeader class="bg-zinc-50/70">
                                <TableRow>
                                    <TableHead class="w-10 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="isAllShortlinksSelected"
                                            @change="toggleSelectAllShortlinks"
                                            class="h-4 w-4 rounded-sm border-zinc-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                            title="Pilih semua data pada halaman ini"
                                        />
                                    </TableHead>
                                    <TableHead class="w-14 text-center text-xs">
                                        <DataTableColumnHeader
                                            title="No."
                                            column="id"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            align="center"
                                            @sort="onSort"
                                        />
                                    </TableHead>
                                    <TableHead class="min-w-[190px]">
                                        <DataTableColumnHeader
                                            title="Kode & Tautan Pendek"
                                            column="code"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            @sort="onSort"
                                        />
                                    </TableHead>
                                    <TableHead class="min-w-[160px]">
                                        <DataTableColumnHeader
                                            title="Nama Pegawai"
                                            column="pegawai_name"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            @sort="onSort"
                                        />
                                    </TableHead>
                                    <TableHead class="min-w-[200px]">
                                        <DataTableColumnHeader
                                            title="Tujuan URL"
                                            column="destination_url"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            @sort="onSort"
                                        />
                                    </TableHead>
                                    <TableHead class="w-28 text-center"
                                        >Form Capture</TableHead
                                    >
                                    <TableHead class="w-16 text-center">
                                        <DataTableColumnHeader
                                            title="Klik"
                                            column="clicks_count"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            align="center"
                                            @sort="onSort"
                                        />
                                    </TableHead>
                                    <TableHead class="w-16 text-center">
                                        <DataTableColumnHeader
                                            title="Leads"
                                            column="leads_count"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            align="center"
                                            @sort="onSort"
                                        />
                                    </TableHead>
                                    <TableHead class="w-20 text-center">
                                        <DataTableColumnHeader
                                            title="Status"
                                            column="is_active"
                                            :sort-key="sortBy"
                                            :sort-direction="sortDir"
                                            align="center"
                                            @sort="onSort"
                                        />
                                    </TableHead>
                                    <TableHead class="text-right w-36"
                                        >Aksi</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="!shortlinks?.data?.length">
                                    <TableCell
                                        colspan="10"
                                        class="h-32 text-center text-zinc-400 text-xs"
                                    >
                                        Belum ada data shortlink yang sesuai
                                        kriteria pencarian.
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="(item, index) in shortlinks?.data"
                                    :key="item.id"
                                    :class="[
                                        'hover:bg-zinc-50/60 transition-colors',
                                        selectedShortlinkIds.includes(item.id)
                                            ? 'bg-blue-50/40'
                                            : '',
                                    ]"
                                >
                                    <TableCell class="text-center">
                                        <input
                                            type="checkbox"
                                            :checked="
                                                selectedShortlinkIds.includes(
                                                    item.id,
                                                )
                                            "
                                            @change="
                                                toggleSelectShortlink(item.id)
                                            "
                                            class="h-4 w-4 rounded-sm border-zinc-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                        />
                                    </TableCell>

                                    <TableCell
                                        class="text-center font-mono text-[11px] text-zinc-400 font-semibold"
                                    >
                                        {{
                                            (shortlinks.current_page - 1) *
                                                shortlinks.per_page +
                                            index +
                                            1
                                        }}
                                    </TableCell>

                                    <TableCell>
                                        <div class="space-y-1">
                                            <!-- Code badge & single copy button for full link -->
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="font-mono font-bold text-xs text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md border border-blue-200"
                                                >
                                                    {{ item.code }}
                                                </span>
                                                <button
                                                    type="button"
                                                    @click="
                                                        copyToClipboard(
                                                            getFullShortlink(
                                                                item.code,
                                                            ),
                                                            'link',
                                                            item.id,
                                                        )
                                                    "
                                                    class="text-[11px] font-semibold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2 py-0.5 rounded transition-colors inline-flex items-center gap-1 cursor-pointer"
                                                    title="Salin tautan shortlink lengkap"
                                                >
                                                    <Check
                                                        v-if="
                                                            copyFeedback.type ===
                                                                'link' &&
                                                            copyFeedback.id ===
                                                                item.id
                                                        "
                                                        class="w-3 h-3 text-emerald-600"
                                                    />
                                                    <Copy
                                                        v-else
                                                        class="w-3 h-3"
                                                    />
                                                    <span>{{
                                                        copyFeedback.type ===
                                                            "link" &&
                                                        copyFeedback.id ===
                                                            item.id
                                                            ? "Tersalin!"
                                                            : "Salin Link"
                                                    }}</span>
                                                </button>
                                            </div>

                                            <!-- Full link preview text -->
                                            <div
                                                class="text-[11px] font-mono text-zinc-500 truncate max-w-[210px]"
                                                :title="
                                                    getFullShortlink(item.code)
                                                "
                                            >
                                                {{
                                                    getFullShortlink(item.code)
                                                }}
                                            </div>
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="font-semibold text-xs text-zinc-900 leading-tight"
                                        >
                                            {{ item.pegawai_name }}
                                        </div>
                                        <div
                                            v-if="item.created_by?.name"
                                            class="text-[10px] text-zinc-400 mt-0.5"
                                        >
                                            oleh: {{ item.created_by.name }}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="flex items-center gap-1.5 max-w-sm"
                                        >
                                            <a
                                                :href="item.destination_url"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="text-zinc-600 hover:text-blue-600 text-xs font-mono truncate hover:underline"
                                                :title="item.destination_url"
                                            >
                                                {{ item.destination_url }}
                                            </a>
                                            <ExternalLink
                                                class="w-3 h-3 text-zinc-400 shrink-0"
                                            />
                                        </div>
                                        <div
                                            v-if="item.custom_title"
                                            class="text-[10px] text-zinc-500 mt-0.5 truncate italic"
                                        >
                                            "{{ item.custom_title }}"
                                        </div>
                                    </TableCell>

                                    <TableCell class="text-center">
                                        <span
                                            v-if="item.is_capture_active"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200"
                                        >
                                            <Users class="w-3 h-3" />
                                            Form Aktif
                                        </span>
                                        <span
                                            v-else
                                            class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-zinc-100 text-zinc-500"
                                        >
                                            Langsung
                                        </span>
                                    </TableCell>

                                    <TableCell
                                        class="text-center font-mono text-xs"
                                    >
                                        <span
                                            class="inline-flex items-center gap-1 font-semibold text-zinc-700"
                                        >
                                            <MousePointerClick
                                                class="w-3 h-3 text-zinc-400"
                                            />
                                            {{
                                                (
                                                    item.clicks_count ?? 0
                                                ).toLocaleString("id-ID")
                                            }}
                                        </span>
                                    </TableCell>

                                    <TableCell
                                        class="text-center font-mono text-xs"
                                    >
                                        <button
                                            v-if="item.leads_count > 0"
                                            type="button"
                                            @click="
                                                filterLeadsByShortlink(item.id)
                                            "
                                            class="inline-flex items-center gap-1 font-bold text-blue-600 hover:text-blue-800 bg-blue-50 hover:bg-blue-100 px-2 py-0.5 rounded-full transition-colors cursor-pointer"
                                            title="Klik untuk melihat data leads tautan ini"
                                        >
                                            {{ item.leads_count }}
                                        </button>
                                        <span v-else class="text-zinc-400"
                                            >0</span
                                        >
                                    </TableCell>

                                    <TableCell class="text-center">
                                        <button
                                            type="button"
                                            @click="toggleActive(item)"
                                            class="cursor-pointer transition-opacity hover:opacity-80"
                                            :title="
                                                item.is_active
                                                    ? 'Klik untuk nonaktifkan'
                                                    : 'Klik untuk aktifkan'
                                            "
                                        >
                                            <Badge
                                                :variant="
                                                    item.is_active
                                                        ? 'success'
                                                        : 'secondary'
                                                "
                                                class="text-[10px] cursor-pointer"
                                            >
                                                {{
                                                    item.is_active
                                                        ? "Aktif"
                                                        : "Nonaktif"
                                                }}
                                            </Badge>
                                        </button>
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <div
                                            class="flex items-center justify-end gap-1"
                                        >
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openQrModal(item)"
                                                class="h-7 w-7 p-0 text-zinc-600 hover:text-blue-600 hover:bg-blue-50 cursor-pointer"
                                                title="Tampilkan QR Code"
                                            >
                                                <QrCode class="w-3.5 h-3.5" />
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="openEdit(item)"
                                                class="h-7 px-2 text-zinc-600 hover:text-zinc-900 cursor-pointer"
                                            >
                                                <Pencil
                                                    class="w-3.5 h-3.5 mr-1"
                                                />
                                                Edit
                                            </Button>

                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                @click="deleteShortlink(item)"
                                                class="h-7 w-7 p-0 text-rose-600 hover:text-rose-700 hover:bg-rose-50 cursor-pointer"
                                                title="Hapus shortlink"
                                            >
                                                <Trash2 class="w-3.5 h-3.5" />
                                            </Button>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <DataTablePagination :pagination="shortlinks" />
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 2: DATA LEADS PENGUNJUNG               -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'leads'" class="space-y-4">
                <!-- Leads Toolbar -->
                <div
                    class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-white p-3 rounded-2xl border border-zinc-200/80 shadow-2xs"
                >
                    <div class="relative flex-1">
                        <Search
                            class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400"
                        />
                        <Input
                            v-model="leadSearchQuery"
                            @keyup.enter="handleFilterLeads"
                            placeholder="Cari nama pengunjung, no. whatsapp, email, atau IP..."
                            class="pl-9 pr-8 h-9 text-xs"
                        />
                        <button
                            v-if="leadSearchQuery"
                            @click="
                                leadSearchQuery = '';
                                handleFilterLeads();
                            "
                            type="button"
                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 cursor-pointer"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <div class="flex items-center gap-2">
                        <select
                            v-model="leadShortlinkFilter"
                            @change="handleFilterLeads"
                            class="h-9 px-3 text-xs font-medium bg-zinc-50 hover:bg-zinc-100 border border-zinc-200 rounded-xl text-zinc-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer max-w-xs truncate"
                        >
                            <option value="">Semua Shortlink</option>
                            <option
                                v-for="sl in allShortlinks"
                                :key="sl.id"
                                :value="sl.id"
                            >
                                /s/{{ sl.code }} &bull; {{ sl.pegawai_name }}
                            </option>
                        </select>

                        <Button
                            variant="secondary"
                            size="sm"
                            @click="handleFilterLeads"
                            class="h-9 px-3 text-xs cursor-pointer"
                        >
                            Filter
                        </Button>
                    </div>
                </div>

                <!-- FLOATING LEADS SELECTION ACTION BAR -->
                <div
                    v-if="selectedLeadIds.length > 0"
                    class="bg-blue-50 border border-blue-200 text-blue-900 px-4 py-2.5 rounded-2xl flex flex-wrap items-center justify-between gap-3 shadow-xs animate-in fade-in duration-200"
                >
                    <div
                        class="flex items-center gap-2 text-xs font-bold text-blue-900"
                    >
                        <CheckSquare class="w-4 h-4 text-blue-600" />
                        <span
                            >{{ selectedLeadIds.length }} data leads
                            dipilih</span
                        >
                    </div>

                    <div class="flex items-center gap-1.5">
                        <Button
                            variant="outline"
                            size="sm"
                            @click="bulkLeadsAction('delete')"
                            class="h-8 text-xs bg-white text-rose-700 border-rose-200 hover:bg-rose-50 cursor-pointer"
                        >
                            <Trash2 class="w-3 h-3 mr-1" />
                            Hapus Terpilih
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="selectedLeadIds = []"
                            class="h-8 text-xs text-zinc-600 hover:text-zinc-900 cursor-pointer"
                        >
                            Batal
                        </Button>
                    </div>
                </div>

                <!-- Leads Table -->
                <div
                    class="rounded-2xl border border-zinc-200/80 bg-white shadow-2xs overflow-hidden w-full"
                >
                    <div class="overflow-x-auto w-full">
                        <Table class="w-full">
                            <TableHeader class="bg-zinc-50/70">
                                <TableRow>
                                    <TableHead class="w-10 text-center">
                                        <input
                                            type="checkbox"
                                            :checked="isAllLeadsSelected"
                                            @change="toggleSelectAllLeads"
                                            class="h-4 w-4 rounded-sm border-zinc-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                            title="Pilih semua data pada halaman ini"
                                        />
                                    </TableHead>
                                    <TableHead
                                        class="w-12 text-center text-xs font-semibold"
                                        >No</TableHead
                                    >
                                    <TableHead class="w-44"
                                        >Shortlink</TableHead
                                    >
                                    <TableHead class="min-w-[160px]"
                                        >Nama Pengunjung</TableHead
                                    >
                                    <TableHead class="w-44">WhatsApp</TableHead>
                                    <TableHead class="w-48">Email</TableHead>
                                    <TableHead class="w-36"
                                        >IP & Perangkat</TableHead
                                    >
                                    <TableHead class="w-36"
                                        >Waktu Masuk</TableHead
                                    >
                                    <TableHead class="w-16 text-right"
                                        >Aksi</TableHead
                                    >
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="!leads?.data?.length">
                                    <TableCell
                                        colspan="9"
                                        class="h-32 text-center text-zinc-400 text-xs"
                                    >
                                        Belum ada data leads yang tercatat.
                                    </TableCell>
                                </TableRow>

                                <TableRow
                                    v-for="(lead, index) in leads?.data"
                                    :key="lead.id"
                                    :class="[
                                        'hover:bg-zinc-50/60 transition-colors',
                                        selectedLeadIds.includes(lead.id)
                                            ? 'bg-blue-50/40'
                                            : '',
                                    ]"
                                >
                                    <TableCell class="text-center">
                                        <input
                                            type="checkbox"
                                            :checked="
                                                selectedLeadIds.includes(
                                                    lead.id,
                                                )
                                            "
                                            @change="toggleSelectLead(lead.id)"
                                            class="h-4 w-4 rounded-sm border-zinc-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                        />
                                    </TableCell>

                                    <TableCell
                                        class="text-center text-xs font-mono text-zinc-400 font-semibold"
                                    >
                                        {{
                                            (leads.current_page - 1) *
                                                leads.per_page +
                                            index +
                                            1
                                        }}
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="font-mono text-xs font-bold text-zinc-800"
                                        >
                                            /s/{{ lead.shortlink?.code ?? "-" }}
                                        </div>
                                        <div
                                            class="text-[11px] text-zinc-500 truncate max-w-[150px]"
                                        >
                                            {{
                                                lead.shortlink?.pegawai_name ??
                                                "-"
                                            }}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="font-semibold text-xs text-zinc-900"
                                        >
                                            {{ lead.nama || "-" }}
                                        </div>
                                    </TableCell>

                                    <TableCell>
                                        <a
                                            v-if="lead.whatsapp"
                                            :href="`https://wa.me/${formatWaNumber(lead.whatsapp)}`"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1.5 text-emerald-700 hover:text-emerald-800 font-mono text-xs font-medium bg-emerald-50 hover:bg-emerald-100 px-2 py-0.5 rounded-lg border border-emerald-200 transition-colors"
                                        >
                                            <PhoneCall
                                                class="w-3 h-3 text-emerald-600"
                                            />
                                            {{ lead.whatsapp }}
                                        </a>
                                        <span
                                            v-else
                                            class="text-zinc-400 text-xs"
                                            >-</span
                                        >
                                    </TableCell>

                                    <TableCell>
                                        <a
                                            v-if="lead.email"
                                            :href="`mailto:${lead.email}`"
                                            class="inline-flex items-center gap-1.5 text-blue-700 hover:underline font-mono text-xs truncate max-w-[180px]"
                                        >
                                            <Mail
                                                class="w-3 h-3 text-blue-500 shrink-0"
                                            />
                                            {{ lead.email }}
                                        </a>
                                        <span
                                            v-else
                                            class="text-zinc-400 text-xs"
                                            >-</span
                                        >
                                    </TableCell>

                                    <TableCell>
                                        <div
                                            class="text-xs font-mono text-zinc-600"
                                        >
                                            {{ lead.ip_address || "-" }}
                                        </div>
                                        <div
                                            class="text-[10px] text-zinc-400 truncate max-w-[130px]"
                                            :title="lead.user_agent"
                                        >
                                            {{ lead.user_agent || "-" }}
                                        </div>
                                    </TableCell>

                                    <TableCell
                                        class="text-xs text-zinc-600 font-mono"
                                    >
                                        {{
                                            new Date(
                                                lead.created_at,
                                            ).toLocaleDateString("id-ID", {
                                                day: "2-digit",
                                                month: "short",
                                                year: "numeric",
                                                hour: "2-digit",
                                                minute: "2-digit",
                                            })
                                        }}
                                    </TableCell>

                                    <TableCell class="text-right">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            @click="deleteLead(lead)"
                                            class="h-7 w-7 p-0 text-rose-600 hover:text-rose-700 hover:bg-rose-50 cursor-pointer"
                                            title="Hapus lead"
                                        >
                                            <Trash2 class="w-3.5 h-3.5" />
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                    <DataTablePagination :pagination="leads" />
                </div>
            </div>

            <!-- ========================================== -->
            <!-- TAB 3: INTEGRASI GOOGLE SHEETS            -->
            <!-- ========================================== -->
            <div v-if="activeTab === 'settings'" class="space-y-6">
                <!-- Section 1: Feed Daftar Shortlink Google Sheets -->
                <div
                    class="bg-white p-6 rounded-2xl border border-zinc-200/80 shadow-2xs space-y-4"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="p-2.5 rounded-xl bg-blue-50 text-blue-600 border border-blue-100"
                            >
                                <Link2 class="w-6 h-6" />
                            </div>
                            <div>
                                <h2 class="text-base font-bold text-zinc-900">
                                    1. Live Sync Daftar Shortlink ke Google
                                    Sheets (=IMPORTDATA)
                                </h2>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    Impor daftar seluruh tautan shortlink yang
                                    terdaftar, kode, pegawai, dan total klik
                                    secara otomatis ke Google Sheets.
                                </p>
                            </div>
                        </div>

                        <Button
                            variant="outline"
                            size="sm"
                            @click="regenerateToken"
                            class="text-xs text-zinc-600 cursor-pointer"
                        >
                            <RefreshCw class="w-3.5 h-3.5 mr-1.5" />
                            Ganti Token
                        </Button>
                    </div>

                    <div
                        class="rounded-xl bg-zinc-50 border border-zinc-200 p-4 space-y-3"
                    >
                        <label class="text-xs font-bold text-zinc-700"
                            >Rumus Formula Google Sheets (Daftar
                            Shortlink):</label
                        >
                        <div class="flex items-center gap-2">
                            <Input
                                readonly
                                :value="googleFormulaShortlinks"
                                class="font-mono text-xs bg-white text-zinc-800"
                            />
                            <Button
                                @click="
                                    copyToClipboard(
                                        googleFormulaShortlinks,
                                        'formula_shortlinks',
                                    )
                                "
                                class="shrink-0 bg-blue-600 hover:bg-blue-700 text-white text-xs h-9 cursor-pointer"
                            >
                                <Check
                                    v-if="
                                        copyFeedback.type ===
                                        'formula_shortlinks'
                                    "
                                    class="w-3.5 h-3.5 mr-1"
                                />
                                <Copy v-else class="w-3.5 h-3.5 mr-1" />
                                {{
                                    copyFeedback.type === "formula_shortlinks"
                                        ? "Tersalin!"
                                        : "Salin Rumus"
                                }}
                            </Button>
                        </div>
                        <p class="text-[11px] text-zinc-500 leading-relaxed">
                            💡 Buka Google Sheets &rarr; klik sel
                            <code>A1</code> pada sheet baru &rarr; paste rumus
                            di atas &rarr; tekan <code>Enter</code>. Seluruh
                            daftar shortlink akan sinkron otomatis.
                        </p>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-700"
                            >URL Feed CSV Daftar Shortlink:</label
                        >
                        <div class="flex items-center gap-2">
                            <Input
                                readonly
                                :value="settings?.shortlinks_feed_csv_url"
                                class="font-mono text-xs bg-zinc-50 text-zinc-700"
                            />
                            <Button
                                variant="outline"
                                @click="
                                    copyToClipboard(
                                        settings?.shortlinks_feed_csv_url,
                                        'url_shortlinks',
                                    )
                                "
                                class="shrink-0 text-xs h-9 cursor-pointer"
                            >
                                <Check
                                    v-if="
                                        copyFeedback.type === 'url_shortlinks'
                                    "
                                    class="w-3.5 h-3.5 mr-1 text-emerald-600"
                                />
                                <Copy v-else class="w-3.5 h-3.5 mr-1" />
                                {{
                                    copyFeedback.type === "url_shortlinks"
                                        ? "Tersalin"
                                        : "Salin URL"
                                }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Section 2: Live Feed Data Leads Google Sheets -->
                <div
                    class="bg-white p-6 rounded-2xl border border-zinc-200/80 shadow-2xs space-y-4"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600 border border-emerald-100"
                        >
                            <FileSpreadsheet class="w-6 h-6" />
                        </div>
                        <div>
                            <h2 class="text-base font-bold text-zinc-900">
                                2. Live Sync Data Leads Masuk ke Google Sheets
                                (=IMPORTDATA)
                            </h2>
                            <p class="text-xs text-zinc-500 mt-0.5">
                                Tampilkan seluruh data kontak pengunjung yang
                                mengisi form capture secara real-time di Google
                                Sheets.
                            </p>
                        </div>
                    </div>

                    <div
                        class="rounded-xl bg-zinc-50 border border-zinc-200 p-4 space-y-3"
                    >
                        <label class="text-xs font-bold text-zinc-700"
                            >Rumus Formula Google Sheets (Data Leads):</label
                        >
                        <div class="flex items-center gap-2">
                            <Input
                                readonly
                                :value="googleFormulaLeads"
                                class="font-mono text-xs bg-white text-zinc-800"
                            />
                            <Button
                                @click="
                                    copyToClipboard(
                                        googleFormulaLeads,
                                        'formula_leads',
                                    )
                                "
                                class="shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white text-xs h-9 cursor-pointer"
                            >
                                <Check
                                    v-if="copyFeedback.type === 'formula_leads'"
                                    class="w-3.5 h-3.5 mr-1"
                                />
                                <Copy v-else class="w-3.5 h-3.5 mr-1" />
                                {{
                                    copyFeedback.type === "formula_leads"
                                        ? "Tersalin!"
                                        : "Salin Rumus"
                                }}
                            </Button>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-700"
                            >URL Feed CSV Leads:</label
                        >
                        <div class="flex items-center gap-2">
                            <Input
                                readonly
                                :value="settings?.feed_csv_url"
                                class="font-mono text-xs bg-zinc-50 text-zinc-700"
                            />
                            <Button
                                variant="outline"
                                @click="
                                    copyToClipboard(
                                        settings?.feed_csv_url,
                                        'url_leads',
                                    )
                                "
                                class="shrink-0 text-xs h-9 cursor-pointer"
                            >
                                <Check
                                    v-if="copyFeedback.type === 'url_leads'"
                                    class="w-3.5 h-3.5 mr-1 text-emerald-600"
                                />
                                <Copy v-else class="w-3.5 h-3.5 mr-1" />
                                {{
                                    copyFeedback.type === "url_leads"
                                        ? "Tersalin"
                                        : "Salin URL"
                                }}
                            </Button>
                        </div>
                    </div>
                </div>

                <!-- Section 3: Integrasi Webhook Otomatis Real-Time (Apps Script) -->
                <div
                    class="bg-white p-6 rounded-2xl border border-zinc-200/80 shadow-2xs space-y-6"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="flex items-center gap-3">
                            <div
                                class="p-2.5 rounded-xl bg-indigo-50 text-indigo-600 border border-indigo-100"
                            >
                                <Code2 class="w-6 h-6" />
                            </div>
                            <div>
                                <div class="flex items-center gap-2">
                                    <h2
                                        class="text-base font-bold text-zinc-900"
                                    >
                                        3. Integrasi Webhook Google Sheets
                                        Real-Time (Apps Script)
                                    </h2>
                                    <Badge
                                        class="bg-indigo-100 text-indigo-800 hover:bg-indigo-100 border-none text-[10px] font-semibold"
                                    >
                                        Rekomendasi Instan
                                    </Badge>
                                </div>
                                <p class="text-xs text-zinc-500 mt-0.5">
                                    Setiap kali pengunjung mengisi formulir
                                    capture shortlink, data langsung dikirimkan
                                    seketika sebagai baris baru di Google
                                    Spreadsheet Anda tanpa jeda waktu.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Webhook URL Configuration Form -->
                    <div
                        class="rounded-xl bg-zinc-50 border border-zinc-200 p-4 space-y-3.5"
                    >
                        <div class="space-y-1">
                            <label
                                class="text-xs font-bold text-zinc-800 flex items-center gap-1.5"
                            >
                                <Send class="w-3.5 h-3.5 text-indigo-600" />
                                URL Webhook Google Apps Script (Web App URL):
                            </label>
                            <p class="text-[11px] text-zinc-500">
                                Masukkan Web App URL yang Anda dapatkan setelah
                                melakukan
                                <em
                                    >Deploy &rarr; New Deployment &rarr; Web
                                    app</em
                                >
                                pada spreadsheet Anda.
                            </p>
                        </div>

                        <div
                            class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2.5"
                        >
                            <Input
                                v-model="webhookForm.spreadsheet_webhook_url"
                                placeholder="https://script.google.com/macros/s/AKfycbx.../exec"
                                class="font-mono text-xs bg-white text-zinc-800 flex-1"
                            />
                            <div class="flex items-center gap-2 shrink-0">
                                <Button
                                    @click="saveWebhook"
                                    :disabled="webhookForm.processing"
                                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs h-9 cursor-pointer"
                                >
                                    <Save class="w-3.5 h-3.5 mr-1.5" />
                                    {{
                                        webhookForm.processing
                                            ? "Menyimpan..."
                                            : "Simpan Webhook"
                                    }}
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    @click="testWebhook"
                                    :disabled="
                                        isTestingWebhook ||
                                        !webhookForm.spreadsheet_webhook_url
                                    "
                                    class="border-indigo-200 text-indigo-700 hover:bg-indigo-50 text-xs h-9 cursor-pointer"
                                >
                                    <Loader2
                                        v-if="isTestingWebhook"
                                        class="w-3.5 h-3.5 mr-1.5 animate-spin"
                                    />
                                    <Send v-else class="w-3.5 h-3.5 mr-1.5" />
                                    {{
                                        isTestingWebhook
                                            ? "Menguji..."
                                            : "Uji Kirim (Test Ping)"
                                    }}
                                </Button>
                            </div>
                        </div>

                        <!-- Feedback Alert if tested -->
                        <div
                            v-if="webhookTestResult"
                            :class="[
                                'p-3 rounded-lg text-xs flex items-start gap-2.5 transition-all',
                                webhookTestResult.success
                                    ? 'bg-emerald-50 text-emerald-800 border border-emerald-200'
                                    : 'bg-rose-50 text-rose-800 border border-rose-200',
                            ]"
                        >
                            <CheckCircle2
                                v-if="webhookTestResult.success"
                                class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5"
                            />
                            <AlertCircle
                                v-else
                                class="w-4 h-4 text-rose-600 shrink-0 mt-0.5"
                            />
                            <div class="flex-1">
                                <p class="font-semibold">
                                    {{
                                        webhookTestResult.success
                                            ? "Uji Coba Berhasil!"
                                            : "Uji Coba Gagal"
                                    }}
                                </p>
                                <p class="text-[11px] mt-0.5 opacity-90">
                                    {{ webhookTestResult.message }}
                                </p>
                            </div>
                            <button
                                @click="webhookTestResult = null"
                                class="text-zinc-400 hover:text-zinc-600 cursor-pointer"
                            >
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>
                    </div>

                    <!-- Code Snippet Box -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <label
                                class="text-xs font-bold text-zinc-700 flex items-center gap-1.5"
                            >
                                <Code2 class="w-3.5 h-3.5 text-zinc-500" />
                                Skrip Kode Google Apps Script:
                            </label>
                            <Button
                                variant="outline"
                                size="sm"
                                @click="
                                    copyToClipboard(
                                        appsScriptCode,
                                        'apps_script',
                                    )
                                "
                                class="text-xs h-7 px-2.5 text-zinc-700 cursor-pointer"
                            >
                                <Check
                                    v-if="copyFeedback.type === 'apps_script'"
                                    class="w-3 h-3 mr-1 text-emerald-600"
                                />
                                <Copy v-else class="w-3 h-3 mr-1" />
                                {{
                                    copyFeedback.type === "apps_script"
                                        ? "Kode Tersalin!"
                                        : "Salin Skrip"
                                }}
                            </Button>
                        </div>
                        <div
                            class="rounded-xl bg-zinc-900 p-4 text-zinc-100 font-mono text-[11px] overflow-x-auto relative"
                        >
                            <pre class="leading-relaxed">{{
                                appsScriptCode
                            }}</pre>
                        </div>
                    </div>

                    <!-- Step-by-step Tutorial Guide -->
                    <div class="space-y-3 pt-2 border-t border-zinc-100">
                        <h3 class="text-xs font-bold text-zinc-800">
                            Panduan Langkah Pemasangan di Google Spreadsheet:
                        </h3>
                        <div
                            class="grid grid-cols-1 md:grid-cols-2 gap-3 text-xs text-zinc-600"
                        >
                            <div
                                class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/80 space-y-1.5"
                            >
                                <div
                                    class="font-bold text-zinc-800 flex items-center gap-2"
                                >
                                    <span
                                        class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 inline-flex items-center justify-center text-[10px] font-bold shrink-0"
                                        >1</span
                                    >
                                    Buka Editor Apps Script
                                </div>
                                <p
                                    class="text-[11px] text-zinc-500 leading-relaxed"
                                >
                                    Buka dokumen Google Spreadsheet &rarr; Klik
                                    menu
                                    <strong>Ekstensi (Extensions)</strong>
                                    &rarr; pilih <strong>Apps Script</strong>.
                                </p>
                            </div>

                            <div
                                class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/80 space-y-1.5"
                            >
                                <div
                                    class="font-bold text-zinc-800 flex items-center gap-2"
                                >
                                    <span
                                        class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 inline-flex items-center justify-center text-[10px] font-bold shrink-0"
                                        >2</span
                                    >
                                    Tempel Skrip & Simpan
                                </div>
                                <p
                                    class="text-[11px] text-zinc-500 leading-relaxed"
                                >
                                    Hapus kode bawaan di dalam file
                                    <code>Code.gs</code>, tempel (paste) skrip
                                    di atas, lalu tekan tombol
                                    <strong>Simpan (Ctrl+S)</strong>.
                                </p>
                            </div>

                            <div
                                class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/80 space-y-1.5"
                            >
                                <div
                                    class="font-bold text-zinc-800 flex items-center gap-2"
                                >
                                    <span
                                        class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 inline-flex items-center justify-center text-[10px] font-bold shrink-0"
                                        >3</span
                                    >
                                    Deploy sebagai Web App
                                </div>
                                <p
                                    class="text-[11px] text-zinc-500 leading-relaxed"
                                >
                                    Klik tombol biru
                                    <strong>Deploy (Terapkan)</strong> di kanan
                                    atas &rarr; pilih
                                    <strong>New deployment</strong> &rarr; klik
                                    ikon gerigi &rarr; pilih
                                    <strong>Web app</strong>.
                                </p>
                            </div>

                            <div
                                class="p-3.5 rounded-xl bg-zinc-50 border border-zinc-200/80 space-y-1.5"
                            >
                                <div
                                    class="font-bold text-zinc-800 flex items-center gap-2"
                                >
                                    <span
                                        class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 inline-flex items-center justify-center text-[10px] font-bold shrink-0"
                                        >4</span
                                    >
                                    Atur Akses & Salin Web App URL
                                </div>
                                <p
                                    class="text-[11px] text-zinc-500 leading-relaxed"
                                >
                                    Pilih <em>Who has access</em>:
                                    <strong>Anyone (Siapa saja)</strong> agar
                                    web BPVP dapat mengirim data &rarr; Klik
                                    <strong>Deploy</strong> &rarr; Salin
                                    <strong>Web App URL</strong> ke kolom input
                                    di atas &rarr; Simpan & Uji Kirim!
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- MODAL DIALOG CREATE / EDIT SHORTLINK       -->
        <!-- ========================================== -->
        <Dialog :open="isDialogOpen" @update:open="isDialogOpen = $event">
            <DialogContent class="sm:max-w-2xl max-h-[92vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle class="text-base font-bold text-zinc-900">
                        {{
                            editItem ? "Edit Shortlink" : "Buat Shortlink Baru"
                        }}
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitShortlink" class="space-y-4 pt-2">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-zinc-700"
                                >Nama Pegawai / Unit
                                <span class="text-rose-500">*</span></label
                            >
                            <Input
                                v-model="form.pegawai_name"
                                placeholder="Contoh: Ridwan (Instruktur Las)"
                                required
                            />
                            <p
                                v-if="form.errors.pegawai_name"
                                class="text-rose-600 text-[11px]"
                            >
                                {{ form.errors.pegawai_name }}
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label class="text-xs font-bold text-zinc-700"
                                >Kode Custom (Opsional)</label
                            >
                            <div class="relative">
                                <span
                                    class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 font-mono text-xs"
                                    >/s/</span
                                >
                                <Input
                                    v-model="form.code"
                                    placeholder="Kosongkan untuk acak 5 huruf"
                                    class="pl-9 font-mono uppercase"
                                    maxlength="20"
                                />
                            </div>
                            <p
                                v-if="form.errors.code"
                                class="text-rose-600 text-[11px]"
                            >
                                {{ form.errors.code }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-700"
                            >URL Tujuan Lengkap
                            <span class="text-rose-500">*</span></label
                        >
                        <Input
                            v-model="form.destination_url"
                            type="url"
                            placeholder="https://..."
                            required
                        />
                        <p
                            v-if="form.errors.destination_url"
                            class="text-rose-600 text-[11px]"
                        >
                            {{ form.errors.destination_url }}
                        </p>
                    </div>

                    <!-- Capture Lead Toggle Box -->
                    <div
                        class="rounded-2xl border border-zinc-200 bg-zinc-50/80 p-4 space-y-4"
                    >
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2.5">
                                <input
                                    type="checkbox"
                                    id="capture"
                                    v-model="form.is_capture_active"
                                    class="h-4 w-4 rounded-md border-zinc-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                                />
                                <label
                                    for="capture"
                                    class="text-xs font-bold text-zinc-800 cursor-pointer select-none"
                                >
                                    Aktifkan Formulir Pengisian Data (Capture
                                    Lead)
                                </label>
                            </div>
                            <Badge
                                v-if="form.is_capture_active"
                                variant="success"
                                class="text-[10px]"
                                >Aktif</Badge
                            >
                        </div>

                        <template v-if="form.is_capture_active">
                            <!-- Fields checklist -->
                            <div
                                class="space-y-1.5 pt-1 border-t border-zinc-200"
                            >
                                <label
                                    class="text-xs font-bold text-zinc-700 block"
                                    >Data yang Diminta dari Pengunjung:</label
                                >
                                <div class="flex flex-wrap items-center gap-4">
                                    <label
                                        class="flex items-center gap-2 text-xs font-medium text-zinc-700 cursor-pointer select-none"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="
                                                form.capture_fields.includes(
                                                    'nama',
                                                )
                                            "
                                            @change="toggleField('nama')"
                                            class="rounded text-blue-600"
                                        />
                                        Nama Lengkap
                                    </label>
                                    <label
                                        class="flex items-center gap-2 text-xs font-medium text-zinc-700 cursor-pointer select-none"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="
                                                form.capture_fields.includes(
                                                    'whatsapp',
                                                )
                                            "
                                            @change="toggleField('whatsapp')"
                                            class="rounded text-blue-600"
                                        />
                                        Nomor WhatsApp
                                    </label>
                                    <label
                                        class="flex items-center gap-2 text-xs font-medium text-zinc-700 cursor-pointer select-none"
                                    >
                                        <input
                                            type="checkbox"
                                            :checked="
                                                form.capture_fields.includes(
                                                    'email',
                                                )
                                            "
                                            @change="toggleField('email')"
                                            class="rounded text-blue-600"
                                        />
                                        Alamat Email
                                    </label>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-zinc-700"
                                    >Judul Halaman Capture</label
                                >
                                <Input
                                    v-model="form.custom_title"
                                    placeholder="Contoh: Silakan Isi Buku Tamu Kunjungan"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-zinc-700"
                                    >Deskripsi / Petunjuk Pengisian</label
                                >
                                <Textarea
                                    v-model="form.custom_description"
                                    placeholder="Tuliskan petunjuk singkat atau informasi kepada pengunjung..."
                                    rows="3"
                                    class="text-xs"
                                />
                            </div>

                            <div class="space-y-1.5">
                                <label class="text-xs font-bold text-zinc-700"
                                    >Teks Tombol Lanjut (Opsional)</label
                                >
                                <Input
                                    v-model="form.custom_button_text"
                                    placeholder="Contoh: Lanjut ke Tautan Pendaftaran"
                                />
                            </div>
                        </template>
                    </div>

                    <div class="flex items-center gap-2.5 pt-1">
                        <input
                            type="checkbox"
                            id="is_active"
                            v-model="form.is_active"
                            class="h-4 w-4 rounded-md border-zinc-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                        />
                        <label
                            for="is_active"
                            class="text-xs font-bold text-zinc-800 cursor-pointer select-none"
                        >
                            Shortlink Aktif (Dapat diakses publik)
                        </label>
                    </div>

                    <DialogFooter class="pt-2">
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
                            class="bg-blue-600 hover:bg-blue-700 text-white cursor-pointer"
                        >
                            {{
                                form.processing
                                    ? "Menyimpan..."
                                    : "Simpan Shortlink"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- ========================================== -->
        <!-- MODAL DIALOG QR CODE (OFFLINE LOCAL SVG)   -->
        <!-- ========================================== -->
        <Dialog :open="isQrDialogOpen" @update:open="isQrDialogOpen = $event">
            <DialogContent class="sm:max-w-sm text-center">
                <DialogHeader>
                    <DialogTitle class="text-base font-bold text-zinc-900">
                        QR Code Tautan Resmi
                    </DialogTitle>
                </DialogHeader>

                <div
                    v-if="activeQrShortlink"
                    class="space-y-4 pt-2 flex flex-col items-center"
                >
                    <div
                        class="p-4 bg-white border border-zinc-200 rounded-2xl shadow-sm flex items-center justify-center min-h-[240px] w-full"
                    >
                        <div
                            v-if="isQrLoading"
                            class="flex flex-col items-center gap-2 text-zinc-400 text-xs py-10"
                        >
                            <RefreshCw
                                class="w-6 h-6 animate-spin text-blue-600"
                            />
                            <span>Menghasilkan QR Code...</span>
                        </div>
                        <div
                            v-else-if="qrSvg"
                            v-html="qrSvg"
                            class="w-56 h-56 flex items-center justify-center [&>svg]:w-full [&>svg]:h-full"
                        ></div>
                        <img
                            v-else-if="qrDataUrl"
                            :src="qrDataUrl"
                            alt="QR Code"
                            class="w-56 h-56 object-contain"
                        />
                        <img
                            v-else
                            :src="`https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=${encodeURIComponent(getFullShortlink(activeQrShortlink.code))}`"
                            alt="QR Code"
                            class="w-56 h-56 object-contain"
                        />
                    </div>

                    <div class="space-y-1 text-center">
                        <div class="font-mono text-sm font-bold text-blue-700">
                            {{ getFullShortlink(activeQrShortlink.code) }}
                        </div>
                        <div class="text-xs text-zinc-500">
                            {{ activeQrShortlink.pegawai_name }}
                        </div>
                    </div>

                    <div class="flex items-center gap-2 w-full pt-2">
                        <Button
                            type="button"
                            @click="downloadQr"
                            class="flex-1 inline-flex items-center justify-center gap-1.5 h-9 rounded-xl text-xs font-semibold bg-zinc-900 text-white hover:bg-zinc-800 transition-colors cursor-pointer"
                        >
                            <Download class="w-3.5 h-3.5" />
                            Download QR
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            @click="
                                copyToClipboard(
                                    getFullShortlink(activeQrShortlink.code),
                                    'qr_modal_link',
                                )
                            "
                            class="flex-1 text-xs h-9 cursor-pointer"
                        >
                            <Check
                                v-if="copyFeedback.type === 'qr_modal_link'"
                                class="w-3.5 h-3.5 mr-1 text-emerald-600"
                            />
                            <Copy v-else class="w-3.5 h-3.5 mr-1" />
                            {{
                                copyFeedback.type === "qr_modal_link"
                                    ? "Tersalin!"
                                    : "Salin Link"
                            }}
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <!-- ========================================== -->
        <!-- MODAL DIALOG IMPORT EXCEL / CSV            -->
        <!-- ========================================== -->
        <Dialog
            :open="isImportDialogOpen"
            @update:open="isImportDialogOpen = $event"
        >
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle
                        class="text-base font-bold text-zinc-900 flex items-center gap-2"
                    >
                        <FileUp class="w-5 h-5 text-blue-600" />
                        Import Shortlink dari Excel / CSV
                    </DialogTitle>
                </DialogHeader>

                <form @submit.prevent="submitImport" class="space-y-4 pt-2">
                    <div
                        class="p-3.5 bg-blue-50 border border-blue-100 rounded-xl space-y-2 text-xs text-blue-900"
                    >
                        <div class="font-bold flex items-center gap-1.5">
                            <AlertCircle
                                class="w-4 h-4 text-blue-600 shrink-0"
                            />
                            Petunjuk Format Import:
                        </div>
                        <p class="text-[11px] leading-relaxed text-blue-800">
                            Gunakan template Excel / CSV resmi untuk memastikan
                            format kolom sesuai (Nama Pegawai, URL Tujuan, Kode,
                            dll).
                        </p>
                        <a
                            href="/admin/shortlinks/template/download"
                            class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-700 bg-white hover:bg-blue-100 px-3 py-1.5 rounded-lg border border-blue-200 transition-colors shadow-2xs"
                        >
                            <Download class="w-3.5 h-3.5 text-blue-600" />
                            Unduh Template CSV / Excel
                        </a>
                    </div>

                    <div class="space-y-1.5">
                        <label class="text-xs font-bold text-zinc-700 block"
                            >Pilih File CSV / Excel (.csv):</label
                        >
                        <input
                            type="file"
                            accept=".csv, text/csv, application/vnd.ms-excel"
                            @change="onFileImportChange"
                            required
                            class="w-full text-xs text-zinc-700 border border-zinc-200 rounded-xl file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer p-1"
                        />
                        <p
                            v-if="importForm.errors.file"
                            class="text-rose-600 text-[11px]"
                        >
                            {{ importForm.errors.file }}
                        </p>
                    </div>

                    <DialogFooter class="pt-2">
                        <Button
                            type="button"
                            variant="outline"
                            @click="isImportDialogOpen = false"
                        >
                            Batal
                        </Button>
                        <Button
                            type="submit"
                            :disabled="
                                !importForm.file || importForm.processing
                            "
                            class="bg-blue-600 hover:bg-blue-700 text-white cursor-pointer"
                        >
                            {{
                                importForm.processing
                                    ? "Mengimpor Data..."
                                    : "Unggah & Impor"
                            }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </DashboardLayout>
</template>
