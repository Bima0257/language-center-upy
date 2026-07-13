<script setup>
import { IconSparkles, IconWorld, IconBrandGithub } from '@tabler/icons-vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <footer class="bg-surface-container w-full border-t border-outline-variant/30">
        <div class="w-full py-12 px-4 md:px-app-margin max-w-7xl mx-auto flex flex-col md:flex-row justify-between gap-8">
            <div class="max-w-xs">
                <div class="flex items-center gap-2 mb-4">
                    <div class="bg-primary p-1.5 rounded-lg">
                        <IconSparkles class="text-white" :size="16" stroke="1.5" fill="currentColor" />
                    </div>
                    <span class="text-title-lg font-bold text-primary">UPY Language Test</span>
                </div>
                <p class="text-text-body text-body-md mb-6">
                    Memberdayakan mahasiswa di seluruh Indonesia dengan persiapan TOEFL iBT dan bimbingan ahli.
                </p>
                <div class="flex gap-4">
                    <a href="#" class="w-8 h-8 rounded-full bg-surface-white flex items-center justify-center text-primary-container hover:bg-primary-container hover:text-white transition-colors">
                        <IconWorld :size="16" />
                    </a>
                    <a href="#" class="w-8 h-8 rounded-full bg-surface-white flex items-center justify-center text-primary-container hover:bg-primary-container hover:text-white transition-colors">
                        <IconBrandGithub :size="16" />
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 gap-8 md:gap-16">
                <div>
                    <h4 class="text-primary font-bold mb-4 text-label-md">Produk</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-text-body text-label-md hover:text-secondary-container hover:underline transition-colors">Kursus</a></li>
                        <li><a href="#" class="text-text-body text-label-md hover:text-secondary-container hover:underline transition-colors">Harga</a></li>
                        <li><a href="#" class="text-text-body text-label-md hover:text-secondary-container hover:underline transition-colors">Metode</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-primary font-bold mb-4 text-label-md">Perusahaan</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-text-body text-label-md hover:text-secondary-container hover:underline transition-colors">Tentang</a></li>
                        <li><a href="#" class="text-text-body text-label-md hover:text-secondary-container hover:underline transition-colors">Cerita Sukses</a></li>
                        <li><a href="#" class="text-text-body text-label-md hover:text-secondary-container hover:underline transition-colors">Keamanan</a></li>
                    </ul>
                </div>
                <div class="col-span-2 sm:col-span-1">
                    <h4 class="text-primary font-bold mb-4 text-label-md">Legal</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="text-text-body text-label-md hover:text-secondary-container hover:underline transition-colors">Ketentuan Layanan</a></li>
                        <li><a href="#" class="text-text-body text-label-md hover:text-secondary-container hover:underline transition-colors">Kebijakan Privasi</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="w-full py-6 px-4 md:px-app-margin max-w-7xl mx-auto border-t border-outline-variant/30 flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-text-muted text-label-md">&copy; 2026 UPY Language Test. Hak cipta dilindungi.</p>
        </div>
    </footer>
</template>
