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
    skills: { type: Array, default: () => [] },
    examTypes: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    exam_type_id: '',
    name: '',
    description: '',
    is_active: true,
});

const editForm = useForm({
    exam_type_id: '',
    name: '',
    description: '',
    is_active: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingSkill = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

const columns = [
    { key: 'name', label: 'Nama', sortable: true, className: 'font-medium text-primary' },
    { key: 'exam_type', label: 'Kategori', render: (val) => val?.name || '-' },
    { key: 'code', label: 'Kode', sortable: true },
    { key: 'questions_count', label: 'Soal', render: (val) => val ?? 0 },
    { key: 'is_active', label: 'Status', badge: true, render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

function openCreate() {
    creating.value = true;
    editingSkill.value = null;
    form.clearErrors();
    form.reset();
    if (props.examTypes.length === 1) {
        form.exam_type_id = String(props.examTypes[0].id);
    }
    showModal.value = true;
}

function submit() {
    form.post(route('admin.master-data.skills.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

function startEdit(skill) {
    creating.value = false;
    editingSkill.value = skill;
    editForm.clearErrors();
    editForm.reset();
    editForm.exam_type_id = String(skill.exam_type_id);
    editForm.name = skill.name;
    editForm.description = skill.description || '';
    editForm.is_active = !!skill.is_active;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingSkill.value = null;
    creating.value = false;
    editForm.reset();
}

function saveEdit() {
    editForm.put(route('admin.master-data.skills.update', editingSkill.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

async function destroy(skill) {
    if (!await confirm.confirm(`Hapus skill "${skill.name}"?`)) return;
    router.delete(route('admin.master-data.skills.destroy', skill.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Master Data - Skill" />
    <DashboardLayout title="Master Data Skill">
        <div class="flex justify-end mb-6">
            <BaseButton @click="openCreate">
                <IconPlus :size="18" /> Tambah Skill Baru
            </BaseButton>
        </div>

        <DataTable :data="skills" :columns="columns">
            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <button @click="startEdit(row)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                    <button @click="destroy(row)" :disabled="row.is_system" :title="row.is_system ? 'Skill sistem tidak dapat dihapus' : 'Hapus'"
                            class="p-2 text-text-muted hover:text-error-red transition-colors disabled:opacity-40 disabled:cursor-not-allowed disabled:hover:text-text-muted"><IconTrash :size="18" /></button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Tambah / Edit -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-8 shadow-soft w-full max-w-2xl mx-4 max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">{{ creating ? 'Tambah Skill' : 'Edit Skill' }}</h2>
                    <button @click="closeModal" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="creating ? submit() : saveEdit()" class="space-y-5">
                    <div>
                        <DropDown
                            v-model="modalForm.exam_type_id"
                            :options="examTypes"
                            label="Kategori Tes *"
                            placeholder="Pilih kategori tes"
                            option-label="name"
                            option-value="id"
                        />
                        <p v-if="modalForm.errors.exam_type_id" class="text-error-red text-xs mt-1">{{ modalForm.errors.exam_type_id }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Nama Skill *</label>
                        <input type="text" v-model="modalForm.name" required placeholder="Reading"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="modalForm.errors.name" class="text-error-red text-xs mt-1">{{ modalForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Deskripsi</label>
                        <RichTextEditor v-model="modalForm.description" placeholder="Jelaskan skill ini..." :min-height="'120px'" />
                        <p v-if="modalForm.errors.description" class="text-error-red text-xs mt-1">{{ modalForm.errors.description }}</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="modalForm.is_active" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <span class="text-label-md text-text-body">Aktif</span>
                    </label>
                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <BaseButton type="submit" :disabled="modalForm.processing" size="xl" class="flex-1">
                            {{ modalForm.processing ? 'Menyimpan...' : (creating ? 'Simpan Skill' : 'Simpan Perubahan') }}
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
