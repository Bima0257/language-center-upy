<script setup>
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref, watch } from "vue";
import {
    IconChartPie,
    IconBooks,
    IconSettings,
    IconLogout,
    IconMenu2,
    IconClipboardCheck,
    IconFileDescription,
    IconEyeCheck,
    IconUsers,
    IconReport,
    IconDatabase,
    IconCertificate,
    IconFolders,
    IconCategory,
    IconListDetails,
    IconChevronDown,
} from "@tabler/icons-vue";

const props = defineProps({
    collapsed: { type: Boolean, default: false },
});

defineEmits(["toggle"]);

const page = usePage();
const roles = page.props.auth?.roles || [];

const isStudent = roles.includes("student");
const isInstructor = roles.includes("instructor");
const isAdmin = roles.includes("admin") || roles.includes("superadmin");
const isProctorOnly = roles.includes("proctor") && !isAdmin;

const nav = [];

if (isStudent) {
    nav.push(
        { label: "Dashboard", icon: IconChartPie, route: "dashboard" },
        { label: "Tryout", icon: IconClipboardCheck, route: "exam.available" },
    );
}

if (isInstructor) {
    nav.push(
        { label: "Dashboard", icon: IconChartPie, route: "dashboard" },
        { label: "Bank Soal", icon: IconBooks, route: "content-library.index" },
        {
            label: "Materi Soal",
            icon: IconFileDescription,
            route: "content-library.passages.index",
        },
    );
}

if (isProctorOnly) {
    nav.push(
        { label: "Dashboard", icon: IconChartPie, route: "dashboard" },
        {
            label: "Dashboard Pengawas",
            icon: IconEyeCheck,
            route: "proctor.dashboard",
        },
    );
}

if (isAdmin) {
    nav.push(
        { label: "Dashboard", icon: IconChartPie, route: "dashboard" },
        {
            label: "Soal",
            icon: IconBooks,
            children: [
                {
                    label: "Jenis Tes",
                    icon: IconCategory,
                    route: "admin.master-data.exam-types.index",
                },
                {
                    label: "Bank Soal Manager",
                    icon: IconFolders,
                    route: "content-library.question-banks.index",
                },
                {
                    label: "Master Skill",
                    icon: IconDatabase,
                    route: "admin.master-data.skills.index",
                },
                {
                    label: "Part Soal",
                    icon: IconListDetails,
                    route: "admin.master-data.parts.index",
                },
                {
                    label: "Materi Soal",
                    icon: IconFileDescription,
                    route: "content-library.passages.index",
                },
                {
                    label: "Bank Soal",
                    icon: IconBooks,
                    route: "content-library.index",
                },
            ],
        },
        {
            label: "Manajemen Ujian",
            icon: IconFileDescription,
            route: "admin.exams.index",
        },
        { label: "Verifikasi", icon: IconUsers, route: "admin.verify-users" },
        {
            label: "Sertifikat",
            icon: IconCertificate,
            route: "admin.certificates.index",
        },
        {
            label: "Dashboard Pengawas",
            icon: IconEyeCheck,
            route: "proctor.dashboard",
        },
        {
            label: "Laporan",
            icon: IconReport,
            route: "admin.reports.integrity",
        },
    );
}

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
            ? "text-white font-bold bg-white/15"
            : "text-white/70 hover:text-white hover:bg-white/10",
    ];
}

function barClass(active, leftClass = "left-0") {
    return [
        "absolute top-1/2 -translate-y-1/2 w-[3px] h-[70%] rounded-r-md bg-white transition-all duration-200",
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
        class="hidden md:flex flex-col py-8 px-3 bg-[#010020] shrink-0 h-full transition-all duration-300 shadow-[6px_0_20px_-8px_rgba(0,0,0,0.25)]"
        :class="collapsed ? 'w-[80px]' : 'w-[260px]'"
    >
        <div
            class="mb-10 flex items-center relative"
            :class="collapsed ? 'px-1' : 'px-4 gap-3'"
        >
            <img
                :src="'/assets/image/logo-white.png'"
                alt="Logo"
                class="shrink-0 object-contain rounded-xl"
                :class="collapsed ? 'w-8 h-8' : 'w-10 h-10'"
            />
            <h1
                v-show="!collapsed"
                class="text-headline-md font-bold text-white whitespace-nowrap"
            >
                UPY
            </h1>
            <button
                v-if="!collapsed"
                @click="$emit('toggle')"
                class="absolute -right-2 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center text-white/70 hover:text-white transition-colors z-10"
                title="Ciutkan sidebar"
            >
                <IconMenu2 :size="18" stroke="1.5" />
            </button>
            <button
                v-else
                @click="$emit('toggle')"
                class="ml-auto shrink-0 w-6 h-6 flex items-center justify-center text-white/70 hover:text-white transition-colors z-10"
                title="Perluas sidebar"
            >
                <IconMenu2 :size="16" stroke="1.5" />
            </button>
        </div>

        <nav class="flex-1 space-y-2">
            <template v-for="item in nav" :key="item.label">
                <div v-if="item.children">
                    <button
                        @click="toggleGroup(item)"
                        class="w-full"
                        :class="itemActiveClass(isGroupActive(item.children))"
                    >
                        <span
                            :class="barClass(isGroupActive(item.children))"
                        ></span>
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
                                        ? 'text-white font-bold'
                                        : 'text-white/70 hover:text-white',
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
                                            ? 'bg-white/15'
                                            : 'group-hover:bg-white/10',
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

        <div class="mt-auto pt-6 border-t border-white/10 space-y-2">
            <Link
                v-for="item in bottom"
                :key="item.label"
                :href="item.route"
                class="flex items-center rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-all duration-200"
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
                class="flex items-center rounded-lg text-white/70 hover:text-white hover:bg-white/10 transition-all w-full text-left"
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
