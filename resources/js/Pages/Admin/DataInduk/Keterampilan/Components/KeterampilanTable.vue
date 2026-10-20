<script setup>
import { IconArrowLeft, IconEdit, IconGripVertical, IconPlus, IconTrash } from '@tabler/icons-vue';
import draggable from 'vuedraggable';

const props = defineProps({
    keterampilan: { type: Array, default: () => [] },
    reordering: { type: Boolean, default: false },
    backUrl: { type: String, required: true },
});

const emit = defineEmits(['create', 'reorder', 'edit', 'delete']);

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
                <h2 class="text-title-lg font-semibold text-primary">Daftar Keterampilan</h2>
                <span v-if="keterampilan.length" class="text-label-md text-text-muted">
                    Seret ikon ⠿ untuk mengubah urutan
                </span>
            </div>
            <div class="flex items-center gap-2">
                <BaseButton variant="secondary" size="sm" :href="props.backUrl">
                    <IconArrowLeft :size="16" /> Kembali
                </BaseButton>
                <BaseButton size="sm" @click="emit('create')">
                    <IconPlus :size="16" /> Tambah Keterampilan
                </BaseButton>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                        <th class="w-12 px-4 py-3"></th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Kode Gabungan</th>
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
                    :disabled="reordering"
                    @end="emit('reorder', $event)"
                >
                    <template #item="{ element }">
                        <tr class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-4 py-3.5">
                                <IconGripVertical
                                    :size="18"
                                    class="drag-handle cursor-grab text-text-muted hover:text-secondary transition-colors"
                                />
                            </td>
                            <td class="px-5 py-3.5 font-medium text-primary">{{ element.kode }}</td>
                            <td class="px-5 py-3.5 font-medium text-primary">{{ element.nama }}</td>
                            <td class="px-5 py-3.5 text-body-md text-text-body">
                                {{ descriptionPreview(element.deskripsi) }}
                            </td>
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

        <div v-if="!keterampilan.length" class="px-5 py-10 text-center">
            <p class="text-text-muted text-body-md">Belum ada data keterampilan.</p>
        </div>
    </BaseCard>
</template>
