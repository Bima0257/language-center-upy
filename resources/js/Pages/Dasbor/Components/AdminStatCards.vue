<script setup>
import {
    IconFileDescription,
    IconUsers,
    IconEyeCheck,
    IconAlertTriangle as IconFlag,
} from "@tabler/icons-vue";

defineProps({
    jumlahPengguna: { type: Number, default: 0 },
    jumlahUjian: { type: Number, default: 0 },
    jumlahSesiAktif: { type: Number, default: 0 },
    jumlahSesiDitandai: { type: Number, default: 0 },
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <BaseCard padding="p-5" class="text-center">
            <IconUsers
                class="mx-auto text-secondary mb-1"
                :size="28"
                stroke="1.5"
            />
                <p class="text-headline-md font-bold text-text-heading">
                {{ jumlahPengguna }}
            </p>
            <p class="text-text-muted text-label-md">Pengguna Terdaftar</p>
        </BaseCard>
        <BaseCard padding="p-5" class="text-center">
            <IconFileDescription
                class="mx-auto text-secondary mb-1"
                :size="28"
                stroke="1.5"
            />
                <p class="text-headline-md font-bold text-text-heading">
                {{ jumlahUjian }}
            </p>
            <p class="text-text-muted text-label-md">Total Ujian</p>
        </BaseCard>
        <BaseCard padding="p-5" class="text-center">
            <IconEyeCheck
                class="mx-auto text-secondary mb-1"
                :size="28"
                stroke="1.5"
            />
                <p class="text-headline-md font-bold text-text-heading">
                {{ jumlahSesiAktif }}
            </p>
            <p class="text-text-muted text-label-md">Sesi Aktif</p>
        </BaseCard>
        <div
            class="bg-surface-white rounded-2xl p-5 shadow-soft border border-error-red/20 text-center"
        >
            <IconFlag
                class="mx-auto text-error-red mb-1"
                :size="28"
                stroke="1.5"
            />
            <p class="text-headline-md font-bold text-error-red">
                {{ jumlahSesiDitandai }}
            </p>
            <p class="text-text-muted text-label-md">Perlu Tinjauan</p>
        </div>
    </div>
</template>
