<script setup>
import { Head, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import CertificateScore from '@/Components/Ujian/CertificateScore.vue';
import { IconTrash, IconCertificate, IconPrinter } from '@tabler/icons-vue';
import { useConfirm } from '@/Composables/useConfirm';

defineProps({
    sertifikat: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const contohSertifikat = {
    certificateNo: '416/PUSAT-B/EPT/X/2026',
    issueDate: '13 October 2026',
    testTakerName: 'MUHAMMAD NAUFAL YANUAR',
    studentId: '23111100018',
    listeningScore: 150,
    readingScore: 160,
    cefrLevel: 'B1-',
    cefrTitle: 'Satisfactory User',
    cefrDescription: 'Lower Intermediate English Proficiency',
    testDate: '21 September 2026',
    validUntil: '21 September 2028',
    headName: 'Rudha Widagsa, ph.D',
    headNis: '1985 0102 2013101 001',
};

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

async function destroy(cert) {
    if (!await confirm.confirm(`Hapus sertifikat "${cert.nomor_sertifikat}"?`)) return;
    router.delete(route('admin.sertifikat.hapus', cert.id), { preserveScroll: true });
}

function isValid(cert) {
    return new Date(cert.berlaku_hingga) >= new Date();
}

function cetakSertifikat() {
    window.print();
}
</script>

<template>
    <Head title="Sertifikat" />
    <TataLetakDasbor title="Daftar Sertifikat" subtitle="Kelola sertifikat yang telah diterbitkan">
        <template #header-actions>
            <BaseButton
                type="button"
                title="Cetak layout sertifikat dengan data contoh"
                @click="cetakSertifikat"
            >
                <IconPrinter :size="18" /> Cetak PDF
            </BaseButton>
        </template>

        <BaseCard :padding="false" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">No. Sertifikat</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Peserta</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Ujian</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Terbit</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Berlaku Hingga</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="cert in sertifikat" :key="cert.id"
                            class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-5 py-4">
                                <p class="font-mono text-label-md font-medium text-text-heading">{{ cert.nomor_sertifikat }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-text-heading">{{ cert.sesi_ujian?.pengguna?.name || '-' }}</p>
                                <p class="text-text-muted text-label-md">{{ cert.sesi_ujian?.pengguna?.email || '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-body-md text-text-body">{{ cert.sesi_ujian?.slot_ujian?.periode_ujian?.ujian?.judul || '-' }}</td>
                            <td class="px-5 py-4 text-body-md text-text-body">{{ formatDate(cert.diterbitkan_pada) }}</td>
                            <td class="px-5 py-4 text-body-md text-text-body">{{ formatDate(cert.berlaku_hingga) }}</td>
                            <td class="px-5 py-4">
                                <BaseBadge :variant="isValid(cert) ? 'success' : 'danger'">
                                    {{ isValid(cert) ? 'Aktif' : 'Kadaluarsa' }}
                                </BaseBadge>
                            </td>
                            <td class="px-5 py-4">
                                <BaseButton
                                    variant="danger"
                                    icon-only
                                    title="Hapus"
                                    aria-label="Hapus"
                                    @click="destroy(cert)"
                                >
                                    <IconTrash :size="16" />
                                </BaseButton>
                            </td>
                        </tr>
                        <tr v-if="sertifikat.length === 0">
                            <td colspan="7" class="px-5 py-16 text-center">
                                <IconCertificate class="mx-auto text-text-muted mb-3" :size="48" stroke="1.5" />
                                <p class="text-text-muted text-body-md">Belum ada sertifikat yang diterbitkan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>

        <div class="cert-print-region" aria-hidden="true">
            <CertificateScore v-bind="contohSertifikat" />
        </div>
    </TataLetakDasbor>
</template>

<style scoped>
.cert-print-region {
    display: none;
}

@media print {
    :global(html),
    :global(body) {
        margin: 0 !important;
        padding: 0 !important;
        background-color: #ffffff !important;
    }

    :global(body *) {
        visibility: hidden !important;
    }

    .cert-print-region,
    .cert-print-region :deep(*) {
        visibility: visible !important;
    }

    .cert-print-region {
        display: block !important;
        position: fixed;
        top: 50%;
        left: 50%;
        width: 100%;
        height: auto;
        margin: 0 !important;
        padding: 0 !important;
        page: certificate;
        break-inside: avoid;
        transform: translate(-50%, -50%);
    }

    :global(.min-h-screen) {
        display: block !important;
        height: auto !important;
        min-height: 0 !important;
        overflow: visible !important;
    }

    :global(.min-h-screen > aside),
    :global(.min-h-screen > nav) {
        display: none !important;
    }

    :global(main) {
        display: block !important;
        height: auto !important;
        overflow: visible !important;
    }

    :global(main > header) {
        display: none !important;
    }

    :global(.dashboard-scroll-area) {
        display: block !important;
        height: 0 !important;
        overflow: hidden !important;
    }
}
</style>
