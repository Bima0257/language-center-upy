<script setup>
import { Link } from '@inertiajs/vue3';
import { IconHome, IconLayoutGrid, IconSparkles, IconLogin2, IconUserPlus } from '@tabler/icons-vue';
import { ref, onMounted, onUnmounted } from 'vue';

const activeSection = ref('home');

const sections = [
    { id: 'home', label: 'Beranda', icon: IconHome, href: '#home' },
    { id: 'features', label: 'Fitur', icon: IconLayoutGrid, href: '#features' },
    { id: 'trial', label: 'Try Out', icon: IconSparkles, href: '#pricing' },
    { id: 'login', label: 'Masuk', icon: IconLogin2, href: route('login') },
    { id: 'signup', label: 'Daftar', icon: IconUserPlus, href: route('register') },
];

function scrollTo(id) {
    if (id === 'login' || id === 'signup') return;
    const el = document.getElementById(id);
    if (el) el.scrollIntoView({ behavior: 'smooth' });
}

function onScroll() {
    const scrollY = window.scrollY + 100;
    const featureEl = document.getElementById('features');
    if (featureEl && scrollY >= featureEl.offsetTop) {
        activeSection.value = 'features';
    } else {
        activeSection.value = 'home';
    }
}

onMounted(() => window.addEventListener('scroll', onScroll));
onUnmounted(() => window.removeEventListener('scroll', onScroll));
</script>

<template>
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-surface-white border-t border-outline-variant/30 shadow-lg">
        <div class="flex items-center justify-around h-16">
            <button v-for="item in sections" :key="item.id"
                    @click="scrollTo(item.id)"
                    class="flex flex-col items-center gap-0.5 px-3 py-1 transition-colors"
                    :class="activeSection === item.id ? 'text-primary-container' : 'text-text-muted'">
                <component :is="item.icon" :size="22" stroke="1.5" />
                <span class="text-[10px] font-medium">{{ item.label }}</span>
            </button>
        </div>
    </nav>
</template>
