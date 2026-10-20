<script setup>
import { onMounted, ref } from 'vue';

const props = defineProps({
    id: { type: String, required: true },
    siteKey: { type: String, default: '' },
    error: { type: String, default: undefined },
});

const emit = defineEmits(['verified', 'expired', 'error']);
const widgetId = ref(null);

function renderTurnstile() {
    if (!props.siteKey || !window.turnstile) return;

    widgetId.value = window.turnstile.render(`#${props.id}`, {
        sitekey: props.siteKey,
        callback: (token) => emit('verified', token),
        'expired-callback': () => {
            emit('expired');
            reset();
        },
        'error-callback': () => emit('error'),
    });
}

window.onTurnstileLoad = renderTurnstile;

onMounted(() => {
    if (window.turnstile) {
        renderTurnstile();
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
    <div :id="id" class="flex justify-center"></div>
    <p v-if="error" class="text-error-red text-xs mt-1">{{ error }}</p>
</template>
