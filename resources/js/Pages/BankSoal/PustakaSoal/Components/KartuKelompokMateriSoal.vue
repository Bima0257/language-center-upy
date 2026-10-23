<script setup>
import { Link } from '@inertiajs/vue3';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import FileUpload from '@/Components/Shared/FileUpload.vue';
import TabelSoal from './TabelSoal.vue';
import FormTambahSoalCepat from './FormTambahSoalCepat.vue';
import PenampilMediaMateriSoal from './PenampilMediaMateriSoal.vue';
import { IconFileDescription, IconEye, IconPlus, IconEdit, IconTrash, IconCheck } from '@tabler/icons-vue';

defineProps({
    group: { type: Object, required: true },
    materiSoalForm: { type: Object, required: true },
    editingMateriSoalId: { type: [String, null], default: null },
    keterampilanNameFn: { type: Function, default: () => '' },
    bankNameFn: { type: Function, default: () => '' },
    optionKeys: { type: Array, default: () => [] },
    quickForm: { type: Object, required: true },
    bankSoal: { type: Array, default: () => [] },
    addingMateriSoalId: { type: [String, null], default: null },
    quickBagianSoalFn: { type: Function, default: () => [] },
    quickIsAudio: { type: Boolean, default: false },
    labelKeterampilanFn: { type: Function, default: () => '' },
    toggleMateriSoalFn: { type: Function, default: () => {} },
    materiSoalTypeLabelFn: { type: Function, default: () => 'Teks' },
    isMateriSoalExpandedFn: { type: Function, default: () => false },
    attached: { type: Boolean, default: false },
});

defineEmits([
    'start-edit',
    'cancel-edit',
    'save-materi-soal',
    'add-soal',
    'close-add',
    'save-quick',
    'soal-delete',
    'delete-materi-soal',
]);
</script>

<template>
    <BaseCard
        :padding="false"
        class="overflow-hidden"
        :class="attached ? 'rounded-none border-0 shadow-none' : ''"
    >
        <div class="px-5 py-4 bg-surface-container-low/60 border-b border-outline-variant/30">
            <!-- TAMPILAN HEADER NORMAL -->
            <template v-if="editingMateriSoalId !== group.materiSoal.id">
                <div class="flex items-center gap-2 flex-wrap">
                    <IconFileDescription :size="20" class="text-secondary shrink-0" />
                    <h3 class="text-title-md font-semibold text-text-heading">{{ group.materiSoal.judul }}</h3>
                    <span class="inline-block bg-pastel-purple/50 text-primary px-2.5 py-0.5 rounded-full text-label-md font-medium">
                        {{ materiSoalTypeLabelFn(group.materiSoal) }}
                    </span>
                    <span class="text-label-md text-text-muted">{{ group.soal.length }} soal</span>
                    <div class="ml-auto flex items-center gap-1">
                        <Link v-if="group.soal.length" :href="route('pustaka-soal.pratinjau', group.soal[0].id)"
                               class="p-1.5 text-text-muted hover:text-secondary transition-colors" title="Pratinjau soal materi ini">
                            <IconEye :size="16" />
                        </Link>
                        <button @click="$emit('add-soal', group.materiSoal)"
                                class="flex items-center gap-1 px-2 py-1.5 rounded-lg text-label-md font-medium text-secondary hover:bg-surface-container-highest transition-colors"
                                title="Tambah soal ke materi soal ini">
                            <IconPlus :size="16" /> Soal
                        </button>
                        <button @click="$emit('start-edit', group.materiSoal)" class="p-1.5 text-text-muted hover:text-secondary transition-colors" title="Ubah materiSoal">
                            <IconEdit :size="16" />
                        </button>
                        <button @click="$emit('delete-materi-soal', group.materiSoal)"
                                :disabled="group.soal.length > 0"
                                :title="group.soal.length > 0 ? `Tidak bisa dihapus — materi soal dipakai ${group.soal.length} soal` : 'Hapus materi soal'"
                                class="p-1.5 text-text-muted hover:text-error-red transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-text-muted">
                            <IconTrash :size="16" />
                        </button>
                    </div>
                </div>

                <PenampilMediaMateriSoal
                    :materi-soal="group.materiSoal"
                    :expanded="isMateriSoalExpandedFn(group.materiSoal.id)"
                    @toggle-expand="toggleMateriSoalFn(group.materiSoal.id)"
                />
            </template>

            <!-- FORM EDIT PASSAGE INLINE -->
            <template v-else>
                <p class="text-label-md font-semibold text-text-heading mb-3">Ubah Materi Soal</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Judul Materi Soal</label>
                        <input type="text" v-model="materiSoalForm.judul" placeholder="Judul materiSoal"
                               class="w-full px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="materiSoalForm.errors.judul" class="text-error-red text-xs mt-1">{{ materiSoalForm.errors.judul }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Konten Teks</label>
                        <RichTextEditor v-model="materiSoalForm.isi_teks" placeholder="Isi teks materiSoal..." />
                        <p v-if="materiSoalForm.errors.isi_teks" class="text-error-red text-xs mt-1">{{ materiSoalForm.errors.isi_teks }}</p>
                    </div>
                </div>

                <div class="mt-3">
                    <FileUpload
                        v-model="materiSoalForm.berkas_audio"
                        label="Ganti File Audio (mp3/wav/m4a, max 50MB) — kosongkan jika tidak diganti"
                        accept=".mp3,.wav,.ogg,.m4a"
                        media-type="audio"
                        :preview-url="group.materiSoal.url_audio ? '/media/' + group.materiSoal.url_audio : null"
                        :error="materiSoalForm.errors.berkas_audio"
                    />
                </div>

                <div class="mt-3">
                    <FileUpload
                        v-model="materiSoalForm.berkas_gambar"
                        label="Ganti File Gambar (jpg/png/webp, max 20MB, otomatis dikompres) — kosongkan jika tidak diganti"
                        accept=".jpg,.jpeg,.png,.webp"
                        media-type="image"
                        :preview-url="group.materiSoal.url_gambar ? '/media/' + group.materiSoal.url_gambar : null"
                        :error="materiSoalForm.errors.berkas_gambar"
                    />
                </div>

                <div class="flex items-center gap-2 mt-4">
                    <BaseButton size="xs" @click="$emit('save-materi-soal', group.materiSoal)" :disabled="materiSoalForm.processing">
                        <IconCheck :size="16" /> {{ materiSoalForm.processing ? 'Menyimpan...' : 'Simpan' }}
                    </BaseButton>
                    <BaseButton variant="secondary" size="xs" @click="$emit('cancel-edit')">
                        Batal
                    </BaseButton>
                </div>
            </template>
        </div>

        <!-- QUICK ADD SOAL KE PASSAGE -->
        <FormTambahSoalCepat
            v-if="addingMateriSoalId === group.materiSoal.id"
            :form="quickForm"
            :materi-soal-judul="group.materiSoal.judul"
            :bank-soal="bankSoal"
            :quick-bagian-soal-fn="quickBagianSoalFn"
            :quick-is-audio="quickIsAudio"
            :label-keterampilan-fn="labelKeterampilanFn"
            :option-keys="optionKeys"
            @save="$emit('save-quick')"
            @cancel="$emit('close-add')"
        />

        <TabelSoal
            :soal="group.soal"
            :keterampilan-name-fn="keterampilanNameFn"
            :bank-name-fn="bankNameFn"
            @delete="(...args) => $emit('soal-delete', ...args)"
        />
    </BaseCard>
</template>
