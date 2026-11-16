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

const MAX_DIMENSION = 1920;
const MAX_BYTES = 2 * 1024 * 1024;

function captureFrame() {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    if (!video || !canvas) return Promise.resolve(null);

    const sourceWidth = video.videoWidth;
    const sourceHeight = video.videoHeight;
    const longest = Math.max(sourceWidth, sourceHeight) || 1;
    const scale = longest > MAX_DIMENSION ? MAX_DIMENSION / longest : 1;

    canvas.width = Math.max(1, Math.round(sourceWidth * scale));
    canvas.height = Math.max(1, Math.round(sourceHeight * scale));

    const context = canvas.getContext('2d');
    if (!context) return Promise.resolve(null);

    context.drawImage(video, 0, 0, canvas.width, canvas.height);

    const encode = (quality) =>
        new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));

    return (async () => {
        for (const quality of [0.8, 0.65, 0.5, 0.4]) {
            const blob = await encode(quality);
            if (!blob) return null;
            if (blob.size <= MAX_BYTES) return blob;
        }

        return encode(0.3);
    })();
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
