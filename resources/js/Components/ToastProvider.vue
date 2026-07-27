<script setup>
import { computed } from 'vue'
import { Toaster } from 'vue-sonner'
import 'vue-sonner/style.css'
import { usePage } from '@inertiajs/vue3'

const page = usePage()

const offset = computed(() => {
    if (page?.component?.startsWith('Exam/')) return 64
    if (page?.component?.startsWith('Auth/')) return 16
    return 96
})
</script>

<template>
    <Toaster position="top-right" richColors :duration="4000" :offset="offset" />
</template>
