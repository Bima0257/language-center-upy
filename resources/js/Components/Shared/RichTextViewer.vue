<script setup>
import { computed } from 'vue';
import DOMPurify from 'dompurify';

const props = defineProps({
    content: { type: String, default: '' },
    clamp: { type: Number, default: 0 },
});

const sanitized = computed(() => DOMPurify.sanitize(props.content || ''));

const clampClass = computed(() => {
    const map = { 1: 'line-clamp-1', 2: 'line-clamp-2', 3: 'line-clamp-3', 4: 'line-clamp-4', 5: 'line-clamp-5', 6: 'line-clamp-6' };
    return map[props.clamp] || '';
});
</script>

<template>
    <div class="rich-text-content text-text-body"
         :class="clampClass"
         v-html="sanitized"></div>
</template>
