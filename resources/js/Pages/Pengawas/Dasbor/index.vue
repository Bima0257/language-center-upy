<script setup>
import { Head, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import KartuSesiBerlangsung from '@/Pages/Pengawas/Dasbor/components/KartuSesiBerlangsung.vue';
import DaftarSesiDitandai from '@/Pages/Pengawas/Dasbor/components/DaftarSesiDitandai.vue';
import { IconRefresh } from '@tabler/icons-vue';
import { onMounted, onUnmounted } from 'vue';

defineProps({
    sesiAktif: { type: Array, default: () => [] },
    sesiDitandai: { type: Array, default: () => [] },
});

let refreshInterval = null;

onMounted(() => {
    refreshInterval = setInterval(() => {
        router.reload({ preserveScroll: true, only: ['sesiAktif', 'sesiDitandai'] });
    }, 10000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

function refreshNow() {
    router.reload({ preserveScroll: true, only: ['sesiAktif', 'sesiDitandai'] });
}
</script>

<template>
    <Head title="Dasbor Pengawas" />
    <TataLetakDasbor title="Dasbor Pengawas" subtitle="Pantau sesi ujian yang sedang berjalan">
        <div class="flex justify-end mb-4">
            <BaseButton variant="secondary" size="xs" @click="refreshNow">
                <IconRefresh :size="18" /> Segarkan (auto 10 detik)
            </BaseButton>
        </div>
        <div class="space-y-6">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-title-lg font-semibold text-text-heading">Sesi Aktif ({{ sesiAktif.length }})</h2>
                </div>
                <div v-if="sesiAktif.length === 0" class="bg-surface-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
                    <p class="text-text-muted text-body-md">Tidak ada sesi ujian aktif.</p>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <KartuSesiBerlangsung v-for="sesiUjian in sesiAktif" :key="sesiUjian.id" :sesi-ujian="sesiUjian" />
                </div>
            </div>
            <DaftarSesiDitandai :sesi-ditandai="sesiDitandai" />
        </div>
    </TataLetakDasbor>
</template>
