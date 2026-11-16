<script setup>
defineProps({
    section: { type: Array, default: () => [] },
    partSoal: { type: Array, default: () => [] },
    selectedSection: { type: [String, Number], default: '' },
    selectedPart: { type: [String, Number], default: '' },
    readOnly: { type: Boolean, default: false },
});

const emit = defineEmits(['update:selectedSection', 'update:selectedPart']);
</script>

<template>
    <div class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5 space-y-4">
        <div>
            <p class="text-label-md font-medium text-text-heading mb-2">Section *</p>
            <div class="flex flex-wrap gap-3">
                <label v-for="opsi in section" :key="opsi.value"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl border transition-all"
                       :class="[
                           selectedSection === opsi.value ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest',
                           readOnly ? 'cursor-default' : 'cursor-pointer hover:border-secondary/50',
                       ]">
                    <input type="radio" name="sectionGlobal" :value="opsi.value" :checked="selectedSection === opsi.value"
                           :disabled="readOnly"
                           @change="emit('update:selectedSection', opsi.value)"
                           class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                           <span class="font-semibold" :class="selectedSection === opsi.value ? 'text-secondary' : 'text-text-heading'">{{ opsi.label }}</span>
                </label>
            </div>
        </div>
        <div>
            <p class="text-label-md font-medium text-text-heading mb-2">Part Soal *</p>
            <div v-if="partSoal.length" class="flex flex-wrap gap-3">
                <label v-for="part in partSoal" :key="part.id"
                       class="flex items-center gap-2 px-4 py-2 rounded-xl border transition-all"
                       :class="[
                           selectedPart === part.id ? 'border-secondary bg-secondary/10' : 'border-outline-variant bg-surface-container-lowest',
                           readOnly ? 'cursor-default' : 'cursor-pointer hover:border-secondary/50',
                       ]">
                    <input type="radio" name="globalPart" :value="part.id" :checked="selectedPart === part.id"
                           :disabled="readOnly"
                           @change="emit('update:selectedPart', part.id)"
                           class="w-4 h-4 border-outline-variant text-secondary focus:ring-secondary" />
                        <span class="font-semibold" :class="selectedPart === part.id ? 'text-secondary' : 'text-text-heading'">{{ part.nama }}</span>
                </label>
            </div>
            <p v-else class="text-label-md text-text-muted">Belum ada partSoal untuk section ini.</p>
        </div>
        <p class="text-label-md text-text-muted">Section & partSoal ini otomatis diterapkan ke semua soal di bawah. Listening otomatis memakai materi audio.</p>
    </div>
</template>
