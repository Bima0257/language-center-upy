<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconPlus } from '@tabler/icons-vue';

const props = defineProps({
    form: { type: Object, required: true },
    jenisTes: { type: Array, default: () => [] },
    lockJenisTes: { type: Boolean, default: false },
});

const emit = defineEmits(['submit']);
</script>

<template>
    <BaseCard class="h-fit">
        <h3 class="text-title-lg font-semibold text-text-heading mb-4">Tambah Interpretasi</h3>
        <form @submit.prevent="emit('submit')" class="space-y-4">
            <div>
                <DropDown
                    v-model="props.form.jenis_tes_id"
                    :options="props.jenisTes"
                    label="Tipe Ujian"
                    placeholder="Pilih tipe ujian"
                    option-label="nama"
                    option-value="id"
                    :disabled="lockJenisTes"
                />
                <p v-if="props.form.errors.jenis_tes_id" class="text-error-red text-xs mt-1">{{ props.form.errors.jenis_tes_id }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1">Min Skor</label>
                    <input type="number" v-model="props.form.skor_minimum" required min="0"
                           class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1">Max Skor</label>
                    <input type="number" v-model="props.form.skor_maksimum" required min="0"
                           class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1">CEFR</label>
                    <input type="text" v-model="props.form.tingkat_cefr" required placeholder="B1+" maxlength="10"
                           class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                </div>
                <div>
                    <label class="text-label-md font-medium text-primary block mb-1">Label</label>
                    <input type="text" v-model="props.form.label_tingkat" required placeholder="Good"
                           class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                </div>
            </div>
            <div>
                <label class="text-label-md font-medium text-primary block mb-1">Deskripsi</label>
                <input type="text" v-model="props.form.deskripsi" placeholder="Opsional"
                       class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
            </div>
            <label class="flex items-center gap-2 cursor-pointer">
                <input type="checkbox" v-model="props.form.lulus" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                <span class="text-label-md text-text-body">Merupakan nilai LULUS</span>
            </label>
            <BaseButton type="submit" :disabled="props.form.processing" class="w-full">
                <IconPlus :size="18" /> {{ props.form.processing ? 'Menyimpan...' : 'Simpan' }}
            </BaseButton>
        </form>
    </BaseCard>
</template>
