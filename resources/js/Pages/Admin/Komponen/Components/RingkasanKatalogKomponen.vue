<script setup>
import { IconCategory } from "@tabler/icons-vue";

defineProps({
    componentCount: { type: Number, required: true },
    globalComponentCount: { type: Number, required: true },
    reusableComponentCount: { type: Number, required: true },
});
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <BaseCard class="flex items-center gap-4 p-5">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary-container text-on-primary-container"
            >
                <IconCategory :size="22" />
            </div>
            <div>
                <p class="text-headline-md font-bold text-text-heading">{{ componentCount }}</p>
                <p class="text-label-md text-text-muted">Komponen terdaftar</p>
            </div>
        </BaseCard>
        <BaseCard class="flex items-center gap-4 p-5">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-pastel-purple text-primary"
            >
                <span class="text-title-lg font-bold">G</span>
            </div>
            <div>
                <p class="text-headline-md font-bold text-text-heading">{{ globalComponentCount }}</p>
                <p class="text-label-md text-text-muted">Global auto-registered</p>
            </div>
        </BaseCard>
        <BaseCard class="flex items-center gap-4 p-5">
            <div
                class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-pastel-blue text-primary"
            >
                <span class="text-title-lg font-bold">R</span>
            </div>
            <div>
                <p class="text-headline-md font-bold text-text-heading">{{ reusableComponentCount }}</p>
                <p class="text-label-md text-text-muted">Reusable explicit import</p>
            </div>
        </BaseCard>
    </div>
</template>
