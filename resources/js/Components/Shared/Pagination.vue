<script setup>
import { Link } from "@inertiajs/vue3";

defineProps({
    links: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    perPage: { type: Number, default: 0 },
    align: { type: String, default: "center" },
    marginless: { type: Boolean, default: false },
});
</script>

<template>
    <div
        v-if="total > perPage"
        class="flex gap-2"
        :class="[align === 'end' ? 'justify-end' : 'justify-center', marginless ? '' : 'mt-6']"
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
