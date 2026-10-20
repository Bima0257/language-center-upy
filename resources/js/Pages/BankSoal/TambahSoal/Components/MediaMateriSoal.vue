<script setup>
import FileUpload from '@/Components/Shared/FileUpload.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';

defineProps({
    form: { type: Object, required: true },
    hasListeningQuestion: { type: Boolean, default: false },
});
</script>

<template>
    <template v-if="hasListeningQuestion">
        <FileUpload
             v-model="form.materi_baru_berkas_audio"
            label="File Audio (mp3/wav/m4a, max 50MB)"
            placeholder="Klik untuk upload audio"
            accept=".mp3,.wav,.ogg,.m4a"
            media-type="audio"
            :required="true"
             :error="form.errors.materi_baru_berkas_audio"
        />
        <FileUpload
             v-model="form.materi_baru_berkas_gambar"
            label="Gambar Pendukung (opsional)"
            placeholder="Klik untuk upload gambar"
            accept=".jpg,.jpeg,.png,.webp"
            media-type="image"
             :error="form.errors.materi_baru_berkas_gambar"
        />
    </template>

    <template v-else>
        <div>
            <label class="text-label-md font-medium text-primary block mb-1.5">Konten Teks</label>
             <RichTextEditor v-model="form.materi_baru_isi_teks" placeholder="Tulis isi teks bacaan di sini..." />
        </div>
        <FileUpload
             v-model="form.materi_baru_berkas_gambar"
            label="File Gambar (opsional, jpg/png/webp, max 20MB, otomatis dikompres)"
            placeholder="Klik untuk upload gambar"
            accept=".jpg,.jpeg,.png,.webp"
            media-type="image"
             :error="form.errors.materi_baru_berkas_gambar"
        />
    </template>
</template>
