<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";
import Sidebar from "@/Layouts/Dasbor/Components/Sidebar.vue";
import TopBar from "@/Layouts/Dasbor/Components/TopBar.vue";
import MobileNav from "@/Layouts/Dasbor/Components/MobileNav.vue";
import ScrollToTop from "@/Components/Shared/ScrollToTop.vue";
import ConfirmDialog from "@/Components/Shared/ConfirmDialog.vue";
import Breadcrumb from "@/Layouts/Dasbor/Components/Breadcrumb.vue";
import PageHeader from "@/Layouts/Dasbor/Components/PageHeader.vue";
import { getBreadcrumbs } from "@/Composables/useNav";

const props = defineProps({
    title: { type: String, default: "Dasbor" },
    subtitle: { type: String, default: "" },
    hideHeader: { type: Boolean, default: false },
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
            <TopBar @toggle-sidebar="sidebarCollapsed = !sidebarCollapsed" />
            <div
                class="flex-1 overflow-y-auto scrollbar-hide dashboard-scroll-area bg-surface"
            >
                <div class="min-h-full flex flex-col p-8">
                    <Breadcrumb :items="breadcrumbs" class="mb-6" />
                    <PageHeader v-if="!hideHeader" :title="title" :subtitle="subtitle" />
                    <slot />
                    <div class="mt-auto -mx-8 -mb-8 pt-8">
                        <footer
                            class="px-8 py-3 bg-surface border-t border-outline-variant/30 flex items-center justify-between text-label-md text-text-muted"
                        >
                            <span>© {{ currentYear }} UPY Language Center</span>
                            <span v-if="appVersion">v{{ appVersion }}</span>
                        </footer>
                    </div>
                </div>
            </div>
            <ScrollToTop container=".dashboard-scroll-area" />
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
