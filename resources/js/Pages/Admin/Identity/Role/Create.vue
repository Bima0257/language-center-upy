<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import RoleForm from '@/Pages/Admin/Identity/Role/Components/RoleForm.vue';

const props = defineProps({
    roles: { type: Array, default: () => [] },
    permissionCategories: { type: Array, default: () => [] },
});

const form = useForm({
    name: '',
    parent_role_ids: [],
    grant_permission_ids: [],
    deny_permission_ids: [],
    _query: { tab: 'custom' },
});

function submit() {
    form.post(route('admin.identity.roles.store'), {
        preserveScroll: true,
    });
}

function cancel() {
    router.visit(route('admin.identity.roles.index', { tab: 'custom' }));
}
</script>

<template>
    <Head title="Tambah Role" />
    <TataLetakDasbor
        title="Tambah Role"
        subtitle="Buat role custom dengan inheritance dan permission"
        :back-href="route('admin.identity.roles.index', { tab: 'custom' })"
    >
        <RoleForm
            :form="form"
            :roles="props.roles"
            :permission-categories="props.permissionCategories"
            :submitting="form.processing"
            @submit="submit"
            @cancel="cancel"
        />
    </TataLetakDasbor>
</template>
