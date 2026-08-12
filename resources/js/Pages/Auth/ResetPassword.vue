<script setup>
import AuthCard from '@/Components/Landing/AuthCard.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import PasswordStrengthCheck from '@/Components/PasswordStrengthCheck.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    email: {
        type: String,
        required: true,
    },
    token: {
        type: String,
        required: true,
    },
});

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <AuthCard
        title="Buat password baru."
        subtitle="Pilih password yang kuat dan mudah diingat untuk akun Anda."
    >
        <Head title="Reset Password" />

        <header class="mb-8">
            <h1 class="text-primary text-headline-md font-bold mb-2">Reset Password</h1>
            <p class="text-text-body text-body-md">Masukkan password baru untuk akun Anda.</p>
        </header>

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

            <div class="space-y-2">
                <InputLabel for="password" value="Password Baru" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <PasswordStrengthCheck :password="form.password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="space-y-2">
                <InputLabel
                    for="password_confirmation"
                    value="Konfirmasi Password"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="flex items-center justify-end">
                <BaseButton
                    :disabled="form.processing"
                >
                    Reset Password
                </BaseButton>
            </div>
        </form>
    </AuthCard>
</template>
