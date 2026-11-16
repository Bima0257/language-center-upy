<script setup>
import { computed, h, ref } from "vue";
import { IconEdit, IconPlus, IconTrash } from "@tabler/icons-vue";
import { toast } from "vue-sonner";
import BaseBadge from "@/Components/Base/BaseBadge.vue";
import BaseButton from "@/Components/Base/BaseButton.vue";
import AppDataTable from "@/Components/Ui/AppDataTable.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import DropDown from "@/Components/Shared/DropDown.vue";
import Pagination from "@/Components/Shared/Pagination.vue";
import TableFilterBar from "@/Components/Shared/TableFilterBar.vue";
import PreviewCard from "@/Pages/Admin/Komponen/Components/KartuPratinjau.vue";
import GrupPratinjau from "@/Pages/Admin/Komponen/Components/GrupPratinjau.vue";

defineProps({
    group: { type: Object, required: true },
    componentMeta: { type: Object, required: true },
    isVisible: { type: Function, required: true },
    paginationLinks: { type: Array, default: () => [] },
    chartLabels: { type: Array, default: () => [] },
    chartData: { type: Array, default: () => [] },
    chartBreakdown: { type: Array, default: () => [] },
});

const filterBarSearch = ref("");
const filterBarStatus = ref("");
const filterBarStatusOptions = [
    { id: "all", nama: "Semua status" },
    { id: "active", nama: "Aktif" },
    { id: "inactive", nama: "Nonaktif" },
];

const appTableLoading = ref(false);
const appTableVariant = ref("default");
const activeAppTableTab = ref("semua");
const appTablePageSizeOptions = [5, 10, 25];

const appTableTabs = [
    { value: "semua", label: "Semua" },
    { value: "aktif", label: "Aktif" },
    { value: "nonaktif", label: "Nonaktif" },
];

const appTableFixedFilters = computed(() => {
    if (activeAppTableTab.value === "aktif") return { status: "Aktif" };
    if (activeAppTableTab.value === "nonaktif") return { status: "Nonaktif" };

    return {};
});

const appTableFilters = [
    {
        key: "section",
        label: "Section",
        options: [
            { value: "Reading", label: "Reading" },
            { value: "Listening", label: "Listening" },
            { value: "Speaking", label: "Speaking" },
            { value: "Writing", label: "Writing" },
        ],
    },
];

const appTableData = [
    { id: 1, nama: "Aisyah Putri", email: "aisyah.putri@example.test", section: "Reading", status: "Aktif" },
    { id: 2, nama: "Bagas Pratama", email: "bagas.pratama@example.test", section: "Listening", status: "Nonaktif" },
    { id: 3, nama: "Citra Lestari", email: "citra.lestari@example.test", section: "Speaking", status: "Aktif" },
    { id: 4, nama: "Dimas Saputra", email: "dimas.saputra@example.test", section: "Writing", status: "Aktif" },
    { id: 5, nama: "Eka Wulandari", email: "eka.wulandari@example.test", section: "Reading", status: "Nonaktif" },
    { id: 6, nama: "Fajar Nugroho", email: "fajar.nugroho@example.test", section: "Listening", status: "Aktif" },
    { id: 7, nama: "Gita Maharani", email: "gita.maharani@example.test", section: "Speaking", status: "Aktif" },
    { id: 8, nama: "Hendra Wijaya", email: "hendra.wijaya@example.test", section: "Writing", status: "Nonaktif" },
    { id: 9, nama: "Indah Permata", email: "indah.permata@example.test", section: "Reading", status: "Aktif" },
    { id: 10, nama: "Joko Susilo", email: "joko.susilo@example.test", section: "Listening", status: "Nonaktif" },
    { id: 11, nama: "Kartika Sari", email: "kartika.sari@example.test", section: "Speaking", status: "Aktif" },
    { id: 12, nama: "Lukman Hakim", email: "lukman.hakim@example.test", section: "Writing", status: "Aktif" },
];

function notifyRowAction(action, row) {
    toast(`${action} ${row.nama}.`);
}

function notifyAppTableCreate() {
    toast("Aksi tambah data (demo).");
}

function toggleAppTableLoading() {
    appTableLoading.value = !appTableLoading.value;

    if (appTableLoading.value) {
        window.setTimeout(() => {
            appTableLoading.value = false;
        }, 1400);
    }
}

function toggleAppTableVariant() {
    appTableVariant.value = appTableVariant.value === "default" ? "activity" : "default";
}

const appTableColumns = [
    {
        accessorKey: "nama",
        header: "Nama Peserta",
        minWidth: 180,
        meta: { group: "Identitas", align: "left" },
    },
    {
        accessorKey: "email",
        header: "Email",
        minWidth: 190,
        meta: { group: "Identitas", align: "left" },
    },
    {
        accessorKey: "section",
        header: "Section",
        minWidth: 120,
        meta: { align: "center" },
        cell: info => h("span", { class: "font-medium text-text-heading" }, info.getValue()),
    },
    {
        accessorKey: "status",
        header: "Status",
        minWidth: 100,
        meta: { align: "center" },
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() === "Aktif" ? "success" : "neutral" },
            () => info.getValue(),
        ),
    },
    {
        id: "actions",
        header: "Aksi",
        meta: { align: "center" },
        cell: info => h("div", { class: "flex items-center justify-center gap-1" }, [
            h(
                BaseButton,
                {
                    variant: "secondary",
                    size: "sm",
                    iconOnly: true,
                    title: "Ubah peserta",
                    "aria-label": "Ubah peserta",
                    onClick: () => notifyRowAction("Ubah", info.row.original),
                },
                () => h(IconEdit, { size: 16 }),
            ),
            h(
                BaseButton,
                {
                    variant: "danger",
                    size: "sm",
                    iconOnly: true,
                    title: "Hapus peserta",
                    "aria-label": "Hapus peserta",
                    onClick: () => notifyRowAction("Hapus", info.row.original),
                },
                () => h(IconTrash, { size: 16 }),
            ),
        ]),
    },
];
</script>

<template>
    <GrupPratinjau :group="group">
        <PreviewCard
            v-if="isVisible('TableFilterBar')"
            name="TableFilterBar"
            scope="Reusable"
            :description="componentMeta.TableFilterBar.description"
            :path="componentMeta.TableFilterBar.path"
        >
            <div class="space-y-3">
                <TableFilterBar
                    v-model:search="filterBarSearch"
                    search-placeholder="Cari peserta..."
                >
                    <template #filters>
                        <DropDown
                            v-model="filterBarStatus"
                            :options="filterBarStatusOptions"
                            option-label="nama"
                            option-value="id"
                            placeholder="Semua status"
                            size="sm"
                            button-class="min-w-[140px]"
                            clearable
                            menu-placement="auto"
                            teleport-menu
                        />
                    </template>
                </TableFilterBar>
                <p class="text-label-md text-text-muted">
                    Search dan filter tambahan dapat dikendalikan oleh parent melalui model dan slot.
                </p>
            </div>
        </PreviewCard>

        <div v-if="isVisible('AppDataTable')" class="xl:col-span-2">
            <PreviewCard
                name="AppDataTable"
                scope="Reusable"
                :description="componentMeta.AppDataTable.description"
                :path="componentMeta.AppDataTable.path"
            >
                <div class="space-y-3">
                    <AppDataTable
                        table-key="preview-app-data-table"
                        title="Daftar Peserta"
                        :columns="appTableColumns"
                        :data="appTableData"
                        :filters="appTableFilters"
                        :fixed-filters="appTableFixedFilters"
                        :tabs="appTableTabs"
                        :active-tab="activeAppTableTab"
                        :page-size="5"
                        :page-size-options="appTablePageSizeOptions"
                        :loading="appTableLoading"
                        :variant="appTableVariant"
                        :persist-state="false"
                        toolbar-start-align="left"
                        @update:active-tab="activeAppTableTab = $event"
                    >
                        <template #actions>
                            <BaseButton size="sm" @click="notifyAppTableCreate">
                                <IconPlus :size="16" />
                                Tambah
                            </BaseButton>
                        </template>

                        <template #toolbar-start>
                            <BaseBadge variant="pastel-blue">Live preview</BaseBadge>
                        </template>
                    </AppDataTable>

                    <div class="flex flex-wrap items-center gap-2">
                        <BaseButton size="sm" variant="secondary" @click="toggleAppTableLoading">
                            {{ appTableLoading ? "Hentikan pemuatan" : "Pratinjau status pemuatan" }}
                        </BaseButton>
                        <BaseButton size="sm" variant="secondary" @click="toggleAppTableVariant">
                            Varian: {{ appTableVariant === "activity" ? "Activity" : "Default" }}
                        </BaseButton>
                        <span class="text-label-md text-text-muted">
                            Demo client-side. Mode server-side memerlukan
                            <code>reloadOnly</code> dan <code>serverTotalRows</code>.
                        </span>
                    </div>
                </div>
            </PreviewCard>
        </div>

        <PreviewCard
            v-if="isVisible('Pagination')"
            name="Pagination"
            scope="Reusable"
            :description="componentMeta.Pagination.description"
            :path="componentMeta.Pagination.path"
        >
            <div class="space-y-3">
                <Pagination :links="paginationLinks" :total="6" :per-page="3" />
                <p class="text-label-md text-text-muted">
                    Fixture link ditampilkan tanpa request data aplikasi.
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BarChart')"
            name="BarChart"
            scope="Reusable"
            :description="componentMeta.BarChart.description"
            :path="componentMeta.BarChart.path"
        >
            <BarChart :labels="chartLabels" :data="chartData" title="Skor per section" />
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('DoughnutChart')"
            name="DoughnutChart"
            scope="Reusable"
            :description="componentMeta.DoughnutChart.description"
            :path="componentMeta.DoughnutChart.path"
        >
            <DoughnutChart
                :labels="chartLabels"
                :data="chartBreakdown"
                title="Distribusi latihan"
            />
        </PreviewCard>
    </GrupPratinjau>
</template>
