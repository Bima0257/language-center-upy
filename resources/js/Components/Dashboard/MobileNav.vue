<script setup>
import { Link } from "@inertiajs/vue3";
import {
    IconChartPie,
    IconBooks,
    IconAward,
    IconUser,
    IconLogout,
} from "@tabler/icons-vue";

const tabs = [
    {
        label: "Dashboard",
        icon: IconChartPie,
        route: "dashboard",
        active: true,
    },
    { label: "Kursus", icon: IconBooks, route: "#" },
    { label: "Lencana", icon: IconAward, route: "#" },
    { label: "Profil", icon: IconUser, route: "#" },
    { label: "Keluar", icon: IconLogout, route: "logout", method: "post" },
];
</script>

<template>
    <nav
        class="md:hidden fixed bottom-0 left-0 right-0 bg-surface-white border-t border-outline-variant px-4 py-2 flex justify-between items-center z-50"
    >
        <template v-for="tab in tabs" :key="tab.label">
            <Link
                v-if="tab.method"
                :href="route(tab.route)"
                method="post"
                as="button"
                class="flex flex-col items-center gap-0.5 px-2 py-1 text-text-muted hover:text-primary transition-colors"
            >
                <component :is="tab.icon" :size="22" stroke="1.5" />
                <span class="text-[10px] font-medium">{{ tab.label }}</span>
            </Link>
            <Link
                v-else
                :href="tab.route === 'dashboard' ? route(tab.route) : tab.route"
                class="flex flex-col items-center gap-0.5 px-2 py-1 transition-colors"
                :class="
                    tab.active
                        ? 'text-primary'
                        : 'text-text-muted hover:text-primary'
                "
            >
                <component :is="tab.icon" :size="22" stroke="1.5" />
                <span class="text-[10px] font-medium">{{ tab.label }}</span>
            </Link>
        </template>
    </nav>
</template>
