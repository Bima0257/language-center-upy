<script setup>
defineProps({
    name: { type: String, required: true },
    description: { type: String, default: "" },
    path: { type: String, required: true },
    scope: { type: String, default: "Reusable" },
});
</script>

<template>
    <BaseCard class="flex h-full flex-col">
        <div class="flex items-start justify-between gap-3 border-b border-outline-variant/30 pb-4">
            <div class="min-w-0">
                <h3 class="text-title-lg font-semibold text-primary">{{ name }}</h3>
                <p class="mt-1 text-label-md leading-relaxed text-text-muted">
                    {{ description }}
                </p>
            </div>
            <BaseBadge :variant="scope === 'Global' ? 'primary' : 'pastel-blue'">
                {{ scope }}
            </BaseBadge>
        </div>

        <div class="flex-1 pt-5">
            <slot />
        </div>

        <code class="mt-5 block truncate rounded-lg bg-surface-container-low px-3 py-2 text-[11px] text-text-muted">
            {{ path }}
        </code>
    </BaseCard>
</template>
