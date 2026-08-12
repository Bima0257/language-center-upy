<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconCheck, IconX, IconRotate } from '@tabler/icons-vue';

defineProps({
    users: Array,
});

function approve(user) {
    useForm({}).post(route('admin.verify-users.approve', user.id), { preserveScroll: true });
}

function reject(user) {
    useForm({}).post(route('admin.verify-users.reject', user.id), { preserveScroll: true });
}

function revert(user) {
    useForm({}).post(route('admin.verify-users.revert', user.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Verifikasi Pengguna" />
    <DashboardLayout title="Verifikasi Pengguna">
        <BaseCard :padding="false" class="overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Nama</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">NIM</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Fakultas / Prodi</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">KTM</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Selfie</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Status</th>
                            <th class="text-label-md font-semibold text-text-muted uppercase tracking-wider px-5 py-4">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="user in users" :key="user.id"
                            class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-5 py-4">
                                <p class="font-medium text-primary">{{ user.name }}</p>
                                <p class="text-text-muted text-label-md">{{ user.email }}</p>
                            </td>
                            <td class="px-5 py-4 text-body-md text-text-body">{{ user.student_profile?.nim || '-' }}</td>
                            <td class="px-5 py-4">
                                <p class="text-body-md text-text-body">{{ user.student_profile?.faculty?.name || '-' }}</p>
                                <p class="text-text-muted text-label-md">{{ user.student_profile?.department?.name || '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <a v-if="user.student_profile?.identity_photo"
                                   :href="'/storage/' + user.student_profile.identity_photo" target="_blank"
                                   class="block w-16 h-16 bg-surface-container-low rounded-lg overflow-hidden border border-outline-variant hover:opacity-80 transition-opacity">
                                    <img :src="'/storage/' + user.student_profile.identity_photo"
                                         class="w-full h-full object-cover" />
                                </a>
                                <span v-else class="text-text-muted text-label-md">-</span>
                            </td>
                            <td class="px-5 py-4">
                                <a v-if="user.photo"
                                   :href="'/storage/' + user.photo" target="_blank"
                                   class="block w-16 h-16 bg-surface-container-low rounded-lg overflow-hidden border border-outline-variant hover:opacity-80 transition-opacity">
                                    <img :src="'/storage/' + user.photo"
                                         class="w-full h-full object-cover" />
                                </a>
                                <span v-else class="text-text-muted text-label-md">-</span>
                            </td>
                            <td class="px-5 py-4">
                                <BaseBadge v-if="user.is_verified" variant="success">Terverifikasi</BaseBadge>
                                <BaseBadge v-else variant="warning">Menunggu</BaseBadge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <template v-if="user.is_verified">
                                        <BaseButton variant="warning" size="sm" @click="revert(user)">
                                            <IconRotate :size="16" /> Batalkan
                                        </BaseButton>
                                    </template>
                                    <template v-else>
                                        <BaseButton variant="success" size="sm" @click="approve(user)">
                                            <IconCheck :size="16" /> Setujui
                                        </BaseButton>
                                        <BaseButton variant="danger" size="sm" @click="reject(user)">
                                            <IconX :size="16" /> Tolak
                                        </BaseButton>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-text-muted text-body-md">
                                Tidak ada pengguna yang menunggu verifikasi.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>
    </DashboardLayout>
</template>
