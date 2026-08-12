<script setup>
import { useVueTable, getCoreRowModel, getSortedRowModel } from '@tanstack/vue-table'
import { Link } from '@inertiajs/vue3'
import { IconChevronUp, IconChevronDown, IconSelector, IconLoader2, IconSearch } from '@tabler/icons-vue'
import { computed, ref } from 'vue'

const props = defineProps({
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    links: { type: Array, default: () => [] },
    meta: { type: Object, default: null },
    loading: { type: Boolean, default: false },
    searchable: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Cari...' },
    searchQuery: { type: String, default: '' },
    rowLink: { type: Function, default: null },
})

const emit = defineEmits(['search'])

const tableColumns = computed(() =>
    props.columns.map(col => ({
        accessorKey: col.key,
        header: col.label || col.key,
        enableSorting: col.sortable ?? false,
        slot: col.slot ?? null,
        cell: info => {
            const val = info.getValue()
            const row = info.row.original
            if (col.render) return col.render(val, row)
            if (val === null || val === undefined) return '-'
            return String(val)
        },
        meta: { badge: col.badge ?? false, className: col.className || '' },
    }))
)

const table = useVueTable({
    get data() { return props.data },
    columns: tableColumns.value,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    initialState: { sorting: [] },
})

const searchInput = ref(props.searchQuery)

function onSearchInput() {
    emit('search', searchInput.value)
}

function badgeVariant(val) {
    const m = {
        'Try Out': 'pastel-blue',
        'Ujian Resmi': 'pastel',
        'Aktif': 'success',
        'Nonaktif': 'neutral',
        'Laki-laki': 'pastel-blue',
        'Perempuan': 'pastel-peach',
    }
    return m[val] || ''
}

function cellDisplayValue(cell) {
    const fn = cell.column.columnDef.cell
    if (fn) {
        const rendered = fn(cell.getContext())
        return rendered ?? ''
    }
    const val = cell.getValue()
    if (val === null || val === undefined) return '-'
    return String(val)
}
</script>

<template>
    <div>
        <div v-if="searchable" class="relative mb-4">
            <IconSearch class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
            <input type="text" v-model="searchInput" @input="onSearchInput"
                   :placeholder="searchPlaceholder"
                   class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
        </div>

        <BaseCard :padding="false" class="overflow-hidden relative">
            <div v-if="loading" class="absolute inset-0 bg-surface-white/60 z-10 flex items-center justify-center">
                <IconLoader2 class="text-primary animate-spin" :size="32" stroke="1.5" />
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id"
                            class="border-b border-outline-variant/30 bg-surface-container-low">
                            <th v-for="header in headerGroup.headers" :key="header.id"
                                class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4"
                                :class="header.column.getCanSort() ? 'cursor-pointer select-none hover:text-primary transition-colors' : ''"
                                @click="header.column.getToggleSortingHandler()?.()">
                                <div class="flex items-center gap-1.5">
                                    <span>{{ header.column.columnDef.header }}</span>
                                    <span v-if="header.column.getCanSort()" class="shrink-0">
                                        <IconChevronUp v-if="header.column.getIsSorted() === 'asc'"
                                                      :size="14" class="text-primary" />
                                        <IconChevronDown v-else-if="header.column.getIsSorted() === 'desc'"
                                                        :size="14" class="text-primary" />
                                        <IconSelector v-else :size="14" class="text-text-muted" />
                                    </span>
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in table.getRowModel().rows" :key="row.id"
                            class="border-b border-outline-variant/20 last:border-0 transition-colors"
                            :class="rowLink ? 'cursor-pointer hover:bg-surface-container-low/50' : 'hover:bg-surface-container-low/50'"
                            @click="rowLink && rowLink(row.original)">
                            <td v-for="cell in row.getVisibleCells()" :key="cell.id"
                                class="px-5 py-4 text-body-md"
                                :class="cell.column.columnDef.meta?.className || 'text-text-body'">
                                <slot v-if="cell.column.columnDef.slot"
                                      :name="cell.column.columnDef.slot"
                                      :row="row.original"
                                      :value="cellDisplayValue(cell)" />
                                <BaseBadge v-else-if="cell.column.columnDef.meta?.badge && badgeVariant(cellDisplayValue(cell))"
                                            :variant="badgeVariant(cellDisplayValue(cell))">
                                    {{ cellDisplayValue(cell) }}
                                </BaseBadge>
                                <span v-else>{{ cellDisplayValue(cell) }}</span>
                            </td>
                        </tr>
                        <tr v-if="data.length === 0 && !loading">
                            <td :colspan="columns.length" class="px-5 py-12 text-center text-text-muted text-body-md">
                                Tidak ada data.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="meta && meta.last_page > 1"
                 class="flex items-center justify-between px-5 py-4 border-t border-outline-variant/30 bg-surface-container-low">
                <p class="text-label-md text-text-muted">
                    {{ meta.from }}–{{ meta.to }} dari {{ meta.total }}
                </p>
                <div class="flex items-center gap-1">
                    <Link v-for="(link, i) in links" :key="i"
                          :href="link.url || '#'"
                          class="min-w-[36px] h-9 flex items-center justify-center rounded-full text-label-md font-medium transition-colors"
                          :class="link.active ? 'bg-primary-container text-white' : link.url ? 'text-text-body hover:bg-surface-container-low' : 'text-text-muted cursor-default'"
                          v-html="link.label" />
                </div>
            </div>
        </BaseCard>
    </div>
</template>
