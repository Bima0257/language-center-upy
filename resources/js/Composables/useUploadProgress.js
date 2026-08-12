import { computed } from "vue";

export function useUploadProgress(
    form,
    { audioField = "audio_file", imageField = "image_file" } = {},
) {
    const showUploadProgress = computed(
        () =>
            form.processing &&
            (form[audioField] !== null || form[imageField] !== null),
    );
    const uploadLabel = computed(() =>
        form[audioField] !== null
            ? "Mengunggah & mengompres audio..."
            : "Mengunggah & mengompres gambar...",
    );

    return { showUploadProgress, uploadLabel };
}
