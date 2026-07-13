import { ref, computed, onUnmounted } from 'vue';

export function useExamTimer({ totalSeconds = 0, elapsedSeconds = 0, onExpire = null } = {}) {
    const remaining = ref(Math.max(0, totalSeconds - elapsedSeconds));

    if (remaining.value <= 0 && totalSeconds > 0) {
        setTimeout(() => onExpire?.(), 0);
    }

    let interval = null;

    const displayMinutes = computed(() => Math.floor(remaining.value / 60));
    const displaySeconds = computed(() => remaining.value % 60);
    const isWarning = computed(() => remaining.value < 300 && remaining.value > 60);
    const isDanger = computed(() => remaining.value <= 60 && remaining.value > 0);
    const isExpired = computed(() => remaining.value <= 0);

    function start() {
        if (interval || isExpired.value) return;
        interval = setInterval(() => {
            if (remaining.value > 0) {
                remaining.value--;
            } else {
                stop();
                onExpire?.();
            }
        }, 1000);
    }

    function stop() {
        if (interval) {
            clearInterval(interval);
            interval = null;
        }
    }

    function sync(serverSeconds) {
        remaining.value = serverSeconds;
    }

    onUnmounted(() => stop());

    return {
        remaining,
        minutes: displayMinutes,
        seconds: displaySeconds,
        isWarning,
        isDanger,
        isExpired,
        start,
        stop,
        sync,
    };
}
