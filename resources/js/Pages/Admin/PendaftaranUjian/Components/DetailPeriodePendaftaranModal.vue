<script setup>
import { computed } from 'vue';
import { IconX } from '@tabler/icons-vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseInfoField from '@/Components/Base/BaseInfoField.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseModalFooter from '@/Components/Base/BaseModalFooter.vue';
import BaseModalHeader from '@/Components/Base/BaseModalHeader.vue';
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    periode: { type: Object, default: null },
});

const emit = defineEmits(['close', 'edit']);

const statusPeriode = computed(() => props.periode?.status_pendaftaran || '');

const catatanTampilan = computed(() => {
    const catatan = props.periode?.catatan || '';

    if (!catatan) return '';

    return /<[a-z][\s\S]*>/i.test(catatan) ? catatan : catatan.replace(/\n/g, '<br>');
});

const adaCatatan = computed(() => catatanTampilan.value.replace(/<[^>]*>/g, '').trim() !== '');

function formatDateTime(value) {
    return value
        ? new Date(value).toLocaleString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        })
        : '-';
}

function formatDate(value) {
    return value
        ? new Date(value).toLocaleDateString('id-ID', {
            weekday: 'long',
            day: 'numeric',
            month: 'long',
            year: 'numeric',
        })
        : '-';
}

function statusLabel(value) {
    return {
        upcoming: 'Akan Dibuka',
        open: 'Sedang Dibuka',
        closed: 'Ditutup',
        inactive: 'Nonaktif',
    }[value] || value;
}

function statusClass(value) {
    return {
        upcoming: 'bg-info-container text-on-info-container',
        open: 'bg-success-container text-on-success-container',
        closed: 'bg-warning-container text-on-warning-container',
        inactive: 'bg-neutral-container text-on-neutral-container',
    }[value] || 'bg-neutral-container text-on-neutral-container';
}
</script>

<template>
    <BaseModal
        :show="props.show"
        max-width="2xl"
        aria-labelledby="detail-periode-pendaftaran-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="flex items-center justify-between gap-4 px-6 py-4">
                    <h2 id="detail-periode-pendaftaran-title" class="text-title-lg font-semibold text-text-heading">
                        Detail Periode Pendaftaran
                    </h2>
                    <button
                        type="button"
                        class="p-2 text-text-muted transition-colors hover:text-primary font-semibold"
                        aria-label="Tutup modal detail"
                        @click="emit('close')"
                    >
                        <IconX :size="20" />
                    </button>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <div v-if="props.periode" class="space-y-5 px-6 pb-6 pt-5">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div class="min-w-0">
                        <p class="text-label-md font-semibold uppercase tracking-wide text-secondary">
                            {{ props.periode.periode_ujian?.ujian?.program_tes?.nama || 'Ujian' }}
                        </p>
                        <h3 class="mt-1 text-title-md font-semibold text-text-heading">
                            {{ props.periode.nama }}
                        </h3>
                        <p class="mt-1 text-label-md text-text-muted">
                            {{ props.periode.periode_ujian?.ujian?.judul || '-' }}
                            <span class="mx-1">•</span>
                            {{ props.periode.periode_ujian?.judul || '-' }}
                        </p>
                    </div>
                    <span
                        class="inline-flex h-fit shrink-0 rounded-full px-3 py-1 text-label-md font-medium"
                        :class="statusClass(statusPeriode)"
                    >
                        {{ statusLabel(statusPeriode) }}
                    </span>
                </div>

                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <BaseInfoField label="Dibuka pada" :value="formatDateTime(props.periode.dibuka_pada)" />
                    <BaseInfoField label="Ditutup pada" :value="formatDateTime(props.periode.ditutup_pada)" />
                    <BaseInfoField
                        label="Pelaksanaan ujian"
                        :value="`${formatDate(props.periode.periode_ujian?.tanggal_mulai)} - ${formatDate(props.periode.periode_ujian?.tanggal_selesai)}`"
                    />
                    <BaseInfoField label="Jumlah slot" :value="props.periode.slot_ujian_count ?? 0" />
                </div>

                <div v-if="adaCatatan">
                    <p class="text-label-md font-medium text-text-heading">Catatan</p>
                    <div class="mt-1.5 rounded-2xl border border-outline-variant/40 bg-surface-container-lowest px-4 py-3">
                        <RichTextViewer :content="catatanTampilan" />
                    </div>
                </div>
            </div>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="flex flex-wrap items-center justify-end gap-2 px-6 py-3">
                    <BaseButton variant="secondary" size="sm" @click="emit('close')">
                        Tutup
                    </BaseButton>
                    <BaseButton variant="primary" size="sm" @click="emit('edit')">
                        Edit
                    </BaseButton>
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
