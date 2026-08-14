<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconSearch } from '@tabler/icons-vue';

defineProps({
    searchQuery: { type: String, default: '' },
    selectedSkillId: { type: String, default: '' },
    selectedPartId: { type: String, default: '' },
    selectedStatus: { type: String, default: '' },
    skills: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    statusLabels: { type: Object, default: () => ({}) },
});

defineEmits([
    'update:searchQuery',
    'update:selectedSkillId',
    'update:selectedPartId',
    'update:selectedStatus',
    'search',
    'filter-change',
]);
</script>

<template>
    <BaseCard class="mb-6">
        <div class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="text-label-md font-medium text-primary block mb-1.5">Cari</label>
                <div class="relative">
                    <IconSearch class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                    <input
                        type="text"
                        :value="searchQuery"
                        @input="$emit('update:searchQuery', $event.target.value); $emit('search')"
                        placeholder="Cari teks soal..."
                        class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"
                    />
                </div>
            </div>
            <div class="w-36">
                <DropDown
                    :model-value="selectedSkillId"
                    :options="skills"
                    label="Skill"
                    placeholder="Semua"
                    option-label="name"
                    option-value="id"
                    @change="$emit('update:selectedSkillId', $event); $emit('filter-change')"
                />
            </div>
            <div class="w-36">
                <DropDown
                    :model-value="selectedPartId"
                    :options="parts"
                    label="Part"
                    placeholder="Semua"
                    :option-label="(p) => p.name + (p.skill ? ' (' + p.skill.name + ')' : '')"
                    option-value="id"
                    @change="$emit('update:selectedPartId', $event); $emit('filter-change')"
                />
            </div>
        </div>

        <div class="flex gap-2 mt-4 pt-4 border-t border-outline-variant/30">
            <button
                v-for="s in statuses"
                :key="s"
                @click="$emit('update:selectedStatus', selectedStatus === s ? '' : s); $emit('filter-change')"
                class="px-4 py-2 rounded-full text-label-md font-medium border transition-all"
                :class="selectedStatus === s ? 'bg-primary-container text-white border-primary-container' : 'border-outline-variant text-text-body hover:border-secondary'"
            >
                {{ statusLabels[s] }}
            </button>
        </div>
    </BaseCard>
</template>
