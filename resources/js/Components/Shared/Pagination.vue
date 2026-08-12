<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    links: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    perPage: { type: Number, default: 0 },
});
</script>

<template>
    <div
        v-if="total > perPage"
        class="flex justify-center mt-6 gap-2"
    >
        <Link
            v-for="link in links"
            :key="link.label"
            :href="link.url || '#'"
            class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
            :class="
                link.active
                    ? 'bg-primary-container text-white'
                    : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'
            "
            v-html="link.label"
        />
    </div>
</template>
