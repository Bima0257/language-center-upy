<script setup>
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import DeleteUserForm from './Partials/DeleteUserForm.vue';
import UpdatePasswordForm from './Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from './Partials/UpdateProfileInformationForm.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});
</script>

<template>
    <Head title="Profil" />

    <TataLetakDasbor title="Profil" subtitle="Kelola informasi akun Anda">
        <div class="mx-auto max-w-3xl space-y-6">
            <BaseCard padding="p-8">
                <UpdateProfileInformationForm
                    :must-verify-email="mustVerifyEmail"
                    :status="status"
                    class="max-w-xl"
                />
            </BaseCard>

            <BaseCard padding="p-8">
                <UpdatePasswordForm class="max-w-xl" />
            </BaseCard>

            <BaseCard padding="p-8">
                <DeleteUserForm class="max-w-xl" />
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
