<script setup>
const props = defineProps({
    pengguna: { type: Object, required: true },
});
</script>

<template>
    <div v-if="props.pengguna.meta && props.pengguna.meta.last_page > 1" class="flex items-center justify-between mt-4">
        <p class="text-label-md text-text-muted">
            {{ props.pengguna.meta.from }}–{{ props.pengguna.meta.to }} dari {{ props.pengguna.meta.total }} pengguna
        </p>
        <div class="flex gap-2">
            <BaseButton v-for="link in props.pengguna.links" :key="link.url" :href="link.url" size="sm" variant="secondary"
                :class="{ 'opacity-50 pointer-events-none': !link.url }">
                {{ link.label }}
            </BaseButton>
        </div>
    </div>
</template>
