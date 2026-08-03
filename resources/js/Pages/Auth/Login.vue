<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { IconMail, IconLock, IconEye, IconEyeOff } from '@tabler/icons-vue';
import AuthCard from '@/Components/Landing/AuthCard.vue';
import { ref, onMounted } from 'vue';

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
const turnstileWidgetId = ref(null);
const turnstileVerified = ref(!siteKey);

const renderTurnstile = () => {
    if (!siteKey) return;
    if (!window.turnstile) return;
    turnstileWidgetId.value = window.turnstile.render('#turnstile-login', {
        sitekey: siteKey,
        callback: (token) => {
            form['cf-turnstile-response'] = token;
            turnstileVerified.value = true;
        },
        'expired-callback': () => {
            form['cf-turnstile-response'] = '';
            turnstileVerified.value = false;
            window.turnstile?.reset(turnstileWidgetId.value);
        },
        'error-callback': () => {
            turnstileVerified.value = false;
        },
    });
};

window.onTurnstileLoad = renderTurnstile;

onMounted(() => {
    if (window.turnstile) {
        renderTurnstile();
    }
});

const submit = () => {
    if (!turnstileVerified.value) return;
    if (window.turnstile) {
        const token = window.turnstile.getResponse(turnstileWidgetId.value);
        if (!token) {
            window.turnstile.reset(turnstileWidgetId.value);
            return;
        }
        form['cf-turnstile-response'] = token;
    }
    form.post(route('login'), {
        onFinish: () => {
            form.reset('password');
            window.turnstile?.reset(turnstileWidgetId.value);
        },
    });
};

const handleGoogleClick = () => {
    googleLoading.value = true;
};
</script>

<template>
    <Head title="Masuk" />

    <AuthCard
        title="Tingkatkan skor TOEFL iBT-mu."
        subtitle="Bergabunglah dengan ribuan kandidat yang meraih impian mereka melalui lingkungan belajar profesional kami."
    >
        <header class="mb-5">
            <h1 class="text-primary text-headline-md font-bold mb-2">Masuk ke Akun Anda</h1>
            <p class="text-text-body text-body-md">Senang melihat Anda kembali. Silakan masukkan data Anda.</p>
        </header>

        <div class="bg-surface-container-lowest rounded-2xl p-5 md:p-6">
        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
                <label class="text-primary text-label-md font-medium block ml-1" for="email">Email</label>
                <div class="relative">
                    <IconMail class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autofocus
                        autocomplete="username"
                        placeholder="nama@email.com"
                        class="w-full pl-11 pr-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-primary text-body-md transition-all placeholder:text-text-muted focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
                    />
                </div>
                <p v-if="form.errors.email" class="text-error-red text-xs mt-1 ml-1">{{ form.errors.email }}</p>
            </div>

            <div class="space-y-2">
                <div class="flex justify-between items-center px-1">
                    <label class="text-primary text-label-md font-medium" for="password">Password</label>
                    <Link v-if="canResetPassword" :href="route('password.request')" class="text-secondary text-label-md font-medium hover:underline">
                        Lupa password?
                    </Link>
                </div>
                <div class="relative">
                    <IconLock class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                    <input
                        :id="'password'"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="current-password"
                        placeholder="••••••••"
                        class="w-full pl-11 pr-12 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-primary text-body-md transition-all placeholder:text-text-muted focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
                    />
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-text-muted hover:text-primary transition-colors">
                        <IconEye v-if="!showPassword" :size="20" />
                        <IconEyeOff v-else :size="20" />
                    </button>
                </div>
                <p v-if="form.errors.password" class="text-error-red text-xs mt-1 ml-1">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center gap-2">
                <input id="remember" type="checkbox" v-model="form.remember" class="rounded border-outline-variant text-primary focus:ring-secondary" />
                <label for="remember" class="text-text-body text-body-md">Ingat saya</label>
            </div>

            <div id="turnstile-login" class="flex justify-center"></div>
            <p v-if="form.errors['cf-turnstile-response']" class="text-error-red text-xs mt-1">{{ form.errors['cf-turnstile-response'] }}</p>

            <button type="submit" :disabled="form.processing || !turnstileVerified"
                class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full mt-2 hover:bg-primary transition-all transform active:scale-95 shadow-lg shadow-primary-container/10 disabled:opacity-50"
            >
                {{ form.processing ? 'Memproses...' : 'Masuk' }}
            </button>
        </form>
        </div>

        <div class="relative my-5 text-center">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-outline-variant"></div>
            </div>
            <span class="relative px-4 bg-surface-white text-text-muted text-label-md font-medium">atau</span>
        </div>

        <a :href="'/auth/google/redirect?action=login&remember=' + (form.remember ? '1' : '0')" @click="handleGoogleClick"
           :class="{'pointer-events-none opacity-50': googleLoading}"
            class="w-full border border-outline-variant bg-surface-white text-primary text-label-md font-medium py-3.5 rounded-full flex items-center justify-center gap-3 hover:bg-surface-container-low transition-colors mb-4">
            <svg class="w-5 h-5 animate-spin" v-if="googleLoading" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            <svg class="w-5 h-5" viewBox="0 0 24 24" v-if="!googleLoading">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05" />
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
            </svg>
            {{ googleLoading ? 'Mengarahkan...' : 'Masuk dengan Google' }}
        </a>

        <p class="text-center text-body-md text-text-body">
            Belum punya akun?
            <Link :href="route('register')" class="text-secondary font-bold hover:underline">Daftar sekarang</Link>
        </p>
    </AuthCard>
</template>
