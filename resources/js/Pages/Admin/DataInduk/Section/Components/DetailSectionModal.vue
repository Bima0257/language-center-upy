<script setup>
import { computed } from 'vue';
import { IconX } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseModalFooter from '@/Components/Base/BaseModalFooter.vue';
import BaseModalHeader from '@/Components/Base/BaseModalHeader.vue';
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';
import { formatProgramTes } from '@/constants/programTes';

const props = defineProps({
    show: { type: Boolean, default: false },
    section: { type: Object, default: null },
    policy: { type: Object, default: null },
});

const emit = defineEmits(['close']);

const adaDeskripsi = computed(() => {
    const teks = String(props.section?.deskripsi || '')
        .replace(/<[^>]*>/g, ' ')
        .replace(/&nbsp;/g, ' ')
        .trim();

    return teks !== '';
});

function ketentuanLabel(value) {
    return {
        required: 'Wajib',
        optional: 'Opsional',
        forbidden: 'Terlarang',
    }[value] || '-';
}
</script>

<template>
    <BaseModal
        :show="show"
        max-width="2xl"
        aria-labelledby="detail-section-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between gap-4">
                        <h2 id="detail-section-title" class="text-headline-md font-bold text-text-heading">Detail Section</h2>
                        <button type="button" aria-label="Tutup detail section" class="p-2 text-text-muted transition-colors hover:text-primary font-semibold" @click="emit('close')">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>
        <template #default>
            <div v-if="section" class="space-y-5 px-8 pb-8 pt-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="text-title-lg font-semibold text-text-heading">{{ section.nama }}</h3>
                        <p class="mt-1 text-label-md text-text-muted">
                            <span class="font-medium text-text-body">Program Tes:</span>
                            {{ formatProgramTes(section.program_tes) }}
                        </p>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <BaseBadge :variant="section.aktif ? 'success' : 'neutral'">
                            {{ section.aktif ? 'Aktif' : 'Nonaktif' }}
                        </BaseBadge>
                        <BaseBadge v-if="section.dipakai_ujian_aktif" variant="warning">
                            Dipakai ujian aktif
                        </BaseBadge>
                    </div>
                </div>

                <dl class="grid grid-cols-1 gap-x-6 gap-y-3 sm:grid-cols-3">
                    <div>
                        <dt class="text-label-md text-text-muted">Kode</dt>
                        <dd class="mt-0.5 text-body-md font-semibold text-text-heading">{{ section.kode || '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-label-md text-text-muted">Urutan</dt>
                        <dd class="mt-0.5 text-body-md font-semibold text-text-heading">{{ section.urutan ?? '-' }}</dd>
                    </div>
                    <div>
                        <dt class="text-label-md text-text-muted">Durasi</dt>
                        <dd class="mt-0.5 text-body-md font-semibold text-text-heading">
                            {{ section.durasi_menit != null ? `${section.durasi_menit} menit` : '-' }}
                        </dd>
                    </div>
                </dl>

                <section>
                    <h4 class="text-label-md font-semibold text-text-heading">Aturan Materi Soal</h4>
                    <div
                        v-if="policy"
                        class="mt-2 divide-y divide-outline-variant/30 rounded-2xl border border-outline-variant/40 bg-surface-container-lowest px-4"
                    >
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <span class="text-label-md text-text-muted">Penggunaan materi</span>
                            <span class="text-body-md font-semibold text-text-heading">{{ ketentuanLabel(policy.ketentuan_materi) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <span class="text-label-md text-text-muted">Teks</span>
                            <span class="text-body-md font-semibold text-text-heading">{{ ketentuanLabel(policy.aturan_media?.text) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <span class="text-label-md text-text-muted">Audio</span>
                            <span class="text-body-md font-semibold text-text-heading">{{ ketentuanLabel(policy.aturan_media?.audio) }}</span>
                        </div>
                        <div class="flex items-center justify-between gap-4 py-2.5">
                            <span class="text-label-md text-text-muted">Gambar</span>
                            <span class="text-body-md font-semibold text-text-heading">{{ ketentuanLabel(policy.aturan_media?.image) }}</span>
                        </div>
                    </div>
                    <p v-else class="mt-2 text-body-md text-text-muted">Belum dikonfigurasi.</p>
                </section>

                <section>
                    <h4 class="text-label-md font-semibold text-text-heading">Deskripsi</h4>
                    <RichTextViewer
                        v-if="adaDeskripsi"
                        :content="section.deskripsi"
                        class="mt-2 text-body-md text-text-body"
                    />
                    <p v-else class="mt-2 text-body-md text-text-muted">Belum ada deskripsi.</p>
                </section>
            </div>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="flex justify-end px-8 py-4">
                    <BaseButton variant="secondary" size="sm" @click="emit('close')">Tutup</BaseButton>
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
