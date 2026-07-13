<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <section class="max-w-7xl mx-auto mb-16 px-4 md:px-0">
        <div class="bg-surface-white rounded-3xl p-8 md:p-12 text-center border border-outline-variant/30 shadow-standard relative overflow-hidden">
            <div class="absolute -top-24 -left-24 w-64 h-64 bg-pastel-blue/40 rounded-full blur-3xl"></div>
            <div class="absolute -bottom-24 -right-24 w-64 h-64 bg-pastel-purple/40 rounded-full blur-3xl"></div>
            <div class="relative z-10">
                <h2 class="text-text-heading text-headline-md font-bold mb-4">
                    Siap capai skor impianmu?
                </h2>
                <p class="text-text-body text-body-md mb-10 max-w-xl mx-auto">
                    Bergabunglah dengan ribuan mahasiswa yang telah meningkatkan skor TOEFL mereka.
                </p>
                <Link :href="route('register')"
                      class="inline-block bg-primary-container text-white px-10 py-4 rounded-full font-bold text-title-lg hover:scale-105 active:scale-95 transition-all">
                    Mulai Uji Coba Gratis
                </Link>
                <p class="mt-4 text-text-muted text-label-md">
                    Tidak perlu kartu kredit. Batalkan kapan saja.
                </p>
            </div>
        </div>
    </section>
</template>
