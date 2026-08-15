<script setup>
import { IconArrowUp } from '@tabler/icons-vue';
import { ref, onMounted, onUnmounted } from 'vue';

const props = defineProps({
    inline: { type: Boolean, default: false },
    container: { type: String, default: '' },
    threshold: { type: Number, default: 200 },
});

const visible = ref(false);
const isDesktop = ref(false);
let containerEl = null;

function onScroll() {
    visible.value = containerEl
        ? containerEl.scrollTop > props.threshold
        : window.scrollY > props.threshold;
}

function scrollToTop() {
    if (containerEl) {
        containerEl.scrollTo({ top: 0, behavior: 'smooth' });
    } else {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
}

onMounted(() => {
    const checkDesktop = () => { isDesktop.value = window.innerWidth >= 768; };
    checkDesktop();
    window.addEventListener('resize', checkDesktop);

    containerEl = props.container ? document.querySelector(props.container) : null;
    if (containerEl) {
        containerEl.addEventListener('scroll', onScroll);
    } else {
        window.addEventListener('scroll', onScroll);
    }
});

onUnmounted(() => {
    if (containerEl) containerEl.removeEventListener('scroll', onScroll);
    else window.removeEventListener('scroll', onScroll);
});
</script>

<template>
    <Transition name="fade">
        <button v-if="visible" @click="scrollToTop"
                :class="inline && isDesktop
                    ? 'absolute bottom-6 right-20 w-11 h-11 bg-primary-container text-white rounded-full shadow-lg flex items-center justify-center hover:bg-primary transition-colors z-40'
                    : container
                        ? 'absolute bottom-6 right-6 w-11 h-11 bg-primary-container text-white rounded-full shadow-lg flex items-center justify-center hover:bg-primary transition-colors z-40'
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
