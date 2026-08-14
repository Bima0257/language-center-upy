<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconTrash, IconCertificate } from '@tabler/icons-vue';
import { useConfirm } from '@/Composables/useConfirm';

defineProps({
    certificates: { type: Array, default: () => [] },
});

const confirm = useConfirm();

function formatDate(d) {
    if (!d) return '-';
    return new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
}

async function destroy(cert) {
    if (!await confirm.confirm(`Hapus sertifikat "${cert.certificate_number}"?`)) return;
    router.delete(route('admin.certificates.destroy', cert.id), { preserveScroll: true });
}

function isValid(cert) {
    return new Date(cert.valid_until) >= new Date();
}
</script>

<template>
    <Head title="Sertifikat" />
    <DashboardLayout title="Daftar Sertifikat">
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
                        <tr v-for="cert in certificates" :key="cert.id"
                            class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-5 py-4">
                                <p class="font-mono text-label-md font-medium text-primary">{{ cert.certificate_number }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <p class="font-medium text-primary">{{ cert.exam_session?.user?.name || '-' }}</p>
                                <p class="text-text-muted text-label-md">{{ cert.exam_session?.user?.email || '' }}</p>
                            </td>
                            <td class="px-5 py-4 text-body-md text-text-body">{{ cert.exam_session?.schedule?.exam?.title || '-' }}</td>
                            <td class="px-5 py-4 text-body-md text-text-body">{{ formatDate(cert.issued_at) }}</td>
                            <td class="px-5 py-4 text-body-md text-text-body">{{ formatDate(cert.valid_until) }}</td>
                            <td class="px-5 py-4">
                                <BaseBadge :variant="isValid(cert) ? 'success' : 'danger'">
                                    {{ isValid(cert) ? 'Aktif' : 'Kadaluarsa' }}
                                </BaseBadge>
                            </td>
                            <td class="px-5 py-4">
                                <button @click="destroy(cert)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus">
                                    <IconTrash :size="18" />
                                </button>
                            </td>
                        </tr>
                        <tr v-if="certificates.length === 0">
                            <td colspan="7" class="px-5 py-16 text-center">
                                <IconCertificate class="mx-auto text-text-muted mb-3" :size="48" stroke="1.5" />
                                <p class="text-text-muted text-body-md">Belum ada sertifikat yang diterbitkan.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>
    </DashboardLayout>
</template>
