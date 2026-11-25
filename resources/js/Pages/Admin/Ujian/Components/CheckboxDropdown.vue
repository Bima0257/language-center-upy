<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { IconSearch, IconSelector, IconX } from '@tabler/icons-vue';
import BaseCheckbox from '@/Components/Base/BaseCheckbox.vue';

const props = defineProps({
    options: { type: Array, default: () => [] },
    label: { type: String, default: '' },
    placeholder: { type: String, default: 'Pilih...' },
    searchPlaceholder: { type: String, default: 'Cari...' },
    emptyMessage: { type: String, default: 'Tidak ada pilihan.' },
    searchable: { type: Boolean, default: true },
    disabled: { type: Boolean, default: false },
});

const model = defineModel({ type: Array, default: () => [] });

const root = ref(null);
const trigger = ref(null);
const panel = ref(null);
const searchInput = ref(null);
const menuOpen = ref(false);
const searchQuery = ref('');
const resolvedPlacement = ref('bottom');
const panelPosition = ref({ left: 0, top: 0, bottom: 0, width: 0 });

const dialogElement = computed(() => trigger.value?.closest('dialog') ?? null);
const shouldTeleport = computed(() => Boolean(dialogElement.value));
const teleportTarget = computed(() => dialogElement.value ?? 'body');

const filteredOptions = computed(() => {
    const keyword = searchQuery.value.trim().toLowerCase();

    if (keyword === '') return props.options;

    return props.options.filter((option) => String(option.label ?? '').toLowerCase().includes(keyword));
});

const selectedValues = computed(() => new Set((model.value ?? []).map((value) => String(value))));

const selectedOptions = computed(() =>
    props.options.filter((option) => selectedValues.value.has(String(option.value))),
);

const selectedCount = computed(() => selectedOptions.value.length);

const triggerLabel = computed(() => {
    if (selectedOptions.value.length === 0) return '';
    if (selectedOptions.value.length === 1) return selectedOptions.value[0].label;

    return `${selectedOptions.value.length} dipilih`;
});

const panelStyle = computed(() => {
    if (!shouldTeleport.value) return undefined;

    const style = {
        left: `${panelPosition.value.left}px`,
        width: `${panelPosition.value.width}px`,
    };

    if (resolvedPlacement.value === 'top') {
        style.bottom = `${panelPosition.value.bottom}px`;
    } else {
        style.top = `${panelPosition.value.top}px`;
    }

    return style;
});

function viewportDimensions() {
    const viewport = window.visualViewport;

    return {
        width: viewport?.width || window.innerWidth,
        height: viewport?.height || window.innerHeight,
    };
}

function updatePanelPosition() {
    if (!shouldTeleport.value || !menuOpen.value || !trigger.value) return;

    const rect = trigger.value.getBoundingClientRect();
    const { width: viewportWidth, height: viewportHeight } = viewportDimensions();
    const gap = 6;
    const estimatedPanelHeight = 336;
    const spaceAbove = Math.max(0, rect.top - gap);
    const spaceBelow = Math.max(0, viewportHeight - rect.bottom - gap);
    const placement = spaceBelow >= estimatedPanelHeight || spaceBelow >= spaceAbove ? 'bottom' : 'top';
    const width = Math.min(rect.width, Math.max(0, viewportWidth - gap * 2));

    resolvedPlacement.value = placement;
    panelPosition.value = {
        left: Math.min(
            Math.max(gap, rect.left),
            Math.max(gap, viewportWidth - width - gap),
        ),
        top: rect.bottom + gap,
        bottom: viewportHeight - rect.top + gap,
        width,
    };
}

function addPositionListeners() {
    if (!shouldTeleport.value) return;

    window.addEventListener('resize', updatePanelPosition);
    window.addEventListener('scroll', updatePanelPosition, true);
    window.visualViewport?.addEventListener('resize', updatePanelPosition);
    window.visualViewport?.addEventListener('scroll', updatePanelPosition);
}

function removePositionListeners() {
    window.removeEventListener('resize', updatePanelPosition);
    window.removeEventListener('scroll', updatePanelPosition, true);
    window.visualViewport?.removeEventListener('resize', updatePanelPosition);
    window.visualViewport?.removeEventListener('scroll', updatePanelPosition);
}

function isSelected(value) {
    return selectedValues.value.has(String(value));
}

function clearSearch() {
    searchQuery.value = '';
    searchInput.value?.focus();
}

function toggleOption(value, checked) {
    const remaining = (model.value ?? []).filter((item) => String(item) !== String(value));

    model.value = checked ? [...remaining, value] : remaining;
}

function selectAllFiltered() {
    const merged = new Map((model.value ?? []).map((value) => [String(value), value]));

    filteredOptions.value.forEach((option) => merged.set(String(option.value), option.value));

    model.value = [...merged.values()];
}

function clearOptions() {
    model.value = [];
}

function closeMenu(refocus = false) {
    if (!menuOpen.value) return;

    menuOpen.value = false;

    if (refocus) trigger.value?.focus();
}

function toggleMenu() {
    if (props.disabled) return;

    if (menuOpen.value) {
        closeMenu();
    } else {
        menuOpen.value = true;
    }
}

function closeOnOutsideClick(event) {
    if (root.value?.contains(event.target) || panel.value?.contains(event.target)) return;

    closeMenu();
}

watch(menuOpen, async (open) => {
    if (!open) {
        searchQuery.value = '';
        removePositionListeners();
        return;
    }

    await nextTick();
    updatePanelPosition();
    addPositionListeners();

    if (props.searchable) {
        searchInput.value?.focus();
    }
});

watch(() => props.disabled, (disabled) => {
    if (disabled) closeMenu();
});

onMounted(() => document.addEventListener('mousedown', closeOnOutsideClick));

onBeforeUnmount(() => {
    document.removeEventListener('mousedown', closeOnOutsideClick);
    removePositionListeners();
});
</script>

<template>
    <div ref="root">
        <label v-if="props.label" class="mb-1.5 block text-label-md font-medium text-text-heading">{{ props.label }}</label>
        <div class="relative">
            <button
                ref="trigger"
                type="button"
                class="flex w-full items-center justify-between gap-2 rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body transition-all focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/20 disabled:cursor-not-allowed disabled:opacity-50 font-semibold"
                :disabled="props.disabled"
                :aria-expanded="menuOpen"
                aria-haspopup="true"
                :aria-label="props.label || props.placeholder"
                @click="toggleMenu"
                @keydown.esc.stop.prevent="closeMenu(true)"
            >
                <span class="truncate text-left" :class="triggerLabel ? 'text-text-body' : 'text-text-muted'">
                    {{ triggerLabel || props.placeholder }}
                </span>
                <IconSelector :size="16" class="shrink-0 text-text-muted" />
            </button>

            <Transition
                enter-active-class="transition duration-100 ease-out"
                enter-from-class="transform scale-95 opacity-0"
                enter-to-class="transform scale-100 opacity-100"
                leave-active-class="transition duration-75 ease-in"
                leave-from-class="transform scale-100 opacity-100"
                leave-to-class="transform scale-95 opacity-0"
            >
                <Teleport :to="teleportTarget" :disabled="!shouldTeleport">
                    <div
                        v-if="menuOpen"
                        ref="panel"
                        role="group"
                        :aria-label="props.label || props.placeholder"
                        class="rounded-2xl border border-outline-variant bg-surface-white shadow-soft"
                        :class="shouldTeleport
                            ? 'fixed z-[100]'
                            : 'absolute left-0 top-full z-[100] mt-1.5 w-full'"
                        :style="panelStyle"
                        @keydown.esc.stop.prevent="closeMenu(true)"
                    >
                        <div class="border-b border-outline-variant p-2">
                            <div v-if="props.searchable" class="relative">
                                <IconSearch :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-text-muted" />
                                <input
                                    ref="searchInput"
                                    v-model="searchQuery"
                                    type="text"
                                    :placeholder="props.searchPlaceholder"
                                    class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest py-2 pl-9 pr-9 text-body-md text-text-body focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/20"
                                    @keydown.enter.prevent
                                />
                                <button
                                    v-if="searchQuery !== ''"
                                    type="button"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full p-1 text-text-muted transition-colors hover:bg-surface-container-low hover:text-text-body font-semibold"
                                    aria-label="Hapus pencarian"
                                    title="Hapus pencarian"
                                    @click="clearSearch"
                                >
                                    <IconX :size="14" />
                                </button>
                            </div>

                            <div
                                v-if="props.options.length > 0"
                                class="flex items-center justify-between gap-2 px-1"
                                :class="props.searchable ? 'mt-2' : ''"
                            >
                                <button
                                    type="button"
                                    class="text-label-md font-semibold text-secondary transition-colors hover:text-secondary/80 disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="filteredOptions.length === 0"
                                    @click="selectAllFiltered"
                                >
                                    Pilih semua
                                </button>
                                <button
                                    type="button"
                                    class="text-label-md font-semibold text-text-muted transition-colors hover:text-text-body disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="selectedCount === 0"
                                    @click="clearOptions"
                                >
                                    Hapus semua
                                </button>
                            </div>
                        </div>

                        <div class="modal-scrollbar max-h-56 overflow-y-auto p-1.5">
                            <BaseCheckbox
                                v-for="option in filteredOptions"
                                :key="String(option.value)"
                                :model-value="isSelected(option.value)"
                                class="flex w-full rounded-xl px-2.5 py-2 hover:bg-surface-container-low"
                                @update:model-value="toggleOption(option.value, $event)"
                            >
                                <span class="truncate">{{ option.label }}</span>
                            </BaseCheckbox>
                            <p v-if="filteredOptions.length === 0" class="px-3 py-4 text-center text-body-md text-text-muted">
                                {{ props.options.length === 0 ? props.emptyMessage : 'Tidak ada hasil.' }}
                            </p>
                        </div>
                    </div>
                </Teleport>
            </Transition>
        </div>
    </div>
</template>
