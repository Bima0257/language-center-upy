import { computed, shallowRef } from 'vue';

const openModals = shallowRef([]);

export function useModalStack() {
    function registerModal(dialogElement) {
        if (!dialogElement) return () => {};

        openModals.value = [
            ...openModals.value.filter(element => element !== dialogElement),
            dialogElement,
        ];

        let registered = true;

        return () => {
            if (!registered) return;

            registered = false;
            openModals.value = openModals.value.filter(element => element !== dialogElement);
        };
    }

    const topModal = computed(() => openModals.value[openModals.value.length - 1] ?? null);

    return {
        topModal,
        registerModal,
    };
}
