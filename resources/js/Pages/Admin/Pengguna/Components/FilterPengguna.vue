<script setup>
import { IconSearch } from '@tabler/icons-vue';

const props = defineProps({
    search: { type: String, default: '' },
    roleFilter: { type: [String, Number], default: '' },
    activeFilter: { type: [String, Number], default: '' },
    roles: { type: Array, default: () => [] },
    roleLabel: { type: Function, required: true },
});

const emit = defineEmits([
    'update:search',
    'update:role-filter',
    'update:active-filter',
    'search',
]);
</script>

<template>
    <div class="flex flex-col md:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <IconSearch class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
            <input type="text" :value="props.search" @input="emit('update:search', $event.target.value)"
                   @keyup.enter="emit('search')" placeholder="Cari nama atau email..."
                   class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
        </div>
        <select :value="props.roleFilter" @change="emit('update:role-filter', $event.target.value); emit('search')"
                class="px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
            <option value="">Semua Role</option>
            <option v-for="role in props.roles" :key="role" :value="role">{{ props.roleLabel(role) }}</option>
        </select>
        <select :value="props.activeFilter" @change="emit('update:active-filter', $event.target.value); emit('search')"
                class="px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary">
            <option value="">Semua Status</option>
            <option value="1">Aktif</option>
            <option value="0">Nonaktif</option>
        </select>
        <BaseButton @click="emit('search')">Cari</BaseButton>
    </div>
</template>
