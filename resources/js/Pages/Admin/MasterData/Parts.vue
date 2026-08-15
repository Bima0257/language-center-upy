<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DataTable from '@/Components/Shared/DataTable.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconPlus, IconEdit, IconTrash, IconX } from '@tabler/icons-vue';
import { computed, ref, watch } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import { skillLabel } from '@/constants/skills';

const props = defineProps({
    parts: { type: Array, default: () => [] },
    questionBanks: { type: Array, default: () => [] },
    skillOptions: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    question_bank_id: '',
    skill: '',
    name: '',
    order: 1,
    directions: '',
    is_active: true,
});

const editForm = useForm({
    question_bank_id: '',
    skill: '',
    name: '',
    order: 1,
    directions: '',
    is_active: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingPart = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

const bankFilter = ref('');
const skillFilter = ref('');

watch(() => props.questionBanks, (banks) => {
    if (!bankFilter.value && banks.length > 0) {
        bankFilter.value = String(banks[0].id);
    }
}, { immediate: true });

function onBankFilterChange(value) {
    bankFilter.value = value;
    router.get(route('admin.master-data.parts.index'), value ? { bank_id: value } : {}, {
        preserveState: true,
        preserveScroll: true,
    });
}

const filteredParts = computed(() => {
    let list = props.parts;
    if (bankFilter.value) {
        list = list.filter(p => String(p.question_bank_id) === String(bankFilter.value));
    }
    if (skillFilter.value) {
        list = list.filter(p => String(p.skill) === String(skillFilter.value));
    }
    return list;
});

const columns = [
    { key: 'name', label: 'Nama', sortable: true, className: 'font-medium text-primary' },
    { key: 'question_bank_id', label: 'Bank Soal', render: (val, row) => row.question_bank?.name || bankName(val) },
    { key: 'skill', label: 'Skill', render: (val) => skillLabel(val) || '-' },
    { key: 'order', label: 'Urutan', sortable: true, render: (val) => val ?? 1 },
    { key: 'questions_count', label: 'Soal', render: (val) => val ?? 0 },
    { key: 'is_active', label: 'Status', badge: true, render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

function bankName(id) {
    return props.questionBanks.find(b => String(b.id) === String(id))?.name || '-';
}

function openCreate() {
    creating.value = true;
    editingPart.value = null;
    form.clearErrors();
    form.reset();
    form.question_bank_id = bankFilter.value || '';
    form.skill = skillFilter.value || '';
    if (props.skillOptions.length === 1) {
        form.skill = props.skillOptions[0].value;
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
    editForm.question_bank_id = String(part.question_bank_id);
    editForm.skill = String(part.skill);
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
</script>

<template>
    <Head title="Master Data - Part Soal" />
    <DashboardLayout title="Master Data Part Soal">
        <div class="flex items-center justify-between mb-6">
            <div class="flex gap-3">
                <div class="w-72">
                    <DropDown
                        v-model="bankFilter"
                        :options="questionBanks"
                        placeholder="Semua Bank"
                        option-label="name"
                        option-value="id"
                        @change="onBankFilterChange"
                    />
                </div>
                <div class="w-36">
                    <DropDown
                        v-model="skillFilter"
                        :options="skillOptions"
                        placeholder="Semua Skill"
                        option-label="label"
                        option-value="value"
                    />
                </div>
            </div>
            <BaseButton @click="openCreate" :disabled="!bankFilter">
                <IconPlus :size="18" /> Tambah Part
            </BaseButton>
        </div>

        <p v-if="!bankFilter" class="text-label-md text-text-muted mb-4">
            Pilih bank soal terlebih dahulu untuk melihat atau menambah part.
        </p>

        <DataTable :data="filteredParts" :columns="columns">
            <template #actions="{ row }">
                <div class="flex items-center gap-2">
                    <button @click="startEdit(row)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                    <button @click="destroy(row)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                </div>
            </template>
        </DataTable>

        <!-- Modal Tambah / Edit -->
        <BaseModal :show="showModal" @close="closeModal" max-width="2xl" scrollable>
            <div class="p-8">
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
                                v-model="modalForm.question_bank_id"
                                :options="questionBanks"
                                label="Bank Soal *"
                                placeholder="Pilih bank"
                                option-label="name"
                                option-value="id"
                                :disabled="creating"
                            />
                            <p v-if="modalForm.errors.question_bank_id" class="text-error-red text-xs mt-1">{{ modalForm.errors.question_bank_id }}</p>
                        </div>
                        <div>
                            <DropDown
                                v-model="modalForm.skill"
                                :options="skillOptions"
                                label="Skill *"
                                placeholder="Pilih skill"
                                option-label="label"
                                option-value="value"
                            />
                            <p v-if="modalForm.errors.skill" class="text-error-red text-xs mt-1">{{ modalForm.errors.skill }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Nama Part *</label>
                            <input type="text" v-model="modalForm.name" required placeholder="Part 1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                            <p v-if="modalForm.errors.name" class="text-error-red text-xs mt-1">{{ modalForm.errors.name }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Urutan *</label>
                            <input type="number" v-model="modalForm.order" required min="1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                            <p v-if="modalForm.errors.order" class="text-error-red text-xs mt-1">{{ modalForm.errors.order }}</p>
                        </div>
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
        </BaseModal>
    </DashboardLayout>
</template>
