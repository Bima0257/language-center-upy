<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import {
    IconSettings,
    IconLogout,
    IconChevronDown,
} from "@tabler/icons-vue";
import { getNav } from "@/Composables/useNav";

const props = defineProps({
    collapsed: { type: Boolean, default: false },
});

const page = usePage();
const roles = page.props.auth?.roles || [];

const nav = getNav(roles);

const bottom = [
    { label: "Pengaturan", icon: IconSettings, route: route("profile.edit") },
];

const openGroups = ref({});

function isActive(routeName) {
    if (routeName === "#") return false;
    try {
        return window.route().current(routeName);
    } catch {
        return page.url?.includes(routeName.replace(".", "/"));
    }
}

function isGroupActive(children) {
    return (children || []).some((child) => isActive(child.route));
}

function itemActiveClass(active) {
    return [
        "relative flex items-center rounded-lg transition-all duration-200",
        props.collapsed ? "justify-center px-2 py-3" : "gap-3 pl-4 pr-4 py-3",
        active
            ? "text-white font-bold bg-white/15 dark:text-primary dark:bg-surface-container"
            : "text-white/70 hover:text-white hover:bg-white/10 dark:text-text-body dark:hover:text-text-heading dark:hover:bg-surface-container",
    ];
}

// Parent grup: indikasi lembut saat salah satu submenu aktif (tanpa highlight penuh)
function groupActiveClass(active) {
    return [
        "relative flex items-center rounded-lg transition-all duration-200",
        props.collapsed ? "justify-center px-2 py-3" : "gap-3 pl-4 pr-4 py-3",
        active
            ? "text-white dark:text-primary"
            : "text-white/70 hover:text-white hover:bg-white/10 dark:text-text-body dark:hover:text-text-heading dark:hover:bg-surface-container",
    ];
}

function barClass(active, leftClass = "left-0") {
    return [
        "absolute top-1/2 -translate-y-1/2 w-[3px] h-[70%] rounded-r-md bg-white dark:bg-primary transition-all duration-200",
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
        class="hidden md:flex flex-col py-8 px-3 bg-[#010020] dark:bg-surface-container-low shrink-0 h-full transition-all duration-300 shadow-[6px_0_20px_-8px_rgba(0,0,0,0.25)]"
        :class="collapsed ? 'w-[80px]' : 'w-[260px]'"
    >
        <div
            class="mb-10 flex items-center relative"
            :class="collapsed ? 'justify-center' : ''"
        >
            <div
                class="flex items-center rounded-xl bg-white/5 dark:bg-surface-container transition-all duration-200"
                :class="collapsed ? 'justify-center p-2.5' : 'w-full gap-3 px-4 py-3'"
            >
                <img
                    :src="'/assets/image/logo-white.png'"
                    alt="Logo"
                    class="shrink-0 object-contain rounded-lg"
                    :class="collapsed ? 'w-8 h-8' : 'w-10 h-10'"
                />
                <h1
                    v-show="!collapsed"
                    class="text-headline-md font-bold text-white dark:text-primary whitespace-nowrap"
                >
                    UPY
                </h1>
            </div>
        </div>

        <nav class="flex-1 space-y-2 scrollbar-hide">
            <template v-for="item in nav" :key="item.label">
                <div v-if="item.children">
                    <button
                        @click="toggleGroup(item)"
                        class="w-full"
                        :class="groupActiveClass(isGroupActive(item.children))"
                    >
                        <component
                            :is="item.icon"
                            :size="22"
                            :stroke="isGroupActive(item.children) ? 2 : 1.5"
                        />
                        <span
                            v-show="!collapsed"
                            class="flex-1 text-left text-label-md font-medium whitespace-nowrap"
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
                                        : 'gap-3 pr-4 py-3',
                                    isActive(child.route)
                                        ? 'text-white font-bold dark:text-primary'
                                        : 'text-white/70 hover:text-white dark:text-text-body dark:hover:text-text-heading',
                                ]"
                                :style="
                                    collapsed ? '' : 'padding-left: 2.25rem'
                                "
                            >
                                <span
                                    class="absolute inset-y-0 right-0 rounded-lg transition-all duration-200"
                                    :class="[
                                        collapsed ? 'left-0' : 'left-[20px]',
                                        isActive(child.route)
                                            ? 'bg-white/15 dark:bg-surface-container'
                                            : 'group-hover:bg-white/10 dark:group-hover:bg-surface-container-high',
                                    ]"
                                ></span>
                                <span
                                    :class="
                                        barClass(
                                            isActive(child.route),
                                            collapsed
                                                ? 'left-0'
                                                : 'left-[20px]',
                                        )
                                    "
                                ></span>
                                <component
                                    :is="child.icon"
                                    :size="18"
                                    :stroke="isActive(child.route) ? 2 : 1.5"
                                    class="shrink-0 relative"
                                />
                                <span
                                    v-show="!collapsed"
                                    class="text-label-md whitespace-nowrap relative"
                                    >{{ child.label }}</span
                                >
                            </Link>
                        </div>
                    </Transition>
                </div>
                <Link
                    v-else
                    :href="item.route === '#' ? '#' : route(item.route)"
                    :class="itemActiveClass(isActive(item.route))"
                >
                    <span :class="barClass(isActive(item.route))"></span>
                    <component
                        :is="item.icon"
                        :size="22"
                        :stroke="isActive(item.route) ? 2 : 1.5"
                    />
                    <span
                        v-show="!collapsed"
                        class="text-label-md font-medium whitespace-nowrap"
                        >{{ item.label }}</span
                    >
                </Link>
            </template>
        </nav>

        <div class="mt-auto pt-6 border-t border-white/10 dark:border-transparent space-y-2">
            <Link
                v-for="item in bottom"
                :key="item.label"
                :href="item.route"
                class="flex items-center rounded-lg text-white/70 hover:text-white hover:bg-white/10 dark:text-text-body dark:hover:text-text-heading dark:hover:bg-surface-container transition-all duration-200"
                :class="
                    collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-4 py-3'
                "
            >
                <component :is="item.icon" :size="22" stroke="1.5" />
                <span
                    v-show="!collapsed"
                    class="text-label-md font-medium whitespace-nowrap"
                    >{{ item.label }}</span
                >
            </Link>
            <Link
                :href="route('logout')"
                method="post"
                as="button"
                class="flex items-center rounded-lg text-white/70 hover:text-white hover:bg-white/10 dark:text-text-body dark:hover:text-text-heading dark:hover:bg-surface-container transition-all w-full text-left"
                :class="
                    collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-4 py-3'
                "
            >
                <IconLogout :size="22" stroke="1.5" />
                <span
                    v-show="!collapsed"
                    class="text-label-md font-medium whitespace-nowrap"
                    >Keluar</span
                >
            </Link>
        </div>
    </aside>
</template>

<style scoped>
.scrollbar-hide {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
.scrollbar-hide::-webkit-scrollbar {
    display: none;
}
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
