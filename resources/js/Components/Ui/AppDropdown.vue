<script setup>
import { Dropdown } from 'floating-vue';
import { onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    placement: { type: String, default: 'bottom-end' },
    disabled: { type: Boolean, default: false },
    distance: { type: Number, default: 8 },
    popperClass: { type: String, default: '' },
    strategy: { type: String, default: 'absolute' },
    flip: { type: Boolean, default: true },
    shift: { type: Boolean, default: true },
    overflowPadding: { type: Number, default: 8 },
    autoHide: { type: Boolean, default: true },
    hideOnScroll: { type: Boolean, default: false },
});

const dropdownRef = ref(null);
let scrollListening = false;

function hidePopper() {
    dropdownRef.value?.hide?.();
}

function addScrollListener() {
    if (typeof window === 'undefined' || scrollListening) return;

    window.addEventListener('scroll', hidePopper, true);
    scrollListening = true;
}

function removeScrollListener() {
    if (typeof window === 'undefined' || !scrollListening) return;

    window.removeEventListener('scroll', hidePopper, true);
    scrollListening = false;
}

function syncScrollListener(enabled) {
    if (enabled) {
        addScrollListener();
    } else {
        removeScrollListener();
    }
}

watch(() => props.hideOnScroll, syncScrollListener, { immediate: true });

onBeforeUnmount(removeScrollListener);
</script>

<template>
    <Dropdown
        ref="dropdownRef"
        theme="app"
        :placement="props.placement"
        :disabled="props.disabled"
        :distance="props.distance"
        :popper-class="props.popperClass"
        :strategy="props.strategy"
        :flip="props.flip"
        :shift="props.shift"
        :overflow-padding="props.overflowPadding"
        :auto-hide="props.autoHide"
        :handle-resize="false"
    >
        <slot />
        <template #popper="{ hide }">
            <slot name="items" :hide="hide" />
        </template>
    </Dropdown>
</template>
