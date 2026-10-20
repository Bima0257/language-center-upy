<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    sesiUjianId: { type: String, required: true },
});

const form = useForm({});

function terminate(reason) {
    form.post(route('pengawas.sesi.hentikan', props.sesiUjianId), { alasan: reason });
}
</script>

<template>
    <div class="space-y-2">
        <button @click="terminate('Dihentikan oleh proctor.')"
                class="w-full bg-error-red text-on-error py-3 rounded-full text-label-md font-medium hover:bg-error-container hover:text-on-error-container transition-all active:scale-95">
            Hentikan Sesi
        </button>
    </div>
</template>
