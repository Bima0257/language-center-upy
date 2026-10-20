<script setup>
import { IconCategory, IconSearch, IconX } from "@tabler/icons-vue";

defineProps({
    activeScope: { type: String, required: true },
    searchQuery: { type: String, default: "" },
    scopeFilters: { type: Array, default: () => [] },
    visibleCount: { type: Number, required: true },
    componentCount: { type: Number, required: true },
});

const emit = defineEmits([
    "update:active-scope",
    "update:search-query",
    "reset",
]);
</script>

<template>
    <BaseCard>
        <div class="flex flex-col gap-6 xl:flex-row xl:items-end xl:justify-between">
            <div class="max-w-2xl">
                <div class="mb-3 flex items-center gap-2 text-secondary">
                    <IconCategory :size="18" />
                    <span class="text-label-md font-semibold uppercase tracking-[0.16em]">
                        UI inventory
                    </span>
                </div>
                <h2 class="text-headline-md font-bold text-primary">
                    Satu tempat untuk memeriksa bahasa visual aplikasi
                </h2>
                <p class="mt-2 text-body-md text-text-muted">
                    Gunakan halaman ini untuk mengecek props, state, dan konsistensi
                    komponen sebelum dipakai di halaman fitur.
                </p>
            </div>

            <div class="flex w-full flex-col gap-3 xl:w-auto xl:min-w-[28rem]">
                <div class="relative">
                    <IconSearch
                        :size="18"
                        class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted"
                    />
                    <input
                        :value="searchQuery"
                        type="search"
                        placeholder="Cari nama atau path komponen..."
                        class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest py-3 pl-11 pr-4 text-body-md text-text-body outline-none transition-all placeholder:text-text-muted focus:border-secondary focus:ring-2 focus:ring-secondary/20"
                        @input="emit('update:search-query', $event.target.value)"
                    />
                </div>
                <BaseTabs
                    :model-value="activeScope"
                    :items="scopeFilters"
                    @update:model-value="emit('update:active-scope', $event)"
                />
            </div>
        </div>

        <div
            class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-outline-variant/30 pt-4"
        >
            <p class="text-label-md text-text-muted">
                Menampilkan <span class="font-semibold text-primary">{{ visibleCount }}</span>
                dari {{ componentCount }} komponen
            </p>
            <button
                v-if="activeScope !== 'all' || searchQuery"
                type="button"
                class="inline-flex items-center gap-1.5 text-label-md font-medium text-secondary hover:underline"
                @click="emit('reset')"
            >
                <IconX :size="15" />
                Reset filter
            </button>
        </div>
    </BaseCard>
</template>
