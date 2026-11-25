<script setup>
import { IconBook, IconEdit, IconGripVertical, IconLock, IconTrash } from '@tabler/icons-vue';
import draggable from 'vuedraggable';
import { computed, ref } from 'vue';

const props = defineProps({
    group: { type: Object, required: true },
    reordering: { type: Boolean, default: false },
    canManage: { type: Boolean, default: false },
    attached: { type: Boolean, default: false },
});

const emit = defineEmits(['reorder', 'edit', 'delete']);
const isEditingOrder = ref(false);
const hasLockedPart = computed(() =>
    props.group.partSoal.some((part) => part.dipakai_ujian_aktif),
);
</script>

<template>
    <BaseCard :padding="false" class="overflow-hidden" :class="attached ? 'rounded-none! border-0! shadow-none!' : ''">
        <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-4 bg-surface-container-low/60 border-b border-outline-variant/30">
            <div class="flex items-center gap-2">
                <IconBook :size="18" class="text-secondary" />
                <p class="text-label-md font-semibold text-text-heading">{{ group.label }}</p>
                <span class="text-label-md text-text-muted">{{ group.partSoal.length }} partSoal</span>
            </div>
            <div v-if="group.partSoal.length" class="flex flex-wrap items-center justify-end gap-3">
                <span v-if="canManage" class="text-label-md text-text-muted">
                    {{ isEditingOrder ? 'Seret ikon ⠿ untuk mengubah urutan' : 'Aktifkan Edit urutan untuk mengatur posisi' }}
                </span>
                <BaseButton
                    v-if="canManage"
                    variant="secondary"
                    size="sm"
                    :disabled="reordering || hasLockedPart"
                    :title="hasLockedPart ? 'Tidak bisa diubah karena part dipakai ujian aktif' : undefined"
                    @click="isEditingOrder = !isEditingOrder"
                >
                    <IconEdit :size="16" />
                    {{ isEditingOrder ? 'Selesai' : 'Edit urutan' }}
                </BaseButton>
            </div>
        </div>

        <div v-if="group.partSoal.length === 0" class="px-5 py-10 text-center">
            <p class="text-text-muted text-body-md">Belum ada partSoal untuk {{ group.label }}.</p>
        </div>

        <div v-else class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                        <th class="w-12 px-4 py-3"></th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Nama</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Urutan</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Soal</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Status</th>
                        <th v-if="canManage" class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <draggable
                    tag="tbody"
                    :list="group.partSoal"
                    item-key="id"
                    handle=".drag-handle"
                    :disabled="reordering || !isEditingOrder || !canManage || hasLockedPart"
                    @end="emit('reorder', group.value, $event)"
                >
                    <template #item="{ element }">
                        <tr class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-3.5">
                                <IconGripVertical
                                    v-if="isEditingOrder && canManage"
                                    :size="18"
                                    class="drag-handle cursor-grab text-text-muted hover:text-secondary transition-colors"
                                />
                            </td>
                            <td class="px-5 py-3.5 font-medium text-text-heading">{{ element.nama }}</td>
                            <td class="px-5 py-3.5 text-body-md text-text-body">{{ element.urutan ?? 1 }}</td>
                            <td class="px-5 py-3.5 text-body-md text-text-body">{{ element.soal_count ?? 0 }}</td>
                            <td class="px-5 py-3.5">
                                <BaseBadge :variant="element.aktif ? 'success' : 'neutral'">
                                    {{ element.aktif ? 'Aktif' : 'Nonaktif' }}
                                </BaseBadge>
                            </td>
                            <td v-if="canManage" class="px-5 py-3.5">
                                <div v-if="element.dipakai_ujian_aktif" class="flex items-center gap-1.5 text-label-md text-warning">
                                    <IconLock :size="16" />
                                    <span>Dikunci ujian aktif</span>
                                </div>
                                <div v-else class="flex items-center gap-1">
                                    <BaseButton
                                        variant="secondary"
                                        icon-only
                                        title="Ubah"
                                        aria-label="Ubah"
                                        @click="emit('edit', element)"
                                    >
                                        <IconEdit :size="16" />
                                    </BaseButton>
                                    <BaseButton
                                        variant="danger"
                                        icon-only
                                        title="Hapus"
                                        aria-label="Hapus"
                                        @click="emit('delete', element)"
                                    >
                                        <IconTrash :size="16" />
                                    </BaseButton>
                                </div>
                            </td>
                        </tr>
                    </template>
                </draggable>
            </table>
        </div>
    </BaseCard>
</template>
