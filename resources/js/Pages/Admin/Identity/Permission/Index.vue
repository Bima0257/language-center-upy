<script setup>
import { h, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseFieldLabel from '@/Components/Base/BaseFieldLabel.vue';
import BaseInput from '@/Components/Base/BaseInput.vue';
import BaseInputError from '@/Components/Base/BaseInputError.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseModalFooter from '@/Components/Base/BaseModalFooter.vue';
import BaseModalHeader from '@/Components/Base/BaseModalHeader.vue';
import { IconEye, IconPencil, IconRefresh } from '@tabler/icons-vue';

const props = defineProps({
    permissionModules: { type: Array, default: () => [] },
});

const scanning = ref(false);
const showModuleModal = ref(false);
const editingModule = ref('');
const moduleForm = useForm({ current_module: '', module: '' });

function scan() {
    scanning.value = true;
    router.post(route('admin.identity.permissions.scan'), {}, {
        preserveScroll: true,
        onFinish: () => { scanning.value = false; },
    });
}

function openModule(module) {
    editingModule.value = module;
    moduleForm.current_module = module;
    moduleForm.module = module;
    showModuleModal.value = true;
}

function submitModule() {
    moduleForm.put(route('admin.identity.permission-modules.update'), {
        preserveScroll: true,
        onSuccess: () => { showModuleModal.value = false; },
    });
}

const columns = [
    {
        accessorKey: 'module',
        header: 'Module',
        cell: info => h('span', { class: 'font-semibold text-text-heading' }, info.getValue()),
    },
    {
        accessorKey: 'categories_count',
        header: 'Kategori',
        meta: { align: 'center' },
        cell: info => info.getValue() ?? 0,
    },
    {
        accessorKey: 'permissions_count',
        header: 'Permission',
        meta: { align: 'center' },
        cell: info => info.getValue() ?? 0,
    },
    {
        accessorKey: 'status',
        header: 'Status',
        meta: { align: 'center' },
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() === 'System' ? 'neutral' : (info.getValue() === 'Custom' ? 'pastel-blue' : 'warning') },
            () => info.getValue(),
        ),
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => h('div', { class: 'flex items-center justify-center gap-1' }, [
            h(
                BaseButton,
                {
                    href: route('admin.identity.permissions.module.show', { module: info.row.original.module }),
                    variant: 'secondary',
                    iconOnly: true,
                    title: 'Detail',
                    'aria-label': 'Detail',
                },
                () => h(IconEye, { size: 16 }),
            ),
            h(
                BaseButton,
                {
                    variant: 'secondary',
                    iconOnly: true,
                    title: 'Ubah modul',
                    'aria-label': 'Ubah modul',
                    onClick: () => openModule(info.row.original.module),
                },
                () => h(IconPencil, { size: 16 }),
            ),
        ]),
    },
];
</script>

<template>
    <Head title="Manajemen Permission" />
    <TataLetakDasbor title="Manajemen Permission" subtitle="Scan route dan kelola kategori permission">
        <AppDataTable
            table-key="identity-permission-module-table"
            :data="props.permissionModules"
            :columns="columns"
            title="Daftar Module Permission"
            searchable
            search-placeholder="Cari module..."
        >
            <template #actions>
                <BaseButton size="sm" :disabled="scanning" @click="scan">
                    <IconRefresh :size="16" />
                    {{ scanning ? 'Memindai...' : 'Scan Permission' }}
                </BaseButton>
            </template>
        </AppDataTable>

        <BaseModal :show="showModuleModal" max-width="md" @close="showModuleModal = false">
            <BaseModalHeader>
                <div class="px-6 py-4 text-title-lg font-semibold text-text-heading">Ubah Module</div>
            </BaseModalHeader>
            <div class="space-y-4 p-6">
                <div>
                    <BaseFieldLabel>Module saat ini</BaseFieldLabel>
                    <BaseInput :model-value="editingModule" disabled />
                </div>
                <div>
                    <BaseFieldLabel required>Module baru</BaseFieldLabel>
                    <BaseInput v-model="moduleForm.module" :error="Boolean(moduleForm.errors.module)" />
                    <BaseInputError :message="moduleForm.errors.module" />
                </div>
            </div>
            <BaseModalFooter>
                <div class="flex justify-end gap-2 border-t border-outline-variant/40 px-6 py-4">
                    <BaseButton variant="secondary" size="sm" @click="showModuleModal = false">Batal</BaseButton>
                    <BaseButton size="sm" :disabled="moduleForm.processing" @click="submitModule">Simpan</BaseButton>
                </div>
            </BaseModalFooter>
        </BaseModal>
    </TataLetakDasbor>
</template>
