<script setup>
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconSearch } from '@tabler/icons-vue';

defineProps({
    searchQuery: { type: String, default: '' },
    keterampilanTerpilih: { type: String, default: '' },
    bagianSoalTerpilihId: { type: String, default: '' },
    selectedStatus: { type: String, default: '' },
    opsiKeterampilan: { type: Array, default: () => [] },
    bagianSoal: { type: Array, default: () => [] },
    statuses: { type: Array, default: () => [] },
    statusLabels: { type: Object, default: () => ({}) },
});

defineEmits([
    'update:search-query',
    'update:keterampilan-terpilih',
    'update:bagian-soal-terpilih-id',
    'update:selected-status',
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
                         @input="$emit('update:search-query', $event.target.value); $emit('search')"
                        placeholder="Cari teks soal..."
                        class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary"
                    />
                </div>
            </div>
            <div class="w-36">
                <DropDown
                    :model-value="keterampilanTerpilih"
                    :options="opsiKeterampilan"
                    label="Keterampilan"
                    placeholder="Semua"
                    option-label="label"
                    option-value="value"
                     @change="$emit('update:keterampilan-terpilih', $event); $emit('filter-change')"
                />
            </div>
            <div class="w-36">
                <DropDown
                    :model-value="bagianSoalTerpilihId"
                    :options="bagianSoal"
                    label="Bagian Soal"
                    placeholder="Semua"
                     :option-label="(p) => p.nama + (p.keterampilan ? ' (' + (p.keterampilan?.nama || '') + ')' : '')"
                    option-value="id"
                  @change="$emit('update:bagian-soal-terpilih-id', $event); $emit('filter-change')"
                />
            </div>
        </div>

        <div class="flex gap-2 mt-4 pt-4 border-t border-outline-variant/30">
            <button
                v-for="s in statuses"
                :key="s"
                 @click="$emit('update:selected-status', selectedStatus === s ? '' : s); $emit('filter-change')"
                class="px-4 py-2 rounded-full text-label-md font-medium border transition-all"
                :class="selectedStatus === s ? 'bg-primary-container text-on-primary-container border-primary-container' : 'border-outline-variant text-text-body hover:border-secondary'"
            >
                {{ statusLabels[s] }}
            </button>
        </div>
    </BaseCard>
</template>
