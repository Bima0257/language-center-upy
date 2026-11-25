<script setup>
import { computed, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import DropDown from '@/Components/Shared/DropDown.vue';
import { labelIdentitasPeserta } from '@/constants/jenisPeserta';
import { IconUpload } from '@tabler/icons-vue';

const props = defineProps({
    show: { type: Boolean, default: false },
    slotUjian: { type: Object, default: null },
    profil: { type: Object, default: null },
    fakultas: { type: Array, default: () => [] },
    programStudi: { type: Array, default: () => [] },
    prodiTerkunci: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'saved']);

const registrationForm = useForm({
    jenis_peserta: '',
    nomor_identitas: '',
    institusi: '',
    fakultas_id: '',
    program_studi_id: '',
    tahun_mulai: '',
    bukti_pembayaran: null,
});

const jenisPeserta = computed(() => registrationForm.jenis_peserta || 'mahasiswa');
const memerlukanAkademik = computed(() => ['mahasiswa', 'dosen'].includes(jenisPeserta.value));
const memerlukanInstitusi = computed(() => jenisPeserta.value === 'umum');
const memerlukanTahun = computed(() => jenisPeserta.value === 'mahasiswa');
const labelIdentitas = computed(() => labelIdentitasPeserta(jenisPeserta.value));

function formatDate(date) {
    return date
        ? new Date(date).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' })
        : '-';
}

function resetForm() {
    registrationForm.clearErrors();
    registrationForm.reset();
    registrationForm.jenis_peserta = props.profil?.jenis_peserta || 'mahasiswa';
    registrationForm.nomor_identitas = props.profil?.nomor_identitas || '';
    registrationForm.institusi = props.profil?.institusi || '';
    registrationForm.fakultas_id = props.profil?.fakultas_id || '';
    registrationForm.program_studi_id = props.profil?.program_studi_id || '';
    registrationForm.tahun_mulai = props.profil?.tahun_mulai || '';
}

watch(
    () => [props.show, props.slotUjian?.id],
    ([show]) => {
        if (show) resetForm();
    },
    { immediate: true },
);

function selectPaymentProof(event) {
    const file = event.target.files[0] || null;
    event.target.value = '';

    if (file && file.size > 2 * 1024 * 1024) {
        registrationForm.bukti_pembayaran = null;
        registrationForm.setError('bukti_pembayaran', 'Ukuran file maksimal 2MB.');

        return;
    }

    registrationForm.clearErrors('bukti_pembayaran');
    registrationForm.bukti_pembayaran = file;
}

function submitRegistration() {
    if (!props.slotUjian) return;

    registrationForm.post(route('ujian.pendaftaran.ajukan', props.slotUjian.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => emit('saved'),
    });
}
</script>

<template>
    <BaseModal
        :show="props.show"
        max-width="xl"
        aria-labelledby="registration-modal-title"
        aria-describedby="registration-modal-description"
        @close="emit('close')"
    >
        <template #header>
            <BaseModalHeader>
                <div class="px-6 py-4 sm:px-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="text-label-md font-semibold uppercase tracking-wide text-secondary">Pendaftaran Official</p>
                            <h2 id="registration-modal-title" class="mt-1 text-headline-md font-bold text-text-heading">
                                {{ props.slotUjian?.tanggal ? formatDate(props.slotUjian.tanggal) : 'Daftar Ujian' }}
                            </h2>
                            <p id="registration-modal-description" class="mt-1 text-body-md text-text-muted">
                                Upload bukti pembayaran untuk diproses admin.
                            </p>
                        </div>
                        <button type="button" class="text-text-muted hover:text-text-heading font-semibold" aria-label="Tutup" @click="emit('close')">&times;</button>
                    </div>
                </div>
            </BaseModalHeader>
        </template>

        <template #default>
            <form id="form-pendaftaran-ujian" class="space-y-5 px-6 pb-6 pt-6 sm:px-8 sm:pb-8 sm:pt-8" @submit.prevent="submitRegistration">
                <BaseFormGrid>
                    <div>
                        <label class="mb-1.5 block text-label-md font-medium text-text-heading">{{ labelIdentitas }}</label>
                        <input v-model="registrationForm.nomor_identitas" type="text" class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/20" />
                        <p v-if="registrationForm.errors.nomor_identitas" class="mt-1 text-xs text-error-red">{{ registrationForm.errors.nomor_identitas }}</p>
                    </div>
                    <div v-if="memerlukanInstitusi">
                        <label class="mb-1.5 block text-label-md font-medium text-text-heading">Institusi</label>
                        <input v-model="registrationForm.institusi" type="text" placeholder="Nama institusi/instansi" class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/20" />
                        <p v-if="registrationForm.errors.institusi" class="mt-1 text-xs text-error-red">{{ registrationForm.errors.institusi }}</p>
                    </div>
                    <template v-if="memerlukanAkademik">
                        <DropDown v-model="registrationForm.fakultas_id" :options="props.fakultas" label="Fakultas" option-label="nama" option-value="id" placeholder="Pilih fakultas" :disabled="props.prodiTerkunci" />
                        <DropDown v-model="registrationForm.program_studi_id" :options="props.programStudi" label="Program Studi" option-label="nama" option-value="id" placeholder="Pilih program studi" :disabled="props.prodiTerkunci" />
                        <div v-if="memerlukanTahun">
                            <label class="mb-1.5 block text-label-md font-medium text-text-heading">Tahun Angkatan</label>
                            <input v-model="registrationForm.tahun_mulai" type="number" min="2000" max="2100" class="w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-body-md text-text-body focus:border-secondary focus:outline-none focus:ring-2 focus:ring-secondary/20" />
                            <p v-if="registrationForm.errors.tahun_mulai" class="mt-1 text-xs text-error-red">{{ registrationForm.errors.tahun_mulai }}</p>
                        </div>
                    </template>
                </BaseFormGrid>
                <p v-if="registrationForm.errors.fakultas_id" class="text-xs text-error-red">{{ registrationForm.errors.fakultas_id }}</p>
                <p v-if="registrationForm.errors.program_studi_id" class="text-xs text-error-red">{{ registrationForm.errors.program_studi_id }}</p>
                <p v-if="props.prodiTerkunci" class="rounded-xl bg-info-container px-4 py-3 text-label-md text-on-info-container">
                    Program studi terkunci karena Anda sudah memiliki riwayat ujian. Hubungi admin untuk perubahan.
                </p>

                <div>
                    <label class="mb-1.5 block text-label-md font-medium text-text-heading">Bukti Pembayaran</label>
                    <label class="flex cursor-pointer items-center gap-3 rounded-2xl border-2 border-dashed border-outline-variant px-4 py-4 hover:border-secondary">
                        <IconUpload :size="20" class="text-text-muted" />
                        <span class="text-body-md text-text-body">{{ registrationForm.bukti_pembayaran?.name || 'Pilih file JPG, PNG, atau WebP' }}</span>
                        <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="selectPaymentProof" />
                    </label>
                    <p class="mt-1 text-label-md text-text-muted">Maksimal 2MB.</p>
                    <p v-if="registrationForm.errors.bukti_pembayaran" class="mt-1 text-xs text-error-red">{{ registrationForm.errors.bukti_pembayaran }}</p>
                </div>
            </form>
        </template>

        <template #footer>
            <BaseModalFooter>
                <div class="px-6 pb-3 sm:px-8">
                    <BaseFormActions
                        form="form-pendaftaran-ujian"
                        :processing="registrationForm.processing"
                        @cancel="emit('close')"
                    />
                </div>
            </BaseModalFooter>
        </template>
    </BaseModal>
</template>
