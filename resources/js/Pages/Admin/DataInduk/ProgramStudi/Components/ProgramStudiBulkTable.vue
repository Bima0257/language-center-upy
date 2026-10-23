<script setup>
import { IconArrowLeft, IconEdit, IconPlus, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    programStudi: { type: Array, default: () => [] },
    maxRows: { type: Number, default: 10 },
    backUrl: { type: String, required: true },
});

const emit = defineEmits(['create', 'edit', 'delete']);
</script>

<template>
    <BaseCard :padding="false" class="overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3.5">
            <div>
                <h2 class="text-title-lg font-semibold text-text-heading">Daftar Program Studi Baru</h2>
                <p class="text-label-md text-text-muted mt-1">{{ props.programStudi.length }}/{{ props.maxRows }} data</p>
            </div>
            <div class="flex items-center gap-2">
                <BaseButton variant="secondary" size="sm" :href="props.backUrl">
                    <IconArrowLeft :size="16" /> Kembali
                </BaseButton>
                <BaseButton size="sm" :disabled="props.programStudi.length >= props.maxRows" @click="emit('create')">
                    <IconPlus :size="16" /> Tambah Data
                </BaseButton>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                        <th class="w-16 text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">No.</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Nama</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Kode</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(program, index) in props.programStudi" :key="`${program.kode}-${index}`" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50">
                        <td class="px-5 py-4 text-body-md text-text-muted">{{ index + 1 }}</td>
                        <td class="px-5 py-4 font-medium text-text-heading">{{ program.nama }}</td>
                        <td class="px-5 py-4 text-body-md text-text-body">{{ program.kode }}</td>
                        <td class="px-5 py-4">
                            <BaseBadge :variant="program.aktif ? 'success' : 'neutral'">
                                {{ program.aktif ? 'Aktif' : 'Nonaktif' }}
                            </BaseBadge>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button type="button" @click="emit('edit', index)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ubah">
                                    <IconEdit :size="18" />
                                </button>
                                <button type="button" @click="emit('delete', index)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus">
                                    <IconTrash :size="18" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="props.programStudi.length === 0">
                        <td colspan="5" class="px-5 py-12 text-center text-text-muted text-body-md">Belum ada data untuk ditambahkan.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </BaseCard>
</template>
