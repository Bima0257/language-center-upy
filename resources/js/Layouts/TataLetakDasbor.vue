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
    breadcrumbs: { type: Array, default: null },
    backHref: { type: String, default: null },
    backLabel: { type: String, default: "Kembali" },
    preferHistory: { type: Boolean, default: true },
});

const breadcrumbs = props.breadcrumbs || getBreadcrumbs(props.title);

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
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                        <Breadcrumb :items="breadcrumbs" class="min-w-0 flex-1" />
                        <div
                            v-if="$slots['breadcrumb-actions']"
                            class="ml-auto flex shrink-0 items-center gap-2"
                        >
                            <slot name="breadcrumb-actions" />
                        </div>
                    </div>
                    <PageHeader
                        v-if="!hideHeader"
                        :title="title"
                        :subtitle="subtitle"
                        :back-href="backHref"
                        :back-label="backLabel"
                        :prefer-history="preferHistory"
                    />
                    <BaseBackButton
                        v-else-if="backHref"
                        :href="backHref"
                        :label="backLabel"
                        :prefer-history="preferHistory"
                        class="mb-6"
                    />
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
            <ScrollToTop container=".dashboard-scroll-area" :bottom-threshold="96" />
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
