<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { loadTurnstile } from '@/Composables/useTurnstile';

const props = defineProps({
    id: { type: String, required: true },
    siteKey: { type: String, default: '' },
    action: { type: String, default: null },
    error: { type: String, default: undefined },
});

const emit = defineEmits(['verified', 'expired', 'error']);
const widgetId = ref(null);
const status = ref('idle');
let isUnmounted = false;

const showSkeleton = computed(
    () => Boolean(props.siteKey) && status.value === 'loading',
);

const showError = computed(
    () => Boolean(props.siteKey) && status.value === 'error',
);

async function renderTurnstile() {
    if (!props.siteKey) return;

    if (widgetId.value !== null) {
        window.turnstile?.remove(widgetId.value);
        widgetId.value = null;
    }

    status.value = 'loading';

    try {
        await loadTurnstile();

        if (isUnmounted) return;

        widgetId.value = window.turnstile.render(`#${props.id}`, {
            sitekey: props.siteKey,
            action: props.action || undefined,
            callback: (token) => emit('verified', token),
            'expired-callback': () => {
                emit('expired');
                reset();
            },
            'error-callback': () => {
                status.value = 'error';
                emit('error');
            },
        });

        status.value = 'ready';
    } catch {
        status.value = 'error';
        emit('error');
    }
}

function retry() {
    status.value = 'idle';
    renderTurnstile();
}

onMounted(renderTurnstile);

onUnmounted(() => {
    isUnmounted = true;

    if (widgetId.value !== null) {
        window.turnstile?.remove(widgetId.value);
        widgetId.value = null;
    }
});

function getResponse() {
    return window.turnstile?.getResponse(widgetId.value);
}

function reset() {
    window.turnstile?.reset(widgetId.value);
}

defineExpose({ getResponse, reset });
</script>

<template>
    <div
        v-if="siteKey"
        class="relative flex min-h-[65px] w-full items-center justify-center"
    >
        <div :id="id" class="flex justify-center"></div>

        <div
            v-if="showSkeleton"
            class="absolute inset-0 flex items-center justify-center rounded-lg bg-surface-container animate-pulse"
            role="status"
            aria-live="polite"
        >
            <span class="text-label-md text-text-muted">Memuat verifikasi…</span>
        </div>

        <div
            v-else-if="showError"
            class="absolute inset-0 flex flex-col items-center justify-center gap-1 rounded-lg border border-outline-variant bg-surface-white text-center"
            role="alert"
        >
            <span class="text-label-md text-text-muted">Verifikasi gagal dimuat.</span>
            <button
                type="button"
                class="text-label-md font-medium text-secondary hover:underline"
                @click="retry"
            >
                Coba lagi
            </button>
        </div>
    </div>

    <p v-if="error" class="text-error-red text-xs mt-1">{{ error }}</p>
</template>
