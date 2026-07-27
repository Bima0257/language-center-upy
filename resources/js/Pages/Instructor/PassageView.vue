<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconPlus, IconEdit, IconTrash, IconFileDescription, IconHeadphones, IconPhoto, IconBook, IconX, IconBooks, IconUpload } from '@tabler/icons-vue';
import { ref, watch } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const confirm = useConfirm();

const props = defineProps({
    passages: { type: Object, default: () => ({ data: [] }) },
});

const showForm = ref(false);
const editingPassage = ref(null);
const audioPreviewUrl = ref(null);
const imagePreviewUrl = ref(null);
const audioFileInput = ref(null);
const imageFileInput = ref(null);

const form = useForm({
    title: '',
    type: 'text',
    content_text: '',
    audio_file: null,
    image_file: null,
    language: 'en',
});

const typeLabels = {
    text: 'Teks (Reading)', audio: 'Audio (Listening)', image: 'Gambar (Photo)', prompt: 'Prompt (Speaking/Writing)',
};

function openCreate() {
    editingPassage.value = null;
    form.reset();
    form.type = 'text';
    audioPreviewUrl.value = null;
    imagePreviewUrl.value = null;
    showForm.value = true;
}

function openEdit(passage) {
    editingPassage.value = passage;
    form.title = passage.title;
    form.type = passage.type || 'text';
    form.content_text = passage.content_text || '';
    form.audio_file = null;
    form.image_file = null;
    form.language = passage.language || 'en';
    audioPreviewUrl.value = passage.audio_url ? '/storage/' + passage.audio_url : null;
    imagePreviewUrl.value = passage.image_url ? '/storage/' + passage.image_url : null;
    showForm.value = true;
}

function closeForm() {
    showForm.value = false;
    editingPassage.value = null;
}

function onAudioFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.audio_file = file;
    audioPreviewUrl.value = URL.createObjectURL(file);
}

function onImageFileChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.image_file = file;
    imagePreviewUrl.value = URL.createObjectURL(file);
}

function removeAudio() {
    form.audio_file = null;
    audioPreviewUrl.value = null;
    if (audioFileInput.value) audioFileInput.value.value = '';
}

function removeImage() {
    form.image_file = null;
    imagePreviewUrl.value = null;
    if (imageFileInput.value) imageFileInput.value.value = '';
}

watch(() => form.type, () => {
    form.audio_file = null;
    form.image_file = null;
    audioPreviewUrl.value = null;
    imagePreviewUrl.value = null;
});

function submit() {
    form.transform(data => ({
        ...data,
        audio_file: data.audio_file || undefined,
        image_file: data.image_file || undefined,
    }));

    if (editingPassage.value) {
        form.put(route('content-library.passages.update', editingPassage.value.id), {
            preserveScroll: true,
            onSuccess: closeForm,
        });
    } else {
        form.post(route('content-library.passages.store'), {
            preserveScroll: true,
            onSuccess: closeForm,
        });
    }
}

async function deletePassage(id, title) {
    if (!await confirm.confirm(`Hapus passage: "${title}"?`)) return;
    router.delete(route('content-library.passages.destroy', id), { preserveScroll: true });
}

function getMediaIcon(passage) {
    if (passage.type === 'audio' || passage.audio_url) return 'audio';
    if (passage.type === 'image' || passage.image_url) return 'image';
    if (passage.type === 'prompt') return 'prompt';
    return 'text';
}

function previewText(text, max) {
    if (!text) return '';
    return text.length > max ? text.substring(0, max) + '...' : text;
}
</script>

<template>
    <Head title="Passage" />
    <DashboardLayout title="Kelola Passage">
        <div class="flex items-center justify-between mb-6">
            <div class="flex items-center gap-4">
                <Link :href="route('content-library.index')" class="text-secondary text-label-md font-medium hover:underline">← Kembali ke Bank Soal</Link>
                <p class="text-text-body text-body-md">{{ passages.total || 0 }} passage</p>
            </div>
            <button @click="openCreate"
                    class="flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                <IconPlus :size="18" /> Passage Baru
            </button>
        </div>

        <div v-if="passages.data?.length === 0" class="bg-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
            <IconBook class="mx-auto text-text-muted mb-3" :size="48" stroke="1.5" />
            <p class="text-text-body text-body-md">Belum ada passage.</p>
            <button @click="openCreate" class="mt-4 inline-flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all">
                <IconPlus :size="16" /> Buat Passage Pertama
            </button>
        </div>

        <div v-else class="space-y-3">
            <div v-for="p in passages.data" :key="p.id"
                 class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 hover:border-secondary/50 transition-all">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3 min-w-0">
                        <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl shrink-0 mt-1"
                              :class="getMediaIcon(p) === 'audio' ? 'bg-pastel-purple/30' : getMediaIcon(p) === 'image' ? 'bg-pastel-peach/30' : getMediaIcon(p) === 'prompt' ? 'bg-pastel-blue/30' : 'bg-surface-container-low'">
                            <IconHeadphones v-if="getMediaIcon(p) === 'audio'" :size="18" class="text-primary" />
                            <IconPhoto v-else-if="getMediaIcon(p) === 'image'" :size="18" class="text-primary" />
                            <IconFileDescription v-else-if="getMediaIcon(p) === 'prompt'" :size="18" class="text-primary" />
                            <IconBook v-else :size="18" class="text-text-muted" />
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <h3 class="text-body-md font-semibold text-primary">{{ p.title }}</h3>
                                <span class="inline-block bg-pastel-purple/30 text-primary px-2 py-0.5 rounded-full text-label-md">{{ typeLabels[p.type] || 'Teks' }}</span>
                            </div>
                            <p v-if="p.content_text" class="text-label-md text-text-body mb-2 line-clamp-2">{{ previewText(p.content_text, 120) }}</p>
                            <template v-else-if="p.audio_url">
                                <div class="flex items-center gap-2 mb-1">
                                    <IconHeadphones :size="14" class="text-text-muted" />
                                    <audio :src="'/storage/' + p.audio_url" controls class="h-8 w-full max-w-xs" preload="none"></audio>
                                </div>
                            </template>
                            <template v-else-if="p.image_url">
                                <div class="flex items-center gap-2 mb-1">
                                    <IconPhoto :size="14" class="text-text-muted" />
                                    <img :src="'/storage/' + p.image_url" class="h-12 rounded-lg object-cover" :alt="p.title" />
                                </div>
                            </template>
                            <p class="text-label-md text-text-muted mt-1">
                                {{ p.questions_count || 0 }} soal
                                <span v-if="p.language"> | {{ p.language.toUpperCase() }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <Link :href="route('content-library.index', { passage_id: p.id })"
                              class="flex items-center gap-1.5 px-3 py-2 bg-surface-container-lowest border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:border-secondary transition-all"
                              title="Lihat Soal">
                            <IconBooks :size="16" /> {{ p.questions_count || 0 }} Soal
                        </Link>
                        <Link :href="route('content-library.create', { passage_id: p.id })"
                              class="flex items-center gap-1.5 px-3 py-2 bg-primary-container text-white rounded-full text-label-md font-medium hover:bg-primary transition-all"
                              title="Tambah Soal">
                            <IconPlus :size="16" /> Tambah Soal
                        </Link>
                        <button @click="openEdit(p)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit">
                            <IconEdit :size="18" />
                        </button>
                        <button @click="deletePassage(p.id, p.title)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus">
                            <IconTrash :size="18" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="passages.total > passages.per_page" class="flex justify-center mt-6 gap-2">
            <Link v-for="link in passages.links" :key="link.label"
                  :href="link.url || '#'"
                  class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                  :class="link.active ? 'bg-primary-container text-white' : 'bg-surface-white border border-outline-variant text-text-body hover:bg-surface-container-low'"
                  v-html="link.label" />
        </div>

        <!-- Modal Form -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-8 shadow-soft w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">{{ editingPassage ? 'Edit Passage' : 'Passage Baru' }}</h2>
                    <button @click="closeForm" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Judul *</label>
                        <input type="text" v-model="form.title" required
                               placeholder="Email: Staff Meeting Reminder / Percakapan di Kafe / ..."
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.title" class="text-error-red text-xs mt-1">{{ form.errors.title }}</p>
                    </div>

                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Tipe Passage *</label>
                        <select v-model="form.type" required
                                class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                            <option v-for="(label, key) in typeLabels" :key="key" :value="key">{{ label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Bahasa *</label>
                        <select v-model="form.language" required
                                class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                            <option value="en">English</option>
                            <option value="ja">Japanese</option>
                            <option value="zh">Chinese</option>
                            <option value="ko">Korean</option>
                            <option value="id">Indonesian</option>
                        </select>
                    </div>

                    <!-- TEXT / PROMPT: content_text -->
                    <div v-if="form.type === 'text' || form.type === 'prompt'">
                        <label class="text-label-md font-medium text-primary block mb-1.5">
                            {{ form.type === 'prompt' ? 'Teks Prompt' : 'Teks Bacaan' }}
                            <span class="text-text-muted">{{ form.type === 'prompt' ? '(Speaking/Writing)' : '(Reading)' }}</span>
                        </label>
                        <textarea v-model="form.content_text" rows="6"
                                  :placeholder="form.type === 'prompt' ? 'Tulis instruksi atau prompt...' : 'Tulis isi bacaan lengkap di sini...'"
                                  class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"></textarea>
                        <p v-if="form.errors.content_text" class="text-error-red text-xs mt-1">{{ form.errors.content_text }}</p>
                    </div>

                    <!-- AUDIO: file upload -->
                    <div v-if="form.type === 'audio'">
                        <label class="text-label-md font-medium text-primary block mb-1.5">Audio</label>
                        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-6 text-center hover:border-secondary transition-colors cursor-pointer"
                             :class="{ 'border-error-red': form.errors.audio_file }"
                             @click="audioFileInput?.click()">
                            <IconUpload class="mx-auto text-text-muted mb-2" :size="24" stroke="1.5" />
                            <p class="text-label-md text-text-body font-medium">
                                {{ form.audio_file ? form.audio_file.name : 'Klik untuk upload file audio' }}
                            </p>
                            <p class="text-xs text-text-muted mt-1">MP3, WAV, OGG, M4A (max 20MB)</p>
                        </div>
                        <p v-if="form.errors.audio_file" class="text-error-red text-xs mt-1">{{ form.errors.audio_file }}</p>
                        <input ref="audioFileInput" type="file" accept="audio/*" @change="onAudioFileChange" class="hidden" />

                        <div v-if="audioPreviewUrl" class="relative mt-3 bg-surface-container-low rounded-xl p-3">
                            <audio :src="audioPreviewUrl" controls class="w-full h-10" preload="metadata"></audio>
                            <button type="button" @click="removeAudio"
                                    class="absolute -top-2 -right-2 w-6 h-6 bg-error-red text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors shadow-sm"
                                    title="Hapus audio">
                                <IconX :size="14" />
                            </button>
                        </div>
                    </div>

                    <!-- IMAGE: file upload -->
                    <div v-if="form.type === 'image'">
                        <label class="text-label-md font-medium text-primary block mb-1.5">Gambar</label>
                        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-6 text-center hover:border-secondary transition-colors cursor-pointer"
                             :class="{ 'border-error-red': form.errors.image_file }"
                             @click="imageFileInput?.click()">
                            <IconUpload class="mx-auto text-text-muted mb-2" :size="24" stroke="1.5" />
                            <p class="text-label-md text-text-body font-medium">
                                {{ form.image_file ? form.image_file.name : 'Klik untuk upload gambar' }}
                            </p>
                            <p class="text-xs text-text-muted mt-1">JPG, PNG, WebP (max 5MB)</p>
                        </div>
                        <p v-if="form.errors.image_file" class="text-error-red text-xs mt-1">{{ form.errors.image_file }}</p>
                        <input ref="imageFileInput" type="file" accept="image/*" @change="onImageFileChange" class="hidden" />

                        <div v-if="imagePreviewUrl" class="relative mt-3 bg-surface-container-low rounded-xl p-3">
                            <img :src="imagePreviewUrl" class="max-h-40 rounded-lg object-contain mx-auto" :alt="form.title" />
                            <button type="button" @click="removeImage"
                                    class="absolute -top-2 -right-2 w-6 h-6 bg-error-red text-white rounded-full flex items-center justify-center hover:bg-red-700 transition-colors shadow-sm"
                                    title="Hapus gambar">
                                <IconX :size="14" />
                            </button>
                        </div>
                    </div>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <button type="submit" :disabled="form.processing"
                                class="flex-1 bg-primary-container text-white py-3.5 rounded-full text-title-lg font-semibold hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                            {{ form.processing ? 'Menyimpan...' : (editingPassage ? 'Simpan Perubahan' : 'Buat Passage') }}
                        </button>
                        <button type="button" @click="closeForm"
                                class="px-8 py-3.5 border border-outline-variant rounded-full text-label-md font-medium text-text-body hover:bg-surface-container-low transition-all">
                            Batal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
