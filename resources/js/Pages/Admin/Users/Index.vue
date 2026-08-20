<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconSearch, IconEdit, IconToggleLeft, IconToggleRight, IconKey } from '@tabler/icons-vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';

const props = defineProps({
    users: { type: Object, default: () => ({ data: [], links: [], meta: null }) },
    roles: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const activeFilter = ref(props.filters.is_active ?? '');

const editForm = useForm({
    name: '',
    email: '',
    phone: '',
    role: '',
});
const editingUser = ref(null);
const showModal = ref(false);

const roleColors = {
    superadmin: 'danger',
    admin: 'danger',
    instructor: 'pastel-blue',
    proctor: 'pastel-purple',
    student: 'success',
};

function doSearch() {
    router.get(route('admin.users.index'), {
        search: search.value || undefined,
        role: roleFilter.value || undefined,
        is_active: activeFilter.value !== '' ? activeFilter.value : undefined,
    }, { preserveState: true, replace: true });
}

function startEdit(user) {
    editingUser.value = user;
    editForm.name = user.name;
    editForm.email = user.email;
    editForm.phone = user.phone || '';
    editForm.role = user.roles?.[0]?.name || 'student';
    showModal.value = true;
}

function saveEdit() {
    editForm.patch(route('admin.users.update', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            editingUser.value = null;
        },
    });
}

async function toggleActive(user) {
    const action = user.is_active ? 'nonaktifkan' : 'aktifkan';
    if (!await confirm.confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} user "${user.name}"?`)) return;
    router.patch(route('admin.users.toggle-active', user.id), {}, { preserveScroll: true });
}

async function resetPassword(user) {
    if (!await confirm.confirm(`Reset password "${user.name}" ke default 'password'?`)) return;
    router.patch(route('admin.users.reset-password', user.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Manajemen User" />
    <DashboardLayout title="Manajemen User">
        <div class="flex flex-col md:flex-row gap-3 mb-6">
            <div class="relative flex-1">
                <IconSearch class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                <input type="text" v-model="search" @keyup.enter="doSearch" placeholder="Cari nama atau email..."
                    class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
            </div>
            <select v-model="roleFilter" @change="doSearch"
                class="px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                <option value="">Semua Role</option>
                <option v-for="r in roles" :key="r" :value="r">{{ r.charAt(0).toUpperCase() + r.slice(1) }}</option>
            </select>
            <select v-model="activeFilter" @change="doSearch"
                class="px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                <option value="">Semua Status</option>
                <option value="1">Aktif</option>
                <option value="0">Nonaktif</option>
            </select>
            <BaseButton @click="doSearch">Cari</BaseButton>
        </div>

        <BaseCard :padding="false" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Nama</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Email</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Role</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Status</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Sesi</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users.data" :key="user.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-5 py-4">
                                <p class="font-medium text-primary text-body-md">{{ user.name }}</p>
                                <p v-if="user.studentProfile?.nim" class="text-label-md text-text-muted">{{ user.studentProfile.nim }}</p>
                            </td>
                            <td class="px-5 py-4 text-text-body text-body-md">{{ user.email }}</td>
                            <td class="px-5 py-4">
                                <BaseBadge :variant="roleColors[user.roles?.[0]?.name] || 'neutral'">
                                    {{ user.roles?.[0]?.name || '-' }}
                                </BaseBadge>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1.5 text-label-md" :class="user.is_active ? 'text-green-600' : 'text-error-red'">
                                    <span class="w-2 h-2 rounded-full" :class="user.is_active ? 'bg-green-500' : 'bg-error-red'"></span>
                                    {{ user.is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-text-body text-body-md">{{ user.exam_sessions_count ?? 0 }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-1">
                                    <button @click="startEdit(user)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Edit">
                                        <IconEdit :size="18" />
                                    </button>
                                    <button @click="toggleActive(user)" class="p-2 transition-colors" :class="user.is_active ? 'text-text-muted hover:text-error-red' : 'text-text-muted hover:text-green-600'" :title="user.is_active ? 'Nonaktifkan' : 'Aktifkan'">
                                        <IconToggleRight v-if="user.is_active" :size="18" />
                                        <IconToggleLeft v-else :size="18" />
                                    </button>
                                    <button @click="resetPassword(user)" class="p-2 text-text-muted hover:text-amber-600 transition-colors" title="Reset Password">
                                        <IconKey :size="18" />
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.data?.length === 0">
                            <td colspan="6" class="px-5 py-12 text-center text-text-muted text-body-md">Tidak ada data user.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>

        <!-- Pagination -->
        <div v-if="users.meta && users.meta.last_page > 1" class="flex items-center justify-between mt-4">
            <p class="text-label-md text-text-muted">
                {{ users.meta.from }}–{{ users.meta.to }} dari {{ users.meta.total }} user
            </p>
            <div class="flex gap-2">
                <BaseButton v-for="link in users.links" :key="link.url" :href="link.url" size="sm" variant="secondary"
                    :class="{ 'opacity-50 pointer-events-none': !link.url }">
                    {{ link.label }}
                </BaseButton>
            </div>
        </div>

        <!-- Edit Modal -->
        <div v-if="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
            <div class="bg-white rounded-3xl p-8 shadow-soft w-full max-w-lg mx-4">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">Edit User</h2>
                    <button @click="showModal = false" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="saveEdit" class="space-y-4">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Nama</label>
                        <input type="text" v-model="editForm.name" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="editForm.errors.name" class="text-error-red text-xs mt-1">{{ editForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Email</label>
                        <input type="email" v-model="editForm.email" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="editForm.errors.email" class="text-error-red text-xs mt-1">{{ editForm.errors.email }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-1.5">Role</label>
                        <select v-model="editForm.role" class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                            <option v-for="r in roles" :key="r" :value="r">{{ r.charAt(0).toUpperCase() + r.slice(1) }}</option>
                        </select>
                    </div>
                    <div class="flex gap-4 pt-2">
                        <BaseButton type="submit" :disabled="editForm.processing" size="xl" class="flex-1">
                            {{ editForm.processing ? 'Menyimpan...' : 'Simpan' }}
                        </BaseButton>
                        <BaseButton type="button" variant="secondary" size="lg" @click="showModal = false">Batal</BaseButton>
                    </div>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
