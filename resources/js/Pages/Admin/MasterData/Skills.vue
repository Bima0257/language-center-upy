<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconPlus, IconEdit, IconTrash, IconCheck } from '@tabler/icons-vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    skills: { type: Array, default: () => [] },
});

const confirm = useConfirm();

const form = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
});

const editing = ref(null);
const editForm = useForm({
    name: '',
    code: '',
    description: '',
    is_active: true,
});

function submit() {
    form.post(route('admin.master-data.skills.store'), {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}

function startEdit(skill) {
    editing.value = skill.id;
    editForm.name = skill.name;
    editForm.code = skill.code;
    editForm.description = skill.description || '';
    editForm.is_active = !!skill.is_active;
}

function saveEdit(skill) {
    editForm.put(route('admin.master-data.skills.update', skill.id), {
        preserveScroll: true,
        onSuccess: () => { editing.value = null; },
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
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 h-fit">
                <h3 class="text-title-lg font-semibold text-primary mb-4">Tambah Skill</h3>
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Nama Skill</label>
                        <input type="text" v-model="form.name" required placeholder="Reading"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.name" class="text-error-red text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Kode</label>
                        <input type="text" v-model="form.code" required placeholder="reading"
                               class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.code" class="text-error-red text-xs mt-1">{{ form.errors.code }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1">Deskripsi</label>
                        <textarea v-model="form.description" rows="2" placeholder="Opsional"
                                  class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"></textarea>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="form.is_active" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <span class="text-label-md text-text-body">Aktif</span>
                    </label>
                    <button type="submit" :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 bg-primary-container text-white py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                        <IconPlus :size="18" /> {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-surface-white rounded-2xl shadow-soft border border-outline-variant/30 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Nama</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Kode</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Soal</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                                <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template v-for="skill in skills" :key="skill.id">
                                <tr v-if="editing !== skill.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50">
                                    <td class="px-5 py-4">
                                        <p class="font-medium text-primary">{{ skill.name }}</p>
                                        <p v-if="skill.description" class="text-text-muted text-label-md">{{ skill.description }}</p>
                                    </td>
                                    <td class="px-5 py-4 text-body-md text-text-body">{{ skill.code }}</td>
                                    <td class="px-5 py-4 text-body-md text-text-body">{{ skill.questions_count || 0 }}</td>
                                    <td class="px-5 py-4">
                                        <span :class="skill.is_active ? 'bg-green-100 dark:bg-green-950/30 text-green-700 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400'"
                                              class="inline-block px-3 py-1 rounded-full text-label-md font-medium">
                                            {{ skill.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </td>
                                    <td class="px-5 py-4">
                                        <div class="flex items-center gap-2">
                                            <button @click="startEdit(skill)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit"><IconEdit :size="18" /></button>
                                            <button @click="destroy(skill)" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Hapus"><IconTrash :size="18" /></button>
                                        </div>
                                    </td>
                                </tr>
                                <tr v-else class="border-b border-outline-variant/20 bg-pastel-blue/10">
                                    <td colspan="5" class="px-5 py-4">
                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                                            <input type="text" v-model="editForm.name" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <input type="text" v-model="editForm.code" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <input type="text" v-model="editForm.description" placeholder="Deskripsi" class="px-4 py-2.5 bg-surface-white border border-outline-variant rounded-xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                            <div class="flex items-center gap-2">
                                                <label class="flex items-center gap-1.5 cursor-pointer">
                                                    <input type="checkbox" v-model="editForm.is_active" class="w-4 h-4" />
                                                    <span class="text-label-md text-text-body">Aktif</span>
                                                </label>
                                                <button @click="saveEdit(skill)" class="p-2 text-green-600 hover:text-green-800 transition-colors" title="Simpan"><IconCheck :size="18" /></button>
                                                <button @click="editing = null" class="p-2 text-text-muted hover:text-error-red transition-colors" title="Batal"><IconTrash :size="18" /></button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                            <tr v-if="skills.length === 0">
                                <td colspan="5" class="px-5 py-12 text-center text-text-muted text-body-md">Belum ada data skill.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
