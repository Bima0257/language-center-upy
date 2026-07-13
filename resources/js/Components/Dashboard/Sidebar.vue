<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import {
    IconSparkles, IconChartPie, IconBooks,
    IconSettings, IconLogout, IconMenu2,
    IconClipboardCheck, IconFileDescription, IconEyeCheck,
    IconUsers, IconReport, IconTags,
} from '@tabler/icons-vue';

defineProps({
    collapsed: { type: Boolean, default: false },
});

defineEmits(['toggle']);

const page = usePage();
const roles = page.props.auth?.roles || [];

const isStudent    = roles.includes('student');
const isInstructor = roles.includes('instructor');
const isAdmin      = roles.includes('admin') || roles.includes('superadmin');
const isProctorOnly= roles.includes('proctor') && !isAdmin;

const nav = [];

if (isStudent) {
    nav.push(
        { label: 'Ikhtisar', icon: IconChartPie, route: 'dashboard' },
        { label: 'Tryout', icon: IconClipboardCheck, route: 'exam.available' },
    );
}

if (isInstructor) {
    nav.push(
        { label: 'Ikhtisar', icon: IconChartPie, route: 'dashboard' },
        { label: 'Manajemen Ujian', icon: IconFileDescription, route: 'admin.exams.index' },
        { label: 'Bank Soal', icon: IconBooks, route: 'content-library.index' },
        { label: 'Tag', icon: IconTags, route: 'content-library.tags.index' },
    );
}

if (isProctorOnly) {
    nav.push(
        { label: 'Ikhtisar', icon: IconChartPie, route: 'dashboard' },
        { label: 'Dashboard Pengawas', icon: IconEyeCheck, route: 'proctor.dashboard' },
    );
}

if (isAdmin) {
    nav.push(
        { label: 'Ikhtisar', icon: IconChartPie, route: 'dashboard' },
        { label: 'Manajemen Ujian', icon: IconFileDescription, route: 'admin.exams.index' },
        { label: 'Verifikasi', icon: IconUsers, route: 'admin.verify-users' },
        { label: 'Dashboard Pengawas', icon: IconEyeCheck, route: 'proctor.dashboard' },
        { label: 'Laporan', icon: IconReport, route: 'admin.reports.integrity' },
    );
}

const bottom = [
    { label: 'Pengaturan', icon: IconSettings, route: route('profile.edit') },
];

function isActive(routeName) {
    if (routeName === '#') return false;
    try {
        return window.route().current(routeName);
    } catch {
        return page.url?.includes(routeName.replace('.', '/'));
    }
}
</script>

<template>
    <aside class="hidden md:flex flex-col py-8 px-3 bg-surface-white border-r border-outline-variant shrink-0 h-full transition-all duration-300"
           :class="collapsed ? 'w-[80px]' : 'w-[260px]'">
        <div class="mb-10 flex items-center relative" :class="collapsed ? 'px-1' : 'px-4 gap-3'">
            <div class="bg-primary-container rounded-xl flex items-center justify-center shrink-0"
                 :class="collapsed ? 'w-8 h-8' : 'w-10 h-10'">
                <IconSparkles class="text-white" :size="collapsed ? 16 : 20" stroke="1.5" fill="currentColor" />
            </div>
            <h1 v-show="!collapsed" class="text-headline-md font-bold text-primary whitespace-nowrap">UPY</h1>
            <button v-if="!collapsed" @click="$emit('toggle')"
                    class="absolute -right-4 top-1/2 -translate-y-1/2 w-8 h-8 rounded-full bg-white border border-outline-variant shadow-sm flex items-center justify-center text-text-muted hover:text-primary hover:border-primary transition-colors z-10"
                    title="Ciutkan sidebar">
                <IconMenu2 :size="18" stroke="1.5" />
            </button>
            <button v-else @click="$emit('toggle')"
                    class="ml-auto shrink-0 w-6 h-6 flex items-center justify-center text-text-muted hover:text-primary transition-colors z-10"
                    title="Perluas sidebar">
                <IconMenu2 :size="16" stroke="1.5" />
            </button>
        </div>

        <nav class="flex-1 space-y-2">
            <Link v-for="item in nav" :key="item.label"
                  :href="item.route === '#' ? '#' : route(item.route)"
                  class="flex items-center rounded-lg transition-all"
                  :class="[collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-4 py-3', isActive(item.route) ? 'text-primary font-bold border-l-4 border-primary bg-surface-container-low' : 'text-text-body hover:bg-surface-container-low']">
                <component :is="item.icon" :size="22" stroke="1.5" />
                <span v-show="!collapsed" class="text-label-md font-medium whitespace-nowrap">{{ item.label }}</span>
            </Link>
        </nav>

        <div class="mt-auto pt-6 border-t border-outline-variant space-y-2">
            <Link v-for="item in bottom" :key="item.label"
                  :href="item.route"
                  class="flex items-center rounded-lg text-text-body hover:bg-surface-container-low transition-all"
                  :class="collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-4 py-3'">
                <component :is="item.icon" :size="22" stroke="1.5" />
                <span v-show="!collapsed" class="text-label-md font-medium whitespace-nowrap">{{ item.label }}</span>
            </Link>
            <Link :href="route('logout')" method="post" as="button"
                  class="flex items-center rounded-lg text-text-body hover:bg-surface-container-low transition-all w-full text-left"
                  :class="collapsed ? 'justify-center px-2 py-3' : 'gap-3 px-4 py-3'">
                <IconLogout :size="22" stroke="1.5" />
                <span v-show="!collapsed" class="text-label-md font-medium whitespace-nowrap">Keluar</span>
            </Link>
        </div>
    </aside>
</template>
