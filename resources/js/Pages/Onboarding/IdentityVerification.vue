<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import OnboardingLayout from '@/Components/Onboarding/OnboardingLayout.vue';

const form = useForm({
    identity_photo: null,
    photo: null,
});

let identityPreview = null;
let selfiePreview = null;

function onFileSelect(e, field, previewField) {
    const file = e.target.files[0];
    if (!file) return;
    form[field] = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        if (field === 'identity_photo') identityPreview = ev.target.result;
        else selfiePreview = ev.target.result;
    };
    reader.readAsDataURL(file);
}

function submit() {
    form.post(route('onboarding.upload-identity'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Verifikasi Identitas" />
    <OnboardingLayout :current-step="2">
        <div class="max-w-xl mx-auto px-4 py-10">
            <h1 class="text-headline-md font-bold text-primary mb-2">Verifikasi Identitas</h1>
            <p class="text-text-body text-body-md mb-8">Unggah foto identitas dan swafoto untuk verifikasi akun Anda.</p>

            <form @submit.prevent="submit" class="space-y-8">
                <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                    <label class="text-label-md font-medium text-primary block mb-4">Foto KTP/Identitas</label>
                    <div class="border-2 border-dashed border-outline-variant rounded-2xl p-8 text-center hover:border-primary transition-colors cursor-pointer"
                         @click="$refs.identityInput.click()">
                        <img v-if="identityPreview" :src="identityPreview" class="max-h-40 mx-auto rounded-lg mb-2" />
                        <p v-else class="text-text-muted text-body-md">Klik untuk upload foto KTP/SIM/Paspor</p>
                        <p class="text-text-muted text-label-md mt-1">Maks 5MB, format JPG/PNG</p>
                        <input ref="identityInput" type="file" accept="image/jpg,image/jpeg,image/png"
                               class="hidden" @change="(e) => onFileSelect(e, 'identity_photo')" />
                    </div>
                    <p v-if="form.errors.identity_photo" class="text-error-red text-xs mt-1">{{ form.errors.identity_photo }}</p>
                </div>

                <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                    <label class="text-label-md font-medium text-primary block mb-4">Swafoto (Selfie)</label>
                    <div class="border-2 border-dashed border-outline-variant rounded-2xl p-8 text-center hover:border-primary transition-colors cursor-pointer"
                         @click="$refs.selfieInput.click()">
                        <img v-if="selfiePreview" :src="selfiePreview" class="max-h-40 mx-auto rounded-lg mb-2" />
                        <p v-else class="text-text-muted text-body-md">Klik untuk upload swafoto Anda</p>
                        <p class="text-text-muted text-label-md mt-1">Maks 5MB, format JPG/PNG</p>
                        <input ref="selfieInput" type="file" accept="image/jpg,image/jpeg,image/png"
                               class="hidden" @change="(e) => onFileSelect(e, 'photo')" />
                    </div>
                    <p v-if="form.errors.photo" class="text-error-red text-xs mt-1">{{ form.errors.photo }}</p>
                </div>

                <button type="submit" :disabled="form.processing || (!form.identity_photo && !form.photo)"
                        class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full hover:bg-primary transition-all active:scale-95 shadow-lg shadow-primary-container/10 disabled:opacity-50">
                    {{ form.processing ? 'Mengunggah...' : 'Kirim Verifikasi' }}
                </button>
            </form>

            <div class="mt-8 bg-pastel-blue/30 rounded-2xl p-5 text-sm text-primary">
                <p class="font-semibold mb-1">Informasi:</p>
                <p>Setelah mengirim, admin akan memverifikasi identitas Anda secara manual. Proses biasanya memakan waktu 1x24 jam. Anda akan mendapat notifikasi setelah diverifikasi.</p>
            </div>
        </div>
    </OnboardingLayout>
</template>
