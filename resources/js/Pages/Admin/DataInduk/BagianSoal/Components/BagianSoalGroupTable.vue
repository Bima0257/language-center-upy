<script setup>
import { IconBook, IconEdit, IconGripVertical, IconTrash } from '@tabler/icons-vue';
import draggable from 'vuedraggable';

defineProps({
    group: { type: Object, required: true },
    reordering: { type: Boolean, default: false },
    attached: { type: Boolean, default: false },
});

const emit = defineEmits(['reorder', 'edit', 'delete']);
</script>

<template>
    <BaseCard :padding="false" class="overflow-hidden" :class="attached ? '!rounded-none !border-0 !shadow-none' : ''">
        <div class="flex items-center justify-between px-5 py-4 bg-surface-container-low/60 border-b border-outline-variant/30">
            <div class="flex items-center gap-2">
                <IconBook :size="18" class="text-secondary" />
                <p class="text-label-md font-semibold text-text-heading">{{ group.label }}</p>
                <span class="text-label-md text-text-muted">{{ group.bagianSoal.length }} bagianSoal</span>
            </div>
            <span v-if="group.bagianSoal.length" class="text-label-md text-text-muted">
                Seret ikon ⠿ untuk mengubah urutan
            </span>
        </div>

        <div v-if="group.bagianSoal.length === 0" class="px-5 py-10 text-center">
            <p class="text-text-muted text-body-md">Belum ada bagianSoal untuk {{ group.label }}.</p>
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
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <draggable
                    tag="tbody"
                    :list="group.bagianSoal"
                    item-key="id"
                    handle=".drag-handle"
                    :disabled="reordering"
                    @end="emit('reorder', group.value, $event)"
                >
                    <template #item="{ element }">
                        <tr class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-3.5">
                                <IconGripVertical
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
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <button @click="emit('edit', element)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ubah"><IconEdit :size="18" /></button>
                                    <button @click="emit('delete', element)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </draggable>
            </table>
        </div>
    </BaseCard>
</template>
