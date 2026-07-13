<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { IconMail, IconLock, IconEye, IconEyeOff, IconUser } from '@tabler/icons-vue';
import AuthCard from '@/Components/Landing/AuthCard.vue';
import PasswordStrengthCheck from '@/Components/PasswordStrengthCheck.vue';
import { ref } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirmPassword = ref(false);

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Daftar" />

    <AuthCard
        title="Mulai Perjalanan TOEFL-mu."
        subtitle="Bergabunglah dengan ribuan peserta yang berhasil meraih skor impian mereka bersama UPY Language Test."
    >
        <header class="mb-8">
            <h1 class="text-primary text-headline-md font-bold mb-2">Buat Akun Baru</h1>
            <p class="text-text-body text-body-md">Mulai persiapan TOEFL Anda bersama kami.</p>
        </header>

        <form @submit.prevent="submit" class="space-y-4">
            <div class="space-y-2">
                <label class="text-primary text-label-md font-medium block ml-1" for="name">Nama Lengkap</label>
                <div class="relative">
                    <IconUser class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                    <input
                        id="name"
                        type="text"
                        v-model="form.name"
                        required
                        autofocus
                        autocomplete="name"
                        placeholder="Nama Anda"
                        class="w-full pl-11 pr-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md transition-all placeholder:text-text-muted focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
                    />
                </div>
                <p v-if="form.errors.name" class="text-error-red text-xs mt-1 ml-1">{{ form.errors.name }}</p>
            </div>

            <div class="space-y-2">
                <label class="text-primary text-label-md font-medium block ml-1" for="email">Email</label>
                <div class="relative">
                    <IconMail class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                    <input
                        id="email"
                        type="email"
                        v-model="form.email"
                        required
                        autocomplete="username"
                        placeholder="nama@email.com"
                        class="w-full pl-11 pr-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md transition-all placeholder:text-text-muted focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
                    />
                </div>
                <p v-if="form.errors.email" class="text-error-red text-xs mt-1 ml-1">{{ form.errors.email }}</p>
            </div>

            <div class="space-y-2">
                <label class="text-primary text-label-md font-medium block ml-1" for="password">Password</label>
                <div class="relative">
                    <IconLock class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                    <input
                        :id="'password'"
                        :type="showPassword ? 'text' : 'password'"
                        v-model="form.password"
                        required
                        autocomplete="new-password"
                        placeholder="Min. 8 karakter, huruf besar & kecil, angka, simbol"
                        class="w-full pl-11 pr-12 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md transition-all placeholder:text-text-muted focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
                    />
                    <button type="button" @click="showPassword = !showPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-text-muted hover:text-primary transition-colors">
                        <IconEye v-if="!showPassword" :size="20" />
                        <IconEyeOff v-else :size="20" />
                    </button>
                </div>
                <PasswordStrengthCheck :password="form.password" />
                <p v-if="form.errors.password" class="text-error-red text-xs mt-1 ml-1">{{ form.errors.password }}</p>
            </div>

            <div class="space-y-2">
                <label class="text-primary text-label-md font-medium block ml-1" for="password_confirmation">Konfirmasi Password</label>
                <div class="relative">
                    <IconLock class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                    <input
                        :id="'password_confirmation'"
                        :type="showConfirmPassword ? 'text' : 'password'"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Ulangi password"
                        class="w-full pl-11 pr-12 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md transition-all placeholder:text-text-muted focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
                    />
                    <button type="button" @click="showConfirmPassword = !showConfirmPassword" class="absolute right-4 top-1/2 -translate-y-1/2 text-text-muted hover:text-primary transition-colors">
                        <IconEye v-if="!showConfirmPassword" :size="20" />
                        <IconEyeOff v-else :size="20" />
                    </button>
                </div>
                <p v-if="form.errors.password_confirmation" class="text-error-red text-xs mt-1 ml-1">{{ form.errors.password_confirmation }}</p>
            </div>

            <button type="submit" :disabled="form.processing"
                class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full mt-2 hover:bg-primary transition-all transform active:scale-95 shadow-lg shadow-primary-container/10 disabled:opacity-50"
            >
                {{ form.processing ? 'Memproses...' : 'Daftar' }}
            </button>
        </form>

        <div class="relative my-7 text-center">
            <div class="absolute inset-0 flex items-center">
                <div class="w-full border-t border-outline-variant"></div>
            </div>
            <span class="relative px-4 bg-surface-white text-text-muted text-label-md font-medium">atau</span>
        </div>

        <a href="/auth/google/redirect?action=register" class="w-full border border-outline-variant bg-surface-white text-primary text-label-md font-medium py-3.5 rounded-full flex items-center justify-center gap-3 hover:bg-surface-container-low transition-colors mb-6">
            <svg class="w-5 h-5" viewBox="0 0 24 24">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05" />
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
            </svg>
            Daftar dengan Google
        </a>

        <p class="text-center text-body-md text-text-body">
            Sudah punya akun?
            <Link :href="route('login')" class="text-secondary font-bold hover:underline">Masuk</Link>
        </p>
    </AuthCard>
</template>
