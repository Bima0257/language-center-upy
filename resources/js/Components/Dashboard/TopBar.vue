<script setup>
import { IconSearch, IconMail, IconBell } from "@tabler/icons-vue";
import { Link } from "@inertiajs/vue3";

defineProps({
    title: { type: String, default: "Dashboard" },
});
</script>

<template>
    <header
        class="flex justify-between items-center w-full px-8 py-6 bg-surface-white"
    >
        <h2 class="text-headline-md font-bold text-primary">{{ title }}</h2>
        <div class="flex items-center gap-6">
            <div class="relative hidden lg:block">
                <IconSearch
                    class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted"
                    :size="18"
                />
                <input
                    class="bg-surface-container-low border-none rounded-full py-2.5 pl-12 pr-6 w-72 text-body-md focus:ring-2 focus:ring-primary/10 placeholder:text-text-muted"
                    placeholder="Tekan Ctrl + F untuk mencari"
                    type="text"
                />
            </div>
            <div class="flex items-center gap-4">
                <button
                    class="w-10 h-10 flex items-center justify-center text-text-body hover:bg-surface-container-low rounded-full transition-colors"
                >
                    <IconMail :size="22" stroke="1.5" />
                </button>
                <button
                    class="w-10 h-10 flex items-center justify-center text-text-body hover:bg-surface-container-low rounded-full transition-colors relative"
                >
                    <IconBell :size="22" stroke="1.5" />
                    <span
                        class="absolute top-2.5 right-2.5 w-2 h-2 bg-error-red rounded-full border-2 border-surface-white"
                    ></span>
                </button>
                <div
                    class="w-10 h-10 rounded-full overflow-hidden border-2 border-outline-variant bg-surface-container flex items-center justify-center text-primary font-bold text-sm"
                >
                    {{ $page.props.auth.user.name.charAt(0).toUpperCase() }}
                </div>
            </div>
        </div>
    </header>
</template>
