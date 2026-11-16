<script>
const consumedHighlightTokens = new Set();
</script>

<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, useSlots, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { FlexRender } from '@tanstack/vue-table-v9';
import BaseCard from '@/Components/Base/BaseCard.vue';
import AppDropdown from '@/Components/Ui/AppDropdown.vue';
import AppFilterDropdown from '@/Components/Ui/AppFilterDropdown.vue';
import AppIcon from '@/Components/Ui/AppIcon.vue';
import AppTabs from '@/Components/Ui/AppTabs.vue';
import { useTable } from '@/Composables/useTable';

const props = defineProps({
    tableKey: { type: String, default: 'app-data-table' },
    columns: { type: Array, required: true },
    data: { type: Array, required: true },
    title: { type: String, default: null },
    searchable: { type: Boolean, default: true },
    searchPlaceholder: { type: String, default: 'Cari...' },
    pageSize: { type: Number, default: 10 },
    pageSizeOptions: { type: Array, default: () => [10, 25, 50, 100] },
    tableVisibleRows: { type: Number, default: 10 },
    tableMaxHeight: { type: String, default: null },
    pageScrollAtVisibleLimit: { type: Boolean, default: true },
    showPagination: { type: Boolean, default: true },
    showPageSize: { type: Boolean, default: true },
    persistState: { type: Boolean, default: true },
    emptyMessage: { type: String, default: 'Tidak ada data.' },
    filters: { type: Array, default: () => [] },
    initialFilters: { type: Object, default: null },
    fixedFilters: { type: Object, default: () => ({}) },
    columnToggle: { type: Boolean, default: true },
    showTitleIcon: { type: Boolean, default: true },
    searchInTitle: { type: Boolean, default: false },
    mobileSearchPlaceholder: { type: String, default: null },
    mobilePrint: { type: Boolean, default: false },
    mobileSearchOnly: { type: Boolean, default: false },
    hideMobileSearch: { type: Boolean, default: false },
    showRowNumber: { type: Boolean, default: true },
    loading: { type: Boolean, default: false },
    highlightIds: { type: Array, default: () => [] },
    highlightPage: { type: Number, default: null },
    draggable: { type: Boolean, default: false },
    rowClick: { type: Function, default: null },
    tabs: { type: Array, default: null },
    activeTab: { type: String, default: null },
    tabsVariant: { type: String, default: 'chrome' },
    serverSide: { type: Boolean, default: false },
    serverTotalRows: { type: Number, default: 0 },
    reloadOnly: { type: Array, default: () => [] },
    toolbarStartAlign: { type: String, default: 'right' },
    variant: { type: String, default: 'default' },
});

const emit = defineEmits(['row-drop', 'update:activeTab', 'filter-change', 'filters-reset']);

const TABLE_QUERY_KEYS = ['q', 'page', 'per_page', 'sort', 'dir'];
const MENU_GROUP_STORAGE_KEY = '__appTableLastMenuGroup';
const HIGHLIGHT_DURATION = 800;
const HIGHLIGHT_RETRY_DELAY = 50;
const HIGHLIGHT_RETRY_LIMIT = 60;
const PERSIST_DELAY = 300;
const SEARCH_DELAY = 300;

const slots = useSlots();
const inertiaPage = usePage();

const rootRef = ref(null);
const tableRef = ref(null);
const scrollContainer = ref(null);
const columnMenuRef = ref(null);
const filterMenuRef = ref(null);

const mountPath = typeof window !== 'undefined' ? window.location.pathname : '';
const currentMenuGroup = detectMenuGroup();

const columnMenuOpen = ref(false);
const filterMenuOpen = ref(false);
const filterDraft = ref({});

const serverTableLoading = ref(false);
const serverVisitPending = ref(false);
const navigationInProgress = ref(false);
const isRestoring = ref(true);
const pendingRestore = ref(false);
const restoreInteractionPending = ref(false);

const canScrollLeft = ref(false);
const canScrollRight = ref(false);
const scrollArrowTop = ref(0);
const actionColumnWidth = ref(0);

const draggedIndex = ref(null);
const dragOverIndex = ref(null);

const highlightedRowIds = ref(new Set());
const pendingHighlightPayload = ref(null);
const componentMounted = ref(false);

let serverVisitTimer = null;
let persistTimer = null;
let highlightClearTimer = null;
let highlightRetryTimer = null;
let restoreRetryTimer = null;
let scrollRaf = null;
let suppressNextPaginationVisit = false;
let lastServerUrl = '';
let serverVisitActive = 0;
let resizeObserver = null;
const routerUnsubscribers = [];

const persistenceEnabled = computed(() => props.persistState !== false);

const normalizedPageSizeOptions = computed(() => {
    const rawOptions = Array.isArray(props.pageSizeOptions) ? props.pageSizeOptions : [];
    const normalized = rawOptions
        .map(value => Number(value))
        .filter(value => Number.isFinite(value) && value > 0);

    return normalized.length ? normalized : [10, 25, 50, 100];
});

function optionValue(option) {
    if (option === null || option === undefined) return '';
    if (typeof option === 'string' || typeof option === 'number') return option;

    return option.value ?? option.id ?? '';
}

function isActionsDefinition(definition) {
    return definition?.id === 'actions' || definition?.accessorKey === 'actions';
}

function isActionsColumn(column) {
    return isActionsDefinition(column?.columnDef);
}

function allColumnIds() {
    const ids = (Array.isArray(props.columns) ? props.columns : [])
        .map(column => column.id ?? column.accessorKey)
        .filter(Boolean);

    if (props.showRowNumber && !ids.includes('no')) ids.push('no');

    return ids;
}

function isSortableColumnId(id) {
    if (!id || id === 'actions' || id === 'no') return false;

    return (Array.isArray(props.columns) ? props.columns : []).some(column => {
        const columnId = column.id ?? column.accessorKey;

        return columnId === id && column.enableSorting !== false && !isActionsDefinition(column);
    });
}

function sanitizeColumnVisibility(source) {
    const validIds = new Set(allColumnIds());
    const result = {};

    for (const [key, value] of Object.entries(source ?? {})) {
        if (validIds.has(key) && typeof value === 'boolean') result[key] = value;
    }

    return result;
}

function filterValueAllowed(definition, value) {
    if (value === null || value === undefined || value === '') return true;

    const options = Array.isArray(definition?.options) ? definition.options : [];

    if (Array.isArray(value)) {
        return value.every(item => options.some(option => String(optionValue(option)) === String(item)));
    }

    return options.some(option => String(optionValue(option)) === String(value));
}

function sanitizeAppliedFilters(source) {
    const result = {};

    for (const definition of Array.isArray(props.filters) ? props.filters : []) {
        const value = source?.[definition.key];

        if (value === null || value === undefined || value === '') continue;
        if (!filterValueAllowed(definition, value)) continue;

        result[definition.key] = value;
    }

    return result;
}

// Query tabel memakai key generik (q/page/per_page/sort/dir/f_*), sehingga aman
// hanya untuk satu tabel per halaman. Dua tabel dalam satu halaman memerlukan prefix tableKey.
function isTableQueryKey(key) {
    return TABLE_QUERY_KEYS.includes(key) || key.startsWith('f_');
}

function readStoredState() {
    if (typeof window === 'undefined') return null;

    try {
        const rawState = window.sessionStorage.getItem(`${props.tableKey}:state`);

        if (!rawState) return null;

        const parsed = JSON.parse(rawState);

        return parsed && typeof parsed === 'object' ? parsed : null;
    } catch {
        return null;
    }
}

function clearAllTableStates() {
    if (typeof window === 'undefined') return;

    const keys = [];

    for (let index = 0; index < window.sessionStorage.length; index += 1) {
        const key = window.sessionStorage.key(index);

        if (key && key.endsWith(':state')) keys.push(key);
    }

    keys.forEach(key => window.sessionStorage.removeItem(key));
}

function clearTableQueryFromUrl() {
    if (typeof window === 'undefined') return;
    if (window.location.pathname !== mountPath) return;

    const url = new URL(window.location.href);

    for (const key of Array.from(url.searchParams.keys())) {
        if (isTableQueryKey(key)) url.searchParams.delete(key);
    }

    window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
}

function detectMenuGroup() {
    if (typeof window === 'undefined') return 'root';

    try {
        const currentRoute = window.route?.().current?.();

        if (currentRoute) {
            const segments = String(currentRoute).split('.');

            return segments.length > 2 ? segments.slice(0, 2).join('.') : segments[0];
        }
    } catch {
        // Fallback ke path jika Ziggy tidak tersedia.
    }

    const segments = window.location.pathname.split('/').filter(Boolean);

    return segments.slice(0, 2).join('/') || 'root';
}

function resetStateForMenuGroup() {
    if (typeof window === 'undefined') return;

    try {
        const lastMenuGroup = window.sessionStorage.getItem(MENU_GROUP_STORAGE_KEY);

        if (lastMenuGroup !== null && lastMenuGroup !== currentMenuGroup) {
            clearAllTableStates();
            clearTableQueryFromUrl();
        }

        window.sessionStorage.setItem(MENU_GROUP_STORAGE_KEY, currentMenuGroup);
    } catch {
        // sessionStorage bisa diblokir; komponen tetap berjalan tanpa persistence.
    }
}

function currentTableParams() {
    const params = new URLSearchParams();

    if (typeof window === 'undefined') return params;

    const url = new URL(window.location.href);

    for (const [key, value] of url.searchParams.entries()) {
        if (isTableQueryKey(key)) params.append(key, value);
    }

    return params;
}

function canonicalQueryString(params) {
    const clone = new URLSearchParams(params);
    clone.sort();

    return clone.toString();
}

function queryStringFromValues(values) {
    const params = new URLSearchParams();
    const activeFilters = { ...(props.fixedFilters ?? {}), ...(values.filters ?? {}) };
    const activeSort = Array.isArray(values.sorting) ? values.sorting[0] : null;

    if (values.search) params.set('q', values.search);

    for (const [key, value] of Object.entries(activeFilters)) {
        if (value === null || value === undefined || value === '') continue;

        params.set(`f_${key}`, String(value));
    }

    if (values.pageIndex > 0) params.set('page', String(values.pageIndex + 1));

    params.set('per_page', String(values.pageSize));

    if (activeSort) {
        params.set('sort', activeSort.id);
        params.set('dir', activeSort.desc ? 'desc' : 'asc');
    }

    return params.toString();
}

function parseInitialState() {
    const pageSizeOptions = normalizedPageSizeOptions.value;

    if (!persistenceEnabled.value) {
        return {
            search: '',
            filters: sanitizeAppliedFilters(props.initialFilters ?? {}),
            pageIndex: 0,
            pageSize: pageSizeOptions.includes(Number(props.pageSize))
                ? Number(props.pageSize)
                : pageSizeOptions[0],
            sorting: [],
            columnVisibility: {},
            anchorRowId: null,
            scrollTop: 0,
            scrollLeft: 0,
            restoredFromStorage: false,
        };
    }

    const stored = readStoredState();
    const params = typeof window !== 'undefined' ? new URL(window.location.href).searchParams : new URLSearchParams();
    let restoredFromStorage = false;

    let searchValue = '';

    if (params.has('q')) {
        searchValue = params.get('q') ?? '';
    } else if (typeof stored?.search === 'string') {
        searchValue = stored.search;
        restoredFromStorage = true;
    }

    let filtersValue;
    const urlHasFilters = Array.from(params.keys()).some(key => key.startsWith('f_'));

    if (urlHasFilters) {
        const fromUrl = {};

        for (const definition of Array.isArray(props.filters) ? props.filters : []) {
            const rawValue = params.get(`f_${definition.key}`);

            if (rawValue !== null && filterValueAllowed(definition, rawValue)) {
                fromUrl[definition.key] = rawValue;
            }
        }

        filtersValue = fromUrl;
    } else if (stored?.filters && typeof stored.filters === 'object') {
        filtersValue = sanitizeAppliedFilters(stored.filters);
        restoredFromStorage = true;
    } else {
        filtersValue = sanitizeAppliedFilters(props.initialFilters ?? {});
    }

    let pageIndex = 0;

    if (params.has('page')) {
        const parsedPage = Number.parseInt(params.get('page') ?? '', 10);

        if (Number.isInteger(parsedPage) && parsedPage >= 1) pageIndex = parsedPage - 1;
    } else if (Number.isInteger(stored?.page) && stored.page >= 0) {
        pageIndex = stored.page;
        restoredFromStorage = true;
    }

    let pageSizeValue = pageSizeOptions.includes(Number(props.pageSize))
        ? Number(props.pageSize)
        : pageSizeOptions[0];

    if (params.has('per_page')) {
        const parsedPageSize = Number.parseInt(params.get('per_page') ?? '', 10);

        if (pageSizeOptions.includes(parsedPageSize)) pageSizeValue = parsedPageSize;
    } else if (pageSizeOptions.includes(Number(stored?.perPage))) {
        pageSizeValue = Number(stored.perPage);
        restoredFromStorage = true;
    }

    let sortId = null;
    let sortDirection = 'asc';

    if (params.has('sort')) {
        const candidate = params.get('sort');

        if (isSortableColumnId(candidate)) sortId = candidate;

        sortDirection = params.get('dir') === 'desc' ? 'desc' : 'asc';
    } else if (typeof stored?.sort === 'string' && isSortableColumnId(stored.sort)) {
        sortId = stored.sort;
        sortDirection = stored.dir === 'desc' ? 'desc' : 'asc';
        restoredFromStorage = true;
    }

    let columnVisibilityValue = {};

    if (stored?.columnVisibility && typeof stored.columnVisibility === 'object') {
        columnVisibilityValue = sanitizeColumnVisibility(stored.columnVisibility);
    }

    const storedAnchorRowId = typeof stored?.anchorRowId === 'string' && stored.anchorRowId !== ''
        ? stored.anchorRowId
        : null;
    const storedScrollTop = Number(stored?.scrollTop);
    const storedScrollLeft = Number(stored?.scrollLeft);

    return {
        search: searchValue,
        filters: filtersValue,
        pageIndex,
        pageSize: pageSizeValue,
        sorting: sortId ? [{ id: sortId, desc: sortDirection === 'desc' }] : [],
        columnVisibility: columnVisibilityValue,
        anchorRowId: storedAnchorRowId,
        scrollTop: Number.isFinite(storedScrollTop) && storedScrollTop >= 0 ? storedScrollTop : 0,
        scrollLeft: Number.isFinite(storedScrollLeft) && storedScrollLeft >= 0 ? storedScrollLeft : 0,
        restoredFromStorage,
    };
}

resetStateForMenuGroup();

const initialRestore = parseInitialState();
const initialGeneratedParams = new URLSearchParams(queryStringFromValues(initialRestore));
const initialCurrentParams = currentTableParams();
const needsServerSync = persistenceEnabled.value
    && props.serverSide
    && initialRestore.restoredFromStorage
    && canonicalQueryString(initialGeneratedParams) !== canonicalQueryString(initialCurrentParams);

const search = ref(initialRestore.search);
const appliedFilters = ref(initialRestore.filters);
const sorting = ref(initialRestore.sorting);
const columnVisibility = ref(initialRestore.columnVisibility);
const pagination = ref({
    pageIndex: initialRestore.pageIndex,
    pageSize: initialRestore.pageSize,
});
const anchorRowId = ref(initialRestore.anchorRowId);
const activeTabValue = ref(props.activeTab ?? null);

function getByPath(row, key) {
    if (!key) return undefined;
    if (!key.includes('.')) return row?.[key];

    return key.split('.').reduce((value, segment) => value?.[segment], row);
}

function rowMatchesFilters(row) {
    const activeFilters = { ...(props.fixedFilters ?? {}), ...(appliedFilters.value ?? {}) };

    return Object.entries(activeFilters).every(([key, filterValue]) => {
        if (filterValue === null || filterValue === undefined || filterValue === '') return true;

        const rowValue = getByPath(row, key);

        if (Array.isArray(rowValue)) {
            if (Array.isArray(filterValue)) {
                return filterValue.some(value => rowValue.some(item => String(item) === String(value)));
            }

            return rowValue.some(item => String(item) === String(filterValue));
        }

        if (Array.isArray(filterValue)) {
            return filterValue.some(value => String(rowValue) === String(value));
        }

        return String(rowValue) === String(filterValue);
    });
}

const tableData = computed(() => {
    const rows = Array.isArray(props.data) ? props.data : [];

    return props.serverSide ? rows : rows.filter(row => rowMatchesFilters(row));
});

const serverRowCount = computed(() => Math.max(0, Number(props.serverTotalRows) || 0));

function rowNumberFor(row) {
    const pageRows = table.getRowModel().rows;
    const pageRowIndex = pageRows.findIndex(candidate => candidate.id === row.id);
    const localIndex = pageRowIndex >= 0 ? pageRowIndex : row.index;

    return props.serverSide
        ? pagination.value.pageIndex * pagination.value.pageSize + localIndex + 1
        : localIndex + 1;
}

const tableColumns = computed(() => {
    const columns = (Array.isArray(props.columns) ? props.columns : []).map(column => {
        const definition = { ...column };

        if (isActionsDefinition(definition)) definition.enableSorting = false;

        return definition;
    });

    const hasNumberColumn = columns.some(column => column.id === 'no' || column.accessorKey === 'no');

    if (props.showRowNumber && !hasNumberColumn) {
        columns.unshift({
            id: 'no',
            header: 'No.',
            enableSorting: false,
            meta: { align: 'center', isRowNumber: true },
            cell: info => rowNumberFor(info.row),
        });
    }

    return columns;
});

const table = useTable({
    key: props.tableKey,
    columns: tableColumns,
    data: tableData,
    getRowId: (originalRow, index) => String(originalRow?.id ?? originalRow?.uuid ?? index),
    autoResetPageIndex: false,
    manualPagination: props.serverSide,
    manualSorting: props.serverSide,
    manualFiltering: props.serverSide,
    ...(props.serverSide ? { rowCount: serverRowCount } : {}),
    globalFilterFn: 'includesString',
    state: {
        get sorting() {
            return sorting.value;
        },
        get pagination() {
            return pagination.value;
        },
        get columnVisibility() {
            return columnVisibility.value;
        },
        get globalFilter() {
            return search.value;
        },
    },
    onSortingChange: updater => {
        sorting.value = typeof updater === 'function' ? updater(sorting.value) : updater;
    },
    onPaginationChange: updater => {
        pagination.value = typeof updater === 'function' ? updater(pagination.value) : updater;
    },
    onColumnVisibilityChange: updater => {
        columnVisibility.value = typeof updater === 'function' ? updater(columnVisibility.value) : updater;
    },
    onGlobalFilterChange: updater => {
        search.value = typeof updater === 'function' ? updater(search.value) : updater;
    },
});

const rows = computed(() => table.getRowModel().rows);
const visibleColumns = computed(() => table.getVisibleLeafColumns());
const allLeafColumns = computed(() => table.getAllLeafColumns());
const flatHeaders = computed(() => table.getFlatHeaders());
const pageCount = computed(() => table.getPageCount());
const pageIndex = computed(() => pagination.value.pageIndex);
const isTableLoading = computed(() => Boolean(props.loading) || serverTableLoading.value);
const empty = computed(() => !isTableLoading.value && rows.value.length === 0);
const hasVisibleColumns = computed(() => visibleColumns.value.length > 0);
const hasGroupedHeader = computed(() => visibleColumns.value.some(column => Boolean(column.columnDef.meta?.group)));

const headerSegments = computed(() => {
    const segments = [];

    for (const column of visibleColumns.value) {
        const group = column.columnDef.meta?.group || null;

        if (!group) {
            segments.push({ type: 'column', column });
            continue;
        }

        const lastSegment = segments[segments.length - 1];

        if (lastSegment?.type === 'group' && lastSegment.label === group) {
            lastSegment.columns.push(column);
        } else {
            segments.push({ type: 'group', label: group, columns: [column] });
        }
    }

    return segments;
});

const groupedLeafColumns = computed(() =>
    headerSegments.value
        .filter(segment => segment.type === 'group')
        .flatMap(segment => segment.columns),
);

const hasTabs = computed(() => Array.isArray(props.tabs) && props.tabs.length > 0);
const hasMobileSlot = computed(() => Boolean(slots.mobile));
const reloadOnly = computed(() => (Array.isArray(props.reloadOnly) ? props.reloadOnly : []));

const totalRows = computed(() => (props.serverSide
    ? serverRowCount.value
    : table.getFilteredRowModel().rows.length));

const pageStart = computed(() => {
    if (totalRows.value === 0) return 0;

    return pagination.value.pageIndex * pagination.value.pageSize + 1;
});

const pageEnd = computed(() => {
    if (totalRows.value === 0) return 0;

    return Math.min(
        pageStart.value + Math.max(rows.value.length - 1, 0),
        totalRows.value,
    );
});

const pageSizeSelectOptions = computed(() =>
    normalizedPageSizeOptions.value.map(value => ({ value, label: String(value) })),
);

const sortableColumns = computed(() =>
    visibleColumns.value.filter(column => column.getCanSort() && !isActionsColumn(column)),
);

const allColumnsVisible = computed(() =>
    allLeafColumns.value.length > 0 && allLeafColumns.value.every(column => column.getIsVisible()),
);

const visibleFilters = computed(() =>
    (Array.isArray(props.filters) ? props.filters : []).filter(definition => !definition.hidden),
);

const filterBadgeCount = computed(() =>
    visibleFilters.value.filter(definition => {
        const value = appliedFilters.value?.[definition.key];

        return value !== null && value !== undefined && value !== '';
    }).length,
);

const showTitleRow = computed(() =>
    Boolean(props.title) || Boolean(slots.actions) || (props.searchInTitle && props.searchable),
);

const showTabsRow = computed(() => hasTabs.value || Boolean(slots['tabs-actions']));

const showToolbar = computed(() =>
    (props.searchable && !props.searchInTitle)
    || props.columnToggle
    || visibleFilters.value.length > 0
    || Boolean(slots['toolbar-start']),
);

const showDesktopHeader = computed(() =>
    Boolean(props.title)
    || props.searchable
    || visibleFilters.value.length > 0
    || Boolean(slots.actions)
    || showTabsRow.value
    || showToolbar.value,
);

const showFooter = computed(() => Boolean(props.showPagination) && !empty.value);

const toolbarStartClass = computed(() =>
    props.toolbarStartAlign === 'right' ? 'md:ml-auto' : '',
);

const columnToggleClass = computed(() => {
    if (!slots['toolbar-start']) return 'md:ml-auto';
    if (props.toolbarStartAlign === 'left') return 'md:ml-auto';

    return '';
});

const filterButtonClass = computed(() =>
    props.columnToggle ? 'ml-auto md:ml-0' : 'ml-auto',
);

const toolbarSearchClass = computed(() =>
    !props.columnToggle && visibleFilters.value.length === 0 ? 'md:ml-auto' : '',
);

const cardClasses = computed(() => {
    if (props.variant === 'activity') {
        const hasHeaderMenus = props.columnToggle || visibleFilters.value.length > 0;

        return hasHeaderMenus
            ? 'relative overflow-visible rounded-[16px]! border-surface-container-high! shadow-standard!'
            : 'relative overflow-hidden rounded-[16px]! border-surface-container-high! shadow-standard!';
    }

    return 'relative overflow-visible border-surface-container-high! shadow-soft!';
});

const cardHeaderClass = computed(() => (props.variant === 'activity'
    ? 'bg-primary-container text-on-primary-container'
    : 'bg-surface-white text-text-heading'));

const tableHeadClass = computed(() => (props.variant === 'activity'
    ? 'bg-primary-container text-on-primary-container'
    : 'bg-surface-container-low text-text-muted'));

const headerBackgroundClass = computed(() => (props.variant === 'activity'
    ? 'bg-primary-container'
    : 'bg-surface-container-low'));

const footerSurfaceClass = computed(() => (props.variant === 'activity'
    ? 'bg-primary-container text-on-primary-container'
    : 'bg-surface-container-low text-text-body'));

const tableMaxHeightValue = computed(() => {
    if (props.tableMaxHeight) return props.tableMaxHeight;

    const visibleRows = Math.max(Number(props.tableVisibleRows) || 1, 1);

    if (props.pageScrollAtVisibleLimit && pagination.value.pageSize <= visibleRows) {
        return 'none';
    }

    return `calc(max(${visibleRows}, 1) * 3.5rem + 4rem)`;
});

const scrollContainerStyle = computed(() => ({
    maxHeight: tableMaxHeightValue.value === 'none' ? 'none' : tableMaxHeightValue.value,
    overflowX: 'auto',
    overflowY: tableMaxHeightValue.value === 'none' ? 'visible' : 'auto',
}));

const isHorizontallyScrollable = computed(() => canScrollLeft.value || canScrollRight.value);

const toolbarButtonClass = 'inline-flex h-9 shrink-0 items-center justify-center gap-2 rounded-full border border-surface-container-high bg-surface-white px-4 text-body-md text-text-body transition-colors hover:bg-surface-container-low focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary';

const searchInputClass = 'h-9 w-full rounded-full border border-surface-container-high bg-surface-container-lowest pl-9 pr-4 text-body-md text-text-body focus:border-secondary focus:outline-none';

function alignClass(column) {
    const alignment = column?.columnDef?.meta?.align ?? 'left';

    return {
        left: 'text-left',
        center: 'text-center',
        right: 'text-right',
    }[alignment] ?? 'text-left';
}

function justifyClass(column) {
    if (isActionsColumn(column)) return 'justify-center';

    const alignment = column?.columnDef?.meta?.align ?? 'left';

    return {
        left: 'justify-start',
        center: 'justify-center',
        right: 'justify-end',
    }[alignment] ?? 'justify-start';
}

function headerText(column) {
    const definition = column?.columnDef ?? {};

    if (column?.id === 'no') return 'No.';
    if (column?.id === 'actions') return 'Aksi';
    if (typeof definition.header === 'string') return definition.header;
    if (typeof definition.header === 'number') return String(definition.header);

    return definition.meta?.headerLabel ?? definition.id ?? column?.id ?? '';
}

function headerOf(column) {
    return flatHeaders.value.find(header => header.column.id === column.id) ?? null;
}

function skeletonMinWidth(column) {
    if (column?.columnDef?.minWidth) return `${column.columnDef.minWidth}px`;

    const label = headerText(column).toLowerCase();

    if (/(nama|name|judul|title|deskripsi|description|visi|alamat)/.test(label)) return '160px';
    if (/(kode|code|sks|semester|tahun|nilai|status|jenjang)/.test(label)) return '90px';

    return '120px';
}

function sortIcon(column) {
    const sorted = column.getIsSorted();

    if (sorted === 'asc') return 'arrow_upward';
    if (sorted === 'desc') return 'arrow_downward';

    return 'unfold_more';
}

function sortIconClass(column) {
    if (column.getIsSorted()) {
        return props.variant === 'activity' ? 'text-on-primary-container' : 'text-secondary';
    }

    return props.variant === 'activity'
        ? 'text-on-primary-container opacity-60'
        : 'text-text-muted';
}

function headerCellClasses(column, subHeader = false) {
    const actionColumn = isActionsColumn(column);
    const classes = [
        'whitespace-nowrap border-b border-surface-container-high',
        subHeader ? 'sticky top-[47px] z-10' : 'sticky top-0 z-10',
        headerBackgroundClass.value,
    ];

    if (actionColumn) {
        classes.push(
            'w-px min-w-25 px-3 py-3 text-center',
            props.variant === 'activity'
                ? 'text-[11px] font-bold uppercase tracking-wider'
                : 'text-label-md font-semibold uppercase tracking-wider',
        );
    } else {
        classes.push(
            alignClass(column),
            props.variant === 'activity'
                ? 'px-4 py-3.5 text-[11px] font-bold uppercase tracking-wider'
                : 'px-6 py-4 text-label-md font-semibold uppercase tracking-wider',
        );
    }

    if (actionColumn && isHorizontallyScrollable.value) {
        classes.push('sticky right-0 z-20 border-l border-surface-container-high');
    }

    return classes;
}

function bodyCellClasses(column) {
    const actionColumn = isActionsColumn(column);
    const classes = [
        'whitespace-nowrap text-body-md text-text-heading',
        actionColumn
            ? 'px-3 py-3 text-center align-middle'
            : (props.variant === 'activity' ? 'px-4 py-3.5' : 'px-4 py-4'),
        actionColumn ? '' : alignClass(column),
        column?.columnDef?.meta?.cellClass ?? '',
    ];

    if (actionColumn) {
        classes.push(
            'w-px min-w-25 bg-surface-white',
            props.variant === 'activity'
                ? 'group-hover:bg-pastel-blue'
                : 'group-hover:bg-surface-container-low',
        );

        if (isHorizontallyScrollable.value) {
            classes.push('sticky right-0 z-10 border-l border-surface-container-high action-column-shadow');
        }
    }

    return classes;
}

function rowClass(row, rowIndex) {
    return [
        'group transition-colors duration-150',
        props.rowClick ? 'cursor-pointer' : '',
        props.variant === 'activity' ? 'hover:bg-pastel-blue' : 'hover:bg-surface-container-low',
        rowHighlighted(row) ? 'app-table-anchor-highlight' : '',
        draggedIndex.value === rowIndex ? 'opacity-50' : '',
        dragOverIndex.value === rowIndex ? 'bg-pastel-blue' : '',
    ];
}

function rowIdOf(row) {
    const original = row?.original ?? {};

    return String(original.id ?? original.uuid ?? row?.id ?? '');
}

function rowHighlighted(row) {
    return highlightedRowIds.value.has(rowIdOf(row));
}

function printTable() {
    window.print();
}

function isEllipsisItem(item) {
    return typeof item === 'string';
}

function pageItems(currentPage, totalPages) {
    if (totalPages <= 0) return [];
    if (totalPages <= 7) return Array.from({ length: totalPages }, (_, index) => index + 1);

    const items = [1];
    const start = Math.max(2, currentPage - 1);
    const end = Math.min(totalPages - 1, currentPage + 1);

    if (start > 2) items.push('ellipsis-start');

    for (let page = start; page <= end; page += 1) items.push(page);

    if (end < totalPages - 1) items.push('ellipsis-end');

    items.push(totalPages);

    return items;
}

function setPageIndex(nextPageIndex) {
    if (nextPageIndex === pagination.value.pageIndex) return;

    pagination.value = { ...pagination.value, pageIndex: nextPageIndex };
}

function setPageSize(value) {
    const nextPageSize = Number(value);

    if (!normalizedPageSizeOptions.value.includes(nextPageSize)) return;
    if (nextPageSize === pagination.value.pageSize) return;

    pagination.value = { pageIndex: 0, pageSize: nextPageSize };
}

function resetPageWithoutVisit() {
    if (pagination.value.pageIndex === 0) return;

    suppressNextPaginationVisit = true;
    pagination.value = { ...pagination.value, pageIndex: 0 };
}

function toggleColumn(column) {
    if (!column) return;

    const visible = table.getVisibleLeafColumns();

    if (column.getIsVisible() && visible.length <= 1) return;
    if (typeof column.getCanHide === 'function' && !column.getCanHide()) return;

    column.toggleVisibility(!column.getIsVisible());
}

function toggleAllColumns() {
    const columns = allLeafColumns.value;

    if (!columns.length) return;

    const hideableColumns = columns.filter(column =>
        typeof column.getCanHide !== 'function' || column.getCanHide(),
    );

    if (allColumnsVisible.value) {
        const keeper = hideableColumns.find(column => column.getIsVisible())
            ?? columns.find(column => column.getIsVisible())
            ?? columns[0];

        columns.forEach(column => {
            if (typeof column.getCanHide === 'function' && !column.getCanHide()) return;

            column.toggleVisibility(column.id === keeper?.id);
        });
    } else {
        hideableColumns.forEach(column => column.toggleVisibility(true));
    }
}

function canHideColumn(column) {
    if (typeof column.getCanHide === 'function' && !column.getCanHide()) return false;

    const visible = table.getVisibleLeafColumns();

    return !(column.getIsVisible() && visible.length <= 1);
}

function updateFilterDraft(key, value) {
    filterDraft.value = { ...filterDraft.value, [key]: value };
}

function toggleFilterMenu() {
    filterMenuOpen.value = !filterMenuOpen.value;
    columnMenuOpen.value = false;

    if (filterMenuOpen.value) filterDraft.value = { ...appliedFilters.value };
}

function applyFilters() {
    const nextFilters = sanitizeAppliedFilters(filterDraft.value);

    filterMenuOpen.value = false;
    appliedFilters.value = nextFilters;
    emit('filter-change', { ...nextFilters });
}

function resetFilters() {
    const resetValue = sanitizeAppliedFilters(props.initialFilters ?? {});

    filterDraft.value = { ...resetValue };
    appliedFilters.value = resetValue;
    filterMenuOpen.value = false;
    emit('filters-reset');
}

function toggleColumnMenu() {
    columnMenuOpen.value = !columnMenuOpen.value;
    filterMenuOpen.value = false;
}

function onDocumentPointerDown(event) {
    if (event.target instanceof Element && event.target.closest('.v-popper__popper')) return;

    if (columnMenuOpen.value && !columnMenuRef.value?.contains(event.target)) {
        columnMenuOpen.value = false;
    }

    if (filterMenuOpen.value && !filterMenuRef.value?.contains(event.target)) {
        filterMenuOpen.value = false;
    }
}

function onDocumentKeydown(event) {
    if (event.key !== 'Escape') return;

    columnMenuOpen.value = false;
    filterMenuOpen.value = false;
}

function onDragStart(rowIndex, event) {
    if (!props.draggable) return;

    draggedIndex.value = rowIndex;

    if (event?.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(rowIndex));
    }
}

function onDragOver(rowIndex, event) {
    if (!props.draggable || draggedIndex.value === null) return;

    event?.preventDefault?.();
    dragOverIndex.value = rowIndex;
}

function onDrop(rowIndex) {
    if (!props.draggable || draggedIndex.value === null) return;

    const from = draggedIndex.value;
    const to = rowIndex;

    draggedIndex.value = null;
    dragOverIndex.value = null;

    if (from !== to) emit('row-drop', { from, to });
}

function onDragEnd() {
    draggedIndex.value = null;
    dragOverIndex.value = null;
}

function toggleMobileSort(column, hide) {
    const activeSort = sorting.value[0];

    if (activeSort?.id === column.id) {
        sorting.value = [{ id: column.id, desc: !activeSort.desc }];
    } else {
        sorting.value = [{ id: column.id, desc: false }];
    }

    hide?.();
}

function captureRowAction(row, event) {
    const target = event?.target;

    if (!(target instanceof Element)) return;
    if (!target.closest('button, a, [role="button"]')) return;

    anchorRowId.value = rowIdOf(row);
    persistTableState();
}

function handleRowClick(row, event) {
    if (!props.rowClick) return;

    const target = event?.target;

    if (target instanceof Element && target.closest('button, a, [role="button"]')) return;

    props.rowClick(row?.original ?? row);
}

function buildStateValues() {
    return {
        search: search.value,
        filters: appliedFilters.value,
        pageIndex: pagination.value.pageIndex,
        pageSize: pagination.value.pageSize,
        sorting: sorting.value,
    };
}

function buildState() {
    const activeSort = sorting.value[0];
    const container = scrollContainer.value;

    return {
        filters: { ...appliedFilters.value },
        search: search.value,
        page: pagination.value.pageIndex,
        perPage: pagination.value.pageSize,
        sort: activeSort?.id ?? null,
        dir: activeSort ? (activeSort.desc ? 'desc' : 'asc') : null,
        menuGroup: currentMenuGroup,
        columnVisibility: { ...columnVisibility.value },
        anchorRowId: anchorRowId.value,
        scrollTop: container?.scrollTop ?? initialRestore.scrollTop,
        scrollLeft: container?.scrollLeft ?? initialRestore.scrollLeft,
        query: queryStringFromValues(buildStateValues()),
    };
}

function persistTableState() {
    if (!persistenceEnabled.value) return;
    if (typeof window === 'undefined') return;

    try {
        window.sessionStorage.setItem(`${props.tableKey}:state`, JSON.stringify(buildState()));
    } catch {
        // sessionStorage bisa penuh atau diblokir; state persistence bersifat opsional.
    }
}

function schedulePersist(immediate = false) {
    if (!persistenceEnabled.value) return;
    if (typeof window === 'undefined') return;

    window.clearTimeout(persistTimer);

    if (immediate) {
        persistTimer = null;
        persistTableState();

        return;
    }

    persistTimer = window.setTimeout(() => {
        persistTimer = null;
        persistTableState();
    }, PERSIST_DELAY);
}

function applyStateToParams(params) {
    for (const key of Array.from(params.keys())) {
        if (isTableQueryKey(key)) params.delete(key);
    }

    const generated = new URLSearchParams(queryStringFromValues(buildStateValues()));

    generated.forEach((value, key) => params.append(key, value));
}

function syncUrl() {
    if (!persistenceEnabled.value) return;
    if (typeof window === 'undefined') return;
    if (window.location.pathname !== mountPath) return;
    if (navigationInProgress.value) return;
    if (isRestoring.value) return;

    const url = new URL(window.location.href);
    applyStateToParams(url.searchParams);
    window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
}

function buildServerUrl() {
    const url = new URL(window.location.href);
    applyStateToParams(url.searchParams);

    return `${url.pathname}${url.search}${url.hash}`;
}

function triggerServerVisit() {
    if (!props.serverSide || reloadOnly.value.length === 0) return;
    if (isRestoring.value) {
        pendingRestore.value = true;

        return;
    }

    const url = buildServerUrl();

    if (serverVisitPending.value && url === lastServerUrl) return;

    lastServerUrl = url;
    serverVisitPending.value = true;
    syncUrl();

    router.visit(url, {
        only: [...reloadOnly.value],
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onFinish: () => {
            serverVisitPending.value = false;
        },
    });
}

function scheduleServerVisit(delay = 0) {
    if (!props.serverSide || reloadOnly.value.length === 0) return;

    window.clearTimeout(serverVisitTimer);

    if (isRestoring.value) {
        pendingRestore.value = true;
        restoreInteractionPending.value = true;

        return;
    }

    if (delay > 0) {
        serverVisitTimer = window.setTimeout(() => {
            serverVisitTimer = null;
            triggerServerVisit();
        }, delay);

        return;
    }

    triggerServerVisit();
}

function guardPage() {
    if (isRestoring.value) return;

    const totalPages = pageCount.value;
    const currentPageIndex = pagination.value.pageIndex;

    if (totalPages <= 0) {
        if (currentPageIndex !== 0) pagination.value = { ...pagination.value, pageIndex: 0 };

        return;
    }

    if (currentPageIndex >= totalPages) {
        pagination.value = { ...pagination.value, pageIndex: totalPages - 1 };
    }
}

function updateScrollMetrics() {
    const container = scrollContainer.value;

    if (!container) return;

    canScrollLeft.value = container.scrollLeft > 8;
    canScrollRight.value = container.scrollLeft + container.clientWidth < container.scrollWidth - 8;

    const header = container.querySelector('thead');
    const headerHeight = header?.getBoundingClientRect().height ?? 0;
    const dataViewportHeight = Math.max(0, container.clientHeight - headerHeight);

    scrollArrowTop.value = headerHeight + dataViewportHeight / 2;

    const actionCell = container.querySelector('[data-action-column]');

    actionColumnWidth.value = actionCell?.getBoundingClientRect().width ?? 0;
}

function scheduleScrollMetrics() {
    if (typeof window === 'undefined') return;
    if (scrollRaf !== null) return;

    scrollRaf = window.requestAnimationFrame(() => {
        scrollRaf = null;
        updateScrollMetrics();
    });
}

function onTableScroll() {
    scheduleScrollMetrics();
    schedulePersist(false);
}

function scrollHorizontally(direction) {
    const container = scrollContainer.value;

    if (!container) return;

    const amount = Math.max(240, container.clientWidth * 0.75);

    container.scrollBy({ left: direction * amount, behavior: 'smooth' });
}

function setupResizeObserver() {
    resizeObserver?.disconnect();

    if (typeof ResizeObserver === 'undefined') return;

    resizeObserver = new ResizeObserver(scheduleScrollMetrics);

    if (scrollContainer.value) resizeObserver.observe(scrollContainer.value);
    if (tableRef.value) resizeObserver.observe(tableRef.value);
}

function isTableHidden() {
    const element = rootRef.value;

    if (!element) return true;

    return element.getClientRects().length === 0;
}

function applyHighlightPayload(payload, isProp = false) {
    if (!payload || typeof payload !== 'object') return;
    if (payload.tableKey !== props.tableKey) return;
    if (!Array.isArray(payload.rowIds) || payload.rowIds.length === 0) return;
    if (!['created', 'updated', 'imported'].includes(payload.action)) return;

    if (!componentMounted.value) {
        pendingHighlightPayload.value = { payload, isProp };

        return;
    }

    if (isTableHidden()) return;

    const rowIds = payload.rowIds.map(String).filter(Boolean);

    if (rowIds.length === 0) return;

    if (!isProp) {
        if (typeof payload.token !== 'string' || payload.token === '') return;
        if (consumedHighlightTokens.has(payload.token)) return;

        consumedHighlightTokens.add(payload.token);
    }

    const targetPage = Number(payload.page);

    if (Number.isInteger(targetPage) && targetPage > 0) {
        setPageIndex(targetPage - 1);
    }

    window.clearTimeout(highlightClearTimer);
    window.clearTimeout(highlightRetryTimer);

    highlightedRowIds.value = new Set(rowIds);
    attemptHighlightScroll(rowIds, 0);

    highlightClearTimer = window.setTimeout(() => {
        highlightedRowIds.value = new Set();
    }, HIGHLIGHT_DURATION);
}

function attemptHighlightScroll(rowIds, attempt) {
    const container = scrollContainer.value;

    if (!container) {
        retryHighlightScroll(rowIds, attempt);

        return;
    }

    for (const rowId of rowIds) {
        const escapedId = window.CSS?.escape
            ? window.CSS.escape(rowId)
            : rowId.replace(/["\\]/g, '\\$&');
        const rowElement = container.querySelector(`[data-row-id="${escapedId}"]`);

        if (rowElement) {
            scrollRowToCenter(rowElement, container);
            scheduleScrollMetrics();

            return;
        }
    }

    retryHighlightScroll(rowIds, attempt);
}

function retryHighlightScroll(rowIds, attempt) {
    if (attempt >= HIGHLIGHT_RETRY_LIMIT) return;

    highlightRetryTimer = window.setTimeout(() => {
        attemptHighlightScroll(rowIds, attempt + 1);
    }, HIGHLIGHT_RETRY_DELAY);
}

function scrollRowToCenter(rowElement, container) {
    const rowRect = rowElement.getBoundingClientRect();
    const containerRect = container.getBoundingClientRect();
    const delta = rowRect.top - containerRect.top - container.clientHeight / 2 + rowRect.height / 2;

    container.scrollTop += delta;
}

function restorePosition() {
    const container = scrollContainer.value;

    if (!container) return;

    if (initialRestore.anchorRowId) {
        restoreAnchor(initialRestore.anchorRowId, 0);
        container.scrollLeft = initialRestore.scrollLeft;

        return;
    }

    container.scrollTop = initialRestore.scrollTop;
    container.scrollLeft = initialRestore.scrollLeft;
}

function restoreAnchor(rowId, attempt) {
    const container = scrollContainer.value;

    if (!container) return;

    const escapedId = window.CSS?.escape
        ? window.CSS.escape(String(rowId))
        : String(rowId).replace(/["\\]/g, '\\$&');
    const rowElement = container.querySelector(`[data-row-id="${escapedId}"]`);

    if (rowElement) {
        scrollRowToCenter(rowElement, container);
        scheduleScrollMetrics();

        return;
    }

    if (attempt >= HIGHLIGHT_RETRY_LIMIT) {
        container.scrollTop = initialRestore.scrollTop;
        container.scrollLeft = initialRestore.scrollLeft;

        return;
    }

    restoreRetryTimer = window.setTimeout(() => {
        restoreAnchor(rowId, attempt + 1);
    }, HIGHLIGHT_RETRY_DELAY);
}

function applyPendingHighlight() {
    const pending = pendingHighlightPayload.value;

    if (!pending) return;

    pendingHighlightPayload.value = null;
    applyHighlightPayload(pending.payload, pending.isProp);
}

function registerRouterListeners() {
    routerUnsubscribers.push(
        router.on('start', () => {
            if (props.serverSide) {
                serverVisitActive += 1;
                serverTableLoading.value = true;
            }

            if (!serverVisitPending.value) navigationInProgress.value = true;
        }),
    );

    routerUnsubscribers.push(
        router.on('finish', () => {
            if (props.serverSide) {
                serverVisitActive = Math.max(0, serverVisitActive - 1);

                if (serverVisitActive === 0) serverTableLoading.value = false;
            }

            navigationInProgress.value = false;
            serverVisitPending.value = false;
        }),
    );

    routerUnsubscribers.push(
        router.on('flash', event => {
            const flash = event?.detail?.flash ?? event?.flash;
            const payload = flash?.highlight ?? flash;

            if (payload && typeof payload === 'object') applyHighlightPayload(payload);
        }),
    );
}

watch(search, () => {
    resetPageWithoutVisit();
    schedulePersist(false);
    syncUrl();

    if (isRestoring.value) restoreInteractionPending.value = true;
    if (props.serverSide) scheduleServerVisit(SEARCH_DELAY);
});

watch(appliedFilters, () => {
    resetPageWithoutVisit();
    schedulePersist(false);
    syncUrl();

    if (isRestoring.value) restoreInteractionPending.value = true;
    if (props.serverSide) scheduleServerVisit();
}, { deep: true });

watch(sorting, () => {
    resetPageWithoutVisit();
    schedulePersist(true);
    syncUrl();

    if (isRestoring.value) restoreInteractionPending.value = true;
    if (props.serverSide) scheduleServerVisit();
}, { deep: true });

watch(pagination, () => {
    const wasSuppressed = suppressNextPaginationVisit;

    suppressNextPaginationVisit = false;
    schedulePersist(true);
    syncUrl();

    if (!isRestoring.value) {
        scrollContainer.value?.scrollTo({ top: 0 });
        scheduleScrollMetrics();
    }

    if (!wasSuppressed && props.serverSide) scheduleServerVisit();
}, { deep: true });

watch(columnVisibility, () => {
    schedulePersist(true);
    scheduleScrollMetrics();
}, { deep: true });

watch(() => props.fixedFilters, () => {
    resetPageWithoutVisit();
    schedulePersist(false);
    syncUrl();

    if (props.serverSide) scheduleServerVisit();
}, { deep: true });

watch(() => Number(props.pageSize), value => {
    if (isRestoring.value) return;
    if (!normalizedPageSizeOptions.value.includes(value)) return;
    if (pagination.value.pageSize === value) return;

    setPageSize(value);
});

watch([pageIndex, pageCount], guardPage);

watch(() => rows.value.length, scheduleScrollMetrics);
watch(visibleColumns, scheduleScrollMetrics);
watch(() => pagination.value.pageSize, scheduleScrollMetrics);
watch([hasVisibleColumns, hasGroupedHeader], async () => {
    await nextTick();
    setupResizeObserver();
    scheduleScrollMetrics();
});

watch(() => props.data, () => {
    scheduleScrollMetrics();
    attemptPendingHighlightRetry();
}, { deep: false });

watch(() => props.serverTotalRows, () => {
    guardPage();
    scheduleScrollMetrics();
});

watch(() => props.activeTab, value => {
    activeTabValue.value = value;
});

watch(() => [props.highlightIds, props.highlightPage], () => {
    if (!Array.isArray(props.highlightIds) || props.highlightIds.length === 0) return;

    const rowIds = props.highlightIds.map(String).filter(Boolean);

    if (rowIds.length === 0) return;

    const token = `prop:${props.tableKey}:${props.highlightPage ?? ''}:${rowIds.join(',')}`;

    if (consumedHighlightTokens.has(token)) return;

    consumedHighlightTokens.add(token);
    applyHighlightPayload({
        tableKey: props.tableKey,
        rowIds,
        action: 'updated',
        token,
        page: props.highlightPage,
    }, true);
}, { deep: true, immediate: true });

watch(() => inertiaPage.props?.flash?.highlight ?? null, payload => {
    if (payload) applyHighlightPayload(payload);
}, { deep: true, immediate: true });

watch(() => inertiaPage.flash?.highlight ?? null, payload => {
    if (payload) applyHighlightPayload(payload);
}, { deep: true, immediate: true });

function attemptPendingHighlightRetry() {
    if (highlightedRowIds.value.size === 0) return;

    window.clearTimeout(highlightRetryTimer);
    attemptHighlightScroll([...highlightedRowIds.value], 0);
}

function handleTabChange(value) {
    activeTabValue.value = value;
    emit('update:activeTab', value);
}

registerRouterListeners();

onMounted(async () => {
    updateScrollMetrics();
    setupResizeObserver();

    document.addEventListener('mousedown', onDocumentPointerDown);
    document.addEventListener('keydown', onDocumentKeydown);
    window.addEventListener('resize', scheduleScrollMetrics);

    await nextTick();

    componentMounted.value = true;
    restorePosition();
    applyPendingHighlight();

    isRestoring.value = false;

    await nextTick();

    syncUrl();

    if (pendingRestore.value || restoreInteractionPending.value || needsServerSync) {
        scheduleServerVisit();
    }

    scheduleScrollMetrics();
});

onBeforeUnmount(() => {
    window.clearTimeout(serverVisitTimer);
    window.clearTimeout(persistTimer);
    window.clearTimeout(highlightClearTimer);
    window.clearTimeout(highlightRetryTimer);
    window.clearTimeout(restoreRetryTimer);

    if (scrollRaf !== null) window.cancelAnimationFrame(scrollRaf);

    resizeObserver?.disconnect();
    resizeObserver = null;

    document.removeEventListener('mousedown', onDocumentPointerDown);
    document.removeEventListener('keydown', onDocumentKeydown);
    window.removeEventListener('resize', scheduleScrollMetrics);

    routerUnsubscribers.forEach(unsubscribe => unsubscribe?.());

    if (persistenceEnabled.value && !navigationInProgress.value) {
        persistTableState();
        syncUrl();
    }
});
</script>

<template>
    <div ref="rootRef" class="relative">
        <BaseCard :padding="false" :class="cardClasses">
            <div v-if="hasMobileSlot" class="overflow-hidden rounded-2xl md:hidden">
                <div class="border-b border-surface-container-high p-4" :class="cardHeaderClass">
                    <div v-if="!props.mobileSearchOnly" class="flex items-center justify-between gap-3">
                        <h3 v-if="props.title" class="min-w-0 flex-1 truncate text-title-lg font-semibold">
                            {{ props.title }}
                        </h3>
                        <div class="ml-auto flex shrink-0 items-center gap-2">
                            <slot name="mobile-actions" />

                            <AppDropdown
                                placement="bottom-end"
                                strategy="fixed"
                                popper-class="min-w-56 rounded-xl border border-surface-container-high bg-surface-white p-1 shadow-soft"
                            >
                                <button
                                    type="button"
                                    class="flex h-10 w-10 items-center justify-center rounded-xl border border-surface-container-high bg-surface-white text-text-body transition-colors hover:bg-surface-container-low focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
                                    aria-label="Urutkan data"
                                    aria-haspopup="menu"
                                >
                                    <AppIcon name="sort" :size="18" />
                                </button>

                                <template #items="{ hide }">
                                    <div role="menu" aria-label="Urutkan data" class="max-h-64 overflow-y-auto modal-scrollbar">
                                        <button
                                            v-for="column in sortableColumns"
                                            :key="column.id"
                                            type="button"
                                            role="menuitemradio"
                                            :aria-checked="column.getIsSorted() !== false"
                                            class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-body-md transition-colors hover:bg-surface-container-low"
                                            :class="column.getIsSorted() ? 'font-semibold text-text-heading' : 'text-text-body'"
                                            @click="toggleMobileSort(column, hide)"
                                        >
                                            <span class="min-w-0 flex-1 truncate">{{ headerText(column) }}</span>
                                            <AppIcon
                                                :name="sortIcon(column)"
                                                :size="14"
                                                :class="sortIconClass(column)"
                                            />
                                        </button>
                                        <p v-if="sortableColumns.length === 0" class="px-3 py-4 text-center text-body-md text-text-muted">
                                            Tidak ada kolom yang bisa diurutkan.
                                        </p>
                                    </div>
                                </template>
                            </AppDropdown>

                            <button
                                v-if="props.mobilePrint"
                                type="button"
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-surface-container-high bg-surface-white text-text-body transition-colors hover:bg-surface-container-low focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
                                aria-label="Cetak data"
                                @click="printTable"
                            >
                                <AppIcon name="print" :size="18" />
                            </button>
                        </div>
                    </div>

                    <div v-if="!props.hideMobileSearch" class="mt-3 flex items-center gap-2">
                        <div class="relative min-w-0 flex-1">
                            <AppIcon
                                name="search"
                                :size="16"
                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-text-muted"
                            />
                            <input
                                v-model="search"
                                type="search"
                                :placeholder="props.mobileSearchPlaceholder ?? props.searchPlaceholder"
                                class="h-10 w-full rounded-xl border border-surface-container-high bg-surface-container-lowest py-0 pl-9 pr-3 text-body-md text-text-body focus:border-secondary focus:outline-none"
                            />
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <div v-if="isTableLoading" class="space-y-3" aria-busy="true" aria-label="Memuat data">
                        <div
                            v-for="skeletonRow in 4"
                            :key="`mobile-skeleton-${skeletonRow}`"
                            class="h-[76px] animate-pulse rounded-xl bg-surface-container-high"
                        ></div>
                    </div>

                    <div
                        v-else-if="rows.length === 0"
                        class="flex min-h-48 flex-col items-center justify-center px-4 text-center"
                    >
                        <AppIcon name="search_off" :size="40" class="mb-2 text-text-muted opacity-40" />
                        <p class="text-body-md text-text-muted">{{ props.emptyMessage }}</p>
                    </div>

                    <slot v-else name="mobile" :rows="rows" />
                </div>

                <div
                    v-if="showFooter"
                    class="flex items-center justify-between gap-3 border-t border-surface-container-high px-4 py-3"
                    :class="footerSurfaceClass"
                >
                    <p class="text-xs">
                        {{ pageStart }}-{{ pageEnd }} dari {{ totalRows }}
                    </p>

                    <div class="flex items-center gap-2">
                        <div v-if="props.showPageSize" class="min-w-20">
                            <AppFilterDropdown
                                label="Baris per halaman"
                                :options="pageSizeSelectOptions"
                                :model-value="pagination.pageSize"
                                :all-option="false"
                                strategy="fixed"
                                solid-background
                                @update:model-value="setPageSize"
                            />
                        </div>

                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-surface-container-high bg-surface-white text-text-body transition-colors hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!table.getCanPreviousPage()"
                            aria-label="Halaman sebelumnya"
                            @click="table.previousPage()"
                        >
                            <AppIcon name="chevron_left" :size="16" />
                        </button>

                        <span v-if="pageCount > 1" class="text-xs font-semibold">
                            {{ pageIndex + 1 }}/{{ pageCount }}
                        </span>

                        <button
                            type="button"
                            class="flex h-9 w-9 items-center justify-center rounded-xl border border-surface-container-high bg-surface-white text-text-body transition-colors hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="!table.getCanNextPage()"
                            aria-label="Halaman berikutnya"
                            @click="table.nextPage()"
                        >
                            <AppIcon name="chevron_right" :size="16" />
                        </button>
                    </div>
                </div>
            </div>

            <div :class="hasMobileSlot ? 'hidden md:block' : ''">
                <div
                    v-if="showDesktopHeader"
                    class="border-b border-surface-container-high"
                    :class="[
                        cardHeaderClass,
                        props.variant === 'activity' ? 'rounded-t-[16px]' : 'rounded-t-2xl',
                    ]"
                >
                    <div v-if="showTitleRow" class="p-6">
                        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    v-if="props.variant === 'activity' && props.showTitleIcon"
                                    class="h-3 w-3 shrink-0 rounded-full bg-on-primary-container"
                                ></span>
                                <h3 v-if="props.title" class="truncate text-title-lg font-semibold">
                                    {{ props.title }}
                                </h3>
                            </div>

                            <div class="flex w-full flex-wrap items-center gap-3 md:w-auto md:justify-end">
                                <div v-if="props.searchInTitle && props.searchable" class="relative w-full md:w-64">
                                    <AppIcon
                                        name="search"
                                        :size="16"
                                        class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-text-muted"
                                    />
                                    <input
                                        v-model="search"
                                        type="search"
                                :placeholder="props.searchPlaceholder"
                                        class="w-full rounded-full border border-surface-container-high bg-surface-container-low py-2 pl-9 pr-4 text-body-md text-text-body focus:border-secondary focus:outline-none"
                                    />
                                </div>

                                <slot name="actions" />
                            </div>
                        </div>
                    </div>

                    <div
                        v-if="showTabsRow"
                        class="flex flex-wrap items-center justify-between gap-3 px-6 py-3"
                        :class="showTitleRow ? 'border-t border-surface-container-high' : ''"
                    >
                        <AppTabs
                            v-if="hasTabs"
                            :tabs="props.tabs"
                            :model-value="activeTabValue"
                            :variant="props.tabsVariant"
                            class="min-w-0"
                            @update:model-value="handleTabChange"
                        />

                        <div v-if="$slots['tabs-actions']" class="shrink-0">
                            <slot name="tabs-actions" />
                        </div>
                    </div>

                    <div
                        v-if="showToolbar"
                        class="flex flex-wrap items-center gap-3 px-6 py-3"
                        :class="showTitleRow || showTabsRow || visibleFilters.length > 0 || $slots.actions
                            ? 'border-t border-surface-container-high'
                            : ''"
                    >
                        <div v-if="$slots['toolbar-start']" :class="toolbarStartClass">
                            <slot name="toolbar-start" />
                        </div>

                        <div v-if="props.columnToggle" ref="columnMenuRef" class="relative" :class="columnToggleClass">
                            <button
                                type="button"
                                :class="[toolbarButtonClass, 'w-28 md:w-32']"
                                :aria-expanded="columnMenuOpen"
                                aria-haspopup="menu"
                                @click="toggleColumnMenu"
                            >
                                <AppIcon name="adjustments_horizontal" :size="16" />
                                <span>Kolom</span>
                            </button>

                            <div
                                v-if="columnMenuOpen"
                                role="menu"
                                aria-label="Pilih kolom"
                                class="modal-scrollbar absolute right-0 z-40 mt-2 max-h-80 w-56 overflow-y-auto rounded-xl border border-surface-container-high bg-surface-white p-2 shadow-soft"
                            >
                                <button
                                    type="button"
                                    role="menuitemcheckbox"
                                    :aria-checked="allColumnsVisible"
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-body-md font-semibold text-text-heading transition-colors hover:bg-surface-container-low"
                                    @click="toggleAllColumns"
                                >
                                    <span
                                        class="flex size-4 shrink-0 items-center justify-center rounded border"
                                        :class="allColumnsVisible
                                            ? 'border-secondary bg-secondary text-on-secondary'
                                            : 'border-surface-container-high bg-surface-white'"
                                    >
                                        <AppIcon v-if="allColumnsVisible" name="check_circle" :size="12" />
                                    </span>
                                    <span class="flex-1">Pilih Semua</span>
                                </button>

                                <div class="my-1 h-px bg-surface-container-high"></div>

                                <button
                                    v-for="column in allLeafColumns"
                                    :key="column.id"
                                    type="button"
                                    role="menuitemcheckbox"
                                    :aria-checked="column.getIsVisible()"
                                    :disabled="!canHideColumn(column)"
                                    class="flex w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-body-md transition-colors hover:bg-surface-container-low disabled:cursor-not-allowed disabled:opacity-50"
                                    :class="column.getIsVisible() ? 'text-text-heading' : 'text-text-muted'"
                                    @click="toggleColumn(column)"
                                >
                                    <span
                                        class="flex size-4 shrink-0 items-center justify-center rounded border"
                                        :class="column.getIsVisible()
                                            ? 'border-secondary bg-secondary text-on-secondary'
                                            : 'border-surface-container-high bg-surface-white'"
                                    >
                                        <AppIcon v-if="column.getIsVisible()" name="check_circle" :size="12" />
                                    </span>
                                    <span class="min-w-0 flex-1 truncate">{{ headerText(column) }}</span>
                                </button>

                                <p v-if="allLeafColumns.length === 0" class="px-3 py-4 text-center text-body-md text-text-muted">
                                    Tidak ada kolom yang ditampilkan.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="visibleFilters.length > 0"
                            ref="filterMenuRef"
                            class="relative"
                            :class="filterButtonClass"
                        >
                            <button
                                type="button"
                                :class="[toolbarButtonClass, 'min-w-28 md:min-w-32']"
                                :aria-expanded="filterMenuOpen"
                                aria-haspopup="menu"
                                @click="toggleFilterMenu"
                            >
                                <AppIcon name="filter" :size="16" />
                                <span>Filter</span>
                                <span
                                    v-if="filterBadgeCount > 0"
                                    class="inline-flex min-w-5 items-center justify-center rounded-full bg-secondary px-1.5 text-[10px] leading-4 font-semibold text-on-secondary"
                                >
                                    {{ filterBadgeCount }}
                                </span>
                            </button>

                            <div
                                v-if="filterMenuOpen"
                                role="menu"
                                aria-label="Filter data"
                                class="absolute right-0 z-40 mt-2 rounded-xl border border-surface-container-high bg-surface-white p-4 shadow-soft"
                                style="width: min(384px, calc(100vw - 80px))"
                            >
                                <div
                                    v-for="filter in visibleFilters"
                                    :key="filter.key"
                                    class="mb-3 flex items-center gap-3"
                                >
                                    <span class="w-32 shrink-0 text-body-md text-text-body">{{ filter.label }}</span>
                                    <div class="min-w-0 flex-1">
                                        <AppFilterDropdown
                                            :label="filter.label"
                                            :options="filter.options ?? []"
                                            :model-value="filterDraft[filter.key] ?? ''"
                                            :all-option="filter.allOption !== false"
                                            @update:model-value="updateFilterDraft(filter.key, $event)"
                                        />
                                    </div>
                                </div>

                                <slot name="filter-extra" />

                                <div class="mt-4 flex justify-end gap-2">
                                    <button
                                        type="button"
                                        class="inline-flex h-9 items-center justify-center rounded-full border border-surface-container-high bg-surface-white px-4 text-body-md text-text-body transition-colors hover:bg-surface-container-low focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
                                        @click="resetFilters"
                                    >
                                        Reset
                                    </button>
                                    <button
                                        type="button"
                                        class="inline-flex h-9 items-center justify-center rounded-full bg-primary px-4 text-body-md text-on-primary transition-colors hover:bg-primary-container hover:text-on-primary-container focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
                                        @click="applyFilters"
                                    >
                                        Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="props.searchable && !props.searchInTitle"
                            class="relative w-full md:w-64"
                            :class="toolbarSearchClass"
                        >
                            <AppIcon
                                name="search"
                                :size="16"
                                class="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-text-muted"
                            />
                            <input
                                v-model="search"
                                type="search"
                                        :placeholder="props.searchPlaceholder"
                                :class="searchInputClass"
                            />
                        </div>
                    </div>
                </div>

                <div
                    v-if="!hasVisibleColumns"
                    class="flex min-h-96 items-center justify-center px-6 text-center text-body-md text-text-muted"
                >
                    Tidak ada kolom yang ditampilkan.
                </div>

                <phantom-ui v-else :loading="isTableLoading" fallback-radius="9999">
                    <div class="relative">
                        <div
                            ref="scrollContainer"
                            class="modal-scrollbar overscroll-x-contain overscroll-y-auto"
                            :class="[
                                !showDesktopHeader
                                    ? (props.variant === 'activity' ? 'rounded-t-[16px]' : 'rounded-t-2xl')
                                    : '',
                                !showFooter
                                    ? (props.variant === 'activity' ? 'rounded-b-[16px]' : 'rounded-b-2xl')
                                    : '',
                            ]"
                            :style="scrollContainerStyle"
                            @scroll="onTableScroll"
                        >
                            <table ref="tableRef" class="w-full text-left">
                                <thead data-shimmer-ignore :class="tableHeadClass">
                                    <template v-if="hasGroupedHeader">
                                        <tr>
                                            <template
                                                v-for="(segment, segmentIndex) in headerSegments"
                                                :key="segment.type === 'group'
                                                    ? `group-top-${segmentIndex}`
                                                    : `column-top-${segment.column.id}`"
                                            >
                                                <th
                                                    v-if="segment.type === 'group'"
                                                    :colspan="segment.columns.length"
                                                    class="sticky top-0 z-10 whitespace-nowrap border-b border-surface-container-high text-center"
                                                    :class="[headerBackgroundClass, props.variant === 'activity'
                                                        ? 'px-4 py-3.5 text-[11px] font-bold uppercase tracking-wider'
                                                        : 'px-6 py-4 text-label-md font-semibold uppercase tracking-wider']"
                                                >
                                                    {{ segment.label }}
                                                </th>
                                                <th
                                                    v-else
                                                    :rowspan="2"
                                                    :aria-sort="segment.column.getIsSorted() === 'asc'
                                                        ? 'ascending'
                                                        : segment.column.getIsSorted() === 'desc'
                                                            ? 'descending'
                                                            : 'none'"
                                                    :class="headerCellClasses(segment.column)"
                                                >
                                                    <button
                                                        v-if="segment.column.getCanSort() && !isActionsColumn(segment.column)"
                                                        type="button"
                                                        class="flex w-full cursor-pointer items-center gap-1 select-none"
                                                        :class="justifyClass(segment.column)"
                                                        @click="segment.column.getToggleSortingHandler()?.($event)"
                                                    >
                                                        <FlexRender
                                                            v-if="headerOf(segment.column)"
                                                            :header="headerOf(segment.column)"
                                                        />
                                                        <span v-else>{{ headerText(segment.column) }}</span>
                                                        <AppIcon
                                                            :name="sortIcon(segment.column)"
                                                            :size="14"
                                                            class="shrink-0"
                                                            :class="sortIconClass(segment.column)"
                                                        />
                                                    </button>
                                                    <span v-else class="flex items-center gap-1" :class="justifyClass(segment.column)">
                                                        <FlexRender
                                                            v-if="headerOf(segment.column)"
                                                            :header="headerOf(segment.column)"
                                                        />
                                                        <span v-else>{{ headerText(segment.column) }}</span>
                                                    </span>
                                                </th>
                                            </template>
                                        </tr>

                                        <tr>
                                            <th
                                                v-for="column in groupedLeafColumns"
                                                :key="`group-leaf-${column.id}`"
                                                :aria-sort="column.getIsSorted() === 'asc'
                                                    ? 'ascending'
                                                    : column.getIsSorted() === 'desc'
                                                        ? 'descending'
                                                        : 'none'"
                                                :class="headerCellClasses(column, true)"
                                            >
                                                <button
                                                    v-if="column.getCanSort() && !isActionsColumn(column)"
                                                    type="button"
                                                    class="flex w-full cursor-pointer items-center gap-1 select-none"
                                                    :class="justifyClass(column)"
                                                    @click="column.getToggleSortingHandler()?.($event)"
                                                >
                                                    <FlexRender v-if="headerOf(column)" :header="headerOf(column)" />
                                                    <span v-else>{{ headerText(column) }}</span>
                                                    <AppIcon
                                                        :name="sortIcon(column)"
                                                        :size="14"
                                                        class="shrink-0"
                                                        :class="sortIconClass(column)"
                                                    />
                                                </button>
                                                <span v-else class="flex items-center gap-1" :class="justifyClass(column)">
                                                    <FlexRender v-if="headerOf(column)" :header="headerOf(column)" />
                                                    <span v-else>{{ headerText(column) }}</span>
                                                </span>
                                            </th>
                                        </tr>
                                    </template>

                                    <tr v-else>
                                        <th
                                            v-for="column in visibleColumns"
                                            :key="column.id"
                                            :aria-sort="column.getIsSorted() === 'asc'
                                                ? 'ascending'
                                                : column.getIsSorted() === 'desc'
                                                    ? 'descending'
                                                    : 'none'"
                                            :class="headerCellClasses(column)"
                                        >
                                            <button
                                                v-if="column.getCanSort() && !isActionsColumn(column)"
                                                type="button"
                                                class="flex w-full cursor-pointer items-center gap-1 select-none"
                                                :class="justifyClass(column)"
                                                @click="column.getToggleSortingHandler()?.($event)"
                                            >
                                                <FlexRender v-if="headerOf(column)" :header="headerOf(column)" />
                                                <span v-else>{{ headerText(column) }}</span>
                                                <AppIcon
                                                    :name="sortIcon(column)"
                                                    :size="14"
                                                    class="shrink-0"
                                                    :class="sortIconClass(column)"
                                                />
                                            </button>
                                            <span v-else class="flex items-center gap-1" :class="justifyClass(column)">
                                                <FlexRender v-if="headerOf(column)" :header="headerOf(column)" />
                                                <span v-else>{{ headerText(column) }}</span>
                                            </span>
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-surface-container-high bg-surface-white">
                                    <template v-if="isTableLoading">
                                        <tr v-for="skeletonRow in 5" :key="`skeleton-${skeletonRow}`">
                                            <td
                                                v-for="column in visibleColumns"
                                                :key="`skeleton-${skeletonRow}-${column.id}`"
                                                :class="bodyCellClasses(column)"
                                            >
                                                <span
                                                    class="inline-block text-body-md text-text-muted opacity-50"
                                                    :style="{ minWidth: skeletonMinWidth(column) }"
                                                >
                                                    {{ headerText(column) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </template>

                                    <template v-else-if="rows.length === 0">
                                        <tr>
                                            <td :colspan="visibleColumns.length || 1" class="px-6 py-16 text-center">
                                                <AppIcon name="search_off" :size="40" class="mx-auto mb-2 text-text-muted opacity-40" />
                                                <p class="text-body-md text-text-muted">{{ props.emptyMessage }}</p>
                                            </td>
                                        </tr>
                                    </template>

                                    <template v-else-if="hasGroupedHeader && $slots['grouped-body']">
                                        <slot name="grouped-body" :rows="rows" />
                                    </template>

                                    <template v-else>
                                        <tr
                                            v-for="(row, rowIndex) in rows"
                                            :key="row.id"
                                            :data-row-id="rowIdOf(row)"
                                            :draggable="props.draggable"
                                            :class="rowClass(row, rowIndex)"
                                            :aria-label="rowHighlighted(row) ? 'Baris baru diperbarui' : undefined"
                                            @click.capture="captureRowAction(row, $event)"
                                            @click="handleRowClick(row, $event)"
                                            @dragstart="onDragStart(rowIndex, $event)"
                                            @dragover="onDragOver(rowIndex, $event)"
                                            @drop.prevent="onDrop(rowIndex)"
                                            @dragend="onDragEnd"
                                        >
                                            <td
                                                v-for="cell in row.getVisibleCells()"
                                                :key="cell.id"
                                                :data-action-column="isActionsColumn(cell.column) ? '' : undefined"
                                                :class="bodyCellClasses(cell.column)"
                                            >
                                                <div
                                                    v-if="isActionsColumn(cell.column)"
                                                    class="flex min-w-max flex-nowrap items-center justify-center whitespace-nowrap"
                                                >
                                                    <FlexRender :cell="cell" />
                                                </div>
                                                <FlexRender v-else :cell="cell" />
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>

                                <tfoot v-if="$slots.tfoot">
                                    <slot name="tfoot" />
                                </tfoot>
                            </table>
                        </div>

                        <button
                            v-if="canScrollLeft"
                            type="button"
                            class="absolute left-2 z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-surface-container-high bg-surface-white text-secondary shadow-soft transition-colors hover:bg-surface-container-low focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
                            :style="{ top: `${scrollArrowTop}px` }"
                            aria-label="Geser tabel ke kiri"
                            @click="scrollHorizontally(-1)"
                        >
                            <AppIcon name="chevron_left" :size="18" />
                        </button>

                        <button
                            v-if="canScrollRight"
                            type="button"
                            class="absolute z-30 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-surface-container-high bg-surface-white text-secondary shadow-soft transition-colors hover:bg-surface-container-low focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary"
                            :style="{ top: `${scrollArrowTop}px`, right: `${actionColumnWidth + 8}px` }"
                            aria-label="Geser tabel ke kanan"
                            @click="scrollHorizontally(1)"
                        >
                            <AppIcon name="chevron_right" :size="18" />
                        </button>
                    </div>
                </phantom-ui>

                <div
                    v-if="showFooter"
                    class="flex flex-col items-center justify-between gap-3 border-t border-surface-container-high px-6 py-4 sm:flex-row"
                    :class="[
                        footerSurfaceClass,
                        hasMobileSlot ? 'hidden md:flex' : '',
                        props.variant === 'activity' ? 'rounded-b-[16px]' : 'rounded-b-2xl',
                    ]"
                >
                    <p class="text-body-md">
                        Menampilkan
                        <span
                            class="font-semibold"
                            :class="props.variant === 'activity' ? 'text-on-primary-container' : 'text-text-heading'"
                        >
                            {{ pageStart }}-{{ pageEnd }}
                        </span>
                        dari
                        <span
                            class="font-semibold"
                            :class="props.variant === 'activity' ? 'text-on-primary-container' : 'text-text-heading'"
                        >
                            {{ totalRows }}
                        </span>
                        data
                    </p>

                    <div class="flex w-full flex-col items-center gap-3 sm:w-auto sm:flex-row sm:justify-end">
                        <div v-if="props.showPageSize" class="min-w-20">
                            <AppFilterDropdown
                                label="Baris per halaman"
                                :options="pageSizeSelectOptions"
                                :model-value="pagination.pageSize"
                                :all-option="false"
                                strategy="fixed"
                                solid-background
                                @update:model-value="setPageSize"
                            />
                        </div>

                        <div v-if="pageCount > 1" class="flex items-center gap-1">
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                :class="props.variant === 'activity'
                                    ? 'text-on-primary-container hover:bg-primary'
                                    : 'text-text-body hover:bg-surface-container-high'"
                                :disabled="!table.getCanPreviousPage()"
                                aria-label="Halaman sebelumnya"
                                @click="table.previousPage()"
                            >
                                <AppIcon name="chevron_left" :size="16" />
                            </button>

                            <template v-for="(item, itemIndex) in pageItems(pageIndex + 1, pageCount)" :key="`page-${itemIndex}`">
                                <span
                                    v-if="isEllipsisItem(item)"
                                    class="px-1 text-body-md"
                                    :class="props.variant === 'activity' ? 'text-on-primary-container' : 'text-text-muted'"
                                >
                                    ...
                                </span>
                                <button
                                    v-else
                                    type="button"
                                    class="flex h-8 min-w-8 items-center justify-center rounded-full px-2 text-body-md transition-colors"
                                    :class="item === pageIndex + 1
                                        ? (props.variant === 'activity'
                                            ? 'bg-surface-white font-semibold text-text-heading'
                                            : 'bg-primary-container font-semibold text-on-primary-container')
                                        : (props.variant === 'activity'
                                            ? 'text-on-primary-container hover:bg-primary'
                                            : 'text-text-body hover:bg-surface-container-high')"
                                    :aria-current="item === pageIndex + 1 ? 'page' : undefined"
                                    :aria-label="`Halaman ${item}`"
                                    @click="setPageIndex(item - 1)"
                                >
                                    {{ item }}
                                </button>
                            </template>

                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                :class="props.variant === 'activity'
                                    ? 'text-on-primary-container hover:bg-primary'
                                    : 'text-text-body hover:bg-surface-container-high'"
                                :disabled="!table.getCanNextPage()"
                                aria-label="Halaman berikutnya"
                                @click="table.nextPage()"
                            >
                                <AppIcon name="chevron_right" :size="16" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </BaseCard>
    </div>
</template>

<style scoped>
.action-column-shadow {
    box-shadow: -8px 0 12px -12px color-mix(in srgb, var(--color-deep-space) 25%, transparent);
}

tr.app-table-anchor-highlight {
    animation: app-table-anchor-highlight 800ms ease-out;
}

@keyframes app-table-anchor-highlight {
    0% {
        background-color: color-mix(in srgb, var(--color-secondary, #6750a4) 8%, transparent);
    }

    100% {
        background-color: transparent;
    }
}
</style>



