<script setup>
defineProps({
    label: { type: String, required: true },
    placeholder: { type: String, required: true },
    preview: { type: String, default: null },
    error: { type: String, default: undefined },
});

const emit = defineEmits(['select']);
</script>

<template>
    <BaseCard>
        <label class="text-label-md font-medium text-primary block mb-4">{{ label }}</label>
        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-8 text-center relative hover:border-primary transition-colors cursor-pointer">
            <img v-if="preview" :src="preview" class="max-h-40 mx-auto rounded-lg mb-2 pointer-events-none" />
            <p v-else class="text-text-muted text-body-md">{{ placeholder }}</p>
            <p class="text-text-muted text-label-md mt-1">Maks 5MB, format JPG/PNG</p>
            <input
                type="file"
                accept="image/*"
                class="absolute inset-0 opacity-0 cursor-pointer"
                @change="emit('select', $event)"
            />
        </div>
        <p v-if="error" class="text-error-red text-xs mt-1">{{ error }}</p>
    </BaseCard>
</template>
