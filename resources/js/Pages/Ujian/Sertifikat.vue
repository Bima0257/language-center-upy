<script setup>
import { Head } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { IconCertificate, IconCheck } from '@tabler/icons-vue';

defineProps({
    sertifikat: { type: Array, default: () => [] },
});

function formatDate(date) {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}
</script>

<template>
    <Head title="Sertifikat" />
    <TataLetakDasbor title="Sertifikat" subtitle="Unduh sertifikat hasil ujian Anda">
        <div v-if="sertifikat.length === 0" class="text-center py-16">
            <IconCertificate class="mx-auto text-text-muted mb-4" :size="48" stroke="1.5" />
            <p class="text-text-muted text-body-md">Belum ada sertifikat yang diterbitkan untuk akun Anda.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <BaseCard v-for="cert in sertifikat" :key="cert.id">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-primary-container flex items-center justify-center shrink-0">
                        <IconCheck class="text-on-primary-container" :size="22" />
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-primary text-title-lg">
                            {{ cert.sesi_ujian?.slot_ujian?.periode_ujian?.ujian?.judul || 'Ujian' }}
                        </h3>
                        <p class="text-label-md text-text-muted mt-1">
                            No. {{ cert.nomor_sertifikat }}
                        </p>
                        <div class="flex items-center gap-4 mt-2 text-label-md text-text-muted">
                            <span>Terbit: {{ formatDate(cert.diterbitkan_pada) }}</span>
                            <span v-if="cert.berlaku_hingga">Berlaku hingga: {{ formatDate(cert.berlaku_hingga) }}</span>
                        </div>
                        <div v-if="cert.sesi_ujian?.skor_total !== null" class="mt-2">
                            <span class="inline-flex items-center gap-1.5 bg-success-container text-on-success-container px-3 py-1 rounded-full text-label-md font-medium">
                                Skor: {{ cert.sesi_ujian.skor_total }}/120
                            </span>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
