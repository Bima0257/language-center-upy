<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import { IconCheck, IconRefresh } from '@tabler/icons-vue';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';

const props = defineProps({
    sudahUnggah: { type: Boolean, default: false },
    profile: { type: Object, default: null },
    fakultas: { type: Array, default: () => [] },
    programStudi: { type: Array, default: () => [] },
});

const form = useForm({
    nim: props.profile?.nim || '',
    fakultas_id: props.profile?.fakultas_id || '',
    program_studi_id: props.profile?.program_studi_id || '',
    tahun_angkatan: props.profile?.tahun_angkatan || '',
    foto_identitas: null,
    foto: null,
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
        if (field === 'foto_identitas') identityPreview.value = ev.target.result;
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
            <template v-if="sudahUnggah && !showForm">
                <div class="bg-warning-container border border-warning rounded-2xl p-8 text-center">
                    <IconCheck class="mx-auto text-success mb-4" :size="48" stroke="1.5" />
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
            <template v-if="!sudahUnggah || showForm">
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
                                v-model="form.fakultas_id"
                                :options="fakultas"
                                label="Fakultas"
                                placeholder="Pilih fakultas"
                                 option-label="nama"
                                option-value="id"
                            />
                            <p v-if="form.errors.fakultas_id" class="text-error-red text-xs mt-1">{{ form.errors.fakultas_id }}</p>
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
                            <p v-if="form.errors.program_studi_id" class="text-error-red text-xs mt-1">{{ form.errors.program_studi_id }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Tahun Angkatan</label>
                             <input type="number" v-model="form.tahun_angkatan" placeholder="Contoh: 2025"
                                   min="2000" max="2100"
                                   class="w-full border border-outline-variant rounded-xl px-4 py-3 text-body-md text-primary placeholder:text-text-muted focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" />
                             <p v-if="form.errors.tahun_angkatan" class="text-error-red text-xs mt-1">{{ form.errors.tahun_angkatan }}</p>
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
                                   @change="(e) => onFileSelect(e, 'foto_identitas')" />
                        </div>
                        <p v-if="form.errors.foto_identitas" class="text-error-red text-xs mt-1">{{ form.errors.foto_identitas }}</p>
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
                                   @change="(e) => onFileSelect(e, 'foto')" />
                        </div>
                        <p v-if="form.errors.foto" class="text-error-red text-xs mt-1">{{ form.errors.foto }}</p>
                    </BaseCard>

                    <BaseButton type="submit"
                            :disabled="form.processing || !form.nim || !form.fakultas_id || !form.program_studi_id || !form.tahun_angkatan || !form.foto_identitas || !form.foto"
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
