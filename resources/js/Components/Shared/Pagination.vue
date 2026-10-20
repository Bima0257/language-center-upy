<script setup>
import { Link } from "@inertiajs/vue3";
import { computed } from "vue";

const props = defineProps({
    mode: { type: String, default: "server" },
    links: { type: Array, default: () => [] },
    total: { type: Number, default: 0 },
    perPage: { type: Number, default: 0 },
    currentPage: { type: Number, default: 1 },
    lastPage: { type: Number, default: 1 },
    align: { type: String, default: "center" },
    marginless: { type: Boolean, default: false },
});

const emit = defineEmits(["page-change"]);

const clientPageItems = computed(() => {
    if (props.lastPage <= 7) {
        return Array.from({ length: props.lastPage }, (_, index) => index + 1);
    }

    const items = [1];
    const start = Math.max(2, props.currentPage - 1);
    const end = Math.min(props.lastPage - 1, props.currentPage + 1);

    if (start > 2) items.push(null);

    for (let page = start; page <= end; page++) {
        items.push(page);
    }

    if (end < props.lastPage - 1) items.push(null);

    items.push(props.lastPage);

    return items;
});

function changePage(page) {
    if (page < 1 || page > props.lastPage || page === props.currentPage) return;

    emit("page-change", page);
}
</script>

<template>
    <div
        v-if="mode === 'client' ? lastPage > 1 : total > perPage"
        class="flex gap-2"
        :class="[align === 'end' ? 'justify-end' : 'justify-center', marginless ? '' : 'mt-6']"
    >
        <template v-if="mode === 'client'">
            <button
                type="button"
                class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                :class="currentPage === 1
                    ? 'bg-surface-container-low text-outline-variant cursor-not-allowed'
                    : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'"
                :disabled="currentPage === 1"
                aria-label="Halaman sebelumnya"
                @click="changePage(currentPage - 1)"
            >
                &laquo;
            </button>
            <template v-for="(page, index) in clientPageItems" :key="`${page ?? 'ellipsis'}-${index}`">
                <span v-if="page === null" class="px-2 py-2 text-label-md text-text-muted">…</span>
                <button
                    v-else
                    type="button"
                    class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                    :class="page === currentPage
                        ? 'bg-primary-container text-on-primary-container'
                        : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'"
                    :aria-label="`Halaman ${page}`"
                    :aria-current="page === currentPage ? 'page' : undefined"
                    @click="changePage(page)"
                >
                    {{ page }}
                </button>
            </template>
            <button
                type="button"
                class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                :class="currentPage === lastPage
                    ? 'bg-surface-container-low text-outline-variant cursor-not-allowed'
                    : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'"
                :disabled="currentPage === lastPage"
                aria-label="Halaman berikutnya"
                @click="changePage(currentPage + 1)"
            >
                &raquo;
            </button>
        </template>
        <template v-else>
            <Link
                v-for="link in links"
                :key="link.label"
                :href="link.url || '#'"
                class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                :class="
                    link.active
                        ? 'bg-primary-container text-on-primary-container'
                        : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'
                "
                v-html="link.label"
            />
        </template>
    </div>
</template>
