<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { IconLock, IconMail } from '@tabler/icons-vue';
import { onMounted, ref } from 'vue';
import AuthInputField from '@/Pages/Auth/Components/AuthInputField.vue';
import AuthPageIntro from '@/Pages/Auth/Components/AuthPageIntro.vue';
import AuthShell from '@/Pages/Auth/Components/AuthShell.vue';
import AuthTurnstile from '@/Pages/Auth/Components/AuthTurnstile.vue';
import GoogleAuthButton from '@/Pages/Auth/Components/GoogleAuthButton.vue';

defineProps({
    canResetPassword: Boolean,
    status: String,
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
    'cf-turnstile-response': '',
});

const siteKey = import.meta.env.VITE_TURNSTILE_SITE_KEY;

const showPassword = ref(false);
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
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
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
    <Head title="Masuk" />

    <AuthShell>
        <AuthPageIntro
            title="Masuk ke Akun Anda"
            description="Selamat datang kembali, silakan masuk untuk melanjutkan."
            spacing-class="mb-4"
        />

        <form class="auth-form flex flex-col gap-3" @submit.prevent="submit">
            <AuthInputField
                id="email"
                label="Email"
                v-model="form.email"
                :icon="IconMail"
                type="email"
                placeholder="nama@email.com"
                autocomplete="username"
                autofocus
                required
                :error="form.errors.email"
            />

            <AuthInputField
                id="password"
                label="Password"
                v-model="form.password"
                :icon="IconLock"
                type="password"
                placeholder="••••••••"
                autocomplete="current-password"
                required
                password-toggle
                :show-password="showPassword"
                :error="form.errors.password"
                @toggle-password="showPassword = !showPassword"
            >
                <template #labelAction>
                    <Link
                        v-if="canResetPassword"
                        :href="route('password.request')"
                        class="text-secondary text-label-md font-medium hover:underline"
                    >
                        Lupa password?
                    </Link>
                </template>
            </AuthInputField>

            <BaseCheckbox v-model="form.remember" label="Ingat saya" />

            <div class="flex w-full items-center justify-center">
                <AuthTurnstile
                    id="turnstile-login"
                    ref="turnstile"
                    :site-key="siteKey"
                    action="login"
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
                {{ form.processing ? 'Memproses...' : 'Masuk' }}
            </BaseButton>
        </form>

        <div class="auth-divider relative my-4 text-center">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-outline-variant"></div>
            </div>
            <span class="relative bg-surface-white px-4 text-label-md font-medium text-text-muted">atau</span>
        </div>

        <GoogleAuthButton
            :href="'/auth/google/redirect?action=login&remember=' + (form.remember ? '1' : '0')"
            :loading="googleLoading"
            label="Masuk dengan Google"
            spacing-class="mb-2"
            @click="handleGoogleClick"
        />

        <p class="auth-footer text-center text-body-md text-text-body">
            Belum punya akun?
            <Link :href="route('register')" class="font-bold text-secondary hover:underline">
                Daftar sekarang
            </Link>
        </p>
    </AuthShell>
</template>
