<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { IconLock, IconMail, IconUser } from '@tabler/icons-vue';
import { onMounted, ref } from 'vue';
import AuthInputField from '@/Pages/Auth/Components/AuthInputField.vue';
import AuthPageIntro from '@/Pages/Auth/Components/AuthPageIntro.vue';
import AuthShell from '@/Pages/Auth/Components/AuthShell.vue';
import AuthTurnstile from '@/Pages/Auth/Components/AuthTurnstile.vue';
import GoogleAuthButton from '@/Pages/Auth/Components/GoogleAuthButton.vue';
import PasswordStrengthCheck from '@/Components/Shared/PasswordStrengthCheck.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    'cf-turnstile-response': '',
});

const siteKey = import.meta.env.VITE_TURNSTILE_SITE_KEY;

const showPassword = ref(false);
const showConfirmPassword = ref(false);
const googleLoading = ref(false);
const turnstile = ref(null);
const turnstileVerified = ref(!siteKey);

function onTurnstileVerified(token) {
    form['cf-turnstile-response'] = token;
    turnstileVerified.value = true;
}

function onTurnstileExpired() {
    form['cf-turnstile-response'] = '';
    turnstileVerified.value = false;
}

function onTurnstileError() {
    turnstileVerified.value = false;
}

const submit = () => {
    if (!turnstileVerified.value) return;
    if (siteKey) {
        const token = turnstile.value?.getResponse();
        if (!token) {
            turnstile.value?.reset();
            return;
        }
        form['cf-turnstile-response'] = token;
    }
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
            turnstile.value?.reset();
        },
    });
};

const handleGoogleClick = () => {
    googleLoading.value = true;
};

onMounted(() => {
    document.documentElement.classList.remove('dark');
    sessionStorage.theme = 'light';
});
</script>

<template>
    <Head title="Daftar" />

    <AuthShell>
        <AuthPageIntro
            title="Buat Akun Baru"
            description="Mulai persiapan Tes Bahasa Anda bersama kami."
            spacing-class="mb-4"
        />

        <form class="auth-form flex flex-col gap-3" @submit.prevent="submit">
            <AuthInputField
                id="name"
                label="Nama Lengkap"
                v-model="form.name"
                :icon="IconUser"
                type="text"
                placeholder="Nama Anda"
                autocomplete="name"
                autofocus
                required
                :error="form.errors.name"
            />

            <AuthInputField
                id="email"
                label="Email"
                v-model="form.email"
                :icon="IconMail"
                type="email"
                placeholder="nama@email.com"
                autocomplete="username"
                required
                :error="form.errors.email"
            />

            <AuthInputField
                id="password"
                label="Password"
                v-model="form.password"
                :icon="IconLock"
                type="password"
                placeholder="Min. 8 karakter, huruf besar & kecil, angka, simbol"
                autocomplete="new-password"
                required
                password-toggle
                :show-password="showPassword"
                :error="form.errors.password"
                @toggle-password="showPassword = !showPassword"
            >
                <template #after-input>
                    <PasswordStrengthCheck :password="form.password" />
                </template>
            </AuthInputField>

            <AuthInputField
                id="password_confirmation"
                label="Konfirmasi Password"
                v-model="form.password_confirmation"
                :icon="IconLock"
                type="password"
                placeholder="Ulangi password"
                autocomplete="new-password"
                required
                password-toggle
                :show-password="showConfirmPassword"
                :error="form.errors.password_confirmation"
                @toggle-password="showConfirmPassword = !showConfirmPassword"
            />

            <div class="flex w-full items-center justify-center">
                <AuthTurnstile
                    id="turnstile-register"
                    ref="turnstile"
                    :site-key="siteKey"
                    action="register"
                    :error="form.errors['cf-turnstile-response']"
                    @verified="onTurnstileVerified"
                    @expired="onTurnstileExpired"
                    @error="onTurnstileError"
                />
            </div>

            <BaseButton
                type="submit"
                size="xl"
                class="h-12 w-full"
                :disabled="form.processing || !turnstileVerified"
            >
                {{ form.processing ? 'Memproses...' : 'Daftar' }}
            </BaseButton>
        </form>

        <div class="auth-divider relative my-4 text-center">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-outline-variant"></div>
            </div>
            <span class="relative bg-surface-white px-4 text-label-md font-medium text-text-muted">atau</span>
        </div>

        <GoogleAuthButton
            href="/auth/google/redirect?action=register"
            :loading="googleLoading"
            label="Daftar dengan Google"
            spacing-class="mb-2"
            @click="handleGoogleClick"
        />

        <p class="auth-footer text-center text-body-md text-text-body">
            Sudah punya akun?
            <Link :href="route('login')" class="font-bold text-secondary hover:underline">
                Masuk
            </Link>
        </p>
    </AuthShell>
</template>
