<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import RoleForm from '@/Pages/Admin/Identity/Role/Components/RoleForm.vue';

const props = defineProps({
    role: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    permissionCategories: { type: Array, default: () => [] },
});

const form = useForm({
    name: props.role.name,
    parent_role_ids: [...(props.role.parent_ids ?? [])],
    grant_permission_ids: [...(props.role.grant_permission_ids ?? [])],
    deny_permission_ids: [...(props.role.deny_permission_ids ?? [])],
});

function submit() {
    form.put(route('admin.identity.roles.update', props.role.id), {
        preserveScroll: true,
    });
}

function cancel() {
    const tab = props.role.is_default ? 'default' : (props.role.is_system ? 'system' : 'custom');
    router.visit(route('admin.identity.roles.index', { tab }));
}
</script>

<template>
    <Head :title="`Ubah Role ${props.role.name}`" />
    <TataLetakDasbor
        :title="`Ubah Role ${props.role.name}`"
        subtitle="Atur parent dan permission role"
        :back-href="route('admin.identity.roles.index', { tab: props.role.is_default ? 'default' : (props.role.is_system ? 'system' : 'custom') })"
    >
        <RoleForm
            :form="form"
            :roles="props.roles"
            :permission-categories="props.permissionCategories"
            :role="props.role"
            :submitting="form.processing"
            @submit="submit"
            @cancel="cancel"
        />
    </TataLetakDasbor>
</template>
