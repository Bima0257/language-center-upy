<script setup>
defineProps({
    group: { type: Object, required: true },
});
</script>

<template>
    <section class="space-y-4">
        <div>
            <h2 class="text-headline-md font-bold text-text-heading">{{ group.title }}</h2>
            <p class="mt-1 text-body-md text-text-muted">{{ group.description }}</p>
        </div>

        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <slot />
        </div>
    </section>
</template>
