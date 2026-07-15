<script setup>
import { ref } from "vue";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";
import TopBar from "@/Components/Dashboard/TopBar.vue";
import MobileNav from "@/Components/Dashboard/MobileNav.vue";
import ScrollToTop from "@/Components/Dashboard/ScrollToTop.vue";

defineProps({
    title: { type: String, default: "Dashboard" },
});

const sidebarCollapsed = ref(false);
</script>

<template>
    <div class="min-h-screen md:h-screen md:overflow-hidden flex">
        <Sidebar
            :collapsed="sidebarCollapsed"
            @toggle="sidebarCollapsed = !sidebarCollapsed"
        />
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            <TopBar :title="title" />
            <div
                class="flex-1 overflow-y-auto p-8 scrollbar-hide dashboard-scroll-area"
            >
                <slot />
            </div>
            <ScrollToTop />
        </main>
        <MobileNav />
    </div>
</template>

<style scoped>
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
