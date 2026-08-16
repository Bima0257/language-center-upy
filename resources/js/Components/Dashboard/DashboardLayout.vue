<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import Sidebar from "@/Components/Dashboard/Sidebar.vue";
import TopBar from "@/Components/Dashboard/TopBar.vue";
import MobileNav from "@/Components/Dashboard/MobileNav.vue";
import ScrollToTop from "@/Components/ScrollToTop.vue";
import ConfirmDialog from "@/Components/ConfirmDialog.vue";
import Breadcrumb from "@/Components/Dashboard/Breadcrumb.vue";
import { getBreadcrumbs } from "@/Composables/useNav";

const props = defineProps({
    title: { type: String, default: "Dashboard" },
});

const breadcrumbs = getBreadcrumbs(props.title);

const sidebarCollapsed = ref(false);

const page = usePage();
const appVersion = page.props.app?.version || "";
const currentYear = new Date().getFullYear();
</script>

<template>
    <div class="min-h-screen md:h-screen md:overflow-hidden flex">
        <Sidebar
            :collapsed="sidebarCollapsed"
        />
        <main class="flex-1 flex flex-col min-w-0 overflow-hidden relative">
            <TopBar :title="title" @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed" />
            <div
                class="flex-1 overflow-y-auto p-8 scrollbar-hide dashboard-scroll-area bg-surface"
            >
                <Breadcrumb :items="breadcrumbs" class="mb-6" />
                <slot />
            </div>
            <ScrollToTop container=".dashboard-scroll-area" />
            <footer
                class="shrink-0 px-8 py-3 bg-surface border-t border-outline-variant/30 flex items-center justify-between text-label-md text-text-muted"
            >
                <span>© {{ currentYear }} UPY Language Center</span>
                <span v-if="appVersion">v{{ appVersion }}</span>
            </footer>
        </main>
        <MobileNav />
        <ConfirmDialog />
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
