<script setup>
import { Head } from "@inertiajs/vue3";
import { h } from "vue";
import {
    IconInfoCircle,
    IconPlayerPlay,
} from "@tabler/icons-vue";
import TataLetakDasbor from "@/Layouts/TataLetakDasbor.vue";
import AppDataTable from "@/Components/Ui/AppDataTable.vue";
import BaseBadge from "@/Components/Base/BaseBadge.vue";
import BaseButton from "@/Components/Base/BaseButton.vue";
import BaseCard from "@/Components/Base/BaseCard.vue";

const props = defineProps({
    ujian: { type: Array, default: () => [] },
});

function totalSoal(item) {
    return Number(item.total_soal || 0);
}

function modeLabel(mode) {
    return mode === "tryout" ? "Try Out" : "Ujian Resmi";
}

function modeVariant(mode) {
    return mode === "tryout" ? "pastel-blue" : "pastel";
}

const columns = [
    {
        accessorKey: "judul",
        header: "Judul Ujian",
        meta: { cellClass: "font-medium text-text-heading" },
        cell: (info) => info.getValue() || "-",
    },
    {
        id: "program_tes",
        header: "Program Tes",
        accessorFn: (row) => row.program_tes?.nama || "-",
    },
    {
        accessorKey: "mode",
        header: "Mode",
        cell: (info) =>
            h(
                BaseBadge,
                { variant: modeVariant(info.getValue()) },
                () => modeLabel(info.getValue()),
            ),
    },
    {
        accessorKey: "durasi_menit",
        header: "Durasi",
        cell: (info) => `${info.getValue() ?? 0} menit`,
    },
    {
        accessorKey: "part_ujian_count",
        header: "Part",
        cell: (info) => info.getValue() ?? 0,
    },
    {
        accessorKey: "total_soal",
        header: "Total Soal",
        cell: (info) => totalSoal(info.row.original),
    },
    {
        accessorKey: "aktif",
        header: "Status",
        cell: (info) =>
            h(
                BaseBadge,
                { variant: info.getValue() ? "success" : "neutral" },
                () => (info.getValue() ? "Aktif" : "Nonaktif"),
            ),
    },
    {
        id: "actions",
        header: "Aksi",
        meta: { align: "center" },
        cell: (info) => {
            const row = info.row.original;
            const bisaDemo = totalSoal(row) > 0;

            return h(
                BaseButton,
                {
                    href: bisaDemo
                        ? route("admin.demo-ujian.mulai", row.id)
                        : null,
                    size: "sm",
                    variant: bisaDemo ? "primary" : "secondary",
                    disabled: !bisaDemo,
                },
                () =>
                    bisaDemo
                        ? h("span", { class: "inline-flex items-center gap-1.5" }, [
                            h(IconPlayerPlay, { size: 16 }),
                            h("span", null, "Mulai Demo"),
                        ])
                        : "Belum ada soal",
            );
        },
    },
];
</script>

<template>
    <Head title="Demo Ujian" />
    <TataLetakDasbor
        title="Demo Ujian"
        subtitle="Coba alur pengerjaan ujian dan lihat skor tanpa menyimpan jawaban"
    >
        <BaseCard padding="p-5" class="mb-6">
            <div class="flex items-start gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pastel-blue text-text-pastel"
                >
                    <IconInfoCircle :size="20" />
                </span>
                <div class="min-w-0">
                    <p class="text-title-lg font-semibold text-text-heading">
                        Mode Demo
                    </p>
                    <p class="mt-1 text-body-md text-text-muted">
                        Demo memakai soal dari ujian yang dipilih. Jawaban dan
                        ringkasan tersimpan di browser. Skor dihitung saat demo
                        selesai tanpa membuat sesi atau menyimpan jawaban ke
                        database.
                    </p>
                </div>
            </div>
        </BaseCard>

        <AppDataTable
            table-key="admin-demo-ujian"
            :data="props.ujian"
            :columns="columns"
            title="Daftar Ujian Demo"
            searchable
            search-placeholder="Cari ujian atau program tes..."
            empty-message="Belum ada ujian untuk didemokan."
        />
    </TataLetakDasbor>
</template>
