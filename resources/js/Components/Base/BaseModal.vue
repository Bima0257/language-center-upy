<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, onUnmounted, ref, watch } from 'vue';
import { useModalStack } from '@/Composables/useModalStack';

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
    fitContent: {
        type: Boolean,
        default: false,
    },
    closeable: {
        type: Boolean,
        default: true,
    },
    scrollable: {
        type: Boolean,
        default: false,
    },
    ariaLabel: {
        type: String,
        default: null,
    },
    ariaLabelledby: {
        type: String,
        default: null,
    },
    ariaDescribedby: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(['close']);

const { registerModal, topModal } = useModalStack();

const dialog = ref();
const modalPanel = ref(null);
const showSlot = ref(props.show);
const isClosing = ref(false);
const lockedSize = ref(null);
let bodyScrollLocked = false;
let previouslyFocusedElement = null;
let unregisterModal = null;

const openDialog = () => {
    isClosing.value = false;
    lockedSize.value = null;

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

        if (!unregisterModal) {
            unregisterModal = registerModal(dialog.value);
        }

        const firstFocusableElement = dialog.value.querySelector(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])',
        );

        firstFocusableElement?.focus();
    });
};

const closeDialog = () => {
    if (!dialog.value?.open) {
        if (bodyScrollLocked) {
            unlockBodyScroll();
            bodyScrollLocked = false;
        }

        if (unregisterModal) {
            unregisterModal();
            unregisterModal = null;
        }

        showSlot.value = false;
        isClosing.value = false;
        lockedSize.value = null;
        previouslyFocusedElement = null;
        return;
    }

    if (modalPanel.value) {
        const width = modalPanel.value.offsetWidth;
        const height = modalPanel.value.offsetHeight;

        if (width > 0 && height > 0) {
            lockedSize.value = { width, height };
        }
    }

    isClosing.value = true;

    if (bodyScrollLocked) {
        unlockBodyScroll();
        bodyScrollLocked = false;
    }
};

const finishClose = () => {
    if (props.show) {
        isClosing.value = false;
        lockedSize.value = null;
        return;
    }

    if (dialog.value?.open) dialog.value.close();

    if (unregisterModal) {
        unregisterModal();
        unregisterModal = null;
    }

    showSlot.value = false;
    isClosing.value = false;
    lockedSize.value = null;

    if (!topModal.value && previouslyFocusedElement?.isConnected) {
        previouslyFocusedElement.focus();
    }

    previouslyFocusedElement = null;
};

const handleCancel = (event) => {
    event.preventDefault();

    if (props.closeable) emit('close');
};

watch(
    () => props.show,
    (visible) => (visible ? openDialog() : closeDialog()),
    { immediate: true },
);

onMounted(() => {
    if (props.show) openDialog();
});

onBeforeUnmount(() => {
    if (unregisterModal) {
        unregisterModal();
        unregisterModal = null;
    }
});

onUnmounted(() => {
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
        '3xl': 'sm:max-w-3xl',
        '4xl': 'sm:max-w-4xl',
    }[props.maxWidth];
});

const modalWidthClass = computed(() => [
    props.fitContent ? 'w-fit max-w-[calc(100vw-2rem)]' : 'w-full',
    maxWidthClass.value,
]);
</script>

<template>
    <dialog
        class="z-50 m-0 min-h-full min-w-full overflow-hidden bg-transparent backdrop:bg-transparent"
        ref="dialog"
        aria-modal="true"
        :aria-label="props.ariaLabel || undefined"
        :aria-labelledby="props.ariaLabelledby || undefined"
        :aria-describedby="props.ariaDescribedby || undefined"
        @cancel="handleCancel"
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
                    class="fixed inset-0 z-0 transform transition-all"
                >
                    <div
                        class="absolute inset-0 bg-scrim/40 backdrop-blur-xs"
                    />
                </div>
            </Transition>

            <Transition
                enter-active-class="ease-out duration-300"
                enter-from-class="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                enter-to-class="opacity-100 translate-y-0 sm:scale-100"
                leave-active-class="ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0"
                leave-to-class="opacity-0 translate-y-4 sm:translate-y-0"
                @after-leave="finishClose"
            >
                <div
                    v-show="show"
                    ref="modalPanel"
                    class="modal-scroll-container relative z-10 mb-0 flex max-h-[calc(100dvh-3rem)] min-h-0 flex-col overflow-hidden overscroll-contain rounded-2xl bg-surface-white shadow-soft transition-all transform dark:bg-surface-container/80 dark:backdrop-blur-xs sm:mx-auto"
                    :class="[
                        modalWidthClass,
                        scrollable
                            ? 'h-[calc(100dvh-3rem)]'
                            : 'h-fit',
                        isClosing ? 'pointer-events-none' : '',
                    ]"
                    :inert="isClosing"
                    :style="isClosing && lockedSize
                        ? {
                            width: `${lockedSize.width}px`,
                            height: `${lockedSize.height}px`,
                        }
                        : undefined"
                >
                    <div
                        v-if="showSlot && $slots.header"
                        class="sticky top-0 z-20 w-full shrink-0"
                    >
                        <slot name="header" />
                    </div>
                    <div class="modal-scrollbar min-h-0 flex-1 overflow-y-auto overscroll-contain">
                        <slot v-if="showSlot" />
                    </div>
                    <div
                        v-if="showSlot && $slots.footer"
                        class="sticky bottom-0 z-20 w-full shrink-0"
                    >
                        <slot name="footer" />
                    </div>
                </div>
            </Transition>
        </div>
    </dialog>
</template>
