<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { useMediaLoad } from '@/Composables/useMediaLoad';
import { IconCheck, IconX, IconRotate } from '@tabler/icons-vue';

defineProps({
    pengguna: Array,
});

const identityLoading = useMediaLoad();
const photoLoading = useMediaLoad();

function approve(pengguna) {
    useForm({}).post(route('admin.verifikasi-pengguna.setujui', pengguna.id), { preserveScroll: true });
}

function reject(pengguna) {
    useForm({}).post(route('admin.verifikasi-pengguna.tolak', pengguna.id), { preserveScroll: true });
}

function revert(pengguna) {
    useForm({}).post(route('admin.verifikasi-pengguna.batalkan-verifikasi', pengguna.id), { preserveScroll: true });
}
</script>

<template>
    <Head title="Verifikasi Pengguna" />
    <TataLetakDasbor title="Verifikasi Pengguna" subtitle="Setujui atau tolak identitas peserta">
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
                        <tr v-for="pengguna in pengguna" :key="pengguna.id"
                            class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                            <td class="px-5 py-4">
                                <p class="font-medium text-text-heading">{{ pengguna.name }}</p>
                                <p class="text-text-muted text-label-md">{{ pengguna.email }}</p>
                            </td>
                            <td class="px-5 py-4 text-body-md text-text-body">{{ pengguna.profil_mahasiswa?.nim || '-' }}</td>
                            <td class="px-5 py-4">
                                <p class="text-body-md text-text-body">{{ pengguna.profil_mahasiswa?.fakultas?.nama || '-' }}</p>
                                <p class="text-text-muted text-label-md">{{ pengguna.profil_mahasiswa?.program_studi?.nama || '-' }}</p>
                            </td>
                            <td class="px-5 py-4">
                                <a v-if="pengguna.profil_mahasiswa?.foto_identitas"
                                   :href="'/media/' + pengguna.profil_mahasiswa.foto_identitas" target="_blank"
                                   class="block w-16 h-16 bg-surface-container-low rounded-lg overflow-hidden border border-outline-variant hover:opacity-80 transition-opacity">
                                    <BaseMediaLoader
                                        :loading="identityLoading.loading.value"
                                        media-type="image"
                                        skeleton-class="h-16 rounded-none border-0"
                                    >
                                        <img :src="'/media/' + pengguna.profil_mahasiswa.foto_identitas"
                                             class="w-full h-full object-cover"
                                             @load="identityLoading.onLoad()"
                                             @error="identityLoading.onError()" />
                                    </BaseMediaLoader>
                                </a>
                                <span v-else class="text-text-muted text-label-md">-</span>
                            </td>
                            <td class="px-5 py-4">
                                <a v-if="pengguna.foto"
                                   :href="'/media/' + pengguna.foto" target="_blank"
                                   class="block w-16 h-16 bg-surface-container-low rounded-lg overflow-hidden border border-outline-variant hover:opacity-80 transition-opacity">
                                    <BaseMediaLoader
                                        :loading="photoLoading.loading.value"
                                        media-type="image"
                                        skeleton-class="h-16 rounded-none border-0"
                                    >
                                        <img :src="'/media/' + pengguna.foto"
                                             class="w-full h-full object-cover"
                                             @load="photoLoading.onLoad()"
                                             @error="photoLoading.onError()" />
                                    </BaseMediaLoader>
                                </a>
                                <span v-else class="text-text-muted text-label-md">-</span>
                            </td>
                            <td class="px-5 py-4">
                                <BaseBadge v-if="pengguna.terverifikasi" variant="success">Terverifikasi</BaseBadge>
                                <BaseBadge v-else variant="warning">Menunggu</BaseBadge>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-2">
                                    <template v-if="pengguna.terverifikasi">
                                        <BaseButton variant="warning" size="sm" @click="revert(pengguna)">
                                            <IconRotate :size="16" /> Batalkan
                                        </BaseButton>
                                    </template>
                                    <template v-else>
                                        <BaseButton variant="success" size="sm" @click="approve(pengguna)">
                                            <IconCheck :size="16" /> Setujui
                                        </BaseButton>
                                        <BaseButton variant="danger" size="sm" @click="reject(pengguna)">
                                            <IconX :size="16" /> Tolak
                                        </BaseButton>
                                    </template>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="pengguna.length === 0">
                            <td colspan="7" class="px-5 py-12 text-center text-text-muted text-body-md">
                                Tidak ada pengguna yang menunggu verifikasi.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </BaseCard>
    </TataLetakDasbor>
</template>
