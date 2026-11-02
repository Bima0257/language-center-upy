<script setup>
defineProps({
    title: { type: String, default: "" },
    subtitle: { type: String, default: "" },
    backHref: { type: String, default: null },
    backLabel: { type: String, default: "Kembali" },
});
</script>

<template>
    <BaseCard class="mb-6">
        <div class="flex items-center gap-3">
            <BaseBackButton
                v-if="backHref"
                :href="backHref"
                label=""
                variant="primary"
                :aria-label="backLabel"
                class="!size-10 !p-0 shrink-0"
            />
            <div class="min-w-0 flex-1">
                <h1 class="text-headline-md font-bold text-text-heading">{{ title }}</h1>
                <p v-if="subtitle" class="text-body-md text-text-muted mt-1">{{ subtitle }}</p>
            </div>
        </div>
    </BaseCard>
</template>
