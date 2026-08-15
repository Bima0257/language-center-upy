<script setup>
import AuthCard from '@/Components/Landing/AuthCard.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    password: '',
});

const submit = () => {
    form.post(route('password.confirm'), {
        onFinish: () => form.reset(),
    });
};
</script>

<template>
    <AuthCard
        title="Verifikasi keamanan."
        subtitle="Konfirmasi identitas Anda sebelum melanjutkan ke area sensitif."
    >
        <Head title="Konfirmasi Password" />

        <header class="mb-8">
            <h1 class="text-primary text-headline-md font-bold mb-2">Konfirmasi Password</h1>
            <p class="text-text-body text-body-md">Ini adalah area aman. Silakan konfirmasi password Anda sebelum melanjutkan.</p>
        </header>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-2">
                <BaseFieldLabel for="password" value="Password" />
                <BaseInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="current-password"
                    autofocus
                />
                <BaseInputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="flex justify-end">
                <BaseButton
                    class="ms-4"
                    :disabled="form.processing"
                >
                    Konfirmasi
                </BaseButton>
            </div>
        </form>
    </AuthCard>
</template>
