<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconCheck, IconX } from '@tabler/icons-vue';

defineProps({
    users: Array,
});

function approve(user) {
    useForm({}).post(route('admin.verify-users.approve', user.id));
}

function reject(user) {
    useForm({}).post(route('admin.verify-users.reject', user.id));
}
</script>

<template>
    <Head title="Verifikasi Pengguna" />
    <DashboardLayout title="Verifikasi Pengguna">
        <div class="space-y-4">
            <div v-if="users.length === 0" class="bg-white rounded-2xl p-10 text-center shadow-soft border border-outline-variant/30">
                <p class="text-text-body text-body-md">Tidak ada pengguna yang menunggu verifikasi.</p>
            </div>

            <div v-for="user in users" :key="user.id"
                 class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                <div class="flex flex-col md:flex-row gap-6">
                    <div class="flex-1">
                        <h3 class="text-title-lg font-semibold text-primary">{{ user.name }}</h3>
                        <p class="text-text-muted text-label-md mb-4">{{ user.email }}</p>
                        <p class="text-text-muted text-body-md">Daftar: {{ new Date(user.created_at).toLocaleDateString('id-ID') }}</p>
                        <div class="mt-2">
                            <span v-if="user.is_verified"
                                  class="inline-block bg-green-100 text-green-700 px-3 py-1 rounded-full text-label-md font-medium">Terverifikasi</span>
                            <span v-else
                                  class="inline-block bg-amber-100 text-amber-700 px-3 py-1 rounded-full text-label-md font-medium">Menunggu</span>
                        </div>
                    </div>
                    <div class="flex flex-col md:flex-row gap-4">
                        <div v-if="user.identity_photo" class="text-center">
                            <p class="text-label-md font-medium text-primary mb-2">KTP</p>
                            <a :href="'/storage/' + user.identity_photo" target="_blank"
                               class="block w-32 h-32 bg-surface-container-low rounded-xl overflow-hidden border border-outline-variant hover:opacity-80 transition-opacity">
                                <img :src="'/storage/' + user.identity_photo" class="w-full h-full object-cover" />
                            </a>
                        </div>
                        <div v-if="user.photo" class="text-center">
                            <p class="text-label-md font-medium text-primary mb-2">Selfie</p>
                            <a :href="'/storage/' + user.photo" target="_blank"
                               class="block w-32 h-32 bg-surface-container-low rounded-xl overflow-hidden border border-outline-variant hover:opacity-80 transition-opacity">
                                <img :src="'/storage/' + user.photo" class="w-full h-full object-cover" />
                            </a>
                        </div>
                    </div>
                </div>
                <div v-if="!user.is_verified" class="flex gap-3 mt-4 pt-4 border-t border-outline-variant/30">
                    <button @click="approve(user)"
                            class="flex items-center gap-2 bg-green-600 text-white px-5 py-2 rounded-full text-label-md font-medium hover:bg-green-700 transition-colors active:scale-95">
                        <IconCheck :size="18" /> Setujui
                    </button>
                    <button @click="reject(user)"
                            class="flex items-center gap-2 bg-error text-white px-5 py-2 rounded-full text-label-md font-medium hover:bg-red-700 transition-colors active:scale-95">
                        <IconX :size="18" /> Tolak
                    </button>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
