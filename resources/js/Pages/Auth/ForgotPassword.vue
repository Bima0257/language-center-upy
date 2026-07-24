<script setup>
import AuthCard from '@/Components/Landing/AuthCard.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({
    status: {
        type: String,
    },
});

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

        <div
            v-if="status"
            class="mb-4 text-sm font-medium text-green-600 bg-green-50 border border-green-200 rounded-2xl px-5 py-4"
        >
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-2">
                <InputLabel for="email" value="Email" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autofocus
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="flex items-center justify-end">
                <PrimaryButton
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Kirim Tautan Reset Password
                </PrimaryButton>
            </div>
        </form>
    </AuthCard>
</template>
