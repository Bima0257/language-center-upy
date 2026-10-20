<script setup>
import { Link } from "@inertiajs/vue3";
import { IconMenu2, IconSearch, IconMail, IconBell, IconSun, IconMoon, IconSettings, IconLogout } from "@tabler/icons-vue";
import { ref, onMounted, onUnmounted } from "vue";

defineEmits(["toggle-sidebar"]);

const accountOpen = ref(false);

function toggleAccount() {
    accountOpen.value = !accountOpen.value;
}

function closeAccount() {
    accountOpen.value = false;
}

function onDocumentClick(event) {
    if (!event.target.closest("[data-account-menu]")) {
        accountOpen.value = false;
    }
}

function onEscape(event) {
    if (event.key === "Escape") {
        accountOpen.value = false;
    }
}

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
    document.addEventListener('click', onDocumentClick);
    document.addEventListener('keydown', onEscape);
});

onUnmounted(() => {
    document.removeEventListener('click', onDocumentClick);
    document.removeEventListener('keydown', onEscape);
});
</script>

<template>
    <header
        class="relative z-10 flex justify-between items-center w-full px-8 py-3 bg-surface-container-low dark:bg-surface-container-lowest shadow-[0_8px_24px_-8px_rgba(0,0,0,0.3)]"
    >
        <div class="flex items-center gap-4">
            <button
                @click="$emit('toggle-sidebar')"
                class="w-10 h-10 flex items-center justify-center text-text-body hover:bg-surface-container-low dark:hover:bg-surface-container rounded-full transition-colors"
                title="Ciutkan / perluas sidebar"
            >
                <IconMenu2 :size="22" stroke="1.5" />
            </button>
            <div class="relative hidden lg:block">
                <IconSearch
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted"
                    :size="18"
                />
                <input
                    class="bg-surface-container-low dark:bg-surface-container border-none rounded-full py-2.5 pl-12 pr-6 w-72 text-text-body text-body-md focus:ring-2 focus:ring-primary/10 placeholder:text-text-muted"
                    placeholder="Tekan Ctrl + F untuk mencari"
                    type="text"
                />
            </div>
        </div>
        <div class="flex items-center gap-6">
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
                <div class="relative" data-account-menu>
                    <button
                        @click="toggleAccount"
                        class="rounded-full transition-all"
                        :class="accountOpen ? 'ring-2 ring-secondary/40' : ''"
                        title="Menu akun"
                    >
                        <BaseAvatar
                            variant="neutral"
                            :name="$page.props.auth.user.name"
                            :size="40"
                            class="border-2 border-outline-variant cursor-pointer"
                        />
                    </button>
                    <Transition
                        enter-active-class="transition duration-100 ease-out"
                        enter-from-class="transform scale-95 opacity-0"
                        enter-to-class="transform scale-100 opacity-100"
                        leave-active-class="transition duration-75 ease-in"
                        leave-from-class="transform scale-100 opacity-100"
                        leave-to-class="transform scale-95 opacity-0"
                    >
                        <div
                            v-show="accountOpen"
                            class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-2xl bg-surface-white border border-outline-variant shadow-soft"
                        >
                            <div class="px-4 py-3 border-b border-outline-variant/30">
                                <p class="text-body-md font-semibold text-primary truncate">{{ $page.props.auth.user.name }}</p>
                                <p class="text-label-md text-text-muted truncate">{{ $page.props.auth.user.email }}</p>
                            </div>
                            <div class="py-1.5">
                                <Link
                                    :href="route('profile.edit')"
                                    @click="closeAccount"
                                    class="flex items-center gap-3 px-4 py-2.5 text-body-md text-text-body hover:bg-surface-container-low transition-colors"
                                >
                                    <IconSettings :size="18" class="text-text-muted" /> Pengaturan
                                </Link>
                                <Link
                                    :href="route('logout')"
                                    method="post"
                                    as="button"
                                    class="flex w-full items-center gap-3 px-4 py-2.5 text-body-md text-error-red hover:bg-error-red/5 transition-colors"
                                >
                                    <IconLogout :size="18" /> Keluar
                                </Link>
                            </div>
                        </div>
                    </Transition>
                </div>
            </div>
        </div>
    </header>
</template>
