<script setup>
defineProps({
    icon: { type: Object, required: true },
    iconBg: { type: String, default: 'bg-pastel-blue' },
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    action: { type: String, default: 'more_horiz' },
    completed: { type: Boolean, default: false },
});
</script>

<template>
    <div class="bg-white p-5 rounded-2xl shadow-soft border border-surface-container-low flex items-center group hover:border-primary/20 transition-colors">
        <div class="w-12 h-12 rounded-xl flex items-center justify-center text-primary group-hover:scale-110 transition-transform"
             :class="iconBg">
            <component :is="icon" :size="24" stroke="1.5" />
        </div>
        <div class="ml-4 flex-1 min-w-0">
            <h5 class="text-title-lg font-semibold text-primary truncate">{{ title }}</h5>
            <p v-if="subtitle" class="text-body-md text-text-muted truncate">{{ subtitle }}</p>
        </div>
        <slot name="action">
            <span v-if="completed" class="text-primary">
                <slot name="completed-icon"></slot>
            </span>
            <span v-else class="text-text-muted">•••</span>
        </slot>
    </div>
</template>
