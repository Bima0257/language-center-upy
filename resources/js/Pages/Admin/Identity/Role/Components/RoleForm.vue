<script setup>
import { computed } from 'vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import BaseCheckbox from '@/Components/Base/BaseCheckbox.vue';
import BaseCheckboxGroup from '@/Components/Base/BaseCheckboxGroup.vue';
import BaseFieldLabel from '@/Components/Base/BaseFieldLabel.vue';
import BaseInput from '@/Components/Base/BaseInput.vue';
import BaseInputError from '@/Components/Base/BaseInputError.vue';

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
</script>

<template>
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

        <BaseCard>
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-title-lg font-semibold text-text-heading">Permission</h2>
                    <p class="text-body-md text-text-muted">
                        Permission warisan otomatis aktif. Menghapus centang permission warisan akan menyimpan deny.
                    </p>
                </div>
                <div class="flex gap-2">
                    <BaseButton variant="secondary" size="sm" @click="selectAll">Pilih Semua</BaseButton>
                    <BaseButton variant="secondary" size="sm" @click="resetAll">Reset</BaseButton>
                    <BaseButton variant="danger" size="sm" @click="clearAll">Hapus Semua</BaseButton>
                </div>
            </div>

            <div class="space-y-4">
                <div
                    v-for="category in categories"
                    :key="category.id"
                    class="rounded-2xl border border-outline-variant/40 p-4"
                >
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                        <div>
                            <p class="text-body-md font-semibold text-text-heading">{{ category.name }}</p>
                            <p class="text-label-md text-text-muted">{{ category.module }} · {{ category.code }}</p>
                        </div>
                        <div class="flex gap-2">
                            <BaseButton variant="secondary" size="xs" @click="selectCategory(category, true)">Pilih Semua</BaseButton>
                            <BaseButton variant="secondary" size="xs" @click="selectCategory(category, false)">Reset</BaseButton>
                        </div>
                    </div>
                    <div class="grid gap-2 md:grid-cols-2">
                        <BaseCheckbox
                            v-for="permission in category.permissions"
                            :key="permission.id"
                            :model-value="permission.checked"
                            :label="permission.name"
                            @update:model-value="togglePermission(permission, $event)"
                        />
                    </div>
                </div>

                <p v-if="categories.length === 0" class="py-6 text-center text-body-md text-text-muted">
                    Belum ada permission. Jalankan scan permission terlebih dahulu.
                </p>
            </div>
        </BaseCard>

        <div class="flex justify-end gap-2">
            <BaseButton variant="secondary" size="sm" @click="emit('cancel')">Batal</BaseButton>
            <BaseButton size="sm" :disabled="submitting" @click="emit('submit')">
                {{ submitting ? 'Menyimpan...' : 'Simpan Role' }}
            </BaseButton>
        </div>
    </div>
</template>
