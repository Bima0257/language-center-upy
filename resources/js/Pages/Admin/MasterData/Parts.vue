<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconPlus, IconEdit, IconTrash, IconX, IconGripVertical, IconBook } from '@tabler/icons-vue';
import { computed, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    parts: { type: Array, default: () => [] },
    questionBanks: { type: Array, default: () => [] },
    skillOptions: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();

const form = useForm({
    question_bank_id: '',
    skill_id: '',
    name: '',
    directions: '',
    is_active: true,
});

const editForm = useForm({
    question_bank_id: '',
    skill_id: '',
    name: '',
    directions: '',
    is_active: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingPart = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

const bankFilter = ref('');

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

// ===== Group per skill =====
const skillGroups = computed(() => props.skillOptions.map((skill) => ({
    ...skill,
    parts: props.parts
        .filter((p) => !bankFilter.value || String(p.question_bank_id) === String(bankFilter.value))
        .filter((p) => String(p.skill_id) === String(skill.value))
        .sort((a, b) => (a.order ?? 0) - (b.order ?? 0)),
})));

// ===== Drag & drop reorder (per grup skill) =====
const reordering = ref(false);
const originalOrders = ref({});

watch(skillGroups, (groups) => {
    originalOrders.value = Object.fromEntries(
        groups.map((g) => [g.value, g.parts.map((p) => p.id).join(',')]),
    );
}, { immediate: true });

function onReorder(skillId, evt) {
    if (reordering.value) return;

    const prev = (originalOrders.value[skillId] || '').split(',').filter(Boolean).map(Number);
    if (prev.length === 0 || typeof evt.oldIndex !== 'number' || typeof evt.newIndex !== 'number') return;

    // Hitung urutan baru secara deterministik dari posisi drop (tidak bergantung timing mutasi list)
    const ids = [...prev];
    const [moved] = ids.splice(evt.oldIndex, 1);
    ids.splice(evt.newIndex, 0, moved);

    if (ids.join(',') === prev.join(',')) return;

    reordering.value = true;
    router.post(route('admin.master-data.parts.reorder'), { parts: ids }, {
        preserveScroll: true,
        onError: () => {
            toast.error('Gagal menyimpan urutan.');
            router.reload({ only: ['parts'] });
        },
        onFinish: () => { reordering.value = false; },
    });
}

// ===== Modal =====
function openCreate() {
    creating.value = true;
    editingPart.value = null;
    form.clearErrors();
    form.reset();
    form.question_bank_id = bankFilter.value || '';
    if (props.skillOptions.length === 1) {
        form.skill_id = props.skillOptions[0].value;
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
    editForm.skill_id = String(part.skill_id);
    editForm.name = part.name;
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
            <BaseButton @click="openCreate" :disabled="!bankFilter">
                <IconPlus :size="18" /> Tambah Part
            </BaseButton>
        </div>

        <p v-if="!bankFilter" class="text-label-md text-text-muted mb-4">
            Pilih bank soal terlebih dahulu untuk melihat atau menambah part.
        </p>

        <div class="space-y-6">
            <BaseCard v-for="group in skillGroups" :key="group.value" :padding="false" class="overflow-hidden">
                <div class="flex items-center justify-between px-5 py-4 bg-surface-container-low/60 border-b border-outline-variant/30">
                    <div class="flex items-center gap-2">
                        <IconBook :size="18" class="text-secondary" />
                        <p class="text-label-md font-semibold text-primary">{{ group.label }}</p>
                        <span class="text-label-md text-text-muted">{{ group.parts.length }} part</span>
                    </div>
                    <span v-if="group.parts.length" class="text-label-md text-text-muted">
                        Seret ikon ⠿ untuk mengubah urutan
                    </span>
                </div>

                <div v-if="group.parts.length === 0" class="px-5 py-10 text-center">
                    <p class="text-text-muted text-body-md">Belum ada part untuk {{ group.label }}.</p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                                <th class="w-12 px-4 py-3"></th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Nama</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Urutan</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Soal</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Status</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Aksi</th>
                            </tr>
                        </thead>
                        <draggable
                            tag="tbody"
                            :list="group.parts"
                            item-key="id"
                            handle=".drag-handle"
                            :disabled="reordering"
                            @end="onReorder(group.value, $event)"
                        >
                            <template #item="{ element }">
                                <tr class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                                    <td class="px-4 py-3.5">
                                        <IconGripVertical
                                            :size="18"
                                            class="drag-handle cursor-grab text-text-muted hover:text-secondary transition-colors"
                                        />
                                    </td>
                                    <td class="px-5 py-3.5 font-medium text-primary">{{ element.name }}</td>
                                    <td class="px-5 py-3.5 text-body-md text-text-body">{{ element.order ?? 1 }}</td>
                                    <td class="px-5 py-3.5 text-body-md text-text-body">{{ element.questions_count ?? 0 }}</td>
                                    <td class="px-5 py-3.5">
                                        <BaseBadge :variant="element.is_active ? 'success' : 'neutral'">
                                            {{ element.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </BaseBadge>
                                    </td>
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-2">
                                            <button @click="startEdit(element)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                                            <button @click="destroy(element)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </draggable>
                    </table>
                </div>
            </BaseCard>
        </div>

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
                                v-model="modalForm.skill_id"
                                :options="skillOptions"
                                label="Skill *"
                                placeholder="Pilih skill"
                                option-label="label"
                                option-value="value"
                                empty-message="Belum ada data skill."
                            />
                            <p v-if="modalForm.errors.skill_id" class="text-error-red text-xs mt-1">{{ modalForm.errors.skill_id }}</p>
                        </div>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Nama Part *</label>
                        <input type="text" v-model="modalForm.name" required placeholder="Part 1"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="modalForm.errors.name" class="text-error-red text-xs mt-1">{{ modalForm.errors.name }}</p>
                        <p v-if="creating" class="text-label-md text-text-muted mt-1">
                            Urutan otomatis ditambahkan di akhir daftar part — ubah urutan lewat drag &amp; drop di tabel.
                        </p>
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
