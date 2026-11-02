<script setup>
import { IconEdit, IconTrash } from '@tabler/icons-vue';

defineProps({
    programStudi: { type: Array, default: () => [] },
});

const emit = defineEmits(['create', 'edit', 'delete']);
</script>

<template>
    <BaseCard :padding="false" class="overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3.5">
            <h2 class="text-title-lg font-semibold text-text-heading">Daftar Program Studi</h2>
            <div class="flex items-center gap-2">
                <BaseTambahData @click="emit('create')" />
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Nama</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Kode</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Fakultas</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="program in programStudi" :key="program.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50">
                        <td class="px-5 py-4 font-medium text-text-heading">{{ program.nama }}</td>
                        <td class="px-5 py-4 text-body-md text-text-body">{{ program.kode }}</td>
                        <td class="px-5 py-4 text-body-md text-text-body">{{ program.fakultas?.nama || '-' }}</td>
                        <td class="px-5 py-4">
                            <BaseBadge :variant="program.aktif ? 'success' : 'neutral'">
                                {{ program.aktif ? 'Aktif' : 'Nonaktif' }}
                            </BaseBadge>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button @click="emit('edit', program)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ubah"><IconEdit :size="18" /></button>
                                <button @click="emit('delete', program)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                            </div>
                        </td>
                        </tr>
                    <tr v-if="programStudi.length === 0">
                        <td colspan="5" class="px-5 py-12 text-center text-text-muted text-body-md">Belum ada data program studi.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </BaseCard>
</template>
