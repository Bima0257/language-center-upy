<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { Toaster } from 'vue-sonner'
import 'vue-sonner/style.css'
import { usePage } from '@inertiajs/vue3'
import { useModalStack } from '@/Composables/useModalStack'

const page = usePage()
const { topModal } = useModalStack()

const offset = computed(() => {
    if (page?.component?.startsWith('Ujian/')) return 64
    if (page?.component?.startsWith('Auth/')) return 16
    return 96
})

const isDark = ref(
    typeof document !== 'undefined' &&
        document.documentElement.classList.contains('dark'),
)

const theme = computed(() => (isDark.value ? 'dark' : 'light'))

function syncTheme() {
    isDark.value = document.documentElement.classList.contains('dark')
}

onMounted(() => {
    syncTheme()
    document.addEventListener('dashboard-theme-changed', syncTheme)
    document.addEventListener('theme-changed', syncTheme)
})

onUnmounted(() => {
    document.removeEventListener('dashboard-theme-changed', syncTheme)
    document.removeEventListener('theme-changed', syncTheme)
})
</script>

<template>
    <Teleport :to="topModal ?? 'body'" :disabled="!topModal">
        <Toaster position="top-right" richColors :theme="theme" :duration="4000" :offset="offset" />
    </Teleport>
</template>
