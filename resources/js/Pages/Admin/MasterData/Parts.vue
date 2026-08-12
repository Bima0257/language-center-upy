<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DataTable from '@/Components/Shared/DataTable.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconPlus, IconEdit, IconTrash, IconX } from '@tabler/icons-vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    parts: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    skill_id: '',
    name: '',
    order: 1,
    directions: '',
    is_active: true,
});

const editForm = useForm({
    skill_id: '',
    name: '',
    order: 1,
    directions: '',
    is_active: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingPart = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

const skillFilter = ref('');

const filteredParts = computed(() => {
    if (!skillFilter.value) return props.parts;
    return props.parts.filter(p => String(p.skill_id) === String(skillFilter.value));
});

const columns = [
    { key: 'name', label: 'Nama', sortable: true, className: 'font-medium text-primary' },
    { key: 'skill', label: 'Skill', render: (val) => val?.name || '-' },
    { key: 'order', label: 'Urutan', sortable: true, render: (val) => val ?? 1 },
    { key: 'questions_count', label: 'Soal', render: (val) => val ?? 0 },
    { key: 'is_active', label: 'Status', badge: true, render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

function openCreate() {
    creating.value = true;
    editingPart.value = null;
    form.clearErrors();
    form.reset();
    form.skill_id = skillFilter.value || '';
    if (props.skills.length === 1) {
        form.skill_id = String(props.skills[0].id);
    }
    showModal.value = true;
}

function submit() {
    form.post(route('admin.master-data.parts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

function startEdit(part) {
    creating.value = false;
    editingPart.value = part;
    editForm.clearErrors();
    editForm.reset();
    editForm.skill_id = String(part.skill_id);
    editForm.name = part.name;
    editForm.order = part.order ?? 1;
    editForm.directions = part.directions || '';
    editForm.is_active = !!part.is_active;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.master-data.parts.update', editingPart.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingPart.value = null;
    creating.value = false;
    editForm.reset();
}

async function destroy(part) {
    if (!await confirm.confirm(`Hapus part "${part.name}"?`)) return;
    router.delete(route('admin.master-data.parts.destroy', part.id), { preserveScroll: true });
}

function skillName(id) {
    return props.skills.find(s => String(s.id) === String(id))?.name || '';
}

function skillCategoryName(id) {
    const s = props.skills.find(s => String(s.id) === String(id));
    return s?.exam_type?.name || '';
}
</script>

<template>
    <Head title="Master Data - Part Soal" />
    <DashboardLayout title="Master Data Part Soal">
        <div class="flex items-center justify-between mb-6">
            <div class="w-72">
                <DropDown
                    v-model="skillFilter"
                    :options="skills"
                    placeholder="Semua Skill"
                    :option-label="(s) => s.name + ' (' + (s.exam_type?.name || '-') + ')'"
                    option-value="id"
                />
            </div>
            <BaseButton @click="openCreate">
                <IconPlus :size="18" /> Tambah Part
            </BaseButton>
        </div>

        <DataTable :data="filteredParts" :columns="columns">
            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <button @click="startEdit(row)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                    <button @click="destroy(row)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Tambah / Edit -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-8 shadow-soft w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">{{ creating ? 'Tambah Part' : 'Edit Part' }}</h2>
                    <button @click="closeModal" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="creating ? submit() : saveEdit()" class="space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <DropDown
                                v-model="modalForm.skill_id"
                                :options="skills"
                                label="Skill *"
                                placeholder="Pilih skill"
                                :option-label="(s) => s.name + ' (' + (s.exam_type?.name || '-') + ')'"
                                option-value="id"
                            />
                            <p v-if="modalForm.errors.skill_id" class="text-error-red text-xs mt-1">{{ modalForm.errors.skill_id }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Urutan *</label>
                            <input type="number" v-model="modalForm.order" required min="1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                            <p v-if="modalForm.errors.order" class="text-error-red text-xs mt-1">{{ modalForm.errors.order }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Nama Part *</label>
                        <input type="text" v-model="modalForm.name" required placeholder="Part 1"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="modalForm.errors.name" class="text-error-red text-xs mt-1">{{ modalForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Directions</label>
                        <RichTextEditor v-model="modalForm.directions" placeholder="Teks arahan untuk part ini..." :min-height="'120px'" />
                        <p v-if="modalForm.errors.directions" class="text-error-red text-xs mt-1">{{ modalForm.errors.directions }}</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="modalForm.is_active" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <span class="text-label-md text-text-body">Aktif</span>
                    </label>
                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <BaseButton type="submit" :disabled="modalForm.processing" size="xl" class="flex-1">
                            {{ modalForm.processing ? 'Menyimpan...' : (creating ? 'Simpan Part' : 'Simpan Perubahan') }}
                        </BaseButton>
                        <BaseButton type="button" variant="secondary" size="lg" @click="closeModal">
                            Batal
                        </BaseButton>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
