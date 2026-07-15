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
        <div class="bg-white rounded-2xl shadow-soft border border-outline-variant/30 overflow-hidden">
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
                                <p class="text-body-md text-text-body">{{ user.student_profile?.faculty || '-' }}</p>
                                <p class="text-text-muted text-label-md">{{ user.student_profile?.department || '-' }}</p>
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
                                <span v-if="user.is_verified"
                                      class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-label-md font-medium">
                                    Terverifikasi
                                </span>
                                <span v-else
                                      class="inline-block bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-label-md font-medium">
                                    Menunggu
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <template v-if="user.is_verified">
                                        <button @click="revert(user)"
                                                class="flex items-center gap-1.5 bg-amber-500 text-white px-4 py-2 rounded-full text-label-md font-medium hover:bg-amber-600 transition-colors active:scale-95">
                                            <IconRotate :size="16" /> Batalkan
                                        </button>
                                    </template>
                                    <template v-else>
                                        <button @click="approve(user)"
                                                class="flex items-center gap-1.5 bg-green-600 text-white px-4 py-2 rounded-full text-label-md font-medium hover:bg-green-700 transition-colors active:scale-95">
                                            <IconCheck :size="16" /> Setujui
                                        </button>
                                        <button @click="reject(user)"
                                                class="flex items-center gap-1.5 bg-error-red text-white px-4 py-2 rounded-full text-label-md font-medium hover:bg-red-700 transition-colors active:scale-95">
                                            <IconX :size="16" /> Tolak
                                        </button>
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
        </div>
    </DashboardLayout>
</template>
