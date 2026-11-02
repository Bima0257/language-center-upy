<script setup>
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import KebijakanMateriSoalFields from './KebijakanMateriSoalFields.vue';
import { IconX } from '@tabler/icons-vue';

defineProps({
    show: { type: Boolean, default: false },
    creating: { type: Boolean, default: false },
    form: { type: Object, required: true },
    jenisTes: { type: Array, default: () => [] },
    lockJenisTes: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);
</script>

<template>
    <BaseModal
        :show="show"
        @close="emit('close')"
        max-width="2xl"
        aria-labelledby="keterampilan-modal-title"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-8 py-4">
                    <div class="flex items-center justify-between">
                         <h2 id="keterampilan-modal-title" class="text-headline-md font-bold text-text-heading">{{ creating ? 'Tambah Keterampilan' : 'Ubah Keterampilan' }}</h2>
                         <button type="button" aria-label="Tutup form keterampilan" @click="emit('close')" class="p-2 text-text-muted hover:text-primary transition-colors">
                            <IconX :size="20" />
                        </button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>
        <template #default>
            <form id="form-keterampilan" @submit.prevent="emit('submit')" class="space-y-5 px-8 pb-8 pt-6">
                <BaseFormGrid>
                    <div>
                        <DropDown
                            v-model="form.jenis_tes_id"
                            :options="jenisTes"
                            label="Jenis Tes *"
                            placeholder="Pilih jenis tes"
                            option-label="nama"
                            option-value="id"
                            :disabled="!creating || lockJenisTes"
                            empty-message="Belum ada jenis tes aktif."
                        />
                        <p v-if="form.errors.jenis_tes_id" class="text-error-red text-xs mt-1">{{ form.errors.jenis_tes_id }}</p>
                        <p v-if="creating" class="text-label-md text-text-muted mt-1">Kode keterampilan dibuat otomatis setelah disimpan.</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-text-heading block mb-1.5">Nama Keterampilan *</label>
                        <input type="text" v-model="form.nama" required placeholder="Reading"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.nama" class="text-error-red text-xs mt-1">{{ form.errors.nama }}</p>
                    </div>
                </BaseFormGrid>
                <div v-if="!creating && form.kode" class="rounded-2xl bg-surface-container-low px-4 py-3">
                    <p class="text-label-md text-text-muted">Kode Gabungan</p>
                    <p class="text-body-md font-semibold text-text-heading mt-1">{{ form.kode }}</p>
                </div>
                <div>
                    <label class="text-label-md font-medium text-text-heading block mb-1.5">Deskripsi</label>
                    <RichTextEditor v-model="form.deskripsi" placeholder="Jelaskan keterampilan ini..." :min-height="'120px'" />
                    <p v-if="form.errors.deskripsi" class="text-error-red text-xs mt-1">{{ form.errors.deskripsi }}</p>
                </div>
                <KebijakanMateriSoalFields :form="form" />
                <BaseToggle v-model="form.aktif" label="Keterampilan aktif" />
            </form>
        </template>
        <template #footer>
            <BaseModalFooter>
                <div class="px-8 pb-3">
                    <BaseFormActions
                        form="form-keterampilan"
                        :processing="form.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
