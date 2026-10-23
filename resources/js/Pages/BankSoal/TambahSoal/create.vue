<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import UserGuide from '@/Components/Shared/UserGuide.vue';
import PemilihKeterampilanBagian from '@/Pages/BankSoal/TambahSoal/Components/PemilihKeterampilanBagian.vue';
import FormMateriSoal from '@/Pages/BankSoal/TambahSoal/Components/FormMateriSoal.vue';
import DaftarSoal from '@/Pages/BankSoal/TambahSoal/Components/DaftarSoal.vue';
import { useUploadProgress } from '@/Composables/useUploadProgress';
import { IconBooks } from '@tabler/icons-vue';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    bankSoal: { type: Array, default: () => [] },
    opsiKeterampilan: { type: Array, default: () => [] },
    bagianSoal: { type: Array, default: () => [] },
    materiSoal: { type: Array, default: () => [] },
    kebijakanMateriSoal: { type: Array, default: () => [] },
    bankSoalTerpilih: { type: String, default: null },
    filterKembali: { type: Object, default: () => ({}) },
});

const optionKeys = ['A', 'B', 'C', 'D'];

const materiSoalMode = ref('none');
const daftarSoalRef = ref(null);

const keterampilanGlobal = ref('');
const globalPart = ref('');

function bagianSoalUntukKeterampilan(keterampilanId) {
    return props.bagianSoal.filter(p =>
        String(p.keterampilan_id) === String(keterampilanId) &&
        String(p.bank_soal_id) === String(form.bank_soal_id),
    );
}

function globalParts() {
    return bagianSoalUntukKeterampilan(keterampilanGlobal.value);
}

function materialForSkill(bankId, keterampilanId) {
    return props.kebijakanMateriSoal.find(p =>
        String(p.bank_soal_id) === String(bankId) &&
        String(p.keterampilan_id) === String(keterampilanId),
    )?.material || 'text';
}

const isAudioQuestion = (q) => materialForSkill(form.bank_soal_id, q.keterampilan_id) === 'audio';

const hasListeningQuestion = computed(() => materialForSkill(form.bank_soal_id, keterampilanGlobal.value) === 'audio');

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
    materi_soal_id: null,
    materi_baru_judul: '',
    materi_baru_isi_teks: '',
    materi_baru_berkas_audio: null,
    materi_baru_berkas_gambar: null,
    _kembali_keterampilan: props.filterKembali?.keterampilan || '',
    _kembali_bagian_soal_id: props.filterKembali?.bagian_soal_id || '',
    soal: [newQuestion()],
});

const selectedBank = computed(() =>
    props.bankSoal.find(b => String(b.id) === String(form.bank_soal_id)) || null,
);

const materiSoalUntukBank = computed(() =>
    props.materiSoal.filter(p => String(p.bank_soal_id) === String(form.bank_soal_id)),
);

const materiSoalSesuai = computed(() =>
    hasListeningQuestion.value
        ? materiSoalUntukBank.value.filter(p => p.memiliki_audio)
        : materiSoalUntukBank.value.filter(p => !p.memiliki_audio),
);

watch(keterampilanGlobal, (keterampilan) => {
    globalPart.value = '';
    for (const q of form.soal) {
        q.keterampilan_id = keterampilan;
        q.bagian_soal_id = '';
    }

    form.materi_soal_id = null;
    if (!hasListeningQuestion.value) {
        form.materi_baru_berkas_audio = null;
    }
});

watch(() => form.bank_soal_id, () => {
    keterampilanGlobal.value = '';
    globalPart.value = '';
    form.materi_soal_id = null;
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
    if (!form.bank_soal_id) return false;
    if (form.soal.length === 0) return false;
    if (!keterampilanGlobal.value) return false;
    if (!globalPart.value) return false;

    if (materiSoalMode.value === 'existing' && !form.materi_soal_id) return false;
    if (materiSoalMode.value === 'new') {
        if (!form.materi_baru_judul.trim()) return false;
        if (hasListeningQuestion.value && !form.materi_baru_berkas_audio) return false;
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
    <TataLetakDasbor title="Tambah Soal Baru" subtitle="Buat soal baru beserta materi pendampingnya">
        <Link :href="route('pustaka-soal.daftar', indexUrl)" class="inline-block text-secondary text-label-md font-medium hover:underline mb-6">← Kembali ke Pustaka Soal</Link>
        <div class="max-w-5xl mx-auto">
            <div class="bg-surface-white rounded-3xl p-8 shadow-soft border border-outline-variant/30">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-text-heading">Tambah Soal Baru</h2>
                    <UserGuide
                        title="Panduan Tambah Soal"
                        :steps="[
                             { title: 'Pilih Bank Soal', desc: 'Bank menentukan kategori tes.' },
                             { title: 'Pilih Keterampilan & Bagian Soal', desc: 'Listening wajib menggunakan materiSoal audio; Reading memakai materiSoal teks/gambar.' },
                             { title: 'Pilih / Buat Materi Soal', desc: 'Listening: audio (wajib) + gambar (opsional). Reading: teks atau gambar.' },
                             { title: 'Isi Soal & Kunci', desc: 'Soal standalone langsung diisi; kunci jawaban di bawah soal.' },
                        ]"
                        :rules="[
                            'Listening: materi, soal & pilihan ada di audio — materiSoal audio wajib.',
                            'Reading: teks soal + 4 pilihan jawaban wajib diisi.',
                        ]"
                    />
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                             <div v-if="bankSoalTerpilih">
                                <label class="text-label-md font-medium text-primary block mb-1.5">Bank Soal</label>
                                <div class="flex items-center gap-2 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl">
                                    <IconBooks :size="18" class="text-secondary shrink-0" />
                                     <span class="text-body-md font-medium text-text-heading truncate">{{ selectedBank?.nama }}</span>
                                     <span v-if="selectedBank?.jenis_tes" class="inline-block bg-pastel-blue/50 text-primary px-2.5 py-0.5 rounded-full text-label-md shrink-0">
                                         {{ selectedBank.jenis_tes.nama }}
                                    </span>
                                </div>
                            </div>
                            <div v-else>
                                <DropDown
                                     v-model="form.bank_soal_id"
                                    :options="bankSoal"
                                    label="Bank Soal *"
                                    placeholder="Pilih Bank Soal"
                                     :option-label="(b) => b.nama + (b.jenis_tes ? ' (' + b.jenis_tes.nama + ')' : '')"
                                    option-value="id"
                                />
                            </div>
                             <p v-if="form.errors.bank_soal_id" class="text-error-red text-xs mt-1">{{ form.errors.bank_soal_id }}</p>
                        </div>
                    </div>
                    <PemilihKeterampilanBagian
                        v-model:selected-keterampilan="keterampilanGlobal"
                        v-model:selected-bagian="globalPart"
                        :keterampilan="opsiKeterampilan"
                        :bagian-soal="globalParts()"
                    />

                    <hr class="border-outline-variant/50" />

                    <FormMateriSoal
                        v-model:mode="materiSoalMode"
                        :form="form"
                        :materi-soal="materiSoalSesuai"
                        :has-listening-question="hasListeningQuestion"
                    />

                    <DaftarSoal
                        ref="daftarSoalRef"
                        :questions="form.soal"
                        :materi-soal-mode="materiSoalMode"
                        :option-keys="optionKeys"
                        :is-audio-question="isAudioQuestion"
                        @add="addQuestion"
                        @remove="removeQuestion"
                    />

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <BaseButton type="submit" :disabled="form.processing || !canSubmit" size="xl" class="flex-1">
                            {{ form.processing ? 'Menyimpan...' : `Simpan ${form.soal.length} Soal` }}
                        </BaseButton>
                        <BaseButton :href="route('pustaka-soal.daftar', indexUrl)" variant="secondary" size="lg">Batal</BaseButton>
                    </div>
                </form>
            </div>
        </div>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" :media-type="mediaType" />
    </TataLetakDasbor>
</template>
