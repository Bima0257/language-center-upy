<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import UserGuide from '@/Components/Shared/UserGuide.vue';
import FormMateriSoal from '@/Pages/BankSoal/TambahSoal/Components/FormMateriSoal.vue';
import DaftarSoal from '@/Pages/BankSoal/TambahSoal/Components/DaftarSoal.vue';
import { useUploadProgress } from '@/Composables/useUploadProgress';
import { IconBook, IconBooks, IconFileDescription } from '@tabler/icons-vue';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    bankSoal: { type: Array, default: () => [] },
    opsiKeterampilan: { type: Array, default: () => [] },
    bagianSoal: { type: Array, default: () => [] },
    kebijakanMateriSoal: { type: Array, default: () => [] },
    bankSoalTerpilih: { type: String, default: null },
    filterKembali: { type: Object, default: () => ({}) },
});

const optionKeys = ['A', 'B', 'C', 'D'];

const materiSoalMode = ref('none');
const daftarSoalRef = ref(null);

const keterampilanGlobal = ref(String(props.filterKembali?.keterampilan || ''));
const globalPart = ref(String(props.filterKembali?.bagian_soal_id || ''));

function policyForSkill(bankId, keterampilanId) {
    const bank = props.bankSoal.find((item) => String(item.id) === String(bankId));

    return props.kebijakanMateriSoal.find((policy) =>
        String(policy.jenis_tes_id) === String(bank?.jenis_tes_id) &&
        String(policy.keterampilan_id) === String(keterampilanId),
    ) || null;
}

const isAudioQuestion = (q) => policyForSkill(form.bank_soal_id, q.keterampilan_id)?.materi === 'audio';

const selectedPolicy = computed(() => policyForSkill(form.bank_soal_id, keterampilanGlobal.value));
const hasListeningQuestion = computed(() => selectedPolicy.value?.materi === 'audio');

function newQuestion() {
    return {
        keterampilan_id: keterampilanGlobal.value,
        bagian_soal_id: globalPart.value,
        teks_soal: '',
        pilihan_a: '',
        pilihan_b: '',
        pilihan_c: '',
        pilihan_d: '',
        kunci_jawaban: '',
    };
}

const form = useForm({
    bank_soal_id: props.bankSoalTerpilih || '',
    materi_baru_judul: '',
    materi_baru_isi_teks: '',
    materi_baru_berkas_audio: null,
    materi_baru_berkas_gambar: null,
    _kembali_keterampilan: props.filterKembali?.keterampilan || '',
    _kembali_bagian_soal_id: props.filterKembali?.bagian_soal_id || '',
    soal: [newQuestion()],
});

watch(selectedPolicy, (policy) => {
    if (!policy) {
        materiSoalMode.value = 'none';
        form.materi_baru_judul = '';
        form.materi_baru_isi_teks = '';
        form.materi_baru_berkas_audio = null;
        form.materi_baru_berkas_gambar = null;

        return;
    }

    if (policy?.ketentuan_materi === 'required') {
        materiSoalMode.value = 'new';
    } else if (policy?.ketentuan_materi === 'forbidden') {
        materiSoalMode.value = 'none';
    }

    if (policy.aturan_media?.text === 'forbidden') form.materi_baru_isi_teks = '';
    if (policy.aturan_media?.audio === 'forbidden') form.materi_baru_berkas_audio = null;
    if (policy.aturan_media?.image === 'forbidden') form.materi_baru_berkas_gambar = null;
}, { immediate: true });

watch(materiSoalMode, (mode) => {
    if (mode !== 'new') {
        form.materi_baru_judul = '';
        form.materi_baru_isi_teks = '';
        form.materi_baru_berkas_audio = null;
        form.materi_baru_berkas_gambar = null;
    }
});

const selectedBank = computed(() =>
    props.bankSoal.find(b => String(b.id) === String(form.bank_soal_id)) || null,
);

const bankIsLocked = computed(() => Number(selectedBank.value?.bagian_ujian_count || 0) > 0);

const selectedKeterampilan = computed(() =>
    props.opsiKeterampilan.find((skill) => String(skill.value) === String(keterampilanGlobal.value)) || null,
);

const selectedBagianSoal = computed(() =>
    props.bagianSoal.find((part) =>
        String(part.id) === String(globalPart.value) &&
        String(part.bank_soal_id) === String(form.bank_soal_id),
    ) || null,
);

watch(keterampilanGlobal, (keterampilan) => {
    globalPart.value = '';
    for (const q of form.soal) {
        q.keterampilan_id = keterampilan;
        q.bagian_soal_id = '';
    }

    if (selectedPolicy.value?.aturan_media?.audio === 'forbidden') {
        form.materi_baru_berkas_audio = null;
    }

    if (selectedPolicy.value?.ketentuan_materi === 'required') {
        materiSoalMode.value = 'new';
    } else if (selectedPolicy.value?.ketentuan_materi === 'forbidden') {
        materiSoalMode.value = 'none';
    }
});

watch(() => form.bank_soal_id, () => {
    keterampilanGlobal.value = '';
    globalPart.value = '';
    for (const q of form.soal) {
        q.keterampilan_id = '';
        q.bagian_soal_id = '';
    }
    if (form.materi_baru_judul) {
        form.materi_baru_judul = '';
        form.materi_baru_isi_teks = '';
        form.materi_baru_berkas_audio = null;
        form.materi_baru_berkas_gambar = null;
        materiSoalMode.value = 'none';
    }
});

watch(globalPart, (value) => {
    for (const q of form.soal) {
        q.bagian_soal_id = value;
    }
});

async function addQuestion(afterIndex) {
    const insertAt = afterIndex === undefined ? form.soal.length : afterIndex + 1;
    form.soal.splice(insertAt, 0, newQuestion());
    await nextTick();
    daftarSoalRef.value?.scrollTo(insertAt);
}

function removeQuestion(index) {
    if (form.soal.length > 1) {
        form.soal.splice(index, 1);
    }
}

const canSubmit = computed(() => {
    if (bankIsLocked.value) return false;
    if (!form.bank_soal_id) return false;
    if (form.soal.length === 0) return false;
    if (!keterampilanGlobal.value) return false;
    if (!globalPart.value) return false;
    if (!selectedPolicy.value) return false;

    if (materiSoalMode.value === 'new') {
        if (!form.materi_baru_judul.trim()) return false;
        if (selectedPolicy.value.ketentuan_materi === 'forbidden') return false;
        if (!form.materi_baru_isi_teks.trim() && !form.materi_baru_berkas_audio && !form.materi_baru_berkas_gambar) return false;
        if (selectedPolicy.value.aturan_media?.text === 'required' && !form.materi_baru_isi_teks.trim()) return false;
        if (selectedPolicy.value.aturan_media?.audio === 'required' && !form.materi_baru_berkas_audio) return false;
        if (selectedPolicy.value.aturan_media?.image === 'required' && !form.materi_baru_berkas_gambar) return false;
    } else if (selectedPolicy.value.ketentuan_materi === 'required') {
        return false;
    }

    return form.soal.every(q => {
        if (!q.keterampilan_id || !q.bagian_soal_id || !q.kunci_jawaban) return false;

        if (isAudioQuestion(q)) {
            return materiSoalMode.value !== 'none';
        }
        return q.teks_soal.trim() &&
            q.pilihan_a.trim() && q.pilihan_b.trim() &&
            q.pilihan_c.trim() && q.pilihan_d.trim();
    });
});

function submit() {
    form.post(route('pustaka-soal.simpan'));
}

const { showUploadProgress, uploadLabel, mediaType } = useUploadProgress(form, {
    audioField: 'materi_baru_berkas_audio',
    imageField: 'materi_baru_berkas_gambar',
});

const indexUrl = computed(() => {
    const p = {};
    if (form.bank_soal_id) p.bank_soal_id = form.bank_soal_id;
    if (form._kembali_keterampilan) p.keterampilan = form._kembali_keterampilan;
    if (form._kembali_bagian_soal_id) p.bagian_soal_id = form._kembali_bagian_soal_id;
    return p;
});
</script>

<template>
    <Head title="Tambah Soal Baru" />
    <TataLetakDasbor
        title="Tambah Soal Baru"
        hide-header
        :breadcrumbs="[
            { label: 'Bank Soal', route: 'pustaka-soal.daftar' },
            { label: 'Tambah Soal Baru' },
        ]"
    >
        <template #breadcrumb-actions>
            <UserGuide
                title="Panduan Tambah Soal"
                :steps="[
                    { title: 'Tinjau konteks soal', desc: 'Bank soal, keterampilan, dan bagian mengikuti filter Pustaka Soal.' },
                    { title: 'Tentukan materi soal', desc: 'Gunakan tanpa materi atau buat materi baru sesuai tipe keterampilan.' },
                    { title: 'Isi soal & kunci', desc: 'Tambahkan pertanyaan, pilihan jawaban, dan kunci jawaban.' },
                ]"
                :rules="[
                    'Listening wajib menggunakan materi soal audio.',
                    'Reading wajib memiliki teks soal dan empat pilihan jawaban.',
                ]"
            />
        </template>
        <div class="w-full space-y-6">
            <header>
                <div>
                    <h1 class="text-headline-lg font-bold text-text-heading">Tambah Soal Baru</h1>
                    <p class="text-body-md text-text-muted mt-2">Buat beberapa soal sekaligus untuk bank soal yang dipilih.</p>
                </div>
            </header>

            <form @submit.prevent="submit" class="space-y-6 pb-2">
                <BaseCard :padding="false" class="p-5 md:p-7">
                    <div class="space-y-5">
                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
                            <div class="flex min-w-0 items-center gap-3 rounded-xl border border-outline-variant/50 bg-surface-container-lowest px-4 py-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pastel-blue text-secondary">
                                    <IconBooks :size="20" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-label-md text-text-muted mb-1">Bank Soal</p>
                            <p class="truncate text-body-md font-semibold text-text-heading">{{ selectedBank?.nama || 'Belum dipilih' }}</p>
                            <p v-if="bankIsLocked" class="mt-1 text-label-md text-error-red">
                                Bank soal sudah digunakan di ujian dan dikunci.
                            </p>
                                </div>
                            </div>

                            <div class="flex min-w-0 items-center gap-3 rounded-xl border border-outline-variant/50 bg-surface-container-lowest px-4 py-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pastel-purple text-secondary">
                                    <IconBook :size="20" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-label-md text-text-muted mb-1">Keterampilan</p>
                                    <span v-if="selectedKeterampilan" class="inline-flex max-w-full truncate rounded-lg bg-pastel-blue px-3 py-1 text-label-md font-semibold text-secondary">
                                        {{ selectedKeterampilan.label }}
                                    </span>
                                    <p v-else class="text-body-md font-semibold text-text-heading">Belum dipilih</p>
                                </div>
                            </div>

                            <div class="flex min-w-0 items-center gap-3 rounded-xl border border-outline-variant/50 bg-surface-container-lowest px-4 py-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-pastel-peach text-primary">
                                    <IconFileDescription :size="20" />
                                </span>
                                <div class="min-w-0">
                                    <p class="text-label-md text-text-muted mb-1">Bagian Soal</p>
                                    <span v-if="selectedBagianSoal" class="inline-flex max-w-full truncate rounded-lg bg-success-container px-3 py-1 text-label-md font-semibold text-on-success-container">
                                        {{ selectedBagianSoal.nama }}
                                    </span>
                                    <p v-else class="text-body-md font-semibold text-text-heading">Belum dipilih</p>
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-outline-variant/50 pt-6">
                            <h2 class="text-title-lg font-semibold text-text-heading">Materi Soal</h2>
                            <p class="text-body-md text-text-muted mt-1">Pilih tanpa materi atau buat materi baru. Audio wajib untuk soal Listening.</p>
                        </div>

                        <FormMateriSoal
                            v-model:mode="materiSoalMode"
                            :form="form"
                            :policy="selectedPolicy"
                            :has-listening-question="hasListeningQuestion"
                        />
                    </div>
                </BaseCard>

                <section class="space-y-4">
                    <p v-if="form.errors.soal" class="rounded-xl border border-error-red/30 bg-error-container/30 px-4 py-3 text-body-md text-error-red">
                        {{ form.errors.soal }}
                    </p>

                    <DaftarSoal
                        ref="daftarSoalRef"
                        :questions="form.soal"
                        :errors="form.errors"
                        :materi-soal-mode="materiSoalMode"
                        :option-keys="optionKeys"
                        :is-audio-question="isAudioQuestion"
                        @add="addQuestion"
                        @remove="removeQuestion"
                    />
                </section>

                <div class="sticky bottom-0 z-20 -mx-8 border-t border-outline-variant/30 bg-surface/95 px-8 py-4 backdrop-blur">
                    <div class="flex justify-end gap-3">
                        <BaseButton
                            type="button"
                            variant="secondary"
                            size="md"
                            :href="route('pustaka-soal.daftar', indexUrl)"
                            class="!w-28 !rounded-lg"
                        >
                            Batal
                        </BaseButton>
                        <BaseButton
                            type="submit"
                            size="md"
                            :disabled="form.processing || !canSubmit"
                            class="!w-32 !rounded-lg !bg-secondary !text-on-secondary"
                        >
                            {{ form.processing ? 'Menyimpan...' : 'Simpan' }}
                        </BaseButton>
                    </div>
                </div>
            </form>
        </div>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" :media-type="mediaType" />
    </TataLetakDasbor>
</template>
