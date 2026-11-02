<script setup>
import { IconSearch, IconX } from '@tabler/icons-vue'

const props = defineProps({
    showSearch: { type: Boolean, default: true },
    searchPlaceholder: { type: String, default: 'Cari...' },
    searchAriaLabel: { type: String, default: 'Cari data' },
})

const search = defineModel('search', {
    type: String,
    default: '',
})
</script>

<template>
    <div
        v-if="props.showSearch || $slots.filters"
        class="flex flex-wrap items-center justify-end gap-3 border-b border-outline-variant/30 bg-surface-container-low/30 px-5 py-3.5"
    >
        <div v-if="props.showSearch" class="relative w-full sm:w-64">
            <IconSearch class="absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted" :size="17" />
            <input
                v-model="search"
                type="search"
                :placeholder="props.searchPlaceholder"
                :aria-label="props.searchAriaLabel"
                class="w-full rounded-full border border-outline-variant bg-surface-container-lowest py-2.5 pl-10 pr-10 text-body-md text-text-body focus:border-secondary focus:outline-none focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
            />
            <button
                v-if="search"
                type="button"
                class="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted transition-colors hover:text-primary"
                title="Hapus pencarian"
                aria-label="Hapus pencarian"
                @click="search = ''"
            >
                <IconX :size="16" />
            </button>
        </div>
        <div v-if="$slots.filters" class="ml-auto flex items-center justify-end gap-3">
            <slot name="filters" />
        </div>
    </div>
</template>
