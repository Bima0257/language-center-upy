<script setup>
import AuthCard from '@/Components/Landing/AuthCard.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
});

const submit = () => {
    form.post(route('password.email'));
};
</script>

<template>
    <AuthCard
        title="Lupa password?"
        subtitle="Jangan khawatir, kami akan bantu mengamankan akun Anda."
    >
        <Head title="Lupa Password" />

        <header class="mb-8">
            <h1 class="text-primary text-headline-md font-bold mb-2">Lupa Password</h1>
            <p class="text-text-body text-body-md">Masukkan email Anda dan kami akan kirimkan tautan reset password.</p>
        </header>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-2">
                <BaseFieldLabel for="email" value="Email" />

                <BaseInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <BaseInputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center justify-end">
                <BaseButton
                    :disabled="form.processing"
                >
                    Kirim Tautan Reset Password
                </BaseButton>
            </div>
        </form>
    </AuthCard>
</template>
