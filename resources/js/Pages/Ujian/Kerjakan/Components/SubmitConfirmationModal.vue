<script setup>
defineProps({
    show: { type: Boolean, default: false },
    answeredCount: { type: Number, default: 0 },
    jumlahSoal: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal :show="show" max-width="md" :closeable="false">
        <div
            role="dialog"
            aria-modal="true"
            aria-labelledby="submit-confirm-title"
            class="bg-surface-white rounded-3xl p-8 shadow-app-frame max-w-md w-full text-center"
        >
            <div class="w-16 h-16 bg-pastel-purple rounded-full flex items-center justify-center mx-auto mb-5">
                <span class="text-3xl">📝</span>
            </div>
            <h2
                class="text-headline-md font-bold text-text-heading mb-2"
            >
                <span id="submit-confirm-title">Yakin ingin mengumpulkan?</span>
            </h2>
            <p class="text-text-body text-body-md mb-1">
                Soal terjawab: <strong>{{ answeredCount }}</strong> dari {{ jumlahSoal }}
            </p>
            <p class="text-text-muted text-label-md mb-6">
                Jawaban tidak bisa diubah setelah dikumpulkan.
            </p>
            <div class="flex gap-3">
                <button
                    type="button"
                    @click="emit('close')"
                    class="flex-1 px-5 py-3 rounded-full text-label-md font-semibold border border-outline-variant bg-surface-white text-text-heading hover:bg-surface-container-low transition-all"
                >
                    Kembali
                </button>
                <button
                    type="button"
                    @click="emit('submit')"
                    class="flex-1 px-5 py-3 rounded-full text-label-md font-semibold bg-secondary text-on-secondary hover:bg-secondary/90 transition-all shadow-md active:scale-95 duration-150"
                >
                    Kumpulkan
                </button>
            </div>
        </div>
    </BaseModal>
</template>
