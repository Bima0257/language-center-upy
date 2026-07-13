<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    session: { type: Object, required: true },
});

function formatDuration(startedAt) {
    if (!startedAt) return '-';
    const minutes = Math.floor((Date.now() - new Date(startedAt).getTime()) / 60000);
    return `${minutes}m`;
}
</script>

<template>
    <Link :href="route('proctor.session.show', session.id)"
          class="block bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 hover:border-secondary hover:shadow-md transition-all active:scale-[0.98]">
        <div class="flex items-start justify-between mb-3">
            <div class="min-w-0 flex-1">
                <p class="text-title-lg font-semibold text-primary truncate">{{ session.user?.name }}</p>
                <p class="text-label-md text-text-muted truncate">{{ session.user?.nim || 'NIM: -' }}</p>
            </div>
            <span class="flex items-center gap-1.5 text-label-md font-medium text-green-600 shrink-0">
                LIVE → <span class="w-2 h-2 rounded-full bg-green-500 inline-block animate-pulse"></span>
                AKTIF
            </span>
        </div>
        <p class="text-body-md text-text-body truncate mb-3">{{ session.schedule?.exam?.title }}</p>
        <div class="flex items-center gap-3 text-label-md text-text-muted">
            <span>⏱ {{ formatDuration(session.started_at) }}</span>
            <span class="w-px h-3 bg-outline-variant"></span>
            <span :class="(session.violation_strikes || 0) > 0 ? 'text-amber-600 font-medium' : ''">
                ⚠ {{ session.violation_strikes || 0 }} pelanggaran
            </span>
            <span class="w-px h-3 bg-outline-variant"></span>
            <span>{{ session.answers_count || session.answers?.length || 0 }} terjawab</span>
        </div>
    </Link>
</template>
