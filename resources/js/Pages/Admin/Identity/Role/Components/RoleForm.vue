<script setup>
import { computed, h, ref } from 'vue';
import { IconSettings, IconX } from '@tabler/icons-vue';
import AppDataTable from '@/Components/Ui/AppDataTable.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import BaseCheckbox from '@/Components/Base/BaseCheckbox.vue';
import BaseCheckboxGroup from '@/Components/Base/BaseCheckboxGroup.vue';
import BaseFieldLabel from '@/Components/Base/BaseFieldLabel.vue';
import BaseInput from '@/Components/Base/BaseInput.vue';
import BaseInputError from '@/Components/Base/BaseInputError.vue';
import BaseModal from '@/Components/Base/BaseModal.vue';
import BaseModalFooter from '@/Components/Base/BaseModalFooter.vue';
import BaseModalHeader from '@/Components/Base/BaseModalHeader.vue';

const props = defineProps({
    form: { type: Object, required: true },
    roles: { type: Array, default: () => [] },
    permissionCategories: { type: Array, default: () => [] },
    role: { type: Object, default: null },
    submitting: { type: Boolean, default: false },
});

const emit = defineEmits(['submit', 'cancel']);

const inheritedIds = computed(() => new Set(props.role?.inherited_permission_ids ?? []));
const grantIds = computed(() => new Set(props.form.grant_permission_ids ?? []));
const denyIds = computed(() => new Set(props.form.deny_permission_ids ?? []));
const showPermissionModal = ref(false);
const activeCategoryId = ref(null);

const parentOptions = computed(() => props.roles
    .filter(role => role.id !== props.role?.id)
    .map(role => ({ value: role.id, label: role.name })));

const categories = computed(() => props.permissionCategories.map(category => ({
    ...category,
    permissions: category.permissions.map(permission => ({
        ...permission,
        inherited: inheritedIds.value.has(permission.id),
        checked: (grantIds.value.has(permission.id) || inheritedIds.value.has(permission.id))
            && !denyIds.value.has(permission.id),
    })),
})));

const activeCategory = computed(() => categories.value.find(
    category => category.id === activeCategoryId.value,
) ?? null);

const permissionTableKey = computed(() => `identity-role-permissions-${props.role?.id ?? 'create'}`);

function checkedPermissionCount(category) {
    return category.permissions.filter(permission => permission.checked).length;
}

function togglePermission(permission, checked) {
    const grants = new Set(props.form.grant_permission_ids ?? []);
    const denies = new Set(props.form.deny_permission_ids ?? []);

    if (checked) {
        denies.delete(permission.id);

        if (!permission.inherited) {
            grants.add(permission.id);
        }
    } else {
        grants.delete(permission.id);

        if (permission.inherited) {
            denies.add(permission.id);
        }
    }

    props.form.grant_permission_ids = [...grants];
    props.form.deny_permission_ids = [...denies];
}

function selectCategory(category, selected) {
    category.permissions.forEach(permission => togglePermission(permission, selected));
}

function selectAll() {
    categories.value.forEach(category => selectCategory(category, true));
}

function clearAll() {
    categories.value.forEach(category => selectCategory(category, false));
}

function resetAll() {
    props.form.grant_permission_ids = [];
    props.form.deny_permission_ids = [];
}

function openPermissionModal(category) {
    activeCategoryId.value = category.id;
    showPermissionModal.value = true;
}

function closePermissionModal() {
    showPermissionModal.value = false;
    activeCategoryId.value = null;
}

const permissionColumns = [
    {
        accessorKey: 'name',
        header: 'Kategori',
        cell: info => h('span', { class: 'font-semibold text-text-heading' }, info.getValue()),
    },
    {
        accessorKey: 'module',
        header: 'Module',
        cell: info => h('span', { class: 'text-body-md text-text-body' }, info.getValue()),
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
        id: 'selected',
        header: 'Dipilih',
        meta: { align: 'center' },
        cell: info => {
            const category = info.row.original;

            return `${checkedPermissionCount(category)} / ${category.permissions.length}`;
        },
    },
    {
        id: 'actions',
        header: 'Aksi',
        meta: { align: 'center' },
        cell: info => h(
            BaseButton,
            {
                variant: 'secondary',
                size: 'sm',
                class: '!p-2',
                title: 'Atur permission',
                'aria-label': 'Atur permission',
                onClick: () => openPermissionModal(info.row.original),
            },
            () => h(IconSettings, { size: 18 }),
        ),
    },
];
</script>

<template>
    <div>
        <div class="space-y-5">
        <BaseCard>
            <div class="grid gap-4 md:grid-cols-2">
                <div>
                    <BaseFieldLabel required>Nama Role</BaseFieldLabel>
                    <BaseInput
                        v-model="props.form.name"
                        :disabled="Boolean(props.role?.is_default)"
                        :error="Boolean(props.form.errors.name)"
                    />
                    <BaseInputError :message="props.form.errors.name" />
                    <p v-if="props.form.name" class="mt-1 text-label-md text-text-muted">
                        Slug: {{ props.form.name.toLowerCase().replace(/\s+/g, '-') }}
                    </p>
                </div>
                <div>
                    <BaseFieldLabel>Parent Role</BaseFieldLabel>
                    <BaseCheckboxGroup
                        v-model="props.form.parent_role_ids"
                        :options="parentOptions"
                        :disabled="Boolean(props.role?.is_default)"
                    />
                    <BaseInputError :message="props.form.errors.parent_role_ids" />
                </div>
            </div>
        </BaseCard>

        <div class="space-y-3">
            <p class="text-body-md text-text-muted">
                Permission warisan otomatis aktif. Menghapus centang permission warisan akan menyimpan deny.
            </p>

            <AppDataTable
                :table-key="permissionTableKey"
                :data="categories"
                :columns="permissionColumns"
                title="Permission"
                searchable
                search-placeholder="Cari kategori atau module..."
                empty-message="Belum ada permission. Jalankan scan permission terlebih dahulu."
            >
                <template #actions>
                    <BaseButton variant="secondary" size="sm" @click="selectAll">Pilih Semua</BaseButton>
                    <BaseButton variant="secondary" size="sm" @click="resetAll">Reset</BaseButton>
                    <BaseButton variant="danger" size="sm" @click="clearAll">Hapus Semua</BaseButton>
                </template>
            </AppDataTable>
        </div>

        <div class="flex justify-end gap-2">
            <BaseButton variant="secondary" size="sm" @click="emit('cancel')">Batal</BaseButton>
            <BaseButton size="sm" :disabled="submitting" @click="emit('submit')">
                {{ submitting ? 'Menyimpan...' : 'Simpan Role' }}
            </BaseButton>
        </div>
        </div>

        <Teleport to="body">
            <BaseModal
                :show="showPermissionModal"
        max-width="3xl"
        aria-labelledby="role-permission-modal-title"
        @close="closePermissionModal"
    >
        <template #header>
            <BaseModalHeader>
                <div class="flex items-start justify-between gap-4 px-6 py-4">
                    <div>
                        <h2 id="role-permission-modal-title" class="text-title-lg font-semibold text-text-heading">
                            {{ activeCategory?.name || 'Permission' }}
                        </h2>
                        <p class="text-label-md text-text-muted">
                            {{ activeCategory?.module }} · {{ activeCategory?.code }}
                        </p>
                    </div>
                    <button
                        type="button"
                        aria-label="Tutup modal permission"
                        class="p-2 text-text-muted transition-colors hover:text-text-heading"
                        @click="closePermissionModal"
                    >
                        <IconX :size="20" />
                    </button>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <div class="max-h-[60vh] overflow-y-auto px-6 py-5">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <p class="text-body-md text-text-muted">
                        Centang permission yang diizinkan untuk role ini.
                    </p>
                    <div class="flex gap-2">
                        <BaseButton
                            variant="secondary"
                            size="xs"
                            @click="activeCategory && selectCategory(activeCategory, true)"
                        >
                            Pilih Semua
                        </BaseButton>
                        <BaseButton
                            variant="secondary"
                            size="xs"
                            @click="activeCategory && selectCategory(activeCategory, false)"
                        >
                            Reset
                        </BaseButton>
                    </div>
                </div>

                <div v-if="activeCategory?.permissions.length" class="grid gap-2 md:grid-cols-2">
                    <BaseCheckbox
                        v-for="permission in activeCategory.permissions"
                        :key="permission.id"
                        :model-value="permission.checked"
                        :label="permission.name"
                        @update:model-value="togglePermission(permission, $event)"
                    />
                </div>
                <p v-else class="py-6 text-center text-body-md text-text-muted">
                    Tidak ada permission pada kategori ini.
                </p>
            </div>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="flex justify-end px-6 py-4">
                    <BaseButton variant="secondary" size="sm" @click="closePermissionModal">Tutup</BaseButton>
                </div>
            </BaseModalFooter>
        </template>
            </BaseModal>
        </Teleport>
    </div>
</template>
