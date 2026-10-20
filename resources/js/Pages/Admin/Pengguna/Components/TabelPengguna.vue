<script setup>
import { IconEdit, IconToggleLeft, IconToggleRight, IconKey } from '@tabler/icons-vue';

const props = defineProps({
    pengguna: { type: Object, required: true },
    roleLabel: { type: Function, required: true },
});

const emit = defineEmits(['edit', 'toggle-active', 'reset-password']);

const roleColors = {
    superadmin: 'danger',
    admin: 'danger',
    student: 'success',
};
</script>

<template>
    <BaseCard :padding="false" class="overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-outline-variant/30 bg-surface-container-low">
                        <th class="text-label-md font-semibold text-text-muted px-5 py-4">Nama</th>
                        <th class="text-label-md font-semibold text-text-muted px-5 py-4">Email</th>
                        <th class="text-label-md font-semibold text-text-muted px-5 py-4">Role</th>
                        <th class="text-label-md font-semibold text-text-muted px-5 py-4">Status</th>
                        <th class="text-label-md font-semibold text-text-muted px-5 py-4">Sesi</th>
                        <th class="text-label-md font-semibold text-text-muted px-5 py-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in props.pengguna.data" :key="user.id" class="border-b border-outline-variant/20 last:border-0 hover:bg-surface-container-low/50 transition-colors">
                        <td class="px-5 py-4">
                            <p class="font-medium text-primary text-body-md">{{ user.name }}</p>
                            <p v-if="user.profil_mahasiswa?.nim" class="text-label-md text-text-muted">{{ user.profil_mahasiswa.nim }}</p>
                        </td>
                        <td class="px-5 py-4 text-text-body text-body-md">{{ user.email }}</td>
                        <td class="px-5 py-4">
                            <BaseBadge :variant="roleColors[user.roles?.[0]?.name] || 'neutral'">
                                {{ props.roleLabel(user.roles?.[0]?.name || '-') }}
                            </BaseBadge>
                        </td>
                        <td class="px-5 py-4">
                            <span class="inline-flex items-center gap-1.5 text-label-md" :class="user.aktif ? 'text-success' : 'text-error-red'">
                                <span class="w-2 h-2 rounded-full" :class="user.aktif ? 'bg-success' : 'bg-error-red'"></span>
                                {{ user.aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-text-body text-body-md">{{ user.sesi_ujian_count ?? 0 }}</td>
                        <td class="px-5 py-4">
                            <div class="flex items-center gap-1">
                                <button @click="emit('edit', user)" class="p-2 text-text-muted hover:text-secondary transition-colors" title="Ubah">
                                    <IconEdit :size="18" />
                                </button>
                                <button @click="emit('toggle-active', user)" class="p-2 transition-colors" :class="user.aktif ? 'text-text-muted hover:text-error-red' : 'text-text-muted hover:text-success'" :title="user.aktif ? 'Nonaktifkan' : 'Aktifkan'">
                                    <IconToggleRight v-if="user.aktif" :size="18" />
                                    <IconToggleLeft v-else :size="18" />
                                </button>
                                <button @click="emit('reset-password', user)" class="p-2 text-text-muted hover:text-warning transition-colors" title="Reset Password">
                                    <IconKey :size="18" />
                                </button>
                            </div>
                        </td>
                    </tr>
                    <tr v-if="props.pengguna.data?.length === 0">
                        <td colspan="6" class="px-5 py-12 text-center text-text-muted text-body-md">Tidak ada data pengguna.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </BaseCard>
</template>
