<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { IconCheck, IconRefresh, IconUpload } from '@tabler/icons-vue';
import OnboardingLayout from '@/Components/Onboarding/OnboardingLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';

const props = defineProps({
    hasUploaded: { type: Boolean, default: false },
    profile: { type: Object, default: null },
    faculties: { type: Array, default: () => [] },
    departments: { type: Array, default: () => [] },
});

const form = useForm({
    nim: props.profile?.nim || '',
    faculty_id: props.profile?.faculty_id || '',
    department_id: props.profile?.department_id || '',
    batch_year: props.profile?.batch_year || '',
    identity_photo: null,
    photo: null,
});

const showForm = ref(false);

const identityPreview = ref(null);
const selfiePreview = ref(null);

function onFileSelect(e, field) {
    const file = e.target.files[0];
    if (!file) return;
    form[field] = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        if (field === 'identity_photo') identityPreview.value = ev.target.result;
        else selfiePreview.value = ev.target.result;
    };
    reader.readAsDataURL(file);
    e.target.value = '';
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

            <!-- STATE: sudah upload, menunggu verifikasi -->
            <template v-if="hasUploaded && !showForm">
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
                    <IconCheck class="mx-auto text-green-500 dark:text-green-400 mb-4" :size="48" stroke="1.5" />
                    <h1 class="text-headline-md font-bold text-primary mb-2">Identitas Terkirim</h1>
                    <p class="text-text-body text-body-md mb-6">
                        Foto identitas Anda sudah diunggah dan menunggu verifikasi oleh admin.
                        Proses verifikasi biasanya memakan waktu 1x24 jam.
                    </p>
                    <button @click="showForm = true"
                            class="inline-flex items-center gap-2 border border-outline-variant text-primary px-6 py-3 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all">
                        <IconRefresh :size="18" stroke="1.5" />
                        Upload Ulang
                    </button>
                </div>
            </template>

            <!-- STATE: form upload -->
            <template v-if="!hasUploaded || showForm">
                <h1 class="text-headline-md font-bold text-primary mb-2">Verifikasi Identitas</h1>
                <p class="text-text-body text-body-md mb-8">Unggah foto identitas dan swafoto untuk verifikasi akun Anda.</p>

                <form @submit.prevent="submit" class="space-y-6">
                    <BaseCard class="space-y-5">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">NIM</label>
                            <input type="text" v-model="form.nim" placeholder="Masukkan NIM"
                                   class="w-full border border-outline-variant rounded-xl px-4 py-3 text-body-md text-primary placeholder:text-text-muted focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" />
                            <p v-if="form.errors.nim" class="text-error-red text-xs mt-1">{{ form.errors.nim }}</p>
                        </div>
                        <div>
                            <DropDown
                                v-model="form.faculty_id"
                                :options="faculties"
                                label="Fakultas"
                                placeholder="Pilih fakultas"
                                option-label="name"
                                option-value="id"
                            />
                            <p v-if="form.errors.faculty_id" class="text-error-red text-xs mt-1">{{ form.errors.faculty_id }}</p>
                        </div>
                        <div>
                            <DropDown
                                v-model="form.department_id"
                                :options="departments"
                                label="Program Studi"
                                placeholder="Pilih program studi"
                                option-label="name"
                                option-value="id"
                            />
                            <p v-if="form.errors.department_id" class="text-error-red text-xs mt-1">{{ form.errors.department_id }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Tahun Angkatan</label>
                            <input type="number" v-model="form.batch_year" placeholder="Contoh: 2025"
                                   min="2000" max="2100"
                                   class="w-full border border-outline-variant rounded-xl px-4 py-3 text-body-md text-primary placeholder:text-text-muted focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" />
                            <p v-if="form.errors.batch_year" class="text-error-red text-xs mt-1">{{ form.errors.batch_year }}</p>
                        </div>
                    </BaseCard>

                    <!-- FOTO KTM -->
                    <BaseCard>
                        <label class="text-label-md font-medium text-primary block mb-4">Foto KTM/Kartu Identitas Mahasiswa</label>
                        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-8 text-center relative hover:border-primary transition-colors cursor-pointer">
                            <img v-if="identityPreview" :src="identityPreview" class="max-h-40 mx-auto rounded-lg mb-2 pointer-events-none" />
                            <p v-else class="text-text-muted text-body-md">Klik untuk upload foto KTM/Kartu Identitas Mahasiswa</p>
                            <p class="text-text-muted text-label-md mt-1">Maks 5MB, format JPG/PNG</p>
                            <input type="file" accept="image/*"
                                   class="absolute inset-0 opacity-0 cursor-pointer"
                                   @change="(e) => onFileSelect(e, 'identity_photo')" />
                        </div>
                        <p v-if="form.errors.identity_photo" class="text-error-red text-xs mt-1">{{ form.errors.identity_photo }}</p>
                    </BaseCard>

                    <!-- SWAFOTO -->
                    <BaseCard>
                        <label class="text-label-md font-medium text-primary block mb-4">Swafoto (Selfie)</label>
                        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-8 text-center relative hover:border-primary transition-colors cursor-pointer">
                            <img v-if="selfiePreview" :src="selfiePreview" class="max-h-40 mx-auto rounded-lg mb-2 pointer-events-none" />
                            <p v-else class="text-text-muted text-body-md">Klik untuk upload foto swafoto (selfie)</p>
                            <p class="text-text-muted text-label-md mt-1">Maks 5MB, format JPG/PNG</p>
                            <input type="file" accept="image/*"
                                   class="absolute inset-0 opacity-0 cursor-pointer"
                                   @change="(e) => onFileSelect(e, 'photo')" />
                        </div>
                        <p v-if="form.errors.photo" class="text-error-red text-xs mt-1">{{ form.errors.photo }}</p>
                    </BaseCard>

                    <BaseButton type="submit"
                            :disabled="form.processing || !form.nim || !form.faculty_id || !form.department_id || !form.batch_year || !form.identity_photo || !form.photo"
                            size="xl" class="w-full shadow-lg shadow-primary-container/10">
                        {{ form.processing ? 'Mengunggah...' : 'Kirim Verifikasi' }}
                    </BaseButton>
                </form>

                <div class="mt-8 bg-pastel-blue/30 rounded-2xl p-5 text-sm text-primary">
                    <p class="font-semibold mb-1">Informasi:</p>
                    <p>Setelah mengirim, admin akan memverifikasi identitas Anda secara manual. Proses biasanya memakan waktu 1x24 jam. Anda akan mendapat notifikasi setelah diverifikasi.</p>
                </div>
            </template>

        </div>
    </OnboardingLayout>
</template>
