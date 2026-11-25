<script setup>
import { h } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue'
import AppDataTable from '@/Components/Ui/AppDataTable.vue'
import BaseBadge from '@/Components/Base/BaseBadge.vue'
import {
    jenisPesertaIdentitasLabel,
    jenisPesertaLabel,
    jenisPesertaVariant,
} from '@/constants/jenisPeserta'

defineProps({
    sesiUjian: { type: Array, default: () => [] },
})

const statusLabels = {
    submitted: 'Selesai',
    terminated: 'Dihentikan',
    in_progress: 'Berlangsung',
    pending: 'Menunggu',
}

const columns = [
    {
        accessorKey: 'pengguna',
        header: 'Peserta',
        meta: { cellClass: 'font-medium text-text-heading' },
        cell: info => {
            const pengguna = info.row.original.pengguna
            const profil = pengguna?.profil_peserta
            const jenis = profil?.jenis_peserta
            const children = [
                h('p', { class: 'font-medium text-text-heading' }, pengguna?.name || '-'),
            ]

            if (jenis) {
                const labelIdentitas = jenisPesertaIdentitasLabel[jenis] || 'Identitas'

                children.push(h('div', { class: 'mt-1 flex flex-wrap items-center gap-1' }, [
                    h(BaseBadge, { variant: jenisPesertaVariant[jenis] || 'neutral' }, () => jenisPesertaLabel[jenis] || jenis),
                    h('span', { class: 'text-label-md text-text-muted' }, `${labelIdentitas}: ${profil?.nomor_identitas || '-'}`),
                ]))
            }

            return h('div', children)
        },
    },
    {
        accessorKey: 'ujian',
        header: 'Ujian',
        cell: info => info.row.original.ujian?.judul || info.row.original.slot_ujian?.periode_ujian?.ujian?.judul || '-',
    },
    {
        accessorKey: 'status',
        header: 'Status',
        cell: info => statusLabels[info.getValue()] || info.getValue(),
    },
    {
        accessorKey: 'skor_total',
        header: 'Skor',
        cell: info => (info.getValue() !== null
            ? `${info.getValue()}/${info.row.original.skor_total_maksimum ?? '-'}`
            : '-'),
    },
    {
        accessorKey: 'jumlah_pelanggaran',
        header: 'Pelanggaran',
        cell: info => info.getValue() || 0,
    },
    {
        accessorKey: 'created_at',
        header: 'Tanggal',
        cell: info => (info.getValue() ? new Date(info.getValue()).toLocaleDateString('id-ID') : '-'),
    },
]

function goToSession(sesiUjian) {
    router.visit(route('pengawas.sesi.detail', sesiUjian.id))
}
</script>

<template>
    <Head title="Laporan Sesi" />
    <TataLetakDasbor title="Laporan Sesi" subtitle="Tinjau hasil, skor, dan pelanggaran sesi ujian">
        <AppDataTable
            table-key="admin-laporan-sesi"
            :data="sesiUjian"
            :columns="columns"
            :row-click="goToSession"
            searchable
            search-placeholder="Cari peserta atau ujian..."
        />
    </TataLetakDasbor>
</template>
