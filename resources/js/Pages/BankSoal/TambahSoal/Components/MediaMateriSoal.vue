<script setup>
import FileUpload from '@/Components/Shared/FileUpload.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import { computed } from 'vue';

const props = defineProps({
    form: { type: Object, required: true },
    policy: { type: Object, required: true },
});

function ruleFor(mediaType) {
    return props.policy?.aturan_media?.[mediaType] || 'forbidden';
}

const showText = computed(() => ruleFor('text') !== 'forbidden');
const showAudio = computed(() => ruleFor('audio') !== 'forbidden');
const showImage = computed(() => ruleFor('image') !== 'forbidden');
const textRequired = computed(() => ruleFor('text') === 'required');
const audioRequired = computed(() => ruleFor('audio') === 'required');
const imageRequired = computed(() => ruleFor('image') === 'required');
const visibleMediaCount = computed(() => [showText.value, showAudio.value, showImage.value].filter(Boolean).length);
</script>

<template>
    <div
        class="mt-5 grid grid-cols-1 gap-4"
        :class="visibleMediaCount > 1 ? 'md:grid-cols-2' : 'md:grid-cols-1'"
    >
        <div v-if="showText" class="rounded-xl border border-outline-variant/50 bg-surface-white p-4">
            <div class="mb-3">
                <p class="text-body-md font-semibold text-text-heading">Konten Teks</p>
                <p class="mt-1 text-label-md text-text-muted">
                    {{ textRequired ? 'Teks materi wajib diisi.' : 'Teks materi bersifat opsional.' }}
                </p>
            </div>
            <RichTextEditor
                v-model="form.materi_baru_isi_teks"
                placeholder="Tulis isi materi soal di sini..."
                min-height="180px"
            />
            <p v-if="form.errors.materi_baru_isi_teks" class="mt-1.5 text-xs text-error-red">
                {{ form.errors.materi_baru_isi_teks }}
            </p>
        </div>

        <div v-if="showAudio" class="rounded-xl border border-outline-variant/50 bg-surface-white p-4">
            <FileUpload
                v-model="form.materi_baru_berkas_audio"
                :label="`Audio Materi${audioRequired ? ' — Wajib' : ' — Opsional'}`"
                hint="MP3, WAV, OGG, atau M4A • Maks. 50MB"
                placeholder="Pilih file audio"
                accept=".mp3,.wav,.ogg,.m4a"
                media-type="audio"
                variant="dropzone"
                :required="audioRequired"
                :error="form.errors.materi_baru_berkas_audio"
            />
        </div>
        <div v-if="showImage" class="rounded-xl border border-outline-variant/50 bg-surface-white p-4">
            <FileUpload
                v-model="form.materi_baru_berkas_gambar"
                :label="`Gambar Pendukung${imageRequired ? ' — Wajib' : ' — Opsional'}`"
                hint="JPG, PNG, atau WebP • Maks. 20MB"
                placeholder="Pilih gambar pendukung"
                accept=".jpg,.jpeg,.png,.webp"
                media-type="image"
                variant="dropzone"
                preview-in-dropzone
                :required="imageRequired"
                :error="form.errors.materi_baru_berkas_gambar"
            />
        </div>

        <div v-if="!showText && !showAudio && !showImage" class="rounded-xl border border-warning/30 bg-warning-container/30 px-4 py-3 text-label-md text-on-warning-container md:col-span-2">
            Policy materi belum memiliki media yang diizinkan.
        </div>
    </div>
</template>
