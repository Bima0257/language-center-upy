<script setup>
import { IconSearch, IconMail, IconBell, IconSun, IconMoon } from "@tabler/icons-vue";
import { Link } from "@inertiajs/vue3";
import { ref, onMounted } from "vue";

defineProps({
    title: { type: String, default: "Dashboard" },
});

const isDark = ref(false);

function syncTheme() {
    isDark.value = document.documentElement.classList.contains('dark');
}

function toggleTheme() {
    isDark.value = !isDark.value;
    if (isDark.value) {
        document.documentElement.classList.add('dark');
        localStorage.dashboardTheme = 'dark';
    } else {
        document.documentElement.classList.remove('dark');
        localStorage.dashboardTheme = 'light';
    }
    document.dispatchEvent(new CustomEvent('dashboard-theme-changed'));
}

onMounted(() => {
    syncTheme();
    document.addEventListener('dashboard-theme-changed', syncTheme);
});
</script>

<template>
    <header
        class="relative z-10 flex justify-between items-center w-full px-8 py-6 bg-surface-container-low dark:bg-surface-container-lowest shadow-[0_8px_24px_-8px_rgba(0,0,0,0.3)]"
    >
        <h2 class="text-headline-md font-bold text-primary dark:text-text-heading">{{ title }}</h2>
        <div class="flex items-center gap-6">
            <div class="relative hidden lg:block">
                <IconSearch
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted"
                    :size="18"
                />
                <input
                    class="bg-surface-container-low dark:bg-surface-container border-none rounded-full py-2.5 pl-12 pr-6 w-72 text-body-md focus:ring-2 focus:ring-primary/10 placeholder:text-text-muted"
                    placeholder="Tekan Ctrl + F untuk mencari"
                    type="text"
                />
            </div>
            <div class="flex items-center gap-4">
                <button
                    class="w-10 h-10 flex items-center justify-center text-text-body hover:bg-surface-container-low dark:hover:bg-surface-container rounded-full transition-colors"
                >
                    <IconMail :size="22" stroke="1.5" />
                </button>
                <button
                    class="w-10 h-10 flex items-center justify-center text-text-body hover:bg-surface-container-low dark:hover:bg-surface-container rounded-full transition-colors relative"
                >
                    <IconBell :size="22" stroke="1.5" />
                    <span
                        class="absolute top-2.5 right-2.5 w-2 h-2 bg-error-red rounded-full border-2 border-surface-white dark:border-surface-container-lowest"
                    ></span>
                </button>
                <button @click="toggleTheme"
                        class="w-10 h-10 flex items-center justify-center text-text-body hover:bg-surface-container-low dark:hover:bg-surface-container rounded-full transition-colors"
                        :title="isDark ? 'Mode Terang' : 'Mode Gelap'">
                    <IconSun v-if="isDark" :size="20" class="transition-transform hover:rotate-90" />
                    <IconMoon v-else :size="20" class="transition-transform hover:rotate-12" />
                </button>
                <BaseAvatar
                    variant="neutral"
                    :name="$page.props.auth.user.name"
                    :size="40"
                    class="border-2 border-outline-variant"
                />
            </div>
        </div>
    </header>
</template>
