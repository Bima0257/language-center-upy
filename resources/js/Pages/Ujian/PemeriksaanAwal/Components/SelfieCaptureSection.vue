<script setup>
import { ref } from 'vue';
import { IconCamera, IconRefresh } from '@tabler/icons-vue';

defineProps({
    cameraOk: { type: Boolean, default: false },
    photoPreview: { type: String, default: null },
});

const emit = defineEmits(['capture', 'retake']);
const videoRef = ref(null);
const canvasRef = ref(null);

function attachStream(stream) {
    if (!videoRef.value) return;

    videoRef.value.srcObject = stream;
    videoRef.value.play().catch(() => {});
}

function captureFrame() {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    if (!video || !canvas) return Promise.resolve(null);

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const context = canvas.getContext('2d');
    if (!context) return Promise.resolve(null);

    context.drawImage(video, 0, 0);

    return new Promise((resolve) => {
        canvas.toBlob(resolve, 'image/jpeg', 0.9);
    });
}

defineExpose({ attachStream, captureFrame });
</script>

<template>
    <div v-if="cameraOk" class="space-y-3">
        <p v-if="!photoPreview" class="text-text-muted text-xs px-1">
            Pastikan wajah Anda terlihat jelas dan menghadap ke kamera, lalu klik "Ambil Foto" untuk selfie live.
        </p>
        <div class="bg-surface-container-low rounded-2xl overflow-hidden">
            <video v-show="!photoPreview" ref="videoRef" autoplay playsinline muted class="w-full rounded-2xl" />
            <img v-if="photoPreview" :src="photoPreview" class="w-full rounded-2xl" />
        </div>
        <div class="flex gap-3">
            <BaseButton v-if="!photoPreview" size="md" class="flex-1" @click="emit('capture')">
                <IconCamera :size="18" /> Ambil Foto
            </BaseButton>
            <BaseButton v-if="photoPreview" variant="secondary" size="md" class="flex-1" @click="emit('retake')">
                <IconRefresh :size="18" /> Ambil Ulang
            </BaseButton>
        </div>
        <p v-if="photoPreview" class="text-text-muted text-xs text-center">
            Foto live berhasil diambil — akan dilampirkan ke sesi ujian sebagai verifikasi identitas.
        </p>
    </div>

    <canvas ref="canvasRef" class="hidden" />
</template>
