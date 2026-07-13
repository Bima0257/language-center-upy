<script setup>
import { IconArrowUp } from '@tabler/icons-vue';
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    threshold: { type: Number, default: 200 },
});

const visible = ref(false);
let container = null;

function onScroll() {
    visible.value = container.scrollTop > 200;
}

function scrollToTop() {
    container.scrollTo({ top: 0, behavior: 'smooth' });
}

onMounted(() => {
    container = document.querySelector('.dashboard-scroll-area');
    if (container) container.addEventListener('scroll', onScroll);
});

onUnmounted(() => {
    if (container) container.removeEventListener('scroll', onScroll);
});
</script>

<template>
    <Transition name="fade">
        <button v-if="visible" @click="scrollToTop"
                class="absolute bottom-6 right-6 w-11 h-11 bg-primary-container text-white rounded-full shadow-lg flex items-center justify-center hover:bg-primary transition-colors z-40">
            <IconArrowUp :size="22" stroke="2" />
        </button>
    </Transition>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
}
</style>
