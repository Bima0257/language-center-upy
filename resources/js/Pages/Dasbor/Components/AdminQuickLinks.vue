<script setup>
import { Link } from "@inertiajs/vue3";
import { IconFileDescription, IconUsers } from "@tabler/icons-vue";

defineProps({
    manageExamHref: { type: String, required: true },
    proctorDashboardHref: { type: String, required: true },
    registrationHref: { type: String, required: true },
    sessionReportHref: { type: String, required: true },
});
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <Link
            :href="registrationHref"
            class="bg-surface-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 flex items-center justify-between hover:border-secondary transition-all"
        >
            <div>
                <p class="text-title-lg font-semibold text-text-heading">
                    Pendaftaran Ujian
                </p>
                <p class="text-text-muted text-body-md">
                    Verifikasi bukti pembayaran peserta
                </p>
            </div>
            <IconUsers
                :size="28"
                stroke="1.5"
                class="text-text-muted"
            />
        </Link>
        <Link
            :href="sessionReportHref"
            class="bg-surface-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 flex items-center justify-between hover:border-secondary transition-all"
        >
            <div>
                <p class="text-title-lg font-semibold text-text-heading">
                    Laporan Integritas
                </p>
                <p class="text-text-muted text-body-md">
                    Tinjau log pelanggaran ujian
                </p>
            </div>
            <IconFileDescription
                :size="28"
                stroke="1.5"
                class="text-text-muted"
            />
        </Link>
    </div>
    <BaseCard padding="p-5">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-title-lg font-semibold text-text-heading">
                Akses Cepat
            </h2>
        </div>
        <div class="flex flex-wrap gap-3">
            <BaseButton :href="manageExamHref" size="xs"
                >Kelola Ujian</BaseButton
            >
            <Link
                :href="proctorDashboardHref"
                class="bg-secondary text-on-secondary px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-secondary-container transition-all"
                >Dasbor Pengawas</Link
            >
            <Link
                :href="registrationHref"
                class="border border-outline-variant text-primary px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all"
                >Pendaftaran</Link
            >
            <Link
                :href="sessionReportHref"
                class="border border-outline-variant text-primary px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all"
                >Laporan</Link
            >
        </div>
    </BaseCard>
</template>
