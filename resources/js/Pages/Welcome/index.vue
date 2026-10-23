<script setup>
import { Head } from '@inertiajs/vue3';
import Navbar from '@/Pages/Welcome/components/Navbar.vue';
import Hero from '@/Pages/Welcome/components/Hero.vue';
import Features from '@/Pages/Welcome/components/Features.vue';
import CTA from '@/Pages/Welcome/components/CTA.vue';
import Footer from '@/Pages/Welcome/components/Footer.vue';
import MobileBottomNav from '@/Pages/Welcome/components/MobileBottomNav.vue';
import ScrollToTop from '@/Components/Shared/ScrollToTop.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="UPY Language Test" />

    <div class="flex flex-col min-h-screen md:h-screen md:overflow-hidden bg-surface">
        <Navbar :can-login="canLogin" :can-register="canRegister" class="shrink-0" />

        <main class="flex-1 overflow-hidden relative">
            <div class="h-full overflow-y-auto landing-scroll-area pt-6 md:pt-8">
                <Hero />
                <Features />
                <div id="pricing">
                    <CTA />
                </div>
                <Footer />
            </div>
            <ScrollToTop inline container=".landing-scroll-area" />
        </main>

        <MobileBottomNav />
    </div>
</template>
