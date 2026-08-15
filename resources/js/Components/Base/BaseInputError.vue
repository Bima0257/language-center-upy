<script setup>
defineProps({
    message: {
        type: String,
        default: undefined,
    },
});
</script>

<template>
    <p v-show="message" class="text-error-red text-xs mt-1">
        {{ message }}
    </p>
</template>
