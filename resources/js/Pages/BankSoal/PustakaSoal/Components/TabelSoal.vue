<script setup>
import { Link } from '@inertiajs/vue3';
import { IconHeadphones, IconEye, IconEdit, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    soal: { type: Array, default: () => [] },
    bankLocked: { type: Boolean, default: false },
});

const emit = defineEmits(['delete']);

function isLocked(question) {
    return props.bankLocked || Number(question.bagian_ujian_count || 0) > 0;
}
</script>

<template>
    <div class="overflow-x-auto">
        <table class="w-full text-left">
            <thead>
                <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                    <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Soal</th>
                    <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Pembuat</th>
                    <th class="w-px min-w-20 whitespace-nowrap px-5 py-4 text-center text-label-md font-semibold text-text-muted uppercase tracking-wider">Aksi</th>
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
                    <td class="px-5 py-4 text-body-md text-text-body whitespace-nowrap">{{ q.pembuat?.name || '-' }}</td>
                    <td class="w-px min-w-20 whitespace-nowrap px-5 py-4 text-center">
                        <div class="flex min-w-20 items-center justify-center gap-1 whitespace-nowrap">
                            <Link :href="route('pustaka-soal.pratinjau', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Pratinjau"><IconEye :size="18" /></Link>
                            <Link
                                v-if="!isLocked(q)"
                                :href="route('pustaka-soal.ubah', q.id)"
                                class="p-2 text-text-muted hover:text-secondary transition-colors"
                                title="Ubah"
                            >
                                <IconEdit :size="18" />
                            </Link>
                            <button
                                v-else
                                type="button"
                                disabled
                                class="cursor-not-allowed p-2 text-text-muted opacity-30"
                                title="Tidak bisa diubah karena bank soal sudah digunakan di ujian"
                            >
                                <IconEdit :size="18" />
                            </button>
                            <button
                                :disabled="isLocked(q)"
                                @click="emit('delete', q.id, q.teks_soal)"
                                class="p-2 text-text-muted transition-colors hover:text-error-red disabled:cursor-not-allowed disabled:opacity-30 disabled:hover:text-text-muted"
                                :title="isLocked(q) ? 'Tidak bisa dihapus karena bank soal sudah digunakan di ujian' : 'Hapus'"
                            >
                                <IconTrash :size="18" />
                            </button>
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
