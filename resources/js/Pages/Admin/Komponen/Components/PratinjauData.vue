<script setup>
import { ref } from "vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import DataTable from "@/Components/Shared/DataTable.vue";
import DropDown from "@/Components/Shared/DropDown.vue";
import Pagination from "@/Components/Shared/Pagination.vue";
import TableFilterBar from "@/Components/Shared/TableFilterBar.vue";
import PreviewCard from "@/Pages/Admin/Komponen/Components/KartuPratinjau.vue";
import GrupPratinjau from "@/Pages/Admin/Komponen/Components/GrupPratinjau.vue";

defineProps({
    group: { type: Object, required: true },
    componentMeta: { type: Object, required: true },
    isVisible: { type: Function, required: true },
    tableColumns: { type: Array, default: () => [] },
    tableData: { type: Array, default: () => [] },
    paginationLinks: { type: Array, default: () => [] },
    tableLoading: { type: Boolean, required: true },
    tableSearch: { type: String, default: "" },
    chartLabels: { type: Array, default: () => [] },
    chartData: { type: Array, default: () => [] },
    chartBreakdown: { type: Array, default: () => [] },
    toggleTableLoading: { type: Function, required: true },
    updateTableSearch: { type: Function, required: true },
});

const filterBarSearch = ref("");
const filterBarStatus = ref("");
const filterBarStatusOptions = [
    { id: "all", nama: "Semua status" },
    { id: "active", nama: "Aktif" },
    { id: "inactive", nama: "Nonaktif" },
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

        <div v-if="isVisible('DataTable')" class="xl:col-span-2">
            <PreviewCard
                name="DataTable"
                scope="Reusable"
                :description="componentMeta.DataTable.description"
                :path="componentMeta.DataTable.path"
            >
                <div class="space-y-3">
                    <DataTable
                        :columns="tableColumns"
                        :data="tableData"
                        :loading="tableLoading"
                        searchable
                        search-placeholder="Cari komponen..."
                        :search-query="tableSearch"
                        @search="updateTableSearch"
                    />
                    <BaseButton size="sm" variant="secondary" @click="toggleTableLoading">
                        {{ tableLoading ? "Hentikan pemuatan" : "Pratinjau status pemuatan" }}
                    </BaseButton>
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
            <BarChart :labels="chartLabels" :data="chartData" title="Skor per keterampilan" />
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
