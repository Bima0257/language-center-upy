<script setup>
import { Link } from '@inertiajs/vue3';
import { IconHeadphones, IconEye, IconEdit, IconTrash } from '@tabler/icons-vue';

defineProps({
    soal: { type: Array, default: () => [] },
    keterampilanNameFn: { type: Function, default: () => '' },
    bankNameFn: { type: Function, default: () => '' },
});

defineEmits(['delete']);
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                    <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Soal</th>
                    <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Bank Soal</th>
                    <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Keterampilan</th>
                    <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Pembuat</th>
                    <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="q in soal" :key="q.id"
                    class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                    <td class="px-5 py-4 min-w-[260px] max-w-md">
                        <span v-if="q.materi_soal?.url_audio"
                              class="inline-flex items-center gap-1 bg-pastel-blue/50 text-info px-2 py-0.5 rounded-full text-label-md font-medium mb-1">
                            <IconHeadphones :size="12" /> Audio
                        </span>
                        <p class="text-text-heading text-body-md font-medium line-clamp-2">{{ q.teks_soal }}</p>
                    </td>
                    <td class="px-5 py-4">
                        <span v-if="q.bank_soal_id"
                               class="inline-block bg-warning-container text-on-warning-container px-2.5 py-0.5 rounded-full text-label-md font-medium">
                            {{ bankNameFn(q.bank_soal_id) }}
                        </span>
                    </td>
                    <td class="px-5 py-4">
                        <span v-if="q.keterampilan"
                               class="inline-block bg-info-container text-on-info-container px-2.5 py-0.5 rounded-full text-label-md font-medium">
                            {{ keterampilanNameFn(q.keterampilan) }}
                        </span>
                    </td>
                    <td class="px-5 py-4 text-body-md text-text-body whitespace-nowrap">{{ q.pembuat?.name || '-' }}</td>
                    <td class="px-5 py-4">
                        <div class="flex items-center gap-1">
                            <Link :href="route('pustaka-soal.pratinjau', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Pratinjau"><IconEye :size="18" /></Link>
                            <Link :href="route('pustaka-soal.ubah', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ubah"><IconEdit :size="18" /></Link>
                            <button @click="$emit('delete', q.id, q.teks_soal)"
                                    class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus">
                                <IconTrash :size="18" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
