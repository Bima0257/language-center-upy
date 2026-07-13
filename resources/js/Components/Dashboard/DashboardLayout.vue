<script setup>
import { ref } from 'vue';
import Sidebar from '@/Components/Dashboard/Sidebar.vue';
import TopBar from '@/Components/Dashboard/TopBar.vue';
import MobileNav from '@/Components/Dashboard/MobileNav.vue';
import ScrollToTop from '@/Components/Dashboard/ScrollToTop.vue';

defineProps({
    title: { type: String, default: 'Ikhtisar' },
});

const sidebarCollapsed = ref(false);
</script>

<template>
    <div class="min-h-screen md:h-screen md:overflow-hidden bg-background flex items-center justify-center p-0 md:p-10">
        <div class="app-frame bg-canvas rounded-none md:rounded-3xl shadow-app-frame overflow-hidden flex relative w-full max-w-[1440px] h-full"
             style="max-height: 900px;">
            <Sidebar :collapsed="sidebarCollapsed" @toggle="sidebarCollapsed = !sidebarCollapsed" />
            <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
                <TopBar :title="title" />
                <div class="flex-1 overflow-y-auto p-8 scrollbar-hide dashboard-scroll-area">
                    <slot />
                </div>
                <ScrollToTop />
            </main>
        </div>
        <MobileNav />
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar { display: none; }
.scrollbar-hide { -ms-overflow-style: none; scrollbar-width: none; }
</style>
