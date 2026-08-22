<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import { IconPlus, IconEdit, IconTrash, IconX, IconGripVertical } from '@tabler/icons-vue';
import { computed, ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    skills: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();

const form = useForm({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

const editForm = useForm({
    code: '',
    name: '',
    description: '',
    is_active: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingSkill = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

// ===== Drag & drop reorder =====
const reordering = ref(false);
const originalOrder = ref('');

watch(() => props.skills, (list) => {
    originalOrder.value = list.map((s) => s.id).join(',');
}, { immediate: true });

function onReorder(evt) {
    if (reordering.value) return;

    const prev = originalOrder.value.split(',').filter(Boolean).map(Number);
    if (prev.length === 0 || typeof evt.oldIndex !== 'number' || typeof evt.newIndex !== 'number') return;

    const ids = [...prev];
    const [moved] = ids.splice(evt.oldIndex, 1);
    ids.splice(evt.newIndex, 0, moved);

    if (ids.join(',') === prev.join(',')) return;

    reordering.value = true;
    router.post(route('admin.master-data.skills.reorder'), { skills: ids }, {
        preserveScroll: true,
        onError: () => {
            toast.error('Gagal menyimpan urutan.');
            router.reload({ only: ['skills'] });
        },
        onFinish: () => { reordering.value = false; },
    });
}

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

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
        <div class="flex items-center justify-between mb-6">
            <span v-if="skills.length" class="text-label-md text-text-muted">
                Seret ikon ⠿ untuk mengubah urutan
            </span>
            <BaseButton @click="openCreate">
                <IconPlus :size="18" /> Tambah Skill
            </BaseButton>
        </div>

        <BaseCard :padding="false" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                            <th class="w-12 px-4 py-3"></th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Kode</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Nama</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Deskripsi</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Status</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-3">Aksi</th>
                        </tr>
                    </thead>
                    <draggable
                        tag="tbody"
                        :list="props.skills"
                        item-key="id"
                        handle=".drag-handle"
                        :disabled="reordering"
                        @end="onReorder"
                    >
                        <template #item="{ element }">
                            <tr class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                                <td class="px-4 py-3.5">
                                    <IconGripVertical
                                        :size="18"
                                        class="drag-handle cursor-grab text-text-muted hover:text-secondary transition-colors"
                                    />
                                </td>
                                <td class="px-5 py-3.5 font-medium text-primary">{{ element.code }}</td>
                                <td class="px-5 py-3.5 font-medium text-primary">{{ element.name }}</td>
                                <td class="px-5 py-3.5 text-body-md text-text-body">
                                    {{ (() => { const t = stripHtml(element.description); return t ? (t.length > 60 ? t.substring(0, 60) + '…' : t) : '-'; })() }}
                                </td>
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

            <div v-if="!skills.length" class="px-5 py-10 text-center">
                <p class="text-text-muted text-body-md">Belum ada data skill.</p>
            </div>
        </BaseCard>

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
        </BaseModal>
    </DashboardLayout>
</template>
