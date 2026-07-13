<script setup>
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AuthCard from '@/Components/Landing/AuthCard.vue';
import { IconMail } from '@tabler/icons-vue';

const props = defineProps({
    status: { type: String },
});

const form = useForm({});

const submit = () => {
    form.post(route('verification.send'));
};

const verificationLinkSent = computed(
    () => props.status === 'verification-link-sent',
);
</script>

<template>
    <Head title="Verifikasi Email" />

    <AuthCard title="Hampir Selesai!" subtitle="Verifikasi email Anda untuk melanjutkan ke tahap selanjutnya.">
        <header class="mb-8">
            <h1 class="text-headline-md font-bold text-primary mb-2">Verifikasi Email</h1>
            <p class="text-text-body text-body-md">
                Terima kasih sudah mendaftar! Sebelum melanjutkan, verifikasi alamat email Anda dengan mengklik link yang sudah kami kirimkan.
            </p>
        </header>

        <div class="flex items-center gap-4 bg-pastel-blue/40 rounded-2xl p-5 mb-6">
            <div class="w-12 h-12 bg-primary-container rounded-xl flex items-center justify-center shrink-0">
                <IconMail class="text-white" :size="22" stroke="1.5" />
            </div>
            <div>
                <p class="text-label-md font-medium text-primary">Cek Email Anda</p>
                <p class="text-body-md text-text-muted">Link verifikasi sudah dikirim ke email Anda</p>
            </div>
        </div>

        <div v-if="verificationLinkSent"
             class="bg-green-50 text-green-700 text-body-md rounded-2xl px-5 py-4 mb-6">
            Link verifikasi baru telah dikirim ke email Anda.
        </div>

        <form @submit.prevent="submit" class="space-y-4">
            <button type="submit" :disabled="form.processing"
                    class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full hover:bg-primary transition-all active:scale-95 shadow-lg shadow-primary-container/10 disabled:opacity-50">
                {{ form.processing ? 'Mengirim...' : 'Kirim Ulang Email Verifikasi' }}
            </button>

            <Link :href="route('logout')" method="post" as="button"
                  class="w-full text-center text-text-body text-label-md font-medium hover:text-primary transition-colors py-2">
                Log Out
            </Link>
        </form>
    </AuthCard>
</template>
