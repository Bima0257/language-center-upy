<script setup>
import { IconArrowLeft, IconEdit, IconPlus, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    interpretasiSkor: { type: Array, default: () => [] },
    jenisTes: { type: Array, default: () => [] },
    backUrl: { type: String, required: true },
});

const emit = defineEmits(['create', 'edit', 'delete']);

function examTypeName(id) {
    return props.jenisTes.find((type) => type.id === id)?.nama || '-';
}
</script>

<template>
    <BaseCard :padding="false" class="lg:col-span-2 overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3.5">
                <h2 class="text-title-lg font-semibold text-text-heading">Daftar Interpretasi Skor</h2>
            <div class="flex items-center gap-2">
                <BaseButton variant="secondary" size="sm" :href="props.backUrl">
                    <IconArrowLeft :size="16" /> Kembali
                </BaseButton>
                <BaseButton size="sm" @click="emit('create')">
                    <IconPlus :size="16" /> Tambah Massal
                </BaseButton>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Rentang Skor</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">CEFR</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Label</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Kelulusan</th>
                        <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="interp in props.interpretasiSkor" :key="interp.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50">
                        <td class="px-5 py-4">
                            <p class="font-medium text-text-heading">{{ interp.skor_minimum }} - {{ interp.skor_maksimum }}</p>
                            <p class="text-text-muted text-label-md">{{ examTypeName(interp.jenis_tes_id) }}</p>
                        </td>
                        <td class="px-5 py-4 text-body-md text-text-body">{{ interp.tingkat_cefr }}</td>
                        <td class="px-5 py-4">
                            <p class="text-body-md text-text-body">{{ interp.label_tingkat }}</p>
                            <p v-if="interp.deskripsi" class="text-text-muted text-label-md">{{ interp.deskripsi }}</p>
                        </td>
                        <td class="px-5 py-4">
                            <BaseBadge :variant="interp.lulus ? 'success' : 'danger'">
                                {{ interp.lulus ? 'LULUS' : 'TIDAK LULUS' }}
                            </BaseBadge>
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-2">
                                <button @click="emit('edit', interp)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ubah"><IconEdit :size="18" /></button>
                                <button @click="emit('delete', interp)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="props.interpretasiSkor.length === 0">
                        <td colspan="5" class="px-5 py-12 text-center text-text-muted text-body-md">Belum ada data interpretasi skor.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </BaseCard>
</template>
