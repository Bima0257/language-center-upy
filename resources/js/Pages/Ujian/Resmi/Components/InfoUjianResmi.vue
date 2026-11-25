<script setup>
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import { IconClock, IconFileDescription } from '@tabler/icons-vue';

defineProps({
    ujian: { type: Object, required: true },
});
</script>

<template>
    <BaseCard padding="p-5">
        <div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
            <div class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-surface-container-low text-secondary">
                <IconFileDescription :size="24" stroke="1.8" />
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-headline-md font-bold text-text-heading">{{ ujian.judul }}</h2>
                    <BaseBadge v-if="ujian.program_tes?.nama" variant="pastel-blue">
                        {{ ujian.program_tes.nama }}
                    </BaseBadge>
                </div>

                <div
                    v-if="ujian.deskripsi"
                    class="prose prose-sm mt-1 max-w-none text-body-md text-text-body"
                    v-html="ujian.deskripsi"
                />

                <p v-if="ujian.durasi_menit" class="mt-2 flex items-center gap-1.5 text-label-md text-text-muted">
                    <IconClock :size="14" />
                    Durasi {{ ujian.durasi_menit }} menit
                </p>
            </div>
        </div>
    </BaseCard>
</template>
