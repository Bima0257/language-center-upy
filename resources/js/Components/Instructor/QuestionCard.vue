<script setup>
import { Link } from '@inertiajs/vue3';
import { IconCheck, IconEdit, IconTrash, IconX } from '@tabler/icons-vue';

const props = defineProps({
    question: { type: Object, required: true },
    canReview: { type: Boolean, default: false },
    bankName: { type: Function, default: () => '' },
    skillName: { type: Function, default: () => '' },
    selected: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle', 'review', 'delete']);

const optionKeys = ['A', 'B', 'C', 'D'];

const statusLabels = { draft: 'Draf', approved: 'Disetujui', rejected: 'Ditolak' };
const statusColors = { draft: 'bg-amber-100 dark:bg-amber-950/30 text-amber-700 dark:text-amber-300', approved: 'bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-300', rejected: 'bg-error-red/10 text-error-red' };

function qOption(key) {
    return props.question['option_' + key.toLowerCase()] || '';
}

const q = props.question;
</script>

<template>
    <div class="flex items-start justify-between gap-4 p-4">
        <div class="flex items-start gap-3 min-w-0">
            <input v-if="canReview" type="checkbox" :checked="selected" @change="emit('toggle')"
                   class="mt-1.5 w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary shrink-0" />
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1.5 flex-wrap">
                    <span v-if="q.question_bank_id" class="inline-block bg-pastel-peach/50 text-amber-800 dark:text-amber-300 px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ bankName(q.question_bank_id) }}</span>
                    <span v-if="q.skill_id" class="inline-block bg-pastel-blue/50 text-blue-700 dark:text-blue-300 px-2.5 py-0.5 rounded-full text-label-md font-medium">{{ skillName(q.skill_id) }}</span>
                    <span class="inline-block bg-pastel-purple/50 text-primary px-2.5 py-0.5 rounded-full text-label-md font-medium">Pilihan Ganda</span>
                    <span :class="statusColors[q.status] || 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'" class="px-2.5 py-0.5 rounded-full text-label-md">{{ statusLabels[q.status] || q.status }}</span>
                </div>
                <p class="text-text-body text-body-md text-primary font-medium mb-1">{{ q.question_text }}</p>
                <div class="flex flex-wrap gap-2 mt-2">
                    <span v-for="key in optionKeys" :key="key"
                          :class="q.correct_answer === key ? 'ring-1 ring-green-500 border-green-500 text-green-700 dark:text-green-300' : 'border-outline-variant'"
                          class="inline-flex items-center gap-1 border bg-surface-container-low px-2.5 py-1 rounded-lg text-label-md text-text-body">
                        {{ key }}. {{ qOption(key)?.substring(0, 40) }}
                        <span v-if="q.correct_answer === key" class="text-green-600 dark:text-green-400 font-bold">✓</span>
                    </span>
                </div>
                <p class="text-label-md text-text-muted mt-2">
                    <span v-if="q.creator">oleh {{ q.creator.name }}</span>
                    <span v-if="q.reviewed_at"> | direview {{ new Date(q.reviewed_at).toLocaleDateString('id') }}</span>
                </p>
            </div>
        </div>
        <div class="flex gap-2 shrink-0">
            <template v-if="canReview && q.status === 'draft'">
                <button @click="emit('review', q.id, 'approved')" class="p-2 text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-400 transition-colors" title="Setujui"><IconCheck :size="18" /></button>
                <button @click="emit('review', q.id, 'rejected')" class="p-2 text-error-red hover:text-red-700 dark:hover:text-red-400 transition-colors" title="Tolak"><IconX :size="18" /></button>
            </template>
            <Link :href="route('content-library.edit', q.id)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></Link>
            <button @click="emit('delete', q.id, q.question_text)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
        </div>
    </div>
</template>
