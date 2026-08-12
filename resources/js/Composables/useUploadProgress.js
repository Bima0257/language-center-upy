import { computed } from "vue";

const AUDIO_KEEP_SMALL_MP3_BYTES = 8 * 1024 * 1024; // 8MB
const IMAGE_KEEP_UNDER_BYTES = 5 * 1024 * 1024; // 5MB

function mayNeedCompression(file, type) {
    if (!file || !(file instanceof File)) return false;

    if (type === 'audio') {
        const ext = (file.name.split('.').pop() || '').toLowerCase();
        if (ext === 'mp3' && file.size <= AUDIO_KEEP_SMALL_MP3_BYTES) return false;
        return true;
    }

    if (type === 'image') {
        return file.size > IMAGE_KEEP_UNDER_BYTES;
    }

    return false;
}

export function useUploadProgress(
    form,
    { audioField = "audio_file", imageField = "image_file" } = {},
) {
    const audioMayCompress = computed(() => mayNeedCompression(form[audioField], 'audio'));
    const imageMayCompress = computed(() => mayNeedCompression(form[imageField], 'image'));

    const mediaType = computed(() => {
        if (audioMayCompress.value && imageMayCompress.value) return 'both';
        if (audioMayCompress.value) return 'audio';
        if (imageMayCompress.value) return 'image';
        return null;
    });

    const showUploadProgress = computed(
        () => form.processing && mediaType.value !== null,
    );
    const uploadLabel = computed(() => {
        if (mediaType.value === 'both') {
            return "Mengunggah & mengompres audio dan gambar...";
        }
        if (mediaType.value === 'image') {
            return "Mengunggah & mengompres gambar...";
        }
        return "Mengunggah & mengompres audio...";
    });

    return { showUploadProgress, uploadLabel, mediaType };
}
