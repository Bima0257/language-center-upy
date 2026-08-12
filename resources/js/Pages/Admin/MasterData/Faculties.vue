<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconPlus, IconEdit, IconTrash, IconCheck } from '@tabler/icons-vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    faculties: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    name: '',
    code: '',
    is_active: true,
});

const editing = ref(null);
const editForm = useForm({
    name: '',
    code: '',
    is_active: true,
});

function submit() {
    form.post(route('admin.master-data.faculties.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function startEdit(faculty) {
    editing.value = faculty.id;
    editForm.name = faculty.name;
    editForm.code = faculty.code;
    editForm.is_active = !!faculty.is_active;
}

function saveEdit(faculty) {
    editForm.put(route('admin.master-data.faculties.update', faculty.id), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
    });
}

async function destroy(faculty) {
    if (!await confirm.confirm(`Hapus fakultas "${faculty.name}"?`)) return;
    router.delete(route('admin.master-data.faculties.destroy', faculty.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Master Data - Fakultas" />
    <DashboardLayout title="Master Data Fakultas">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <BaseCard class="h-fit">
                <h3 class="text-title-lg font-semibold text-primary mb-4">Tambah Fakultas</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Nama Fakultas</label>
                        <input type="text" v-model="form.name" required placeholder="Fakultas Teknik"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.name" class="text-error-red text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Kode</label>
                        <input type="text" v-model="form.code" required placeholder="FT"
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
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Prodi</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="faculty in faculties" :key="faculty.id">
                                <tr v-if="editing !== faculty.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50">
                                    <td class="px-5 py-4 font-medium text-primary">{{ faculty.name }}</td>
                                    <td class="px-5 py-4 text-body-md text-text-body">{{ faculty.code }}</td>
                                    <td class="px-5 py-4 text-body-md text-text-body">{{ faculty.departments_count || 0 }}</td>
                                    <td class="px-5 py-4">
                                        <BaseBadge :variant="faculty.is_active ? 'success' : 'neutral'">
                                            {{ faculty.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </BaseBadge>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <button @click="startEdit(faculty)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                                            <button @click="destroy(faculty)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else class="border-b border-outline-variant/20 bg-pastel-blue/10">
                                    <td colspan="5" class="px-5 py-4">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                                            <input type="text" v-model="editForm.name" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <input type="text" v-model="editForm.code" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <div class="flex items-center gap-2">
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="checkbox" v-model="editForm.is_active" class="w-4 h-4" />
                                                    <span class="text-label-md text-text-body">Aktif</span>
                                                </label>
                                                <button @click="saveEdit(faculty)" class="p-2 text-green-600 hover:text-green-800 transition-colors" title="Simpan"><IconCheck :size="18" /></button>
                                                <button @click="editing = null" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Batal"><IconTrash :size="18" /></button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="faculties.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-text-muted text-body-md">Belum ada data fakultas.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
