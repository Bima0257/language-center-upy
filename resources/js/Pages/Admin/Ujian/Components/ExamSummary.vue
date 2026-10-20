<script setup>
import { Link } from '@inertiajs/vue3';
import { IconEdit } from '@tabler/icons-vue';

defineProps({
    ujian: { type: Object, required: true },
    editHref: { type: String, required: true },
});
</script>

<template>
    <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <span class="inline-block bg-pastel-blue/50 text-primary px-3 py-1 rounded-full text-label-md font-medium">
                    {{ ujian.mode === 'tryout' ? 'Try Out' : 'Ujian Resmi' }}
                </span>
                <span class="text-text-muted text-label-md">{{ ujian.durasi_menit }} menit</span>
            </div>
            <Link :href="editHref"
                  class="flex items-center gap-2 text-secondary text-label-md font-medium hover:underline">
                <IconEdit :size="16" /> Ubah
            </Link>
        </div>
        <p v-if="ujian.deskripsi" class="text-text-body text-text-body text-body-md">{{ ujian.deskripsi }}</p>
    </div>
</template>
