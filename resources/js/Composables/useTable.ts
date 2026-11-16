import {
    columnVisibilityFeature,
    createFilteredRowModel,
    createPaginatedRowModel,
    createSortedRowModel,
    createTableHook,
    filterFn_includesString,
    globalFilteringFeature,
    rowPaginationFeature,
    rowSortingFeature,
    sortFns,
    tableFeatures,
} from '@tanstack/vue-table-v9';

/**
 * Shared table features untuk seluruh aplikasi:
 * sorting, pagination, global filter (search), dan column visibility.
 * Didefinisikan sekali agar setiap tabel mendapat API yang sama.
 */
export const features = tableFeatures({
    rowSortingFeature,
    rowPaginationFeature,
    globalFilteringFeature,
    columnVisibilityFeature,
    sortedRowModel: createSortedRowModel(),
    paginatedRowModel: createPaginatedRowModel(),
    filteredRowModel: createFilteredRowModel(),
    sortFns,
    filterFns: { includesString: filterFn_includesString },
});

const { useAppTable, createAppColumnHelper } = createTableHook({ features });

export { createAppColumnHelper };

export function useTable(options: Parameters<typeof useAppTable>[0]) {
    return useAppTable(options);
}

export function tableStateQuery(tableKey: string): string {
    if (typeof window === 'undefined') return '';

    try {
        const rawState = window.sessionStorage.getItem(`${tableKey}:state`);

        if (!rawState) return '';

        const state = JSON.parse(rawState);

        return typeof state?.query === 'string' ? state.query : '';
    } catch {
        return '';
    }
}
