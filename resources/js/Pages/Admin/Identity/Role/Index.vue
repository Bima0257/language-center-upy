<script setup>
import { computed, h } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import AppTabs from '@/Components/Ui/AppTabs.vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { IconEdit, IconPlus, IconTrash } from '@tabler/icons-vue';

const props = defineProps({
    roles: { type: Object, required: true },
    roleCounts: { type: Object, default: () => ({}) },
    filters: { type: Object, default: () => ({}) },
});

const confirm = useConfirm();
const activeTab = computed(() => props.filters?.tab || 'default');

const tabs = computed(() => [
    { value: 'default', label: `Default (${props.roleCounts?.default ?? 0})` },
    { value: 'system', label: `System (${props.roleCounts?.system ?? 0})` },
    { value: 'custom', label: `Custom (${props.roleCounts?.custom ?? 0})` },
]);

function switchTab(tab) {
    router.get(route('admin.identity.roles.index'), { tab, per_page: props.filters?.per_page || 10 }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

async function destroyRole(role) {
    if (!await confirm.confirm(`Hapus role "${role.name}"?`)) return;

    router.delete(route('admin.identity.roles.destroy', role.id), { preserveScroll: true });
}

const columns = [
    {
        accessorKey: 'name',
        header: 'Role',
        cell: info => h('div', { class: 'flex items-center gap-2' }, [
            h('span', { class: 'font-semibold text-text-heading' }, info.getValue()),
            info.row.original.is_default ? h(BaseBadge, { variant: 'primary' }, () => 'Default') : null,
            info.row.original.is_system && !info.row.original.is_default
                ? h(BaseBadge, { variant: 'neutral' }, () => 'System')
                : null,
        ]),
    },
    {
        accessorKey: 'parents',
        header: 'Parent',
        enableSorting: false,
        cell: info => (info.getValue()?.length ? info.getValue().join(', ') : '-'),
    },
    {
        accessorKey: 'users_count',
        header: 'Pengguna',
        meta: { align: 'center' },
        cell: info => info.getValue() ?? 0,
    },
    {
        accessorKey: 'effective_permissions_count',
        header: 'Permission',
        meta: { align: 'center' },
        cell: info => info.getValue() ?? 0,
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => h('div', { class: 'flex items-center justify-center gap-1' }, [
            h(
                BaseButton,
                {
                    href: route('admin.identity.roles.edit', info.row.original.id),
                    variant: 'secondary',
                    iconOnly: true,
                    title: 'Ubah',
                    'aria-label': 'Ubah',
                },
                () => h(IconEdit, { size: 16 }),
            ),
            !info.row.original.is_default
                ? h(
                    BaseButton,
                    {
                        variant: 'danger',
                        iconOnly: true,
                        title: 'Hapus',
                        'aria-label': 'Hapus',
                        onClick: () => destroyRole(info.row.original),
                    },
                    () => h(IconTrash, { size: 16 }),
                )
                : null,
        ]),
    },
];
</script>

<template>
    <Head title="Manajemen Role" />
    <TataLetakDasbor title="Manajemen Role" subtitle="Kelola role default, system, dan custom">
        <div class="space-y-4">
            <AppTabs :tabs="tabs" :model-value="activeTab" @update:model-value="switchTab" />

            <AppDataTable
                :key="activeTab"
                :table-key="`identity-role-table-${activeTab}`"
                :data="props.roles.data"
                :columns="columns"
                title="Daftar Role"
                searchable
                search-placeholder="Cari role..."
                server-side
                :server-total-rows="props.roles.total"
                :reload-only="['roles', 'roleCounts']"
                :page-size="10"
                :page-size-options="[10, 25, 50, 100]"
            >
                <template #actions>
                    <BaseButton
                        v-if="activeTab === 'custom'"
                        size="sm"
                        :href="route('admin.identity.roles.create')"
                    >
                        <IconPlus :size="16" />
                        Tambah Role
                    </BaseButton>
                </template>
            </AppDataTable>
        </div>
    </TataLetakDasbor>
</template>
