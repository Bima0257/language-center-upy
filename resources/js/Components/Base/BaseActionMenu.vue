<script setup>
import { Menu, MenuButton, MenuItem, MenuItems } from '@headlessui/vue';
import { IconDotsVertical } from '@tabler/icons-vue';
import { nextTick, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
    ariaLabel: { type: String, default: 'Buka menu aksi' },
    disabled: { type: Boolean, default: false },
    iconOnly: { type: Boolean, default: false },
    teleportMenu: { type: Boolean, default: false },
    compact: { type: Boolean, default: false },
});

const emit = defineEmits(['select']);
const menuButton = ref(null);
const menuElement = ref(null);
const menuOpen = ref(false);
const menuPosition = ref({ top: 0, right: 0, maxHeight: 240 });

function elementOf(component) {
    return component?.$el ?? component;
}

function updateMenuPosition() {
    const button = elementOf(menuButton.value);
    if (!props.teleportMenu || !button || !menuOpen.value) return;

    const rect = button.getBoundingClientRect();
    const gap = 4;
    const top = rect.top;
    const menu = elementOf(menuElement.value);
    const menuWidth = menu?.getBoundingClientRect().width || (props.iconOnly ? 48 : 192);
    const preferredRight = window.innerWidth - rect.left + gap;
    const maxRight = window.innerWidth - menuWidth - gap;

    menuPosition.value = {
        top,
        right: Math.max(gap, Math.min(preferredRight, maxRight)),
        maxHeight: Math.max(96, window.innerHeight - top - gap),
    };
}

function closeTeleportedMenu() {
    if (!props.teleportMenu) return;

    menuOpen.value = false;
    window.removeEventListener('resize', updateMenuPosition);
    window.removeEventListener('scroll', updateMenuPosition, true);
    document.removeEventListener('pointerdown', onDocumentPointerDown, true);
    document.removeEventListener('keydown', onDocumentKeydown, true);
}

function onDocumentPointerDown(event) {
    const button = elementOf(menuButton.value);
    const menu = elementOf(menuElement.value);

    if (button?.contains(event.target) || menu?.contains(event.target)) return;

    closeTeleportedMenu();
}

function onDocumentKeydown(event) {
    if (event.key === 'Escape') closeTeleportedMenu();
}

function handleMenuButtonClick() {
    if (!props.teleportMenu) return;

    if (menuOpen.value) {
        closeTeleportedMenu();
        return;
    }

    menuOpen.value = true;
    nextTick(() => {
        updateMenuPosition();
        window.addEventListener('resize', updateMenuPosition);
        window.addEventListener('scroll', updateMenuPosition, true);
        document.addEventListener('pointerdown', onDocumentPointerDown, true);
        document.addEventListener('keydown', onDocumentKeydown, true);
    });
}

function teleportedMenuStyle() {
    if (!props.teleportMenu) return undefined;

    return {
        top: `${menuPosition.value.top}px`,
        right: `${menuPosition.value.right}px`,
        maxHeight: `${menuPosition.value.maxHeight}px`,
    };
}

function itemClasses(item, active) {
    if (item.disabled) {
        return 'cursor-not-allowed text-text-muted/50';
    }

    if (item.variant === 'danger') {
        return active
            ? 'bg-error-container/60 text-error-red'
            : 'text-error-red hover:bg-error-container/40';
    }

    return active
        ? 'bg-secondary/10 text-text-heading'
        : 'text-text-body hover:bg-surface-container-low';
}

function selectItem(item) {
    if (item.disabled) return;

    emit('select', item.value);
    closeTeleportedMenu();
}

onBeforeUnmount(closeTeleportedMenu);
</script>

<template>
    <Menu as="div" class="relative inline-flex shrink-0">
        <MenuButton
            ref="menuButton"
            type="button"
            :aria-label="props.ariaLabel"
            :disabled="props.disabled"
            class="inline-flex items-center justify-center text-text-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-1 disabled:pointer-events-none disabled:opacity-50"
            :class="props.compact ? 'h-8 w-6 rounded-md' : 'h-10 w-10 rounded-xl'"
            @click="handleMenuButtonClick"
            @keydown.esc="closeTeleportedMenu"
        >
            <IconDotsVertical :size="19" />
        </MenuButton>

        <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="scale-95 opacity-0"
            enter-to-class="scale-100 opacity-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="scale-100 opacity-100"
            leave-to-class="scale-95 opacity-0"
        >
            <Teleport to="body" :disabled="!props.teleportMenu">
                <MenuItems
                    ref="menuElement"
                    class="z-50 max-h-60 origin-top-right overflow-y-auto rounded-xl border border-outline-variant/50 bg-surface-white py-1.5 shadow-soft focus:outline-none"
                    :class="props.teleportMenu
                        ? ['fixed z-[60]', props.iconOnly ? 'min-w-12' : 'min-w-48']
                        : ['absolute right-0 mt-2', props.iconOnly ? 'min-w-12' : 'min-w-48']"
                    :style="teleportedMenuStyle()"
                >
                    <MenuItem
                        v-for="item in props.items"
                        :key="String(item.value)"
                        v-slot="{ active }"
                        :disabled="item.disabled"
                    >
                        <button
                            type="button"
                            class="flex w-full items-center gap-2 py-2.5 text-label-md transition-colors focus:outline-none"
                            :class="[
                                itemClasses(item, active),
                                props.iconOnly ? 'justify-center px-2.5' : 'px-3.5 text-left',
                            ]"
                            :disabled="item.disabled"
                            :aria-label="item.label"
                            :title="props.iconOnly ? item.label : undefined"
                            @click="selectItem(item)"
                        >
                            <component :is="item.icon" v-if="item.icon" :size="16" class="shrink-0" />
                            <span v-if="!props.iconOnly" class="truncate">{{ item.label }}</span>
                        </button>
                    </MenuItem>
                </MenuItems>
            </Teleport>
        </Transition>
    </Menu>
</template>
