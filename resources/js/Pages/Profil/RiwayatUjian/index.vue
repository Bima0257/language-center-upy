<script setup>
import { computed, h } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import AppTabs from '@/Components/Ui/AppTabs.vue'
import AppDataTable from '@/Components/Ui/AppDataTable.vue'
import BaseBadge from '@/Components/Base/BaseBadge.vue'

const props = defineProps({
    sesiUjian: { type: Object, default: () => ({ data: [], total: 0, per_page: 10 }) },
    filters: { type: Object, default: () => ({}) },
})

const tabs = [
    { value: 'resmi', label: 'Ujian Resmi' },
    { value: 'tryout', label: 'Try Out' },
]

const tabAktif = computed(() => (props.filters?.tab === 'tryout' ? 'tryout' : 'resmi'))

const emptyMessage = computed(() => (
    tabAktif.value === 'resmi'
        ? 'Belum ada riwayat ujian resmi.'
        : 'Belum ada riwayat tryout.'
))

function gantiTab(tab) {
    if (tab === tabAktif.value) return

    router.get(route('ujian.riwayat'), { tab }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

function judulUjian(row) {
    return row.ujian?.judul || row.slot_ujian?.periode_ujian?.ujian?.judul || '-'
}

function namaProgramTes(row) {
    return row.ujian?.program_tes?.nama || row.slot_ujian?.periode_ujian?.ujian?.program_tes?.nama || '-'
}

function formatTanggal(value) {
    return value
        ? new Date(value).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' })
        : '-'
}

function tanggalSesi(row) {
    return row.diserahkan_pada || row.dihentikan_pada || row.created_at
}

function statusVariant(status) {
    return status === 'submitted' ? 'success' : 'danger'
}

function statusLabel(status) {
    return status === 'submitted' ? 'Selesai' : 'Dihentikan'
}

const columns = [
    {
        id: 'ujian',
        header: 'Ujian',
        enableSorting: false,
        cell: info => {
            const row = info.row.original

            return h('div', [
                h('p', { class: 'font-semibold text-text-heading' }, judulUjian(row)),
                h('p', { class: 'text-label-md text-text-muted' }, namaProgramTes(row)),
            ])
        },
    },
    {
        id: 'tanggal',
        header: 'Tanggal',
        enableSorting: false,
        cell: info => formatTanggal(tanggalSesi(info.row.original)),
    },
    {
        accessorKey: 'status',
        header: 'Status',
        enableSorting: false,
        cell: info => h(
            BaseBadge,
            { variant: statusVariant(info.getValue()) },
            () => statusLabel(info.getValue()),
        ),
    },
    {
        accessorKey: 'skor_total',
        header: 'Skor',
        enableSorting: false,
        cell: info => (info.getValue() !== null
            ? `${info.getValue()}/${info.row.original.skor_total_maksimum ?? '-'}`
            : '-'),
    },
    {
        accessorKey: 'lulus',
        header: 'Hasil',
        enableSorting: false,
        cell: info => {
            const lulus = info.getValue()

            if (lulus === null || lulus === undefined) {
                return '-'
            }

            return h(
                BaseBadge,
                { variant: lulus ? 'success' : 'danger' },
                () => (lulus ? 'Lulus' : 'Tidak Lulus'),
            )
        },
    },
    {
        accessorKey: 'label_tingkat',
        header: 'Tingkat',
        enableSorting: false,
        cell: info => info.getValue() || '-',
    },
]
</script>

<template>
    <Head title="Riwayat Ujian" />
    <TataLetakDasbor title="Riwayat Ujian" subtitle="Lihat hasil dan riwayat ujian resmi serta tryout Anda">
        <div>
            <AppTabs
                class="relative z-10 pl-3"
                :tabs="tabs"
                :model-value="tabAktif"
                @update:model-value="gantiTab"
            />

            <AppDataTable
                :key="tabAktif"
                :table-key="`profil-riwayat-ujian-${tabAktif}`"
                :data="sesiUjian.data ?? []"
                :columns="columns"
                :page-size="Number(sesiUjian.per_page) || 10"
                :page-size-options="[10, 25, 50, 100]"
                :server-total-rows="sesiUjian.total ?? 0"
                :reload-only="['sesiUjian', 'filters']"
                :empty-message="emptyMessage"
                server-side
                searchable
                search-placeholder="Cari ujian atau program tes..."
            />
        </div>
    </TataLetakDasbor>
</template>
