<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconPlus, IconEdit, IconTrash, IconCheck } from '@tabler/icons-vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    examTypes: { type: Array, default: () => [] },
    interpretations: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    exam_type_id: '',
    min_score: '',
    max_score: '',
    cefr_level: '',
    level_label: '',
    is_passing: true,
    description: '',
});

const editing = ref(null);
const editForm = useForm({
    exam_type_id: '',
    min_score: '',
    max_score: '',
    cefr_level: '',
    level_label: '',
    is_passing: true,
    description: '',
});

function examTypeName(id) {
    return props.examTypes.find(t => t.id === id)?.name || '-';
}

function submit() {
    form.post(route('admin.master-data.score-interpretations.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function startEdit(interp) {
    editing.value = interp.id;
    editForm.exam_type_id = interp.exam_type_id;
    editForm.min_score = interp.min_score;
    editForm.max_score = interp.max_score;
    editForm.cefr_level = interp.cefr_level;
    editForm.level_label = interp.level_label;
    editForm.is_passing = !!interp.is_passing;
    editForm.description = interp.description || '';
}

function saveEdit(interp) {
    editForm.put(route('admin.master-data.score-interpretations.update', interp.id), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
}

async function destroy(interp) {
    if (!await confirm.confirm(`Hapus interpretasi ${interp.cefr_level} (${interp.min_score}-${interp.max_score})?`)) return;
    router.delete(route('admin.master-data.score-interpretations.destroy', interp.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Master Data - Interpretasi Skor" />
    <DashboardLayout title="Master Data Interpretasi Skor">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <BaseCard class="h-fit">
                <h3 class="text-title-lg font-semibold text-primary mb-4">Tambah Interpretasi</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <DropDown
                            v-model="form.exam_type_id"
                            :options="examTypes"
                            label="Tipe Ujian"
                            placeholder="Pilih tipe ujian"
                            option-label="name"
                            option-value="id"
                        />
                        <p v-if="form.errors.exam_type_id" class="text-error-red text-xs mt-1">{{ form.errors.exam_type_id }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1">Min Skor</label>
                            <input type="number" v-model="form.min_score" required min="0"
                                   class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1">Max Skor</label>
                            <input type="number" v-model="form.max_score" required min="0"
                                   class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1">CEFR</label>
                            <input type="text" v-model="form.cefr_level" required placeholder="B1+" maxlength="10"
                                   class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1">Label</label>
                            <input type="text" v-model="form.level_label" required placeholder="Good"
                                   class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Deskripsi</label>
                        <input type="text" v-model="form.description" placeholder="Opsional"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_passing" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <span class="text-label-md text-text-body">Merupakan nilai LULUS</span>
                    </label>
                    <BaseButton type="submit" :disabled="form.processing" class="w-full">
                        <IconPlus :size="18" /> {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </BaseButton>
                </form>
            </BaseCard>

            <BaseCard :padding="false" class="lg:col-span-2 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Rentang Skor</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">CEFR</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Label</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Kelulusan</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="interp in interpretations" :key="interp.id">
                                <tr v-if="editing !== interp.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50">
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-primary">{{ interp.min_score }} - {{ interp.max_score }}</p>
                                        <p class="text-text-muted text-label-md">{{ examTypeName(interp.exam_type_id) }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-body-md text-text-body">{{ interp.cefr_level }}</td>
                                    <td class="px-5 py-4">
                                        <p class="text-body-md text-text-body">{{ interp.level_label }}</p>
                                        <p v-if="interp.description" class="text-text-muted text-label-md">{{ interp.description }}</p>
                                    </td>
                                    <td class="px-5 py-4">
                                        <BaseBadge :variant="interp.is_passing ? 'success' : 'danger'">
                                            {{ interp.is_passing ? 'LULUS' : 'TIDAK LULUS' }}
                                        </BaseBadge>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <button @click="startEdit(interp)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                                            <button @click="destroy(interp)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else class="border-b border-outline-variant/20 bg-pastel-blue/10">
                                    <td colspan="5" class="px-5 py-4">
                                        <div class="grid grid-cols-2 md:grid-cols-5 gap-3">
                                            <input type="number" v-model="editForm.min_score" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <input type="number" v-model="editForm.max_score" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <input type="text" v-model="editForm.cefr_level" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <input type="text" v-model="editForm.level_label" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <div class="flex items-center gap-2">
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="checkbox" v-model="editForm.is_passing" class="w-4 h-4" />
                                                    <span class="text-label-md text-text-body">Lulus</span>
                                                </label>
                                                <button @click="saveEdit(interp)" class="p-2 text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-300 transition-colors" title="Simpan"><IconCheck :size="18" /></button>
                                                <button @click="editing = null" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Batal"><IconTrash :size="18" /></button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="interpretations.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-text-muted text-body-md">Belum ada data interpretasi skor.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
