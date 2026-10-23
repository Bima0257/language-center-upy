<script setup>
import {
    getCoreRowModel,
    getFilteredRowModel,
    getPaginationRowModel,
    getSortedRowModel,
    useVueTable,
} from '@tanstack/vue-table'
import Pagination from '@/Components/Shared/Pagination.vue'
import DropDown from '@/Components/Shared/DropDown.vue'
import { IconChevronUp, IconChevronDown, IconSelector, IconLoader2, IconSearch, IconX } from '@tabler/icons-vue'
import { computed, ref, watch } from 'vue'

const props = defineProps({
    columns: { type: Array, required: true },
    data: { type: Array, default: () => [] },
    loading: { type: Boolean, default: false },
    searchable: { type: Boolean, default: false },
    searchPlaceholder: { type: String, default: 'Cari...' },
    searchQuery: { type: String, default: '' },
    rowLink: { type: Function, default: null },
    headerTitle: { type: String, default: '' },
    pageSizeOptions: { type: Array, default: () => [10, 25, 50, 100] },
    defaultPageSize: { type: Number, default: 10 },
    showPageSize: { type: Boolean, default: true },
    showColumnFilters: { type: Boolean, default: false },
})

const emit = defineEmits(['search', 'update:page-size'])

const sorting = ref([])
const columnFilters = ref([])
const globalFilter = ref(props.searchQuery)
const dropdownOpen = ref(false)
const initialPageSize = props.pageSizeOptions.map(Number).includes(props.defaultPageSize)
    ? props.defaultPageSize
    : Number(props.pageSizeOptions[0]) || 10
const pagination = ref({ pageIndex: 0, pageSize: initialPageSize })

function stringifyValue(value) {
    if (value === null || value === undefined) return ''
    if (typeof value === 'object') return JSON.stringify(value) || ''

    return String(value)
}

function renderedValue(column, value, row) {
    return column.render ? column.render(value, row) : value
}

function compareValues(firstValue, secondValue) {
    if (typeof firstValue === 'number' && typeof secondValue === 'number') {
        return firstValue - secondValue
    }

    return stringifyValue(firstValue).localeCompare(stringifyValue(secondValue), undefined, {
        numeric: true,
        sensitivity: 'base',
    })
}

function globalFilterFn(row, _columnId, filterValue) {
    const normalizedFilter = stringifyValue(filterValue).trim().toLowerCase()

    if (!normalizedFilter) return true

    return row.getAllCells().some(cell => {
        if (!cell.column.getCanGlobalFilter()) return false

        const columnDefinition = cell.column.columnDef
        const value = columnDefinition.meta?.render
            ? columnDefinition.meta.render(cell.getValue(), row.original)
            : cell.getValue()

        return stringifyValue(value).toLowerCase().includes(normalizedFilter)
    })
}

const tableColumns = computed(() =>
    props.columns.map(column => ({
        accessorKey: column.key,
        header: column.label || column.key,
        enableSorting: column.sortable ?? false,
        enableColumnFilter: column.filterable ?? false,
        enableGlobalFilter: column.searchable ?? !column.slot,
        slot: column.slot ?? null,
        cell: info => {
            const val = info.getValue()
            const row = info.row.original
            if (column.render) return column.render(val, row)
            if (val === null || val === undefined) return '-'
            return String(val)
        },
        sortingFn: (rowA, rowB, columnId) => compareValues(
            renderedValue(column, rowA.getValue(columnId), rowA.original),
            renderedValue(column, rowB.getValue(columnId), rowB.original),
        ),
        filterFn: (row, columnId, filterValue) => stringifyValue(
            renderedValue(column, row.getValue(columnId), row.original),
        ).toLowerCase().includes(stringifyValue(filterValue).trim().toLowerCase()),
        meta: {
            badge: column.badge ?? false,
            className: column.className || '',
            render: column.render,
        },
    }))
)

const table = useVueTable({
    get data() { return props.data },
    columns: tableColumns.value,
    state: {
        get sorting() { return sorting.value },
        get columnFilters() { return columnFilters.value },
        get globalFilter() { return globalFilter.value },
        get pagination() { return pagination.value },
    },
    onSortingChange: updater => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater
    },
    onColumnFiltersChange: updater => {
        columnFilters.value = typeof updater === 'function' ? updater(columnFilters.value) : updater
    },
    onGlobalFilterChange: updater => {
        globalFilter.value = typeof updater === 'function' ? updater(globalFilter.value) : updater
    },
    onPaginationChange: updater => {
        pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater
    },
    globalFilterFn,
    getCoreRowModel: getCoreRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
})

const pageSizeOptions = computed(() => props.pageSizeOptions.map(pageSize => ({
    value: Number(pageSize),
    label: `${pageSize} baris`,
})))

const selectedPageSize = computed({
    get: () => table.getState().pagination.pageSize,
    set: value => updatePageSize(value),
})

const hasColumnFilters = computed(() =>
    props.showColumnFilters && table.getAllLeafColumns().some(column => column.getCanFilter()),
)

const paginationSummary = computed(() => {
    const total = table.getFilteredRowModel().rows.length
    const { pageIndex, pageSize } = table.getState().pagination

    return {
        from: total === 0 ? 0 : pageIndex * pageSize + 1,
        to: Math.min((pageIndex + 1) * pageSize, total),
        total,
    }
})

function updatePageSize(value) {
    const pageSize = Number(value)

    if (!pageSize) return

    table.setPageSize(pageSize)
    table.setPageIndex(0)
    emit('update:page-size', pageSize)
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

function goToPage(page) {
    table.setPageIndex(page - 1)
}

watch(globalFilter, value => {
    table.setPageIndex(0)
    emit('search', value)
})

watch(sorting, () => table.setPageIndex(0), { deep: true })
watch(columnFilters, () => table.setPageIndex(0), { deep: true })
watch(() => props.searchQuery, value => {
    if (value !== globalFilter.value) globalFilter.value = value
})
watch(() => props.data, () => table.setPageIndex(0))

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
    <div :class="dropdownOpen && table.getPageCount() === 1 ? 'pb-48' : ''">
        <BaseCard :padding="false" class="relative">
            <div v-if="loading" class="absolute inset-0 bg-surface-white/60 z-10 flex items-center justify-center">
                <IconLoader2 class="text-primary animate-spin" :size="32" stroke="1.5" />
            </div>

            <div v-if="$slots.toolbar || headerTitle"
                 class="flex flex-wrap items-center gap-3 border-b-2 border-outline-variant/40 bg-surface-white px-5 py-3.5">
                <h2 v-if="headerTitle" class="shrink-0 text-title-lg font-semibold text-text-heading">
                    {{ headerTitle }}
                </h2>
                <div v-if="$slots.toolbar" class="ml-auto flex items-center justify-end gap-3">
                    <slot name="toolbar" />
                </div>
            </div>

            <div v-if="searchable || $slots.filters"
                 class="flex flex-wrap items-center justify-end gap-3 border-b border-outline-variant/30 bg-surface-container-low/30 px-5 py-3.5">
                <div v-if="searchable" class="relative w-full sm:w-64">
                    <IconSearch class="absolute left-3.5 top-1/2 -translate-y-1/2 text-text-muted" :size="17" />
                    <input
                        v-model="globalFilter"
                        type="text"
                        :placeholder="searchPlaceholder"
                        class="w-full rounded-full border border-outline-variant bg-surface-container-lowest py-2.5 pl-10 pr-10 text-body-md text-text-body focus:border-secondary focus:outline-none focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
                    />
                    <button
                        v-if="globalFilter"
                        type="button"
                        class="absolute right-3.5 top-1/2 -translate-y-1/2 text-text-muted hover:text-primary transition-colors"
                        title="Hapus pencarian"
                        aria-label="Hapus pencarian"
                        @click="globalFilter = ''"
                    >
                        <IconX :size="16" />
                    </button>
                </div>
                <div v-if="$slots.filters" class="ml-auto flex items-center justify-end gap-3">
                    <slot name="filters" :table="table" />
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <template v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
                            <tr class="border-b border-outline-variant/30 bg-surface-container-low">
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
                            <tr v-if="hasColumnFilters" class="border-b border-outline-variant/20 bg-surface-container-lowest">
                                <th v-for="header in headerGroup.headers" :key="`${header.id}-filter`" class="px-5 py-2">
                                    <input
                                        v-if="header.column.getCanFilter()"
                                        :value="header.column.getFilterValue() ?? ''"
                                        type="text"
                                        class="w-full rounded-xl border border-outline-variant bg-surface-white px-3 py-2 text-label-md text-text-body focus:border-secondary focus:outline-none"
                                        placeholder="Filter..."
                                        @input="header.column.setFilterValue($event.target.value)"
                                    />
                                </th>
                            </tr>
                        </template>
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
                        <tr v-if="table.getRowModel().rows.length === 0 && !loading">
                            <td :colspan="columns.length" class="px-5 py-12 text-center text-text-muted text-body-md">
                                {{ data.length === 0 ? 'Tidak ada data.' : 'Tidak ada data yang cocok.' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="data.length > 0"
                 class="flex flex-col gap-4 rounded-b-2xl border-t border-outline-variant/30 bg-surface-container-low px-5 py-4 sm:flex-row sm:items-center">
                <p class="text-label-md text-text-muted sm:shrink-0">
                    Menampilkan {{ paginationSummary.from }}–{{ paginationSummary.to }} dari {{ paginationSummary.total }}
                </p>
                <div class="flex items-end justify-end gap-3 sm:ml-auto">
                    <DropDown
                        v-if="showPageSize"
                        v-model="selectedPageSize"
                        :options="pageSizeOptions"
                        option-label="label"
                        option-value="value"
                        size="sm"
                        button-class="min-w-[120px]"
                        :menu-placement="table.getPageCount() > 1 ? 'top' : 'bottom'"
                        @open-change="dropdownOpen = $event"
                    />
                    <Pagination
                        v-if="table.getPageCount() > 1"
                        mode="client"
                        :current-page="table.getState().pagination.pageIndex + 1"
                        :last-page="table.getPageCount()"
                        align="end"
                        marginless
                        @page-change="goToPage"
                    />
                </div>
            </div>
        </BaseCard>
    </div>
</template>
