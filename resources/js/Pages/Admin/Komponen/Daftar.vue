<script setup>
import { computed, ref } from "vue";
import { Head } from "@inertiajs/vue3";
import { toast } from "vue-sonner";
import { IconFileDescription } from "@tabler/icons-vue";
import { useConfirm } from "@/Composables/useConfirm";
import TataLetakDasbor from "@/Layouts/TataLetakDasbor.vue";
import EmptyState from "@/Components/Shared/EmptyState.vue";
import FilterKatalogKomponen from "@/Pages/Admin/Komponen/Components/FilterKatalogKomponen.vue";
import RingkasanKatalogKomponen from "@/Pages/Admin/Komponen/Components/RingkasanKatalogKomponen.vue";
import PratinjauActions from "@/Pages/Admin/Komponen/Components/PratinjauActions.vue";
import PratinjauContent from "@/Pages/Admin/Komponen/Components/PratinjauContent.vue";
import PratinjauData from "@/Pages/Admin/Komponen/Components/PratinjauData.vue";
import PratinjauFeedback from "@/Pages/Admin/Komponen/Components/PratinjauFeedback.vue";
import PratinjauFoundations from "@/Pages/Admin/Komponen/Components/PratinjauFoundations.vue";
import PratinjauForms from "@/Pages/Admin/Komponen/Components/PratinjauForms.vue";

const scopeFilters = [
    { label: "Semua", value: "all" },
    { label: "Global", value: "global" },
    { label: "Reusable", value: "reusable" },
];

const componentMeta = {
    BaseCard: {
        scope: "global",
        path: "resources/js/Components/Base/BaseCard.vue",
        description: "Wadah standar untuk konten dan informasi.",
    },
    BaseBadge: {
        scope: "global",
        path: "resources/js/Components/Base/BaseBadge.vue",
        description: "Label ringkas untuk status dan kategori.",
    },
    BaseAvatar: {
        scope: "global",
        path: "resources/js/Components/Base/BaseAvatar.vue",
        description: "Avatar dengan gambar atau inisial otomatis.",
    },
    EmptyState: {
        scope: "reusable",
        path: "resources/js/Components/Shared/EmptyState.vue",
        description: "Tampilan saat koleksi belum memiliki data.",
    },
    StatusBadge: {
        scope: "reusable",
        path: "resources/js/Components/Shared/StatusBadge.vue",
        description: "Badge dengan label dan warna berbasis status.",
    },
    BaseButton: {
        scope: "global",
        path: "resources/js/Components/Base/BaseButton.vue",
        description: "Aksi utama dengan variant dan ukuran konsisten.",
    },
    BaseToggle: {
        scope: "global",
        path: "resources/js/Components/Base/BaseToggle.vue",
        description: "Kontrol on/off untuk preferensi boolean.",
    },
    BaseTabs: {
        scope: "global",
        path: "resources/js/Components/Base/BaseTabs.vue",
        description: "Navigasi pill untuk berpindah konteks tampilan.",
    },
    BaseTabsChrome: {
        scope: "global",
        path: "resources/js/Components/Base/BaseTabsChrome.vue",
        description: "Navigasi tab bergaya browser yang menempel pada konten.",
    },
    BaseAccordion: {
        scope: "global",
        path: "resources/js/Components/Base/BaseAccordion.vue",
        description: "Panel yang dapat dibuka dan ditutup.",
    },
    BaseTooltip: {
        scope: "global",
        path: "resources/js/Components/Base/BaseTooltip.vue",
        description: "Bantuan singkat saat pointer berada di atas elemen.",
    },
    BaseFieldLabel: {
        scope: "global",
        path: "resources/js/Components/Base/BaseFieldLabel.vue",
        description: "Label field dengan tipografi standar.",
    },
    BaseInput: {
        scope: "global",
        path: "resources/js/Components/Base/BaseInput.vue",
        description: "Input teks dengan focus dan error state.",
    },
    BaseInputError: {
        scope: "global",
        path: "resources/js/Components/Base/BaseInputError.vue",
        description: "Pesan validasi untuk field formulir.",
    },
    BaseTextarea: {
        scope: "global",
        path: "resources/js/Components/Base/BaseTextarea.vue",
        description: "Input multi-baris dengan style yang sama.",
    },
    BaseRadio: {
        scope: "global",
        path: "resources/js/Components/Base/BaseRadio.vue",
        description: "Pilihan tunggal dengan mode vertikal atau inline.",
    },
    DropDown: {
        scope: "reusable",
        path: "resources/js/Components/Shared/DropDown.vue",
        description: "Listbox dengan pencocokan value dan clear action.",
    },
    FileUpload: {
        scope: "reusable",
        path: "resources/js/Components/Shared/FileUpload.vue",
        description: "Upload file inline atau berbentuk dropzone.",
    },
    PasswordStrengthCheck: {
        scope: "reusable",
        path: "resources/js/Components/Shared/PasswordStrengthCheck.vue",
        description: "Indikator aturan kekuatan password secara langsung.",
    },
    BaseSkeleton: {
        scope: "global",
        path: "resources/js/Components/Base/BaseSkeleton.vue",
        description: "Placeholder loading untuk konten yang belum siap.",
    },
    BaseMediaLoader: {
        scope: "global",
        path: "resources/js/Components/Base/BaseMediaLoader.vue",
        description: "Loading state untuk media gambar dan audio.",
    },
    RichTextEditor: {
        scope: "reusable",
        path: "resources/js/Components/Shared/RichTextEditor.vue",
        description: "Editor konten dengan toolbar format dasar.",
    },
    RichTextViewer: {
        scope: "reusable",
        path: "resources/js/Components/Shared/RichTextViewer.vue",
        description: "Renderer HTML yang disanitasi untuk konten rich text.",
    },
    DataTable: {
        scope: "reusable",
        path: "resources/js/Components/Shared/DataTable.vue",
        description: "Tabel client-side berbasis TanStack dengan search, filter, sorting, badge, page size, dan pagination.",
    },
    Pagination: {
        scope: "reusable",
        path: "resources/js/Components/Shared/Pagination.vue",
        description: "Navigasi halaman berbasis link Inertia.",
    },
    BarChart: {
        scope: "reusable",
        path: "resources/js/Components/Charts/BarChart.vue",
        description: "Visualisasi batang berbasis Chart.js.",
    },
    DoughnutChart: {
        scope: "reusable",
        path: "resources/js/Components/Charts/DoughnutChart.vue",
        description: "Visualisasi proporsi berbentuk doughnut.",
    },
    BaseModal: {
        scope: "global",
        path: "resources/js/Components/Base/BaseModal.vue",
        description: "Overlay dialog dengan transition dan scroll lock.",
    },
    UserGuide: {
        scope: "reusable",
        path: "resources/js/Components/Shared/UserGuide.vue",
        description: "Dialog panduan dengan langkah dan ketentuan.",
    },
    UploadProgressBar: {
        scope: "reusable",
        path: "resources/js/Components/Shared/UploadProgressBar.vue",
        description: "Overlay proses upload dan kompresi media.",
    },
    ConfirmDialog: {
        scope: "global",
        path: "resources/js/Components/Shared/ConfirmDialog.vue",
        description: "Dialog konfirmasi yang dipanggil melalui useConfirm.",
    },
    ToastProvider: {
        scope: "global",
        path: "resources/js/Components/ToastProvider.vue",
        description: "Provider notifikasi toast yang dipasang di app shell.",
    },
    ScrollToTop: {
        scope: "global",
        path: "resources/js/Components/Shared/ScrollToTop.vue",
        description: "Aksi kembali ke atas untuk halaman yang panjang.",
    },
};

const componentGroups = [
    {
        id: "foundations",
        title: "Foundations",
        description: "Elemen visual dasar yang dipakai lintas halaman.",
        components: [
            "BaseCard",
            "BaseBadge",
            "BaseAvatar",
            "EmptyState",
            "StatusBadge",
        ],
    },
    {
        id: "actions",
        title: "Actions dan navigasi",
        description: "Kontrol aksi, pilihan, dan navigasi konteks.",
        components: [
            "BaseButton",
            "BaseToggle",
            "BaseTabs",
            "BaseTabsChrome",
            "BaseAccordion",
            "BaseTooltip",
        ],
    },
    {
        id: "forms",
        title: "Forms dan selection",
        description: "Field input dan komponen pemilihan data.",
        components: [
            "BaseFieldLabel",
            "BaseInput",
            "BaseInputError",
            "BaseTextarea",
            "BaseRadio",
            "DropDown",
            "FileUpload",
            "PasswordStrengthCheck",
        ],
    },
    {
        id: "content",
        title: "Content dan media",
        description: "Komponen untuk loading, editing, dan rendering konten.",
        components: [
            "BaseSkeleton",
            "BaseMediaLoader",
            "RichTextEditor",
            "RichTextViewer",
        ],
    },
    {
        id: "data",
        title: "Data dan visualisasi",
        description: "Komponen untuk data tabular dan visualisasi metrik.",
        components: ["DataTable", "Pagination", "BarChart", "DoughnutChart"],
    },
    {
        id: "feedback",
        title: "Feedback dan overlay",
        description: "Feedback proses, dialog, notifikasi, dan utilitas halaman.",
        components: [
            "BaseModal",
            "UserGuide",
            "UploadProgressBar",
            "ConfirmDialog",
            "ToastProvider",
            "ScrollToTop",
        ],
    },
];

const activeScope = ref("all");
const searchQuery = ref("");
const inputValue = ref("");
const textareaValue = ref("Teks contoh untuk komponen textarea.");
const radioValue = ref("reading");
const dropdownValue = ref("reading");
const toggleValue = ref(true);
const activeTab = ref("overview");
const fileValue = ref(null);
const passwordValue = ref("Preview1!");
const mediaLoading = ref(true);
const richText = ref(
    "<p><strong>Konten contoh</strong> untuk melihat hasil editor secara langsung.</p>",
);
const modalOpen = ref(false);
const progressVisible = ref(false);
const tableLoading = ref(false);
const tableSearch = ref("");

const confirmApi = useConfirm();

const demoTabs = [
    { label: "Overview", value: "overview" },
    { label: "Aktivitas", value: "activity" },
    { label: "Pengaturan", value: "settings" },
];

const dropdownOptions = [
    { id: "reading", name: "Reading" },
    { id: "listening", name: "Listening" },
    { id: "speaking", name: "Speaking" },
    { id: "writing", name: "Writing" },
];

const radioOptions = [
    { value: "reading", label: "Reading" },
    { value: "listening", label: "Listening" },
    { value: "speaking", label: "Speaking" },
];

const tableColumns = [
    { key: "component", label: "Komponen", sortable: true },
    { key: "scope", label: "Scope", sortable: true },
    { key: "status", label: "Status", badge: true },
];

const tableData = [
    { component: "BaseButton", scope: "Global", status: "Aktif" },
    { component: "DataTable", scope: "Reusable", status: "Aktif" },
    { component: "RichTextEditor", scope: "Reusable", status: "Aktif" },
];

const paginationLinks = [
    { label: "&laquo;", url: "#", active: false },
    { label: "1", url: "#", active: true },
    { label: "2", url: "#", active: false },
    { label: "&raquo;", url: "#", active: false },
];

const userGuideSteps = [
    { title: "Pilih komponen", desc: "Gunakan filter atau pencarian untuk menemukan komponen." },
    { title: "Uji state", desc: "Coba state aktif, disabled, loading, dan error yang tersedia." },
];

const chartLabels = ["Reading", "Listening", "Speaking", "Writing"];
const chartData = [82, 74, 89, 78];
const chartBreakdown = [35, 25, 20, 20];

const normalizedQuery = computed(() => searchQuery.value.trim().toLowerCase());
const componentCount = Object.keys(componentMeta).length;
const globalComponentCount = computed(
    () => Object.values(componentMeta).filter((item) => item.scope === "global").length,
);
const reusableComponentCount = computed(
    () => Object.values(componentMeta).filter((item) => item.scope === "reusable").length,
);

const visibleGroups = computed(() =>
    componentGroups.filter((group) =>
        group.components.some((componentName) => isVisible(componentName)),
    ),
);

const visibleCount = computed(() =>
    visibleGroups.value.reduce(
        (count, group) =>
            count + group.components.filter((componentName) => isVisible(componentName)).length,
        0,
    ),
);

function isVisible(componentName) {
    const meta = componentMeta[componentName];

    if (!meta) return false;
    if (activeScope.value !== "all" && meta.scope !== activeScope.value) return false;
    if (!normalizedQuery.value) return true;

    const searchableText = `${componentName} ${meta.path} ${meta.description}`.toLowerCase();

    return searchableText.includes(normalizedQuery.value);
}

function clearFilters() {
    activeScope.value = "all";
    searchQuery.value = "";
}

function notifyButtonAction() {
    toast.success("Aksi BaseButton berhasil dipanggil.");
}

function showToast(type) {
    if (type === "success") {
        toast.success("Perubahan berhasil disimpan.");
        return;
    }

    if (type === "error") {
        toast.error("Contoh notifikasi error.");
        return;
    }

    toast("Contoh notifikasi informasi.");
}

function toggleMediaLoading() {
    mediaLoading.value = !mediaLoading.value;
}

function showUploadProgress() {
    progressVisible.value = true;

    window.setTimeout(() => {
        progressVisible.value = false;
    }, 1800);
}

function toggleTableLoading() {
    tableLoading.value = !tableLoading.value;

    if (tableLoading.value) {
        window.setTimeout(() => {
            tableLoading.value = false;
        }, 1400);
    }
}

function updateTableSearch(value) {
    tableSearch.value = value;
}

async function showConfirmDialog() {
    const confirmed = await confirmApi.confirm("Lanjutkan contoh aksi ini?");

    if (confirmed) {
        toast.success("Konfirmasi diterima.");
        return;
    }

    toast("Konfirmasi dibatalkan.");
}
</script>

<template>
    <Head title="Pratinjau Komponen" />

    <TataLetakDasbor
        title="Pratinjau Komponen"
        subtitle="Katalog live preview untuk komponen global dan reusable"
    >
        <div class="space-y-6 pb-12">
            <FilterKatalogKomponen
                :active-scope="activeScope"
                :search-query="searchQuery"
                :scope-filters="scopeFilters"
                :visible-count="visibleCount"
                :component-count="componentCount"
                @update:active-scope="activeScope = $event"
                @update:search-query="searchQuery = $event"
                @reset="clearFilters"
            />

            <RingkasanKatalogKomponen
                :component-count="componentCount"
                :global-component-count="globalComponentCount"
                :reusable-component-count="reusableComponentCount"
            />

            <EmptyState
                v-if="visibleGroups.length === 0"
                :icon="IconFileDescription"
                title="Komponen tidak ditemukan"
                description="Coba gunakan kata kunci atau scope filter yang berbeda."
            />

            <template v-for="group in visibleGroups" :key="group.id">
                <PratinjauFoundations
                    v-if="group.id === 'foundations'"
                    :group="group"
                    :component-meta="componentMeta"
                    :is-visible="isVisible"
                />

                <PratinjauActions
                    v-else-if="group.id === 'actions'"
                    :group="group"
                    :component-meta="componentMeta"
                    :is-visible="isVisible"
                    :toggle-value="toggleValue"
                    :active-tab="activeTab"
                    :demo-tabs="demoTabs"
                    :notify-button-action="notifyButtonAction"
                    @update:toggle-value="toggleValue = $event"
                    @update:active-tab="activeTab = $event"
                />

                <PratinjauForms
                    v-else-if="group.id === 'forms'"
                    :group="group"
                    :component-meta="componentMeta"
                    :is-visible="isVisible"
                    :input-value="inputValue"
                    :textarea-value="textareaValue"
                    :radio-value="radioValue"
                    :dropdown-value="dropdownValue"
                    :file-value="fileValue"
                    :password-value="passwordValue"
                    :dropdown-options="dropdownOptions"
                    :radio-options="radioOptions"
                    @update:input-value="inputValue = $event"
                    @update:textarea-value="textareaValue = $event"
                    @update:radio-value="radioValue = $event"
                    @update:dropdown-value="dropdownValue = $event"
                    @update:file-value="fileValue = $event"
                    @update:password-value="passwordValue = $event"
                />

                <PratinjauContent
                    v-else-if="group.id === 'content'"
                    :group="group"
                    :component-meta="componentMeta"
                    :is-visible="isVisible"
                    :media-loading="mediaLoading"
                    :rich-text="richText"
                    :toggle-media-loading="toggleMediaLoading"
                    @update:rich-text="richText = $event"
                />

                <PratinjauData
                    v-else-if="group.id === 'data'"
                    :group="group"
                    :component-meta="componentMeta"
                    :is-visible="isVisible"
                    :table-columns="tableColumns"
                    :table-data="tableData"
                    :pagination-links="paginationLinks"
                    :table-loading="tableLoading"
                    :table-search="tableSearch"
                    :chart-labels="chartLabels"
                    :chart-data="chartData"
                    :chart-breakdown="chartBreakdown"
                    :toggle-table-loading="toggleTableLoading"
                    :update-table-search="updateTableSearch"
                />

                <PratinjauFeedback
                    v-else-if="group.id === 'feedback'"
                    :group="group"
                    :component-meta="componentMeta"
                    :is-visible="isVisible"
                    :modal-open="modalOpen"
                    :progress-visible="progressVisible"
                    :user-guide-steps="userGuideSteps"
                    :show-upload-progress="showUploadProgress"
                    :show-confirm-dialog="showConfirmDialog"
                    :show-toast="showToast"
                    @update:modal-open="modalOpen = $event"
                />
            </template>
        </div>
    </TataLetakDasbor>
</template>
