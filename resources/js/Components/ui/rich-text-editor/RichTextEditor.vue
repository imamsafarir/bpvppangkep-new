<script setup>
import { ref, watch, onMounted, computed } from "vue";
import {
    Bold,
    Italic,
    Underline,
    Strikethrough,
    AlignLeft,
    AlignCenter,
    AlignRight,
    AlignJustify,
    List,
    ListOrdered,
    Indent,
    Outdent,
    Quote,
    Heading1,
    Heading2,
    Heading3,
    Link2,
    Unlink,
    ImagePlus,
    Table2,
    Minus,
    RemoveFormatting,
    Undo,
    Redo,
    Code,
    Maximize2,
    Minimize2,
    Palette,
    Highlighter,
    Check,
    FileUp,
    Paperclip,
} from "lucide-vue-next";

const props = defineProps({
    modelValue: {
        type: String,
        default: "",
    },
    placeholder: {
        type: String,
        default: "Ketik teks lengkap di sini seperti di Microsoft Word...",
    },
    minHeight: {
        type: String,
        default: "240px",
    },
    uploadFolder: {
        type: String,
        default: "website/uploads",
    },
});

const emit = defineEmits(["update:modelValue"]);

const editorRef = ref(null);
const isSourceMode = ref(false);
const isFullscreen = ref(false);
const sourceHtml = ref("");
const isUploading = ref(false);

const textColorInput = ref(null);
const bgColorInput = ref(null);

// Word & Character count
const wordCount = computed(() => {
    const text = isSourceMode.value
        ? sourceHtml.value.replace(/<[^>]*>/g, "")
        : editorRef.value?.innerText || "";
    const trimmed = text.trim();
    return trimmed ? trimmed.split(/\s+/).length : 0;
});

const charCount = computed(() => {
    const text = isSourceMode.value
        ? sourceHtml.value.replace(/<[^>]*>/g, "")
        : editorRef.value?.innerText || "";
    return text.length;
});

// Sync from external modelValue to contenteditable
watch(
    () => props.modelValue,
    (newVal) => {
        const val = newVal ?? "";
        if (isSourceMode.value) {
            sourceHtml.value = val;
        } else if (editorRef.value && editorRef.value.innerHTML !== val) {
            editorRef.value.innerHTML = val;
        }
    },
    { immediate: true },
);

onMounted(() => {
    if (editorRef.value) {
        editorRef.value.innerHTML = props.modelValue ?? "";
    }
});

const onInput = () => {
    if (!editorRef.value) return;
    const html = editorRef.value.innerHTML;
    emit("update:modelValue", html);
};

const exec = (command, value = null) => {
    if (isSourceMode.value) return;
    editorRef.value?.focus();
    document.execCommand(command, false, value);
    onInput();
};

const setFormatBlock = (e) => {
    const val = e.target.value;
    if (!val) return;
    exec("formatBlock", val);
    e.target.value = "";
};

const promptLink = () => {
    const prevUrl = "";
    const url = prompt(
        "Masukkan tautan link URL (contoh: https://...):",
        prevUrl,
    );
    if (url) {
        exec("createLink", url);
    }
};

const triggerImageUpload = () => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = "image/*";
    input.onchange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        isUploading.value = true;
        try {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("folder", props.uploadFolder);
            formData.append("name", "konten-editor");

            const res = await window.axios.post(
                "/admin/upload-media",
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                },
            );

            if (res.data?.url) {
                editorRef.value?.focus();
                const imgHtml = `<p><img src="${res.data.url}" class="rounded-2xl max-w-full my-4 border border-slate-200 shadow-sm" alt="Gambar Konten" /></p><p><br></p>`;
                document.execCommand("insertHTML", false, imgHtml);
                onInput();
            }
        } catch (err) {
            console.error("Gagal unggah gambar", err);
            alert(
                "Gagal mengunggah gambar. Pastikan ukuran file di bawah 20MB.",
            );
        } finally {
            isUploading.value = false;
        }
    };
    input.click();
};

const triggerDocumentUpload = () => {
    const input = document.createElement("input");
    input.type = "file";
    input.accept = ".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar";
    input.onchange = async (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        isUploading.value = true;
        try {
            const formData = new FormData();
            formData.append("file", file);
            formData.append("folder", props.uploadFolder);
            formData.append("name", "dokumen-lampiran");

            const res = await window.axios.post(
                "/admin/upload-media",
                formData,
                {
                    headers: { "Content-Type": "multipart/form-data" },
                },
            );

            if (res.data?.url) {
                editorRef.value?.focus();
                const isPdf = file.name.toLowerCase().endsWith(".pdf");
                const badgeLabel = isPdf ? "PDF" : "DOC";
                const docHtml = `<p><a href="${res.data.url}" target="_blank" class="inline-flex items-center gap-2.5 px-4 py-2.5 my-3 bg-slate-50 hover:bg-slate-100 border border-slate-300 rounded-xl text-xs font-bold text-blue-700 transition-all shadow-2xs cursor-pointer"><span class="w-6 h-6 rounded-lg bg-blue-600 text-white inline-flex items-center justify-center text-[10px] font-mono shrink-0">${badgeLabel}</span><span>Lampiran: ${file.name}</span></a></p><p><br></p>`;
                document.execCommand("insertHTML", false, docHtml);
                onInput();
            }
        } catch (err) {
            console.error("Gagal unggah dokumen", err);
            alert(
                "Gagal mengunggah dokumen. Pastikan ukuran file di bawah 20MB.",
            );
        } finally {
            isUploading.value = false;
        }
    };
    input.click();
};

const insertContent = (html) => {
    if (isSourceMode.value) {
        sourceHtml.value += html;
        emit("update:modelValue", sourceHtml.value);
    } else {
        editorRef.value?.focus();
        document.execCommand("insertHTML", false, html);
        onInput();
    }
};

defineExpose({
    insertContent,
});

const insertTable = () => {
    const tableHtml = `
        <table class="w-full border-collapse border border-slate-300 my-4 text-xs">
            <thead>
                <tr class="bg-slate-100">
                    <th class="border border-slate-300 p-2 font-bold text-left">Header 1</th>
                    <th class="border border-slate-300 p-2 font-bold text-left">Header 2</th>
                    <th class="border border-slate-300 p-2 font-bold text-left">Header 3</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="border border-slate-300 p-2">Baris 1, Kolom 1</td>
                    <td class="border border-slate-300 p-2">Baris 1, Kolom 2</td>
                    <td class="border border-slate-300 p-2">Baris 1, Kolom 3</td>
                </tr>
                <tr>
                    <td class="border border-slate-300 p-2">Baris 2, Kolom 1</td>
                    <td class="border border-slate-300 p-2">Baris 2, Kolom 2</td>
                    <td class="border border-slate-300 p-2">Baris 2, Kolom 3</td>
                </tr>
            </tbody>
        </table>
        <p><br></p>
    `;
    editorRef.value?.focus();
    document.execCommand("insertHTML", false, tableHtml);
    onInput();
};

const toggleSourceMode = () => {
    if (isSourceMode.value) {
        // Return from source to visual
        if (editorRef.value) {
            editorRef.value.innerHTML = sourceHtml.value;
        }
        emit("update:modelValue", sourceHtml.value);
        isSourceMode.value = false;
    } else {
        // Switch to source
        sourceHtml.value = editorRef.value?.innerHTML || "";
        isSourceMode.value = true;
    }
};

const onSourceInput = (e) => {
    sourceHtml.value = e.target.value;
    emit("update:modelValue", sourceHtml.value);
};

const toggleFullscreen = () => {
    isFullscreen.value = !isFullscreen.value;
};
</script>

<template>
    <div
        :class="[
            'border border-slate-300 rounded-2xl bg-white shadow-xs overflow-hidden flex flex-col transition-all',
            isFullscreen
                ? 'fixed inset-0 z-50 rounded-none h-screen w-screen p-6 bg-slate-900/40 backdrop-blur-xs flex items-center justify-center'
                : '',
        ]"
    >
        <div
            :class="[
                'w-full flex flex-col bg-white border border-slate-200 rounded-2xl overflow-hidden shadow-sm h-full',
                isFullscreen ? 'max-w-6xl max-h-[95vh]' : '',
            ]"
        >
            <!-- WORD-STYLE TOOLBAR RIBBON -->
            <div
                class="bg-slate-50 border-b border-slate-200 p-2 flex flex-wrap items-center gap-1 text-slate-700 select-none"
            >
                <!-- Undo / Redo -->
                <div
                    class="flex items-center border-r border-slate-200 pr-1.5 mr-0.5 gap-0.5"
                >
                    <button
                        type="button"
                        @click="exec('undo')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Urungkan (Undo) - Ctrl+Z"
                    >
                        <Undo class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('redo')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Ulangi (Redo) - Ctrl+Y"
                    >
                        <Redo class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Format / Heading Select -->
                <div class="border-r border-slate-200 pr-1.5 mr-0.5">
                    <select
                        @change="setFormatBlock"
                        class="h-7 text-xs font-semibold bg-white border border-slate-200 rounded-lg px-2 py-0.5 text-slate-700 focus:outline-none focus:ring-1 focus:ring-blue-600 cursor-pointer"
                        title="Gaya Teks & Judul"
                    >
                        <option value="">Gaya Teks...</option>
                        <option value="p">Paragraf Normal</option>
                        <option value="h1">Judul Utama (H1)</option>
                        <option value="h2">Sub Judul (H2)</option>
                        <option value="h3">Bagian Kecil (H3)</option>
                        <option value="blockquote">Kutipan (Quote)</option>
                        <option value="pre">Blok Kode / Monospace</option>
                    </select>
                </div>

                <!-- Font Formatting: Bold, Italic, Underline, Strike -->
                <div
                    class="flex items-center border-r border-slate-200 pr-1.5 mr-0.5 gap-0.5"
                >
                    <button
                        type="button"
                        @click="exec('bold')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-700 font-bold transition-colors"
                        title="Tebal (Bold) - Ctrl+B"
                    >
                        <Bold class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('italic')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-700 italic transition-colors"
                        title="Miring (Italic) - Ctrl+I"
                    >
                        <Italic class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('underline')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-700 underline transition-colors"
                        title="Garis Bawah (Underline) - Ctrl+U"
                    >
                        <Underline class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('strikeThrough')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-700 line-through transition-colors"
                        title="Coret (Strikethrough)"
                    >
                        <Strikethrough class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Alignment: Left, Center, Right, Justify -->
                <div
                    class="flex items-center border-r border-slate-200 pr-1.5 mr-0.5 gap-0.5"
                >
                    <button
                        type="button"
                        @click="exec('justifyLeft')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Rata Kiri (Align Left)"
                    >
                        <AlignLeft class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('justifyCenter')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Rata Tengah (Align Center)"
                    >
                        <AlignCenter class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('justifyRight')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Rata Kanan (Align Right)"
                    >
                        <AlignRight class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('justifyFull')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Rata Kiri Kanan (Justify)"
                    >
                        <AlignJustify class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Lists & Indent -->
                <div
                    class="flex items-center border-r border-slate-200 pr-1.5 mr-0.5 gap-0.5"
                >
                    <button
                        type="button"
                        @click="exec('insertUnorderedList')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Daftar Simbol (Bullet List)"
                    >
                        <List class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('insertOrderedList')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Daftar Nomor (Numbered List)"
                    >
                        <ListOrdered class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('outdent')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Kurangi Indentasi"
                    >
                        <Outdent class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('indent')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Tambah Indentasi"
                    >
                        <Indent class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Color Pickers -->
                <div
                    class="flex items-center border-r border-slate-200 pr-1.5 mr-0.5 gap-1"
                >
                    <!-- Text Color -->
                    <label
                        class="relative p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 cursor-pointer transition-colors"
                        title="Warna Teks (Text Color)"
                    >
                        <Palette class="w-3.5 h-3.5 text-blue-600" />
                        <input
                            ref="textColorInput"
                            type="color"
                            @change="(e) => exec('foreColor', e.target.value)"
                            class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
                        />
                    </label>

                    <!-- Background Highlighter -->
                    <label
                        class="relative p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 cursor-pointer transition-colors"
                        title="Sorot Warna Teks (Highlighter)"
                    >
                        <Highlighter class="w-3.5 h-3.5 text-amber-500" />
                        <input
                            ref="bgColorInput"
                            type="color"
                            value="#fef08a"
                            @change="(e) => exec('hiliteColor', e.target.value)"
                            class="absolute inset-0 opacity-0 w-full h-full cursor-pointer"
                        />
                    </label>
                </div>

                <!-- Insert Objects: Link, Image, Table, Line -->
                <div
                    class="flex items-center border-r border-slate-200 pr-1.5 mr-0.5 gap-0.5"
                >
                    <button
                        type="button"
                        @click="promptLink"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Sisipkan Tautan (Link)"
                    >
                        <Link2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('unlink')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Hapus Tautan (Unlink)"
                    >
                        <Unlink class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="triggerImageUpload"
                        :disabled="isUploading"
                        class="p-1.5 rounded-lg hover:bg-blue-50 text-blue-600 transition-colors font-semibold"
                        title="Sisipkan Gambar dari Komputer (Otomatis AVIF)"
                    >
                        <ImagePlus class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="triggerDocumentUpload"
                        :disabled="isUploading"
                        class="p-1.5 rounded-lg hover:bg-emerald-50 text-emerald-600 transition-colors font-semibold"
                        title="Sisipkan Lampiran File / Dokumen (PDF, Word, Excel, dll)"
                    >
                        <Paperclip class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="insertTable"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Sisipkan Tabel (3x3 Table)"
                    >
                        <Table2 class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="exec('insertHorizontalRule')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        title="Garis Pembatas Horizontal"
                    >
                        <Minus class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Utilities: Clear Format, Code, Fullscreen -->
                <div class="flex items-center ml-auto gap-0.5">
                    <button
                        type="button"
                        @click="exec('removeFormat')"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-500 hover:text-slate-800 transition-colors"
                        title="Hapus Semua Format Teks"
                    >
                        <RemoveFormatting class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="toggleSourceMode"
                        :class="[
                            'p-1.5 rounded-lg transition-colors font-mono text-xs',
                            isSourceMode
                                ? 'bg-blue-600 text-white font-bold'
                                : 'hover:bg-slate-200 text-slate-600',
                        ]"
                        title="Lihat / Edit Kode HTML Langsung"
                    >
                        <Code class="w-3.5 h-3.5" />
                    </button>
                    <button
                        type="button"
                        @click="toggleFullscreen"
                        class="p-1.5 rounded-lg hover:bg-slate-200 text-slate-600 transition-colors"
                        :title="
                            isFullscreen
                                ? 'Keluar Layar Penuh'
                                : 'Mode Layar Penuh (Word View)'
                        "
                    >
                        <Minimize2
                            v-if="isFullscreen"
                            class="w-3.5 h-3.5 text-blue-600"
                        />
                        <Maximize2 v-else class="w-3.5 h-3.5" />
                    </button>
                </div>
            </div>

            <!-- Uploading indicator -->
            <div
                v-if="isUploading"
                class="bg-blue-50 px-4 py-1.5 text-blue-700 text-xs font-semibold flex items-center gap-2 border-b border-blue-100"
            >
                <span
                    class="inline-block w-2.5 h-2.5 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"
                ></span>
                <span>Mengunggah dan mengonversi gambar ke format AVIF...</span>
            </div>

            <!-- EDITOR CANVAS -->
            <div class="relative flex-1 bg-white overflow-y-auto">
                <!-- Source Code View -->
                <textarea
                    v-if="isSourceMode"
                    :value="sourceHtml"
                    @input="onSourceInput"
                    class="w-full h-full p-4 font-mono text-xs text-slate-800 bg-slate-900 text-emerald-400 focus:outline-none resize-none leading-relaxed"
                    :style="{ minHeight }"
                ></textarea>

                <!-- Visual Contenteditable Canvas (Styled like Microsoft Word Document) -->
                <div
                    v-else
                    ref="editorRef"
                    contenteditable="true"
                    @input="onInput"
                    :style="{ minHeight }"
                    class="p-6 focus:outline-none text-slate-900 text-sm leading-relaxed [&_p]:my-2.5 [&_p]:leading-relaxed [&_h1]:text-2xl [&_h1]:font-black [&_h1]:text-slate-900 [&_h1]:mt-5 [&_h1]:mb-2 [&_h1]:tracking-tight [&_h2]:text-xl [&_h2]:font-extrabold [&_h2]:text-slate-900 [&_h2]:mt-4 [&_h2]:mb-2 [&_h3]:text-lg [&_h3]:font-bold [&_h3]:text-slate-800 [&_h3]:mt-3 [&_h3]:mb-1.5 [&_ul]:list-disc [&_ul]:pl-6 [&_ul]:my-3 [&_ul_li]:my-1 [&_ol]:list-decimal [&_ol]:pl-6 [&_ol]:my-3 [&_ol_li]:my-1 [&_blockquote]:border-l-4 [&_blockquote]:border-blue-600 [&_blockquote]:bg-blue-50/40 [&_blockquote]:py-2 [&_blockquote]:px-4 [&_blockquote]:rounded-r-xl [&_blockquote]:italic [&_blockquote]:my-4 [&_blockquote]:text-slate-700 [&_table]:w-full [&_table]:border-collapse [&_table]:border [&_table]:border-slate-300 [&_table]:my-4 [&_th]:border [&_th]:border-slate-300 [&_th]:p-2.5 [&_th]:bg-slate-100 [&_th]:font-bold [&_th]:text-xs [&_th]:text-slate-700 [&_td]:border [&_td]:border-slate-300 [&_td]:p-2.5 [&_td]:text-xs [&_td]:text-slate-600 [&_a]:text-blue-600 [&_a]:underline [&_a]:font-semibold [&_a:hover]:text-blue-800 [&_hr]:border-t [&_hr]:border-slate-200 [&_hr]:my-6 [&_img]:rounded-2xl [&_img]:border [&_img]:border-slate-200 [&_img]:shadow-xs [&_img]:my-4 [&_img]:max-w-full"
                ></div>
            </div>

            <!-- STATUS BAR / FOOTER -->
            <div
                class="bg-slate-50 border-t border-slate-200 px-4 py-2 flex items-center justify-between text-[11px] text-slate-500 font-mono select-none"
            >
                <div class="flex items-center gap-3">
                    <span
                        v-if="isSourceMode"
                        class="text-amber-600 font-bold flex items-center gap-1"
                    >
                        Mode Kode Sumber (HTML)
                    </span>
                    <span
                        v-else
                        class="text-slate-600 font-semibold flex items-center gap-1"
                    >
                        <Check class="w-3.5 h-3.5 text-emerald-600" /> Mode
                        Visual Word
                    </span>
                    <span>•</span>
                    <span>{{ wordCount }} kata</span>
                    <span>•</span>
                    <span>{{ charCount }} karakter</span>
                </div>
                <div class="text-[10px] text-slate-400">
                    Mendukung format Microsoft Word, cetak tabel, & gambar
                </div>
            </div>
        </div>
    </div>
</template>
