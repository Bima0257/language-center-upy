<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconPlus, IconEdit, IconTrash, IconCheck } from '@tabler/icons-vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

defineProps({
    departments: { type: Array, default: () => [] },
    faculties: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    faculty_id: '',
    name: '',
    code: '',
    is_active: true,
});

const editing = ref(null);
const editForm = useForm({
    faculty_id: '',
    name: '',
    code: '',
    is_active: true,
});

function submit() {
    form.post(route('admin.master-data.departments.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function startEdit(dept) {
    editing.value = dept.id;
    editForm.faculty_id = dept.faculty_id;
    editForm.name = dept.name;
    editForm.code = dept.code;
    editForm.is_active = !!dept.is_active;
}

function saveEdit(dept) {
    editForm.put(route('admin.master-data.departments.update', dept.id), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
}

async function destroy(dept) {
    if (!await confirm.confirm(`Hapus prodi "${dept.name}"?`)) return;
    router.delete(route('admin.master-data.departments.destroy', dept.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Master Data - Program Studi" />
    <DashboardLayout title="Master Data Program Studi">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <BaseCard class="h-fit">
                <h3 class="text-title-lg font-semibold text-primary mb-4">Tambah Program Studi</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <DropDown
                            v-model="form.faculty_id"
                            :options="faculties"
                            label="Fakultas"
                            placeholder="Pilih fakultas"
                            option-label="name"
                            option-value="id"
                        />
                        <p v-if="form.errors.faculty_id" class="text-error-red text-xs mt-1">{{ form.errors.faculty_id }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Nama Prodi</label>
                        <input type="text" v-model="form.name" required placeholder="Teknik Informatika"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.name" class="text-error-red text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Kode</label>
                        <input type="text" v-model="form.code" required placeholder="TI"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.code" class="text-error-red text-xs mt-1">{{ form.errors.code }}</p>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <span class="text-label-md text-text-body">Aktif</span>
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
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Nama</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Kode</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Fakultas</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="dept in departments" :key="dept.id">
                                <tr v-if="editing !== dept.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50">
                                    <td class="px-5 py-4 font-medium text-primary">{{ dept.name }}</td>
                                    <td class="px-5 py-4 text-body-md text-text-body">{{ dept.code }}</td>
                                    <td class="px-5 py-4 text-body-md text-text-body">{{ dept.faculty?.name || '-' }}</td>
                                    <td class="px-5 py-4">
                                        <BaseBadge :variant="dept.is_active ? 'success' : 'neutral'">
                                            {{ dept.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </BaseBadge>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <button @click="startEdit(dept)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                                            <button @click="destroy(dept)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else class="border-b border-outline-variant/20 bg-pastel-blue/10">
                                    <td colspan="5" class="px-5 py-4">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                            <DropDown
                                                v-model="editForm.faculty_id"
                                                :options="faculties"
                                                placeholder="Fakultas"
                                                option-label="name"
                                                option-value="id"
                                                size="sm"
                                            />
                                            <input type="text" v-model="editForm.name" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <input type="text" v-model="editForm.code" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <div class="flex items-center gap-2">
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="checkbox" v-model="editForm.is_active" class="w-4 h-4" />
                                                    <span class="text-label-md text-text-body">Aktif</span>
                                                </label>
                                                <button @click="saveEdit(dept)" class="p-2 text-green-600 hover:text-green-800 transition-colors" title="Simpan"><IconCheck :size="18" /></button>
                                                <button @click="editing = null" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Batal"><IconTrash :size="18" /></button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="departments.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-text-muted text-body-md">Belum ada data program studi.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
