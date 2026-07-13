<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import LiveSessionCard from '@/Components/Proctor/LiveSessionCard.vue';
import { IconAlertTriangle, IconRefresh } from '@tabler/icons-vue';
import { onMounted, onUnmounted } from 'vue';

defineProps({
    activeSessions: { type: Array, default: () => [] },
    flaggedSessions: { type: Array, default: () => [] },
});

let refreshInterval = null;

onMounted(() => {
    refreshInterval = setInterval(() => {
        router.reload({ preserveScroll: true, only: ['activeSessions', 'flaggedSessions'] });
    }, 10000);
});

onUnmounted(() => {
    if (refreshInterval) clearInterval(refreshInterval);
});

function refreshNow() {
    router.reload({ preserveScroll: true, only: ['activeSessions', 'flaggedSessions'] });
}
</script>

<template>
    <Head title="Dashboard Pengawas" />
    <DashboardLayout title="Dashboard Pengawas">
        <div class="flex justify-end mb-4">
            <button @click="refreshNow"
                    class="flex items-center gap-2 bg-surface-white border border-outline-variant text-primary px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all active:scale-95">
                <IconRefresh :size="18" /> Segarkan (auto 10 detik)
            </button>
        </div>
        <div class="space-y-6">
            <div>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-title-lg font-semibold text-primary">Sesi Aktif ({{ activeSessions.length }})</h2>
                </div>
                <div v-if="activeSessions.length === 0" class="bg-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
                    <p class="text-text-muted text-body-md">Tidak ada sesi ujian aktif.</p>
                </div>
                <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <LiveSessionCard v-for="session in activeSessions" :key="session.id" :session="session" />
                </div>
            </div>
            <div>
                <h2 class="text-title-lg font-semibold text-primary mb-4 flex items-center gap-2">
                    <IconAlertTriangle class="text-amber-500" :size="22" />
                    Sesi Perlu Review ({{ flaggedSessions.length }})
                </h2>
                <div v-if="flaggedSessions.length === 0" class="bg-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
                    <p class="text-text-muted text-body-md">Semua sesi dalam keadaan baik.</p>
                </div>
                <div v-else class="space-y-3">
                    <div v-for="session in flaggedSessions" :key="session.id"
                         class="bg-white rounded-2xl p-6 shadow-soft border border-error-red/30 hover:border-error-red transition-colors">
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="font-semibold text-primary">{{ session.user?.name }}</p>
                                <p class="text-text-muted text-label-md">{{ session.user?.nim || 'NIM: -' }}</p>
                                <p class="text-text-muted text-label-md">{{ session.flag_reason }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-label-md font-medium" :class="session.violation_strikes >= 3 ? 'text-error-red' : 'text-amber-600'">
                                    {{ session.violation_strikes }} pelanggaran
                                </p>
                                <Link :href="route('proctor.session.show', session.id)"
                                      class="text-secondary text-label-md font-medium hover:underline">
                                    Tinjau →
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
