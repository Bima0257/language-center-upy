<script setup>
import { Head } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconCertificate, IconCheck } from '@tabler/icons-vue';

defineProps({
    certificates: { type: Array, default: () => [] },
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head title="Sertifikat" />
    <DashboardLayout title="Sertifikat">
        <div v-if="certificates.length === 0" class="text-center py-16">
            <IconCertificate class="mx-auto text-text-muted mb-4" :size="48" stroke="1.5" />
            <p class="text-text-muted text-body-md">Belum ada sertifikat yang diterbitkan untuk akun Anda.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseCard v-for="cert in certificates" :key="cert.id">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-container flex items-center justify-center shrink-0">
                        <IconCheck class="text-white" :size="22" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-primary text-title-lg">
                            {{ cert.examSession?.schedule?.exam?.title || 'Ujian' }}
                        </h3>
                        <p class="text-label-md text-text-muted mt-1">
                            No. {{ cert.certificate_number }}
                        </p>
                        <div class="flex items-center gap-4 mt-2 text-label-md text-text-muted">
                            <span>Terbit: {{ formatDate(cert.issued_at) }}</span>
                            <span v-if="cert.valid_until">Berlaku hingga: {{ formatDate(cert.valid_until) }}</span>
                        </div>
                        <div v-if="cert.examSession?.score_total !== null" class="mt-2">
                            <span class="inline-flex items-center gap-1.5 bg-green-100 text-green-700 px-3 py-1 rounded-full text-label-md font-medium">
                                Skor: {{ cert.examSession.score_total }}/120
                            </span>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
