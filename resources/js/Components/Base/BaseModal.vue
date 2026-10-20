<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue';

let bodyScrollLockCount = 0;
let bodyOverflowBeforeModal = '';

function lockBodyScroll() {
    if (bodyScrollLockCount === 0) {
        bodyOverflowBeforeModal = document.body.style.overflow;
        document.body.style.overflow = 'hidden';
    }

    bodyScrollLockCount += 1;
}

function unlockBodyScroll() {
    if (bodyScrollLockCount === 0) return;

    bodyScrollLockCount -= 1;

    if (bodyScrollLockCount === 0) {
        document.body.style.overflow = bodyOverflowBeforeModal;
        bodyOverflowBeforeModal = '';
    }
}

const props = defineProps({
    show: {
        type: Boolean,
        default: false,
    },
    maxWidth: {
        type: String,
        default: '2xl',
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    scrollable: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['close']);

const dialog = ref();
const showSlot = ref(props.show);
const closeTimer = ref(null);
let bodyScrollLocked = false;
let previouslyFocusedElement = null;

const openDialog = () => {
    if (closeTimer.value) {
        clearTimeout(closeTimer.value);
        closeTimer.value = null;
    }

    if (!bodyScrollLocked) {
        previouslyFocusedElement = document.activeElement;
        lockBodyScroll();
        bodyScrollLocked = true;
    }

    showSlot.value = true;

    nextTick(() => {
        if (!props.show || !dialog.value) return;

        if (!dialog.value.open) {
            dialog.value.showModal();
        }

        const firstFocusableElement = dialog.value.querySelector(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
        );

        firstFocusableElement?.focus();
    });
};

const closeDialog = () => {
    if (bodyScrollLocked) {
        unlockBodyScroll();
        bodyScrollLocked = false;
    }

    if (closeTimer.value) clearTimeout(closeTimer.value);

    closeTimer.value = setTimeout(() => {
        if (dialog.value?.open) dialog.value.close();
        showSlot.value = false;
        closeTimer.value = null;

        if (previouslyFocusedElement?.isConnected) {
            previouslyFocusedElement.focus();
        }

        previouslyFocusedElement = null;
    }, 200);
};

watch(
    () => props.show,
    (visible) => (visible ? openDialog() : closeDialog()),
    { immediate: true },
);

onMounted(() => {
    if (props.show) openDialog();
});

onUnmounted(() => {
    if (closeTimer.value) clearTimeout(closeTimer.value);
    if (dialog.value?.open) dialog.value.close();

    if (bodyScrollLocked) {
        unlockBodyScroll();
        bodyScrollLocked = false;
    }
});

const maxWidthClass = computed(() => {
    return {
        sm: 'sm:max-w-sm',
        md: 'sm:max-w-md',
        lg: 'sm:max-w-lg',
        xl: 'sm:max-w-xl',
        '2xl': 'sm:max-w-2xl',
    }[props.maxWidth];
});
</script>

<template>
    <dialog
        class="z-50 m-0 min-h-full min-w-full overflow-hidden bg-transparent backdrop:bg-transparent"
        ref="dialog"
        aria-modal="true"
        @cancel.prevent
    >
        <div
            class="fixed inset-0 z-50 flex items-center justify-center overflow-hidden px-4 py-6 sm:px-0"
            scroll-region
        >
            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0"
                enter-to-class="opacity-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100"
                leave-to-class="opacity-0"
            >
                <div
                    v-show="show"
                    class="fixed inset-0 transform transition-all"
                >
                    <div
                        class="absolute inset-0 bg-scrim/40 backdrop-blur-sm"
                    />
                </div>
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 sm:scale-100"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            >
                <div
                    v-show="show"
                    class="mb-0 max-h-[calc(100dvh-3rem)] transform rounded-3xl bg-surface-white shadow-soft transition-all sm:mx-auto sm:w-full"
                    :class="[
                        maxWidthClass,
                        scrollable
                            ? 'modal-scrollbar overflow-y-auto overscroll-contain'
                            : 'overflow-hidden',
                    ]"
                >
                    <slot v-if="showSlot" />
                </div>
            </Transition>
        </div>
    </dialog>
</template>
