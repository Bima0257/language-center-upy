import { ref, watch } from "vue";

export function useMediaLoad(source = null) {
    const loading = ref(true);

    function onLoad() {
        loading.value = false;
    }

    function onError() {
        loading.value = false;
    }

    if (source) {
        watch(source, () => {
            loading.value = true;
        });
    }

    return { loading, onLoad, onError };
}
