<script setup>
import { useForm } from '@inertiajs/vue3';

defineProps({
    sessionId: { type: Number, required: true },
});

const form = useForm({});

function terminate(reason) {
    form.post(route('proctor.session.terminate', sessionId), { reason });
}
</script>

<template>
    <div class="space-y-2">
        <button @click="terminate('Dihentikan oleh proctor.')"
                class="w-full bg-error-red text-white py-3 rounded-full text-label-md font-medium hover:bg-red-700 transition-all active:scale-95">
            Hentikan Sesi
        </button>
    </div>
</template>
