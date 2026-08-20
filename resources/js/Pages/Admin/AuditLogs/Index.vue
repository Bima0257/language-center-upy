<script setup>
import { Head, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconSearch, IconUser } from '@tabler/icons-vue';
import { ref } from 'vue';

const props = defineProps({
    logs: { type: Object, default: () => ({ data: [], links: [], meta: null }) },
    events: { type: Array, default: () => [] },
    filters: { type: Object, default: () => ({}) },
});

const search = ref(props.filters.search || '');
const eventFilter = ref(props.filters.event || '');

function doSearch() {
    router.get(route('admin.audit-logs.index'), {
        search: search.value || undefined,
        event: eventFilter.value || undefined,
    }, { preserveState: true, replace: true });
}

function eventLabel(event) {
    const map = { created: 'Dibuat', updated: 'Diubah', deleted: 'Dihapus', login: 'Login', logout: 'Logout', approved: 'Disetujui', rejected: 'Ditolak' };
    return map[event] || event;
}

function eventBadgeVariant(event) {
    const map = { created: 'success', updated: 'pastel-blue', deleted: 'danger', login: 'success', logout: 'neutral', approved: 'success', rejected: 'danger' };
    return map[event] || 'neutral';
}

function modelShortName(type) {
    if (!type) return '-';
    const parts = type.split('\\');
    return parts[parts.length - 1];
}

function formatProperties(props) {
    if (!props) return '';
    try {
        const obj = typeof props === 'string' ? JSON.parse(props) : props;
        return Object.entries(obj).map(([k, v]) => {
            if (typeof v === 'object' && v !== null) return `${k}: ${JSON.stringify(v)}`;
            return `${k}: ${v}`;
        }).join(', ');
    } catch { return String(props); }
}
</script>

<template>
    <Head title="Log Aktivitas" />
    <DashboardLayout title="Log Aktivitas">
        <div class="flex flex-col md:flex-row gap-3 mb-6">
            <div class="relative flex-1">
                <IconSearch class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                <input type="text" v-model="search" @keyup.enter="doSearch" placeholder="Cari aktivitas..."
                    class="w-full pl-11 pr-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
            </div>
            <select v-model="eventFilter" @change="doSearch"
                class="px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                <option value="">Semua Event</option>
                <option v-for="e in events" :key="e" :value="e">{{ eventLabel(e) }}</option>
            </select>
            <BaseButton @click="doSearch">Cari</BaseButton>
        </div>

        <BaseCard :padding="false" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">User</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Aksi</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Target</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Detail</th>
                            <th class="text-label-md font-semibold text-text-muted px-5 py-4">Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in logs.data" :key="log.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center shrink-0">
                                        <IconUser class="text-text-muted" :size="14" />
                                    </div>
                                    <div>
                                        <p class="text-body-md text-primary font-medium">{{ log.causer?.name || 'System' }}</p>
                                        <p class="text-label-md text-text-muted">{{ log.causer?.email || '-' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <BaseBadge :variant="eventBadgeVariant(log.event)">{{ eventLabel(log.event) }}</BaseBadge>
                            </td>
                            <td class="px-5 py-4 text-text-body text-body-md">{{ modelShortName(log.subject_type) }} #{{ log.subject_id || '-' }}</td>
                            <td class="px-5 py-4 text-text-muted text-body-md max-w-xs truncate">{{ formatProperties(log.properties) || log.description }}</td>
                            <td class="px-5 py-4 text-text-muted text-body-md whitespace-nowrap">
                                {{ new Date(log.created_at).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) }}
                            </td>
                        </tr>
                        <tr v-if="logs.data?.length === 0">
                            <td colspan="5" class="px-5 py-12 text-center text-text-muted text-body-md">Tidak ada log aktivitas.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>

        <div v-if="logs.meta && logs.meta.last_page > 1" class="flex items-center justify-between mt-4">
            <p class="text-label-md text-text-muted">
                {{ logs.meta.from }}–{{ logs.meta.to }} dari {{ logs.meta.total }} log
            </p>
            <div class="flex gap-2">
                <BaseButton v-for="link in logs.links" :key="link.url" :href="link.url" size="sm" variant="secondary"
                    :class="{ 'opacity-50 pointer-events-none': !link.url }">
                    {{ link.label }}
                </BaseButton>
            </div>
        </div>
    </DashboardLayout>
</template>
