<script setup>
import { IconArrowUp } from '@tabler/icons-vue';
import { ref, onMounted, onUnmounted } from 'vue';

defineProps({
    inline: { type: Boolean, default: false },
});

const visible = ref(false);
const isDesktop = ref(false);
let isWindowMode = true;
let container = null;

function onScroll() {
    visible.value = isWindowMode
        ? window.scrollY > 200
        : container && container.scrollTop > 200;
}

function scrollToTop() {
    if (isWindowMode) {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } else if (container) {
        container.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

onMounted(() => {
    const checkDesktop = () => { isDesktop.value = window.innerWidth >= 768; };
    checkDesktop();
    window.addEventListener('resize', checkDesktop);

    container = document.querySelector('.landing-scroll-area');
    if (container && window.innerWidth >= 768) {
        isWindowMode = false;
        container.addEventListener('scroll', onScroll);
    } else {
        window.addEventListener('scroll', onScroll);
    }
});

onUnmounted(() => {
    if (container) container.removeEventListener('scroll', onScroll);
    else window.removeEventListener('scroll', onScroll);
});
</script>

<template>
    <Transition name="fade">
        <button v-if="visible" @click="scrollToTop"
                :class="inline && isDesktop
                    ? 'absolute bottom-6 right-20 w-11 h-11 bg-primary-container text-white rounded-full shadow-lg flex items-center justify-center hover:bg-primary transition-colors z-40'
                    : 'fixed bottom-24 right-6 md:right-10 w-11 h-11 bg-primary-container text-white rounded-full shadow-lg flex items-center justify-center hover:bg-primary transition-colors z-40'">
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
