<script setup>
import { computed } from 'vue';
import DataTable from '@/Components/Shared/DataTable.vue';
import { IconEye } from '@tabler/icons-vue';

const props = defineProps({
    title: { type: String, required: true },
    data: { type: Array, default: () => [] },
    columns: { type: Array, default: () => [] },
    searchPlaceholder: { type: String, default: 'Cari...' },
});

const emit = defineEmits(['select']);

const tableColumns = computed(() => [
    ...props.columns,
    { key: 'id', label: 'Aksi', slot: 'actions' },
]);
</script>

<template>
    <DataTable
        :data="props.data"
        :columns="tableColumns"
        :header-title="props.title"
        :searchable="props.data.length > 0"
        :search-placeholder="props.searchPlaceholder"
    >
        <template #actions="{ row }">
            <BaseButton
                variant="secondary"
                size="sm"
                class="!p-2"
                :title="`Lihat ${props.title.toLowerCase()}`"
                :aria-label="`Lihat ${props.title.toLowerCase()}`"
                @click="emit('select', row)"
            >
                <IconEye :size="18" />
            </BaseButton>
        </template>
    </DataTable>
</template>
