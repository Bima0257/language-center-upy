<script setup>
import { Head } from '@inertiajs/vue3';
import Navbar from '@/Components/Landing/Navbar.vue';
import Hero from '@/Components/Landing/Hero.vue';
import Features from '@/Components/Landing/Features.vue';
import CTA from '@/Components/Landing/CTA.vue';
import Footer from '@/Components/Landing/Footer.vue';
import MobileBottomNav from '@/Components/Landing/MobileBottomNav.vue';
import ScrollToTop from '@/Components/ScrollToTop.vue';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
});
</script>

<template>
    <Head title="UPY Language Test" />

    <div class="min-h-screen md:h-screen md:overflow-hidden bg-deep-space p-0 md:p-10" style="font-family: 'Plus Jakarta Sans', sans-serif;">
        <div class="flex flex-col h-full max-w-[1440px] mx-auto overflow-hidden md:rounded-3xl shadow-standard">
            <Navbar :can-login="canLogin" :can-register="canRegister" class="shrink-0" />

            <main class="flex-1 bg-[#f4f4f7] overflow-hidden relative">
                <div class="h-full overflow-y-auto landing-scroll-area pt-6 md:pt-8">
                    <Hero />
                    <Features />
                    <div id="pricing">
                        <CTA />
                    </div>
                    <Footer />
                </div>
                <ScrollToTop inline />
            </main>
        </div>

        <MobileBottomNav />
    </div>
</template>
