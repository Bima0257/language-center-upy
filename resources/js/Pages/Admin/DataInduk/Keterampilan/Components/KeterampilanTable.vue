<script setup>
import { IconEdit, IconGripVertical, IconTrash } from '@tabler/icons-vue';
import draggable from 'vuedraggable';
import { ref } from 'vue';

defineProps({
    keterampilan: { type: Array, default: () => [] },
    reordering: { type: Boolean, default: false },
});

const emit = defineEmits(['create', 'reorder', 'edit', 'delete']);
const isEditingOrder = ref(false);

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

function descriptionPreview(html) {
    const text = stripHtml(html);
    return text ? (text.length > 60 ? text.substring(0, 60) + '…' : text) : '-';
}

</script>

<template>
    <BaseCard :padding="false" class="overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3.5">
            <div class="min-w-0 flex-1">
                <h2 class="text-title-lg font-semibold text-text-heading">Daftar Keterampilan</h2>
                <span v-if="keterampilan.length" class="text-label-md text-text-muted">
                    {{ isEditingOrder ? 'Seret ikon ⠿ untuk mengubah urutan' : 'Aktifkan Edit urutan untuk mengatur posisi' }}
                </span>
            </div>
            <div class="flex items-center gap-2">
                <BaseButton
                    v-if="keterampilan.length"
                    variant="secondary"
                    size="sm"
                    :disabled="reordering"
                    @click="isEditingOrder = !isEditingOrder"
                >
                    <IconEdit :size="16" />
                    {{ isEditingOrder ? 'Selesai' : 'Edit urutan' }}
                </BaseButton>
                <BaseTambahData @click="emit('create')" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                        <th class="w-12 px-4 py-3"></th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Kode</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Nama</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Deskripsi</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Status</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Aksi</th>
                    </tr>
                </thead>
                <draggable
                    tag="tbody"
                    :list="keterampilan"
                    item-key="id"
                    handle=".drag-handle"
                    :disabled="reordering || !isEditingOrder"
                    @end="emit('reorder', $event)"
                >
                    <template #item="{ element }">
                        <tr class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-3.5">
                                <IconGripVertical
                                    v-if="isEditingOrder"
                                    :size="18"
                                    class="drag-handle cursor-grab text-text-muted hover:text-secondary transition-colors"
                                />
                            </td>
                            <td class="px-5 py-3.5 font-medium text-text-heading">{{ element.kode }}</td>
                            <td class="px-5 py-3.5 font-medium text-text-heading">{{ element.nama }}</td>
                            <td class="px-5 py-3.5 text-body-md text-text-body">
                                {{ descriptionPreview(element.deskripsi) }}
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex flex-col items-start gap-1.5">
                                    <BaseBadge :variant="element.aktif ? 'success' : 'neutral'">
                                        {{ element.aktif ? 'Aktif' : 'Nonaktif' }}
                                    </BaseBadge>
                                </div>
                            </td>
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-1">
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

        <div v-if="!keterampilan.length" class="px-5 py-10 text-center">
            <p class="text-text-muted text-body-md">Belum ada data keterampilan.</p>
        </div>
    </BaseCard>
</template>
