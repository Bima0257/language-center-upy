<script setup>
import { IconX } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    activeSection: { type: Object, default: null },
    questions: { type: Array, default: () => [] },
    form: { type: Object, required: true },
});

const emit = defineEmits(['close', 'toggle-question', 'submit']);

function soalPreview(question) {
    return (question.teks_soal || '').substring(0, 90);
}
</script>

<template>
    <BaseModal :show="show" @close="emit('close')" max-width="2xl" scrollable>
        <div class="p-8">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-headline-md font-bold text-text-heading">Tambah Soal ke Bagian Ujian</h2>
                <button @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                    <IconX :size="20" />
                </button>
            </div>
            <p v-if="activeSection" class="text-label-md text-text-muted mb-4">
                Bagian Ujian: <span class="font-semibold text-text-heading">{{ activeSection.judul }}</span> — hanya soal approved dari bank bagianUjian yang ditampilkan.
            </p>

            <div v-if="activeSection && questions.length === 0"
                 class="text-center py-8 bg-surface-container-low rounded-2xl">
                <p class="text-text-muted text-body-md">Tidak ada soal approved yang tersedia untuk ditambahkan.</p>
            </div>

            <div v-else class="modal-scrollbar space-y-2 max-h-[50vh] overflow-y-auto overscroll-contain pr-1">
                <label v-for="question in questions" :key="question.id"
                       class="flex items-start gap-3 p-3 rounded-xl border border-outline-variant/40 cursor-pointer hover:border-secondary/50 transition-colors">
                    <input type="checkbox"
                           :checked="form.soal_ids.includes(question.id)"
                           @change="emit('toggle-question', question.id)"
                           class="mt-1 w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                    <div class="min-w-0">
                           <p class="text-body-md text-text-heading font-medium truncate">{{ soalPreview(question) }}</p>
                        <p class="text-label-md text-text-muted truncate">
                             {{ question.bagian_soal?.nama || 'Tanpa bagianSoal' }}<span v-if="question.materi_soal"> — 📄 {{ question.materi_soal.judul }}</span>
                        </p>
                    </div>
                </label>
            </div>

            <div class="flex gap-4 pt-4">
                <BaseButton type="button" :disabled="form.processing || form.soal_ids.length === 0"
                            size="xl" class="flex-1" @click="emit('submit')">
                    {{ form.processing ? 'Menyimpan...' : `Tambah ${form.soal_ids.length || ''} Soal` }}
                </BaseButton>
                <BaseButton type="button" variant="secondary" size="lg" @click="emit('close')">
                    Batal
                </BaseButton>
            </div>
        </div>
    </BaseModal>
</template>
