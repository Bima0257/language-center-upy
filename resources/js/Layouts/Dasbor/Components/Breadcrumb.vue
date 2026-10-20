<script setup>
import { Link } from "@inertiajs/vue3";
import { IconChevronRight } from "@tabler/icons-vue";

defineProps({
    items: { type: Array, default: () => [] },
});
</script>

<template>
    <nav class="flex items-center gap-1.5 flex-wrap text-label-md min-w-0">
        <template v-for="(item, i) in items" :key="i">
            <span v-if="i > 0" class="text-text-muted shrink-0">
                <IconChevronRight :size="14" />
            </span>

            <Link
                v-if="item.route && i < items.length - 1"
                :href="route(item.route)"
                class="text-secondary hover:underline transition-colors whitespace-nowrap"
            >
                {{ item.label }}
            </Link>
            <span
                v-else-if="item.route"
                :class="i === items.length - 1 ? 'text-primary font-semibold' : 'text-text-muted'"
                class="whitespace-nowrap"
            >
                {{ item.label }}
            </span>
            <span
                v-else
                :class="i === items.length - 1 ? 'text-primary font-semibold' : 'text-text-muted'"
                class="whitespace-nowrap"
            >
                {{ item.label }}
            </span>
        </template>
    </nav>
</template>
