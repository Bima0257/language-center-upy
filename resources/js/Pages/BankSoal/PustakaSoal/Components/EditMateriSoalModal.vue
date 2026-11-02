<script setup>
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import FileUpload from '@/Components/Shared/FileUpload.vue';
import { IconTrash, IconX } from '@tabler/icons-vue';
import { computed } from 'vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    materiSoal: { type: Object, required: true },
    form: { type: Object, required: true },
    policy: { type: Object, default: null },
});

const emit = defineEmits(['close', 'submit', 'remove-media']);
const showAudioUpload = computed(() => props.policy?.aturan_media?.audio !== 'forbidden');
</script>

<template>
    <BaseModal
        :show="show"
        max-width="2xl"
        aria-labelledby="edit-materi-soal-modal-title"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                        <h2 id="edit-materi-soal-modal-title" class="text-headline-md font-bold text-text-heading">
                            Ubah Materi Soal
                        </h2>
                        <button
                            type="button"
                            aria-label="Tutup edit materi soal"
                            class="p-2 text-text-muted transition-colors hover:text-primary"
                            @click="emit('close')"
                        >
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <form id="edit-materi-soal-form" class="space-y-5 px-8 pb-8 pt-6" @submit.prevent="emit('submit')">
                <BaseFormGrid>
                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-label-md font-medium text-text-heading">
                            Judul Materi Soal *
                        </label>
                        <input
                            v-model="form.judul"
                            type="text"
                            required
                            placeholder="Judul materi soal"
                            class="w-full rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-3.5 text-body-md text-text-body focus:border-secondary focus:outline-none"
                        />
                        <p v-if="form.errors.judul" class="mt-1 text-xs text-error-red">
                            {{ form.errors.judul }}
                        </p>
                    </div>

                    <div class="sm:col-span-2">
                        <label class="mb-1.5 block text-label-md font-medium text-text-heading">
                            Konten Teks
                        </label>
                        <RichTextEditor
                            v-model="form.isi_teks"
                            placeholder="Isi teks materi soal..."
                        />
                        <p v-if="form.errors.isi_teks" class="mt-1 text-xs text-error-red">
                            {{ form.errors.isi_teks }}
                        </p>
                    </div>

                    <div v-if="showAudioUpload || materiSoal.url_audio" class="space-y-4 sm:col-span-2">
                        <div>
                            <FileUpload
                                v-if="showAudioUpload"
                                v-model="form.berkas_audio"
                                label="Ganti File Audio"
                                hint="MP3, WAV, OGG, atau M4A • Maks. 50MB"
                                accept=".mp3,.wav,.ogg,.m4a"
                                media-type="audio"
                                :preview-url="materiSoal.url_audio ? '/media/' + materiSoal.url_audio : null"
                                :error="form.errors.berkas_audio"
                            />
                            <p
                                v-else
                                class="rounded-xl border border-warning/30 bg-warning-container/30 px-4 py-3 text-label-md text-on-warning-container"
                            >
                                Audio tersimpan tidak sesuai dengan aturan keterampilan ini. Hapus audio sebelum menyimpan perubahan.
                            </p>
                            <button
                                v-if="materiSoal.url_audio"
                                type="button"
                                class="mt-2 inline-flex items-center gap-1.5 text-label-md text-error-red transition-colors hover:text-error-red/80"
                                @click="emit('remove-media', 'audio')"
                            >
                                <IconTrash :size="15" />
                                Hapus audio tersimpan
                            </button>
                        </div>

                        <div>
                            <FileUpload
                                v-model="form.berkas_gambar"
                                label="Ganti File Gambar"
                                hint="JPG, PNG, atau WebP • Maks. 20MB"
                                accept=".jpg,.jpeg,.png,.webp"
                                media-type="image"
                                :preview-url="materiSoal.url_gambar ? '/media/' + materiSoal.url_gambar : null"
                                :error="form.errors.berkas_gambar"
                            />
                            <button
                                v-if="materiSoal.url_gambar"
                                type="button"
                                class="mt-2 inline-flex items-center gap-1.5 text-label-md text-error-red transition-colors hover:text-error-red/80"
                                @click="emit('remove-media', 'image')"
                            >
                                <IconTrash :size="15" />
                                Hapus gambar tersimpan
                            </button>
                        </div>
                    </div>
                </BaseFormGrid>

            </form>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="edit-materi-soal-form"
                        :processing="form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
