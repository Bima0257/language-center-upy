<script setup>
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import OnboardingLayout from '@/Layouts/OnboardingLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import {
    jenisPesertaOptions,
    labelIdentitasPeserta,
} from '@/constants/jenisPeserta';

const props = defineProps({
    profil: { type: Object, default: null },
    jenisPeserta: { type: String, default: 'mahasiswa' },
    opsiJenisPeserta: { type: Array, default: () => jenisPesertaOptions },
    bisaPilihJenis: { type: Boolean, default: true },
    fakultas: { type: Array, default: () => [] },
    programStudi: { type: Array, default: () => [] },
});

const form = useForm({
    jenis_peserta: props.profil?.jenis_peserta || props.jenisPeserta || 'mahasiswa',
    nomor_identitas: props.profil?.nomor_identitas || '',
    institusi: props.profil?.institusi || '',
    fakultas_id: props.profil?.fakultas_id || '',
    program_studi_id: props.profil?.program_studi_id || '',
    tahun_mulai: props.profil?.tahun_mulai || '',
});

const jenis = computed(() => form.jenis_peserta || 'mahasiswa');
const memerlukanAkademik = computed(() => ['mahasiswa', 'dosen'].includes(jenis.value));
const memerlukanInstitusi = computed(() => jenis.value === 'umum');
const memerlukanTahun = computed(() => jenis.value === 'mahasiswa');
const labelIdentitas = computed(() => labelIdentitasPeserta(jenis.value));

const bisaSimpan = computed(() => {
    if (!form.nomor_identitas) return false;
    if (memerlukanAkademik.value && (!form.fakultas_id || !form.program_studi_id)) return false;
    if (memerlukanInstitusi.value && !form.institusi) return false;
    if (memerlukanTahun.value && !form.tahun_mulai) return false;

    return true;
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
                Isi data identitas sebelum mendaftar atau mengikuti ujian.
            </p>

            <form @submit.prevent="submit" class="space-y-6">
                <BaseCard>
                    <BaseFormGrid>
                        <div v-if="bisaPilihJenis">
                            <DropDown
                                v-model="form.jenis_peserta"
                                :options="opsiJenisPeserta"
                                label="Jenis Peserta"
                                placeholder="Pilih jenis peserta"
                                option-label="label"
                                option-value="value"
                            />
                            <p v-if="form.errors.jenis_peserta" class="mt-1 text-xs text-error-red">{{ form.errors.jenis_peserta }}</p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-label-md font-medium text-text-heading">{{ labelIdentitas }}</label>
                            <input
                                v-model="form.nomor_identitas"
                                type="text"
                                :placeholder="`Masukkan ${labelIdentitas}`"
                                class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md text-text-heading outline-none transition-all placeholder:text-text-muted focus:border-primary focus:ring-2 focus:ring-primary/10"
                            />
                            <p v-if="form.errors.nomor_identitas" class="mt-1 text-xs text-error-red">{{ form.errors.nomor_identitas }}</p>
                        </div>

                        <div v-if="memerlukanInstitusi">
                            <label class="mb-1.5 block text-label-md font-medium text-text-heading">Institusi</label>
                            <input
                                v-model="form.institusi"
                                type="text"
                                placeholder="Nama institusi/instansi"
                                class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md text-text-heading outline-none transition-all placeholder:text-text-muted focus:border-primary focus:ring-2 focus:ring-primary/10"
                            />
                            <p v-if="form.errors.institusi" class="mt-1 text-xs text-error-red">{{ form.errors.institusi }}</p>
                        </div>

                        <template v-if="memerlukanAkademik">
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
                                <label class="mb-1.5 block text-label-md font-medium text-text-heading">
                                    {{ jenis === 'mahasiswa' ? 'Tahun Angkatan' : 'Tahun Mulai' }}
                                </label>
                                <input
                                    v-model="form.tahun_mulai"
                                    type="number"
                                    placeholder="Contoh: 2025"
                                    min="2000"
                                    max="2100"
                                    class="w-full rounded-xl border border-outline-variant px-4 py-3 text-body-md text-text-heading outline-none transition-all placeholder:text-text-muted focus:border-primary focus:ring-2 focus:ring-primary/10"
                                />
                                <p v-if="form.errors.tahun_mulai" class="mt-1 text-xs text-error-red">{{ form.errors.tahun_mulai }}</p>
                            </div>
                        </template>
                    </BaseFormGrid>
                </BaseCard>

                <BaseButton
                    type="submit"
                    :disabled="form.processing || !bisaSimpan"
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
