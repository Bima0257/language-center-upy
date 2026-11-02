<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';

const props = defineProps({
    profile: { type: Object, default: null },
    fakultas: { type: Array, default: () => [] },
    programStudi: { type: Array, default: () => [] },
});

const form = useForm({
    nim: props.profile?.nim || '',
    fakultas_id: props.profile?.fakultas_id || '',
    program_studi_id: props.profile?.program_studi_id || '',
    tahun_angkatan: props.profile?.tahun_angkatan || '',
});

function submit() {
    form.post(route('onboarding.upload-identity'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Lengkapi Identitas" />
    <OnboardingLayout :current-step="2">
        <div class="mx-auto max-w-xl px-4 py-10">
            <h1 class="mb-2 text-headline-md font-bold text-text-heading">Lengkapi Identitas</h1>
            <p class="mb-8 text-body-md text-text-body">
                Isi data akademik sebelum mendaftar atau mengikuti ujian.
            </p>

            <form @submit.prevent="submit" class="space-y-6">
                <BaseCard>
                    <BaseFormGrid>
                        <div>
                            <label class="mb-1.5 block text-label-md font-medium text-text-heading">NIM</label>
                            <input
                                v-model="form.nim"
                                type="text"
                                placeholder="Masukkan NIM"
                                class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md text-text-heading outline-none transition-all placeholder:text-text-muted focus:border-primary focus:ring-2 focus:ring-primary/10"
                            />
                            <p v-if="form.errors.nim" class="mt-1 text-xs text-error-red">{{ form.errors.nim }}</p>
                        </div>
                        <div>
                            <DropDown
                                v-model="form.fakultas_id"
                                :options="fakultas"
                                label="Fakultas"
                                placeholder="Pilih fakultas"
                                option-label="nama"
                                option-value="id"
                            />
                            <p v-if="form.errors.fakultas_id" class="mt-1 text-xs text-error-red">{{ form.errors.fakultas_id }}</p>
                        </div>
                        <div>
                            <DropDown
                                v-model="form.program_studi_id"
                                :options="programStudi"
                                label="Program Studi"
                                placeholder="Pilih program studi"
                                option-label="nama"
                                option-value="id"
                            />
                            <p v-if="form.errors.program_studi_id" class="mt-1 text-xs text-error-red">{{ form.errors.program_studi_id }}</p>
                        </div>
                        <div>
                            <label class="mb-1.5 block text-label-md font-medium text-text-heading">Tahun Angkatan</label>
                            <input
                                v-model="form.tahun_angkatan"
                                type="number"
                                placeholder="Contoh: 2025"
                                min="2000"
                                max="2100"
                                class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md text-text-heading outline-none transition-all placeholder:text-text-muted focus:border-primary focus:ring-2 focus:ring-primary/10"
                            />
                            <p v-if="form.errors.tahun_angkatan" class="mt-1 text-xs text-error-red">{{ form.errors.tahun_angkatan }}</p>
                        </div>
                    </BaseFormGrid>
                </BaseCard>

                <BaseButton
                    type="submit"
                    :disabled="form.processing || !form.nim || !form.fakultas_id || !form.program_studi_id || !form.tahun_angkatan"
                    size="xl"
                    class="w-full shadow-lg shadow-primary-container/10"
                >
                    {{ form.processing ? 'Menyimpan...' : 'Simpan Identitas' }}
                </BaseButton>
            </form>

            <div class="mt-8 rounded-2xl bg-pastel-blue/30 p-5 text-sm text-text-heading">
                <p class="mb-1 font-semibold">Informasi:</p>
                <p>Data ini digunakan pada pendaftaran ujian. Verifikasi pembayaran dilakukan oleh admin pada pendaftaran ujian resmi.</p>
            </div>
        </div>
    </OnboardingLayout>
</template>
