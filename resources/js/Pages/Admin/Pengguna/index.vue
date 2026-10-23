<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import FilterPengguna from '@/Pages/Admin/Pengguna/components/FilterPengguna.vue';
import TabelPengguna from '@/Pages/Admin/Pengguna/components/TabelPengguna.vue';
import PaginasiPengguna from '@/Pages/Admin/Pengguna/components/PaginasiPengguna.vue';
import FormEditPenggunaModal from '@/Pages/Admin/Pengguna/components/FormEditPenggunaModal.vue';

const props = defineProps({
    pengguna: { type: Object, default: () => ({ data: [], links: [], meta: null }) },
    roles: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const search = ref(props.filters.search || '');
const roleFilter = ref(props.filters.role || '');
const activeFilter = ref(props.filters.aktif ?? '');

const editForm = useForm({
    name: '',
    email: '',
    nomor_telepon: '',
    role: '',
});
const editingUser = ref(null);
const showModal = ref(false);

const roleLabels = {
    superadmin: 'Superadmin',
    admin: 'Admin',
    student: 'Mahasiswa',
};

function roleLabel(role) {
    return roleLabels[role] || role;
}

function doSearch() {
    router.get(route('admin.pengguna.daftar'), {
        search: search.value || undefined,
        role: roleFilter.value || undefined,
        aktif: activeFilter.value !== '' ? activeFilter.value : undefined,
    }, { preserveState: true, replace: true });
}

function startEdit(pengguna) {
    editingUser.value = pengguna;
    editForm.name = pengguna.name;
    editForm.email = pengguna.email;
    editForm.nomor_telepon = pengguna.nomor_telepon || '';
    editForm.role = pengguna.roles?.[0]?.name || 'student';
    showModal.value = true;
}

function saveEdit() {
    editForm.patch(route('admin.pengguna.perbarui', editingUser.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showModal.value = false;
            editingUser.value = null;
        },
    });
}

async function toggleActive(pengguna) {
    const action = pengguna.aktif ? 'nonaktifkan' : 'aktifkan';
    if (!await confirm.confirm(`${action.charAt(0).toUpperCase() + action.slice(1)} pengguna "${pengguna.name}"?`)) return;
    router.patch(route('admin.pengguna.ubah-keaktifan', pengguna.id), {}, { preserveScroll: true });
}

async function resetPassword(pengguna) {
    if (!await confirm.confirm(`Reset password "${pengguna.name}" ke default 'password'?`)) return;
    router.patch(route('admin.pengguna.atur-ulang-sandi', pengguna.id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Manajemen Pengguna" />
    <TataLetakDasbor title="Manajemen Pengguna" subtitle="Kelola akun, peran, dan status pengguna">
        <FilterPengguna
            v-model:search="search"
            v-model:role-filter="roleFilter"
            v-model:active-filter="activeFilter"
            :roles="roles"
            :role-label="roleLabel"
            @search="doSearch"
        />

        <TabelPengguna
            :pengguna="pengguna"
            :role-label="roleLabel"
            @edit="startEdit"
            @toggle-active="toggleActive"
            @reset-password="resetPassword"
        />

        <PaginasiPengguna :pengguna="pengguna" />

        <FormEditPenggunaModal
            :show="showModal"
            :form="editForm"
            :roles="roles"
            :role-label="roleLabel"
            @close="showModal = false"
            @submit="saveEdit"
        />
    </TataLetakDasbor>
</template>
