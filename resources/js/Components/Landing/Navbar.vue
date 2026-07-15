<script setup>
import { Link } from '@inertiajs/vue3';
import { IconSearch, IconMenu2, IconX } from '@tabler/icons-vue';
import { ref } from 'vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});

const mobileMenuOpen = ref(false);
</script>

<template>
    <nav v-bind="$attrs" class="bg-surface-white w-full px-4 md:px-app-margin py-4 shadow-sm sticky top-0 z-50">
        <div class="flex justify-between items-center max-w-7xl mx-auto w-full">
            <div class="flex items-center gap-2">
                <img src="/assets/image/logo.png" alt="UPY Language Test" class="h-10 w-auto object-contain" />
                <span class="text-headline-md font-bold text-primary">UPY Language Test</span>
            </div>

            <div class="hidden md:flex items-center gap-8">
                <Link href="#features" class="text-text-body hover:text-primary transition-colors duration-200 text-label-md font-medium">Fitur</Link>
                <Link href="#pricing" class="text-text-body hover:text-primary transition-colors duration-200 text-label-md font-medium">Harga</Link>
                <Link href="#methodology" class="text-text-body hover:text-primary transition-colors duration-200 text-label-md font-medium">Metode</Link>
                <Link href="#about" class="text-text-body hover:text-primary transition-colors duration-200 text-label-md font-medium">Tentang</Link>
            </div>

            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center bg-surface-container-low px-4 py-2 rounded-full gap-2 border border-outline-variant/30">
                    <IconSearch class="text-text-muted" :size="16" />
                    <input class="bg-transparent border-none text-body-md focus:ring-0 p-0 w-32 placeholder:text-text-muted text-sm" placeholder="Cari..." type="text" />
                </div>
                <div class="hidden lg:flex items-center gap-2">
                    <Link :href="route('login')" class="px-5 py-2 text-primary font-bold text-label-md hover:opacity-80 transition-opacity">
                        Masuk
                    </Link>
                    <Link :href="route('register')" class="bg-primary-container text-white px-6 py-2.5 rounded-full font-bold text-label-md active:scale-95 transition-transform hover:opacity-90">
                        Daftar
                    </Link>
                </div>
                <button class="md:hidden p-1" @click="mobileMenuOpen = !mobileMenuOpen">
                    <IconMenu2 v-if="!mobileMenuOpen" class="text-primary" :size="24" />
                    <IconX v-else class="text-primary" :size="24" />
                </button>
            </div>
        </div>
    </nav>

    <div v-if="mobileMenuOpen" class="md:hidden bg-surface-white border-t border-outline-variant/30 px-4 py-4 space-y-3">
        <Link href="#features" class="block text-text-body text-body-md py-2" @click="mobileMenuOpen = false">Fitur</Link>
        <Link href="#pricing" class="block text-text-body text-body-md py-2" @click="mobileMenuOpen = false">Harga</Link>
        <Link href="#methodology" class="block text-text-body text-body-md py-2" @click="mobileMenuOpen = false">Metode</Link>
        <Link href="#about" class="block text-text-body text-body-md py-2" @click="mobileMenuOpen = false">Tentang</Link>
        <hr class="border-outline-variant/30">
        <Link :href="route('login')" class="block text-primary font-bold text-label-md py-2">Masuk</Link>
        <Link :href="route('register')" class="block bg-primary-container text-white px-6 py-2.5 rounded-full font-bold text-label-md text-center">
            Daftar
        </Link>
    </div>
</template>
