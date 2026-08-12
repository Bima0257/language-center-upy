<script setup>
defineProps({
    form: { type: Object, required: true },
    optionKeys: { type: Array, default: () => ["A", "B", "C", "D"] },
    size: { type: String, default: "md" },
});
</script>

<template>
    <div>
        <p class="text-label-md font-medium text-primary mb-2">
            Pilihan Jawaban <span class="text-error-red">*</span>
        </p>
        <div :class="size === 'md' ? 'space-y-3' : 'space-y-2'">
            <div
                v-for="key in optionKeys"
                :key="key"
                :class="['flex items-center', size === 'md' ? 'gap-3' : 'gap-2']"
            >
                <span
                    :class="[
                        'shrink-0 flex items-center justify-center rounded-full border border-outline-variant font-semibold text-primary text-sm',
                        size === 'md'
                            ? 'w-8 h-8 bg-surface-container-low'
                            : 'w-7 h-7 bg-surface-white',
                    ]"
                >{{ key }}</span>
                <input
                    type="text"
                    v-model="form['option_' + key.toLowerCase()]"
                    required
                    :placeholder="'Teks pilihan ' + key"
                    :class="[
                        'flex-1 px-4 py-2.5 border border-outline-variant text-body-md text-text-body focus:outline-none focus:border-secondary',
                        size === 'md'
                            ? 'bg-surface-container-lowest rounded-2xl py-3'
                            : 'bg-surface-white rounded-xl',
                    ]"
                />
            </div>
        </div>
    </div>
</template>
