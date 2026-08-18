<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DataTable from '@/Components/Shared/DataTable.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import { IconPlus, IconEdit, IconTrash, IconX } from '@tabler/icons-vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

defineProps({
    examTypes: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    name: '',
    max_strikes: 3,
    description: '',
    is_active: true,
});

const editForm = useForm({
    name: '',
    max_strikes: 3,
    description: '',
    is_active: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingType = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

const columns = [
    { key: 'name', label: 'Nama', sortable: true, className: 'font-medium text-primary' },
    { key: 'max_strikes', label: 'Maks. Strikes', render: (val) => val ?? 3 },
    { key: 'description', label: 'Deskripsi', render: (val) => { const text = stripHtml(val); return text ? (text.length > 60 ? text.substring(0, 60) + '…' : text) : '-'; } },
    { key: 'is_active', label: 'Status', badge: true, render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

function openCreate() {
    creating.value = true;
    editingType.value = null;
    form.clearErrors();
    form.reset();
    showModal.value = true;
}

function submit() {
    form.post(route('admin.master-data.exam-types.store'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

function startEdit(type) {
    creating.value = false;
    editingType.value = type;
    editForm.clearErrors();
    editForm.reset();
    editForm.name = type.name;
    editForm.max_strikes = type.max_strikes ?? 3;
    editForm.description = type.description || '';
    editForm.is_active = !!type.is_active;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.master-data.exam-types.update', editingType.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    editingType.value = null;
    creating.value = false;
    editForm.reset();
}

async function destroy(type) {
    if (!await confirm.confirm(`Hapus jenis tes "${type.name}"?`)) return;
    router.delete(route('admin.master-data.exam-types.destroy', type.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Master Data - Jenis Tes" />
    <DashboardLayout title="Master Data Jenis Tes">
        <div class="flex justify-end mb-6">
            <BaseButton @click="openCreate">
                <IconPlus :size="18" /> Tambah Jenis Tes
            </BaseButton>
        </div>

        <DataTable :data="examTypes" :columns="columns">
            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <button @click="startEdit(row)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                    <button @click="destroy(row)" :disabled="row.question_banks_count > 0 || row.exams_count > 0"
                            class="p-2 text-text-muted hover:text-error-red transition-colors disabled:opacity-30 disabled:cursor-not-allowed disabled:hover:text-text-muted"
                            :title="(row.question_banks_count > 0 || row.exams_count > 0) ? 'Tidak bisa dihapus karena masih digunakan bank soal atau ujian' : 'Hapus'">
                        <IconTrash :size="18" />
                    </button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Tambah / Edit -->
        <BaseModal :show="showModal" @close="closeModal" max-width="2xl" scrollable>
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">{{ creating ? 'Tambah Jenis Tes' : 'Edit Jenis Tes' }}</h2>
                    <button @click="closeModal" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="creating ? submit() : saveEdit()" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Nama Jenis Tes *</label>
                        <input type="text" v-model="modalForm.name" required placeholder="TOEFL iBT"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="modalForm.errors.name" class="text-error-red text-xs mt-1">{{ modalForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Maksimum Strikes</label>
                        <input type="number" v-model="modalForm.max_strikes" min="0" max="10"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="modalForm.errors.max_strikes" class="text-error-red text-xs mt-1">{{ modalForm.errors.max_strikes }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Deskripsi</label>
                        <RichTextEditor v-model="modalForm.description" placeholder="Jelaskan jenis tes ini..." :min-height="'120px'" />
                        <p v-if="modalForm.errors.description" class="text-error-red text-xs mt-1">{{ modalForm.errors.description }}</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="modalForm.is_active" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <span class="text-label-md text-text-body">Aktif</span>
                    </label>
                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <BaseButton type="submit" :disabled="modalForm.processing" size="xl" class="flex-1">
                            {{ modalForm.processing ? 'Menyimpan...' : (creating ? 'Simpan Jenis Tes' : 'Simpan Perubahan') }}
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
