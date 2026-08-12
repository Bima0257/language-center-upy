import { onUnmounted, ref, watch } from "vue";

const TIMEOUT_MS = 10000;

export function useMediaLoad(source = null) {
    const loading = ref(true);
    let timer = null;

    function stopTimer() {
        if (timer) {
            clearTimeout(timer);
            timer = null;
        }
    }

    function startTimer() {
        stopTimer();
        timer = setTimeout(() => {
            loading.value = false;
        }, TIMEOUT_MS);
    }

    function onLoad() {
        loading.value = false;
        stopTimer();
    }

    function onError() {
        loading.value = false;
        stopTimer();
    }

    startTimer();

    if (source) {
        watch(source, () => {
            loading.value = true;
            startTimer();
        });
    }

    onUnmounted(stopTimer);

    return { loading, onLoad, onError };
}
