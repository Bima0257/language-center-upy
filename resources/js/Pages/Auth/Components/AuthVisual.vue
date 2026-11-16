<script setup>
import { IconLanguage } from '@tabler/icons-vue';
</script>

<template>
    <div
        class="relative aspect-[4/3] w-full max-w-[320px] overflow-hidden rounded-2xl border border-outline-variant/60 bg-surface-white shadow-standard"
        aria-hidden="true"
    >
        <div class="absolute -right-8 -top-10 h-32 w-32 rounded-full bg-pastel-blue/60 blur-2xl"></div>
        <div class="absolute -bottom-10 -left-8 h-32 w-32 rounded-full bg-pastel-purple/60 blur-2xl"></div>

        <div class="absolute inset-0 flex items-center justify-center">
            <div class="h-28 w-28 rounded-full border border-outline-variant/50"></div>
            <div class="absolute h-20 w-20 rounded-full bg-surface-container-low"></div>
        </div>

        <div class="relative flex h-full flex-col items-center justify-center gap-4">
            <span
                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-container text-on-primary-container shadow-standard"
            >
                <IconLanguage :size="28" stroke="1.75" />
            </span>
            <div class="flex w-32 flex-col items-center gap-2">
                <div class="h-1.5 w-full overflow-hidden rounded-full bg-track-neutral">
                    <div class="h-full w-[85%] rounded-full bg-primary"></div>
                </div>
                <span class="h-1.5 w-20 rounded-full bg-surface-container-high"></span>
            </div>
        </div>

        <span class="absolute right-6 top-6 h-3 w-3 rounded-full bg-pastel-peach"></span>
    </div>
</template>
