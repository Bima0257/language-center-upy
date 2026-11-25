<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import {
    IconChevronDown,
} from "@tabler/icons-vue";
import { findActiveNavItem, getNav } from "@/Composables/useNav";

const props = defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const roles = page.props.auth?.roles || [];
const permissions = page.props.auth?.permissions || [];

const nav = getNav(roles, permissions);

const openGroups = ref({});

function currentRouteName() {
    try {
        const current = window.route()?.current?.();

        return typeof current === "string" && current ? current : null;
    } catch {
        return null;
    }
}

const activeRouteName = ref(currentRouteName());
const activeNav = computed(() => findActiveNavItem(nav, activeRouteName.value));

function aktif(routeName) {
    if (routeName === "#") return false;

    return activeNav.value?.item?.route === routeName;
}

function isGroupActive(children) {
    const activeChildRoute = activeNav.value?.item?.route;

    return (children || []).some((child) => child.route === activeChildRoute);
}

function itemActiveClass(active) {
    return [
        "relative flex items-center rounded-lg transition-all duration-200",
        props.collapsed ? "justify-center px-2 py-3" : "gap-3 pl-4 pr-4 py-2.5",
        active
            ? "text-sidebar-text font-bold bg-on-primary-container/15"
            : "text-sidebar-text-muted hover:text-sidebar-text-hover hover:bg-on-primary-container/10",
    ];
}

// Parent grup: indikasi lembut saat salah satu submenu aktif (tanpa highlight penuh)
function groupActiveClass(active) {
    return [
        "relative flex items-center rounded-lg transition-all duration-200",
        props.collapsed ? "justify-center px-2 py-3" : "gap-3 pl-4 pr-4 py-2.5",
        active
            ? "text-sidebar-text"
            : "text-sidebar-text-muted hover:text-sidebar-text-hover hover:bg-on-primary-container/10",
    ];
}

function barClass(active, leftClass = "left-0") {
    return [
        "absolute top-1/2 -translate-y-1/2 w-[3px] h-[70%] rounded-r-md bg-accent transition-all duration-200",
        leftClass,
        active ? "opacity-100 scale-y-100" : "opacity-0 scale-y-50",
    ];
}

function toggleGroup(item) {
    if (props.collapsed && item.children) {
        router.visit(route(item.children[0].route));
        return;
    }
    openGroups.value[item.label] = !openGroups.value[item.label];
}

for (const item of nav) {
    if (item.children && isGroupActive(item.children)) {
        openGroups.value[item.label] = true;
    }
}

watch(
    () => page.url,
    () => {
        activeRouteName.value = currentRouteName();

        for (const item of nav) {
            if (item.children && isGroupActive(item.children)) {
                openGroups.value[item.label] = true;
            }
        }
    },
);
</script>

<template>
    <aside
        class="hidden md:flex flex-col px-3 pb-8 bg-sidebar shrink-0 h-full transition-all duration-300 shadow-[6px_0_20px_-8px_rgba(0,0,0,0.25)]"
        :class="collapsed ? 'w-[80px]' : 'w-[280px]'"
    >
        <div
            class="mb-10 flex items-center relative border-b border-on-primary-container/10 dark:border-surface-container-highest transition-all duration-200"
            :class="collapsed ? 'justify-center px-2.5 py-4' : 'gap-3 px-4 py-3'"
        >
            <img
                src="/assets/image/brand/logo-upy.png"
                alt="UPY Language Center"
                title="UPY Language Center"
                class="shrink-0 object-contain rounded-lg"
                :class="collapsed ? 'w-8 h-8' : 'w-10 h-10'"
            />
            <h1
                v-show="!collapsed"
                class="min-w-0 leading-tight text-sidebar-text"
                title="UPY Language Center"
            >
                <span class="block truncate text-title-lg font-bold">UPY</span>
                <span class="block truncate text-label-md font-medium leading-snug text-sidebar-text-muted">Language Center</span>
            </h1>
        </div>

        <nav class="flex-1 space-y-2 overflow-y-auto overscroll-y-contain app-scrollbar">
            <template v-for="item in nav" :key="item.label">
                <div v-if="item.children">
                    <button
                        @click="toggleGroup(item)"
                        class="w-full font-semibold"
                        :class="groupActiveClass(isGroupActive(item.children))"
                    >
                        <component
                            :is="item.icon"
                            :size="22"
                            :stroke="isGroupActive(item.children) ? 2 : 1.5"
                        />
                        <span
                            v-show="!collapsed"
                            class="flex-1 text-left text-body-md leading-tight font-semibold whitespace-nowrap"
                            >{{ item.label }}</span
                        >
                        <IconChevronDown
                            v-show="!collapsed"
                            :size="16"
                            stroke="1.5"
                            class="shrink-0 transition-transform duration-200"
                            :class="
                                openGroups[item.label]
                                    ? 'rotate-0'
                                    : '-rotate-90'
                            "
                        />
                    </button>
                    <Transition name="dropdown">
                        <div
                            v-show="openGroups[item.label]"
                            class="mt-1 space-y-1"
                        >
                            <Link
                                v-for="child in item.children"
                                :key="child.label"
                                :href="route(child.route)"
                                class="group relative flex items-center rounded-lg transition-all duration-200"
                                :class="[
                                    collapsed
                                        ? 'justify-center px-2 py-3'
                                        : 'gap-3 pr-4 py-2.5',
                                    aktif(child.route)
                                         ? 'text-sidebar-text font-bold'
                                         : 'text-sidebar-text-muted hover:text-sidebar-text-hover',
                                ]"
                                :style="
                                    collapsed ? '' : 'padding-left: 2.25rem'
                                "
                            >
                                <span
                                    class="absolute inset-y-0 right-0 rounded-lg transition-all duration-200"
                                    :class="[
                                        collapsed ? 'left-0' : 'left-[20px]',
                                        aktif(child.route)
                                            ? 'bg-on-primary-container/15 dark:bg-surface-container'
                                            : 'group-hover:bg-on-primary-container/10 dark:group-hover:bg-surface-container-high',
                                    ]"
                                ></span>
                                <span
                                    :class="
                                        barClass(
                                            aktif(child.route),
                                            collapsed
                                                ? 'left-0'
                                                : 'left-[20px]',
                                        )
                                    "
                                ></span>
                                <component
                                    :is="child.icon"
                                    :size="18"
                                    :stroke="aktif(child.route) ? 2 : 1.5"
                                    class="shrink-0 relative"
                                />
                                <span
                                    v-show="!collapsed"
                                    class="text-body-md leading-tight font-medium whitespace-nowrap relative"
                                    >{{ child.label }}</span
                                >
                            </Link>
                        </div>
                    </Transition>
                </div>
                <Link
                    v-else
                    :href="item.route === '#' ? '#' : route(item.route)"
                    :class="itemActiveClass(aktif(item.route))"
                >
                    <span :class="barClass(aktif(item.route))"></span>
                    <component
                        :is="item.icon"
                        :size="22"
                        :stroke="aktif(item.route) ? 2 : 1.5"
                    />
                    <span
                        v-show="!collapsed"
                        class="text-body-md leading-tight font-semibold whitespace-nowrap"
                        >{{ item.label }}</span
                    >
                </Link>
            </template>
        </nav>
    </aside>
</template>

<style scoped>
.dropdown-enter-active,
.dropdown-leave-active {
    transition:
        opacity 0.2s ease,
        transform 0.2s ease;
}
.dropdown-enter-from,
.dropdown-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
