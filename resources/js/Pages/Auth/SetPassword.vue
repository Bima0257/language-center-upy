<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/Landing/AuthCard.vue';
import PasswordStrengthCheck from '@/Components/PasswordStrengthCheck.vue';
import { IconLock, IconEye, IconEyeOff } from '@tabler/icons-vue';
import { ref } from 'vue';

const form = useForm({
    password: '',
    password_confirmation: '',
});

const showPassword = ref(false);
const showConfirm = ref(false);

const submit = () => {
    form.post(route('google.set-password.store'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Atur Password" />

    <AuthCard
        title="Akun Berhasil Dibuat!"
        subtitle="Sekarang atur password untuk login manual di masa mendatang."
    >
        <header class="mb-8">
            <h1 class="text-headline-md font-bold text-primary mb-2">Atur Password</h1>
            <p class="text-text-body text-body-md">
                Buat password untuk akun Anda. Setelah ini, Anda bisa login menggunakan Google atau email + password.
            </p>
        </header>

        <form @submit.prevent="submit" class="space-y-5">
            <div class="space-y-2">
                <label class="text-primary text-label-md font-medium block ml-1" for="password">Password</label>
                <div class="relative">
                    <IconLock class="absolute left-4 top-1/2 -translate-y-1/2 text-text-muted" :size="18" />
                    <input
                        id="password"
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
                        id="password_confirmation"
                        :type="showConfirm ? 'text' : 'password'"
                        v-model="form.password_confirmation"
                        required
                        autocomplete="new-password"
                        placeholder="Ulangi password"
                        class="w-full pl-11 pr-12 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md transition-all placeholder:text-text-muted focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]"
                    />
                    <button type="button" @click="showConfirm = !showConfirm" class="absolute right-4 top-1/2 -translate-y-1/2 text-text-muted hover:text-primary transition-colors">
                        <IconEye v-if="!showConfirm" :size="20" />
                        <IconEyeOff v-else :size="20" />
                    </button>
                </div>
                <p v-if="form.errors.password_confirmation" class="text-error-red text-xs mt-1 ml-1">{{ form.errors.password_confirmation }}</p>
            </div>

            <button type="submit" :disabled="form.processing"
                    class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full mt-2 hover:bg-primary transition-all transform active:scale-95 shadow-lg shadow-primary-container/10 disabled:opacity-50">
                {{ form.processing ? 'Menyimpan...' : 'Simpan Password' }}
            </button>
        </form>
    </AuthCard>
</template>
