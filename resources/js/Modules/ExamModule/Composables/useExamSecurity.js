import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

export function useExamSecurity({ sessionId, heartbeatInterval = 30000 }) {
    const violations = ref([]);
    const isFullscreen = ref(false);
    const strikeCount = ref(0);
    let heartbeatTimer = null;

    function activate() {
        enterFullscreen();
        addListeners();
        startHeartbeat();
    }

    function deactivate() {
        removeListeners();
        stopHeartbeat();
    }

    function enterFullscreen() {
        if (document.documentElement.requestFullscreen) {
            document.documentElement.requestFullscreen().catch(() => {});
        }
    }

    function logViolation(type, metadata = {}) {
        axios.post(`/exam/session/${sessionId}/violation`, {
            type,
            metadata,
        }).then(res => {
            strikeCount.value = res.data.strike;
            if (!res.data.sessionActive) {
                window.location.href = '/dashboard';
            }
        }).catch(() => {});
    }

    function handleVisibilityChange() {
        if (document.hidden) {
            violations.value.push({ type: 'tab_switch', time: Date.now() });
            logViolation('tab_switch');
        }
    }

    function handleFullscreenChange() {
        if (!document.fullscreenElement) {
            violations.value.push({ type: 'fullscreen_exit', time: Date.now() });
            logViolation('fullscreen_exit');
            enterFullscreen();
        }
    }

    function handleKeydown(e) {
        if (e.key === 'F12' ||
            (e.ctrlKey && e.shiftKey && ['I', 'C', 'J'].includes(e.key.toUpperCase())) ||
            (e.ctrlKey && e.key.toUpperCase() === 'U')) {
            e.preventDefault();
            logViolation('devtools');
        }
    }

    function startHeartbeat() {
        heartbeatTimer = setInterval(() => {
            axios.post(`/exam/session/${sessionId}/heartbeat`).catch(() => {});
        }, heartbeatInterval);
    }

    function stopHeartbeat() {
        if (heartbeatTimer) clearInterval(heartbeatTimer);
    }

    function addListeners() {
        document.addEventListener('visibilitychange', handleVisibilityChange);
        document.addEventListener('fullscreenchange', handleFullscreenChange);
        document.addEventListener('contextmenu', (e) => e.preventDefault());
        document.addEventListener('keydown', handleKeydown);
    }

    function removeListeners() {
        document.removeEventListener('visibilitychange', handleVisibilityChange);
        document.removeEventListener('fullscreenchange', handleFullscreenChange);
        document.removeEventListener('contextmenu', (e) => e.preventDefault());
        document.removeEventListener('keydown', handleKeydown);
    }

    onUnmounted(() => deactivate());

    return {
        violations,
        isFullscreen,
        strikeCount,
        activate,
        deactivate,
        logViolation,
    };
}
