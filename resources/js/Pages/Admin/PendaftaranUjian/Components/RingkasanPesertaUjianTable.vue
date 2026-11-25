<script setup>
import { computed, h } from 'vue';
import { router } from '@inertiajs/vue3';
import { IconEye } from '@tabler/icons-vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import { statusPendaftaranVariant } from '@/constants/statusPendaftaran';

const props = defineProps({
    ringkasanUjian: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    tableFilters: { type: Array, default: () => [] },
    tableKey: { type: String, default: 'admin-pendaftaran-ujian-peserta-semua-ringkasan' },
    title: { type: String, default: 'Ringkasan Peserta per Ujian' },
    detailUrl: { type: Function, required: true },
});

const initialFilters = computed(() => {
    const keys = props.tableFilters.map(filter => filter.key);

    return Object.fromEntries(
        keys
            .filter(key => props.filters?.[key])
            .map(key => [key, props.filters[key]]),
    );
});

function openDetail(row) {
    router.get(props.detailUrl(row.id));
}

function statusItems(row) {
    return [
        ['pending', 'Menunggu', row.pendingCount],
        ['approved', 'Disetujui', row.approvedCount],
        ['rejected', 'Ditolak', row.rejectedCount],
        ['cancelled', 'Dibatalkan', row.cancelledCount],
    ];
}

const columns = computed(() => [
    {
        id: 'ujian',
        header: 'Ujian',
        enableSorting: false,
        cell: info => {
            const row = info.row.original;

            return h('div', [
                h('p', { class: 'font-semibold text-text-heading' }, row.judul || '-'),
                h('p', { class: 'text-label-md text-text-muted' }, row.programTesNama || '-'),
                h(
                    'div',
                    { class: 'mt-1' },
                    [
                        h(
                            BaseBadge,
                            { variant: row.mode === 'tryout' ? 'pastel-blue' : 'pastel' },
                            () => (row.mode === 'tryout' ? 'Try Out' : 'Ujian Resmi'),
                        ),
                    ],
                ),
            ]);
        },
    },
    {
        accessorKey: 'jumlahPendaftaran',
        header: 'Jumlah Pendaftaran',
        enableSorting: false,
        cell: info => h('span', { class: 'font-semibold text-text-heading' }, info.getValue() ?? 0),
    },
    {
        id: 'status',
        header: 'Status',
        enableSorting: false,
        cell: info => h(
            'div',
            { class: 'flex flex-wrap items-center gap-1' },
            statusItems(info.row.original).map(([status, label, count]) => h(
                BaseBadge,
                { variant: statusPendaftaranVariant(status) },
                () => `${label} ${count ?? 0}`,
            )),
        ),
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => h(
            BaseButton,
            {
                href: props.detailUrl(info.row.original.id),
                variant: 'secondary',
                iconOnly: true,
                title: 'Lihat peserta',
                'aria-label': 'Lihat peserta',
            },
            () => h(IconEye, { size: 16 }),
        ),
    },
]);
</script>

<template>
    <AppDataTable
        :table-key="tableKey"
        :data="ringkasanUjian.data"
        :columns="columns"
        :page-size="Number(ringkasanUjian.per_page) || 15"
        :page-size-options="[15, 25, 50, 100]"
        :filters="tableFilters"
        :initial-filters="initialFilters"
        server-side
        :server-total-rows="ringkasanUjian.total ?? 0"
        :reload-only="['ringkasanUjian', 'filters']"
        :row-click="openDetail"
        searchable
        search-placeholder="Cari ujian atau program tes..."
        :title="title"
    />
</template>
