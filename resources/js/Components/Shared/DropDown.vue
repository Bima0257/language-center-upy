<script setup>
import { Listbox, ListboxButton, ListboxOptions, ListboxOption } from '@headlessui/vue'
import { IconSelector, IconCheck, IconX } from '@tabler/icons-vue'
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps({
    modelValue: { default: null },
    options: { type: Array, default: () => [] },
    placeholder: { type: String, default: 'Pilih...' },
    disabled: { type: Boolean, default: false },
    label: { type: String, default: '' },
    optionLabel: { type: [String, Function], default: null },
    optionValue: { type: [String, Function], default: 'id' },
    size: { type: String, default: 'md' },
    buttonClass: { type: String, default: '' },
    menuPlacement: { type: String, default: 'auto' },
    teleportMenu: { type: Boolean, default: false },
    clearable: { type: Boolean, default: false },
    emptyMessage: { type: String, default: '' },
})

const emit = defineEmits(['update:modelValue', 'change', 'open-change'])

const dropdownRoot = ref(null)
const buttonElement = ref(null)
const menuElement = ref(null)
const menuOpen = ref(false)
const resolvedMenuPlacement = ref(props.menuPlacement === 'top' ? 'top' : 'bottom')
const menuPosition = ref({
    left: 0,
    top: 0,
    bottom: 0,
    width: 0,
})

const dialogElement = computed(() => {
    const button = buttonElement.value?.$el ?? buttonElement.value

    return button?.closest('dialog') ?? null
})

const shouldTeleportMenu = computed(() => props.teleportMenu || Boolean(dialogElement.value))
const teleportTarget = computed(() => dialogElement.value ?? 'body')

function labelOf(option) {
    if (option === null || option === undefined) return ''
    if (typeof props.optionLabel === 'function') return props.optionLabel(option)
    if (typeof props.optionLabel === 'string') return option[props.optionLabel] ?? String(option)
    if (typeof option === 'string' || typeof option === 'number') return String(option)
    return option.name || option.title || option.label || String(option.id ?? '')
}

function valueOf(option) {
    if (option === null || option === undefined) return null
    if (typeof props.optionValue === 'function') return props.optionValue(option)
    if (typeof option === 'string' || typeof option === 'number') return option
    return option[props.optionValue]
}

const selected = computed(() =>
    props.options.find(o => String(valueOf(o)) === String(props.modelValue)) || null,
)

function handleUpdate(val) {
    const v = val === null ? '' : valueOf(val)
    setMenuOpen(false)
    emit('update:modelValue', v)
    emit('change', v)
}

function setMenuOpen(open) {
    menuOpen.value = open
    emit('open-change', open)
}

function toggleMenu() {
    setMenuOpen(!menuOpen.value)
}

function closeMenu() {
    if (menuOpen.value) setMenuOpen(false)
}

function closeOnOutsideClick(event) {
    const menu = menuElement.value?.$el ?? menuElement.value

    if (!dropdownRoot.value?.contains(event.target) && !menu?.contains(event.target)) {
        closeMenu()
    }
}

function viewportDimensions() {
    const viewport = window.visualViewport

    return {
        width: viewport?.width || window.innerWidth,
        height: viewport?.height || window.innerHeight,
    }
}

function clippingContainerOf(element) {
    let parent = element?.parentElement

    while (parent && parent !== document.body) {
        const style = window.getComputedStyle(parent)
        const overflowValues = [style.overflow, style.overflowY]

        if (overflowValues.some(value => /auto|scroll|overlay|hidden|clip/.test(value))) {
            return parent
        }

        parent = parent.parentElement
    }

    return null
}

function updateMenuPosition() {
    const button = buttonElement.value?.$el ?? buttonElement.value

    if (!button || !menuOpen.value) return

    const rect = button.getBoundingClientRect()
    const { width: viewportWidth, height: viewportHeight } = viewportDimensions()
    const clippingContainer = dialogElement.value || !shouldTeleportMenu.value
        ? clippingContainerOf(button)
        : null
    const clippingRect = clippingContainer?.getBoundingClientRect()
    const visibleTop = Math.max(0, clippingRect?.top ?? 0)
    const visibleBottom = Math.min(viewportHeight, clippingRect?.bottom ?? viewportHeight)
    const gap = 6
    const estimatedMenuHeight = Math.max(48, props.options.length * 40 + 12)
    const spaceAbove = Math.max(0, rect.top - visibleTop - gap)
    const spaceBelow = Math.max(0, visibleBottom - rect.bottom - gap)
    const placement = props.menuPlacement === 'auto'
        ? (spaceBelow >= estimatedMenuHeight || spaceBelow >= spaceAbove ? 'bottom' : 'top')
        : props.menuPlacement === 'top' ? 'top' : 'bottom'
    const width = Math.min(rect.width, Math.max(0, viewportWidth - gap * 2))

    resolvedMenuPlacement.value = placement
    menuPosition.value = {
        left: Math.min(
            Math.max(gap, rect.left),
            Math.max(gap, viewportWidth - width - gap),
        ),
        top: rect.bottom + gap,
        bottom: viewportHeight - rect.top + gap,
        width,
    }
}

const menuStyle = computed(() => {
    if (!shouldTeleportMenu.value) return undefined

    const style = {
        left: `${menuPosition.value.left}px`,
        width: `${menuPosition.value.width}px`,
    }

    if (resolvedMenuPlacement.value === 'top') {
        style.bottom = `${menuPosition.value.bottom}px`
    } else {
        style.top = `${menuPosition.value.top}px`
    }

    return style
})

function addPositionListeners() {
    if (!shouldTeleportMenu.value && props.menuPlacement !== 'auto') return

    window.addEventListener('resize', updateMenuPosition)
    window.addEventListener('scroll', updateMenuPosition, true)
    window.visualViewport?.addEventListener('resize', updateMenuPosition)
    window.visualViewport?.addEventListener('scroll', updateMenuPosition)
}

function removePositionListeners() {
    window.removeEventListener('resize', updateMenuPosition)
    window.removeEventListener('scroll', updateMenuPosition, true)
    window.visualViewport?.removeEventListener('resize', updateMenuPosition)
    window.visualViewport?.removeEventListener('scroll', updateMenuPosition)
}

watch(menuOpen, async open => {
    if (!open) {
        removePositionListeners()
        return
    }

    await nextTick()
    updateMenuPosition()
    addPositionListeners()
})

onMounted(() => document.addEventListener('mousedown', closeOnOutsideClick))
onBeforeUnmount(() => {
    document.removeEventListener('mousedown', closeOnOutsideClick)
    removePositionListeners()
})

const sizeClasses = {
    md: 'px-4 py-3 text-body-md rounded-2xl',
    sm: 'px-3 py-2 text-body-md rounded-xl',
}
</script>

<template>
    <div ref="dropdownRoot">
        <label v-if="label" class="text-label-md font-medium text-text-heading block mb-1.5">{{ label }}</label>
        <Listbox :model-value="selected" @update:model-value="handleUpdate" :disabled="disabled">
            <div class="relative">
                <ListboxButton
                    ref="buttonElement"
                    class="w-full flex items-center justify-between gap-2 bg-surface-container-lowest border border-outline-variant text-text-body focus:outline-none focus:border-secondary focus:ring-2 focus:ring-secondary/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                    :class="[sizeClasses[size], buttonClass]"
                    @click="toggleMenu"
                    @keydown.esc="closeMenu"
                >
                    <span class="truncate" :class="selected ? 'text-text-body' : 'text-text-muted'">
                        {{ selected ? labelOf(selected) : placeholder }}
                    </span>
                    <span class="flex items-center gap-1 shrink-0">
                        <button
                            v-if="clearable && selected && !disabled"
                            type="button"
                            @click.stop="handleUpdate(null)"
                            class="text-text-muted hover:text-error-red transition-colors"
                            title="Hapus pilihan"
                        >
                            <IconX :size="14" />
                        </button>
                        <IconSelector :size="16" class="text-text-muted" />
                    </span>
                </ListboxButton>

                <transition
                    enter-active-class="transition duration-100 ease-out"
                    enter-from-class="transform scale-95 opacity-0"
                    enter-to-class="transform scale-100 opacity-100"
                    leave-active-class="transition duration-75 ease-in"
                    leave-from-class="transform scale-100 opacity-100"
                    leave-to-class="transform scale-95 opacity-0"
                >
                    <Teleport :to="teleportTarget" :disabled="!shouldTeleportMenu">
                        <ListboxOptions
                            ref="menuElement"
                            class="rounded-2xl bg-surface-white border border-outline-variant shadow-soft py-1.5 focus:outline-none"
                            :class="[
                                shouldTeleportMenu ? 'fixed z-[100] w-full' : 'absolute left-0 z-[100] w-full',
                                !shouldTeleportMenu && resolvedMenuPlacement === 'top' ? 'bottom-full mb-1.5' : '',
                                !shouldTeleportMenu && resolvedMenuPlacement === 'bottom' ? 'mt-1.5' : '',
                            ]"
                            :style="menuStyle"
                        >
                            <div v-if="options.length === 0 && emptyMessage"
                                 class="px-3.5 py-4 text-center text-text-muted text-body-md">
                                {{ emptyMessage }}
                            </div>
                            <ListboxOption v-else v-slot="{ active, selected: isSelected }" v-for="o in options" :key="valueOf(o)" :value="o">
                                <div
                                    class="flex items-center gap-2 px-3.5 py-2.5 cursor-pointer text-body-md transition-colors"
                                    :class="active ? 'bg-secondary/10 text-text-heading' : 'text-text-body'"
                                >
                                    <span class="flex-1 truncate">{{ labelOf(o) }}</span>
                                    <IconCheck v-if="isSelected" :size="16" class="text-secondary shrink-0" />
                                </div>
                            </ListboxOption>
                        </ListboxOptions>
                    </Teleport>
                </transition>
            </div>
        </Listbox>
    </div>
</template>
