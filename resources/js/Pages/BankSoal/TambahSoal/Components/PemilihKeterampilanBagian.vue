<script setup>
defineProps({
    keterampilan: { type: Array, default: () => [] },
    bagianSoal: { type: Array, default: () => [] },
    selectedKeterampilan: { type: [String, Number], default: '' },
    selectedBagian: { type: [String, Number], default: '' },
});

const emit = defineEmits(['update:selectedKeterampilan', 'update:selectedBagian']);
</script>

<template>
    <div class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5 space-y-4">
        <div>
            <p class="text-label-md font-medium text-primary mb-2">Keterampilan *</p>
            <div class="flex flex-wrap gap-3">
                <label v-for="opsi in keterampilan" :key="opsi.value"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                       :class="selectedKeterampilan === opsi.value ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'">
                    <input type="radio" name="keterampilanGlobal" :value="opsi.value" :checked="selectedKeterampilan === opsi.value"
                           @change="emit('update:selectedKeterampilan', opsi.value)"
                           class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                    <span class="font-semibold" :class="selectedKeterampilan === opsi.value ? 'text-secondary' : 'text-primary'">{{ opsi.label }}</span>
                </label>
            </div>
        </div>
        <div>
            <p class="text-label-md font-medium text-primary mb-2">Bagian Soal *</p>
            <div v-if="bagianSoal.length" class="flex flex-wrap gap-3">
                <label v-for="bagian in bagianSoal" :key="bagian.id"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl border cursor-pointer transition-all"
                       :class="selectedBagian === bagian.id ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest hover:border-secondary/50'">
                    <input type="radio" name="globalPart" :value="bagian.id" :checked="selectedBagian === bagian.id"
                           @change="emit('update:selectedBagian', bagian.id)"
                           class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                    <span class="font-semibold" :class="selectedBagian === bagian.id ? 'text-secondary' : 'text-primary'">{{ bagian.nama }}</span>
                </label>
            </div>
            <p v-else class="text-label-md text-text-muted">Belum ada bagianSoal untuk keterampilan ini.</p>
        </div>
        <p class="text-label-md text-text-muted">Keterampilan & bagianSoal ini otomatis diterapkan ke semua soal di bawah. Listening otomatis memakai materi audio.</p>
    </div>
</template>
