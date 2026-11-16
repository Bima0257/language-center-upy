<script setup>
import { computed, h, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCheckbox from '@/Components/Base/BaseCheckbox.vue';
import BaseFieldLabel from '@/Components/Base/BaseFieldLabel.vue';
import BaseInfoField from '@/Components/Base/BaseInfoField.vue';
import BaseInput from '@/Components/Base/BaseInput.vue';
import BaseInputError from '@/Components/Base/BaseInputError.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseModalFooter from '@/Components/Base/BaseModalFooter.vue';
import BaseModalHeader from '@/Components/Base/BaseModalHeader.vue';
import { IconEye, IconPencil } from '@tabler/icons-vue';

const props = defineProps({
    module: { type: Object, required: true },
    permissionCategories: { type: Object, required: true },
    filters: { type: Object, default: () => ({}) },
    uncategorizedPermissions: { type: Array, default: () => [] },
});

const selectedCategory = ref(null);
const showDetail = ref(false);
const showEdit = ref(false);
const editForm = useForm({
    name: '',
    module: '',
    permission_ids: [],
    _query: {},
});

const availablePermissions = computed(() => {
    const map = new Map();

    props.uncategorizedPermissions.forEach(permission => map.set(permission.id, permission));
    (selectedCategory.value?.permissions ?? []).forEach(permission => map.set(permission.id, permission));

    return [...map.values()].sort((left, right) => left.name.localeCompare(right.name));
});

function openDetail(category) {
    selectedCategory.value = category;
    showDetail.value = true;
}

function openEdit(category) {
    selectedCategory.value = category;
    editForm.name = category.name;
    editForm.module = category.module;
    editForm.permission_ids = (category.permissions ?? []).map(permission => permission.id);
    editForm.clearErrors();
    showEdit.value = true;
}

function togglePermission(permissionId, checked) {
    const ids = new Set(editForm.permission_ids ?? []);

    if (checked) {
        ids.add(permissionId);
    } else {
        ids.delete(permissionId);
    }

    editForm.permission_ids = [...ids];
}

function submitEdit() {
    editForm.put(route('admin.identity.permission-categories.update', selectedCategory.value.id), {
        preserveScroll: true,
        onSuccess: () => { showEdit.value = false; },
    });
}

const columns = [
    {
        accessorKey: 'name',
        header: 'Kategori',
        cell: info => h('span', { class: 'font-semibold text-text-heading' }, info.getValue()),
    },
    {
        accessorKey: 'code',
        header: 'Kode',
        cell: info => h('code', { class: 'text-label-md text-text-muted' }, info.getValue()),
    },
    {
        accessorKey: 'permissions_count',
        header: 'Permission',
        meta: { align: 'center' },
        cell: info => info.getValue() ?? 0,
    },
    {
        accessorKey: 'is_system',
        header: 'Status',
        meta: { align: 'center' },
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() ? 'neutral' : 'pastel-blue' },
            () => (info.getValue() ? 'System' : 'Custom'),
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
                    variant: 'secondary',
                    iconOnly: true,
                    title: 'Detail',
                    'aria-label': 'Detail',
                    onClick: () => openDetail(info.row.original),
                },
                () => h(IconEye, { size: 16 }),
            ),
            h(
                BaseButton,
                {
                    variant: 'secondary',
                    iconOnly: true,
                    title: 'Ubah',
                    'aria-label': 'Ubah',
                    onClick: () => openEdit(info.row.original),
                },
                () => h(IconPencil, { size: 16 }),
            ),
        ]),
    },
];
</script>

<template>
    <Head :title="`Permission ${props.module.module}`" />
    <TataLetakDasbor
        :title="`Permission ${props.module.module}`"
        subtitle="Daftar kategori dan permission pada module ini"
        :back-href="route('admin.identity.permissions.index')"
    >
        <div class="mb-5 grid gap-3 sm:grid-cols-3">
            <BaseInfoField label="Module" :value="props.module.module" accent />
            <BaseInfoField label="Jumlah Kategori" :value="props.module.categories_count" />
            <BaseInfoField label="Jumlah Permission" :value="props.module.permissions_count" />
        </div>

        <AppDataTable
            table-key="identity-permission-category-table"
            :data="props.permissionCategories.data"
            :columns="columns"
            title="Daftar Kategori Permission"
            searchable
            search-placeholder="Cari kategori..."
            server-side
            :server-total-rows="props.permissionCategories.total"
            :reload-only="['permissionCategories']"
            :page-size="10"
            :page-size-options="[10, 25, 50, 100]"
        />

        <BaseModal :show="showDetail" max-width="3xl" @close="showDetail = false">
            <BaseModalHeader>
                <div class="px-6 py-4 text-title-lg font-semibold text-text-heading">
                    {{ selectedCategory?.name }}
                </div>
            </BaseModalHeader>
            <div class="max-h-[60vh] overflow-y-auto p-6">
                <table class="w-full text-left">
                    <thead class="text-label-md uppercase tracking-wider text-text-muted">
                        <tr>
                            <th class="px-3 py-2">Action</th>
                            <th class="px-3 py-2">Route Permission</th>
                            <th class="px-3 py-2">Sumber</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant/30">
                        <tr v-for="permission in selectedCategory?.permissions ?? []" :key="permission.id">
                            <td class="px-3 py-2">
                                <BaseBadge variant="secondary">{{ permission.action || 'access' }}</BaseBadge>
                            </td>
                            <td class="px-3 py-2"><code class="text-label-md">{{ permission.name }}</code></td>
                            <td class="px-3 py-2 text-body-md text-text-muted">
                                {{ permission.category_assignment_source === 'manual' ? 'Manual' : 'Otomatis' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseModal>

        <BaseModal :show="showEdit" max-width="2xl" @close="showEdit = false">
            <BaseModalHeader>
                <div class="px-6 py-4 text-title-lg font-semibold text-text-heading">Ubah Kategori</div>
            </BaseModalHeader>
            <div class="space-y-4 p-6">
                <div>
                    <BaseFieldLabel required>Nama</BaseFieldLabel>
                    <BaseInput v-model="editForm.name" :error="Boolean(editForm.errors.name)" />
                    <BaseInputError :message="editForm.errors.name" />
                </div>
                <div>
                    <BaseFieldLabel required>Module</BaseFieldLabel>
                    <BaseInput v-model="editForm.module" :error="Boolean(editForm.errors.module)" />
                    <BaseInputError :message="editForm.errors.module" />
                </div>
                <div>
                    <BaseFieldLabel>Permission (dari uncategorized)</BaseFieldLabel>
                    <div class="max-h-64 space-y-2 overflow-y-auto rounded-xl border border-outline-variant/40 p-3">
                        <BaseCheckbox
                            v-for="permission in availablePermissions"
                            :key="permission.id"
                            :model-value="(editForm.permission_ids ?? []).includes(permission.id)"
                            :label="permission.name"
                            @update:model-value="togglePermission(permission.id, $event)"
                        />
                        <p v-if="availablePermissions.length === 0" class="text-body-md text-text-muted">
                            Tidak ada permission uncategorized.
                        </p>
                    </div>
                </div>
            </div>
            <BaseModalFooter>
                <div class="flex justify-end gap-2 border-t border-outline-variant/40 px-6 py-4">
                    <BaseButton variant="secondary" size="sm" @click="showEdit = false">Batal</BaseButton>
                    <BaseButton size="sm" :disabled="editForm.processing" @click="submitEdit">Simpan</BaseButton>
                </div>
            </BaseModalFooter>
        </BaseModal>
    </TataLetakDasbor>
</template>
