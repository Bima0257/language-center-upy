<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DataTable from '@/Components/Shared/DataTable.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import { IconPlus, IconEdit, IconTrash, IconX } from '@tabler/icons-vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

defineProps({
    skills: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    code: '',
    name: '',
    description: '',
    order: 1,
    is_active: true,
});

const editForm = useForm({
    code: '',
    name: '',
    description: '',
    order: 1,
    is_active: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingSkill = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

const columns = [
    { key: 'code', label: 'Kode', sortable: true, className: 'font-medium text-primary' },
    { key: 'name', label: 'Nama', sortable: true },
    { key: 'description', label: 'Deskripsi', render: (val) => { const text = stripHtml(val); return text ? (text.length > 60 ? text.substring(0, 60) + '…' : text) : '-'; } },
    { key: 'order', label: 'Urutan', render: (val) => val ?? 1 },
    { key: 'is_active', label: 'Status', badge: true, render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

function openCreate() {
    creating.value = true;
    editingSkill.value = null;
    form.clearErrors();
    form.reset();
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
    editForm.code = skill.code;
    editForm.name = skill.name;
    editForm.description = skill.description || '';
    editForm.order = skill.order ?? 1;
    editForm.is_active = !!skill.is_active;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.master-data.skills.update', editingSkill.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingSkill.value = null;
    creating.value = false;
    editForm.reset();
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
                <IconPlus :size="18" /> Tambah Skill
            </BaseButton>
        </div>

        <DataTable :data="skills" :columns="columns">
            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <button @click="startEdit(row)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                    <button @click="destroy(row)" :disabled="row.skill_parts_count > 0 || row.questions_count > 0"
                            class="p-2 text-text-muted hover:text-error-red transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-text-muted"
                            :title="(row.skill_parts_count > 0 || row.questions_count > 0) ? 'Tidak bisa dihapus karena masih digunakan' : 'Hapus'">
                        <IconTrash :size="18" />
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Tambah / Edit -->
        <BaseModal :show="showModal" @close="closeModal" max-width="2xl" scrollable>
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">{{ creating ? 'Tambah Skill' : 'Edit Skill' }}</h2>
                    <button @click="closeModal" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="creating ? submit() : saveEdit()" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Kode Skill *</label>
                        <input type="text" v-model="modalForm.code" required placeholder="reading" :disabled="!creating"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary disabled:opacity-50" />
                        <p v-if="modalForm.errors.code" class="text-error-red text-xs mt-1">{{ modalForm.errors.code }}</p>
                        <p v-if="creating" class="text-label-md text-text-muted mt-1">Kode harus berupa 'reading' atau 'listening'</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Nama Skill *</label>
                        <input type="text" v-model="modalForm.name" required placeholder="Reading"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="modalForm.errors.name" class="text-error-red text-xs mt-1">{{ modalForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Deskripsi</label>
                        <RichTextEditor v-model="modalForm.description" placeholder="Jelaskan skill ini..." :min-height="'120px'" />
                        <p v-if="modalForm.errors.description" class="text-error-red text-xs mt-1">{{ modalForm.errors.description }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Urutan</label>
                            <input type="number" v-model="modalForm.order" min="1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                            <p v-if="modalForm.errors.order" class="text-error-red text-xs mt-1">{{ modalForm.errors.order }}</p>
                        </div>
                        <div class="flex items-end pb-2">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" v-model="modalForm.is_active" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                                <span class="text-label-md text-text-body">Aktif</span>
                            </label>
                        </div>
                    </div>
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
        </BaseModal>
    </DashboardLayout>
</template>
