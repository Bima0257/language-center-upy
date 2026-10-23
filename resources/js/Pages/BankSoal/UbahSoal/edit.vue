<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { computed } from 'vue';
import AksiFormSoal from './Components/AksiFormSoal.vue';
import FormKonteksSoal from './Components/FormKonteksSoal.vue';
import KontenSoal from './Components/KontenSoal.vue';
import RingkasanSoal from './Components/RingkasanSoal.vue';

const props = defineProps({
    soal: { type: Object, required: true },
    bankSoal: { type: Array, default: () => [] },
    opsiKeterampilan: { type: Array, default: () => [] },
    bagianSoal: { type: Array, default: () => [] },
    materiSoal: { type: Array, default: () => [] },
    kebijakanMateriSoal: { type: Array, default: () => [] },
});

const optionKeys = ['A', 'B', 'C', 'D'];

const form = useForm({
    bank_soal_id: props.soal.bank_soal_id || '',
    keterampilan_id: props.soal.keterampilan_id || '',
    bagian_soal_id: props.soal.bagian_soal_id || '',
    materi_soal_id: props.soal.materi_soal_id || null,
    teks_soal: props.soal.teks_soal || '',
    pilihan_a: props.soal.pilihan_a || '',
    pilihan_b: props.soal.pilihan_b || '',
    pilihan_c: props.soal.pilihan_c || '',
    pilihan_d: props.soal.pilihan_d || '',
    kunci_jawaban: props.soal.kunci_jawaban || '',
});

function namaKeterampilanDariId(keterampilanId) {
    return props.opsiKeterampilan.find(s => String(s.value) === String(keterampilanId))?.label || '';
}

function materialForSkill(bankId, keterampilanId) {
    return props.kebijakanMateriSoal.find(p =>
        String(p.bank_soal_id) === String(bankId) &&
        String(p.keterampilan_id) === String(keterampilanId),
    )?.material || 'text';
}

const isMaterialAudio = computed(() => materialForSkill(form.bank_soal_id, form.keterampilan_id) === 'audio');

const selectedPassage = computed(() =>
    props.materiSoal.find((p) => String(p.id) === String(form.materi_soal_id)) || null,
);

const materiSoalSesuai = computed(() =>
    props.materiSoal.filter((p) =>
        String(p.bank_soal_id) === String(form.bank_soal_id) &&
        (isMaterialAudio.value ? !!p.memiliki_audio : !p.memiliki_audio),
    ),
);

const canSubmit = computed(() => {
    if (isMaterialAudio.value && !selectedPassage.value?.url_audio) return false;
    return true;
});

const bagianSoalsForSelectedSkill = computed(() =>
    props.bagianSoal.filter((p) =>
        String(p.keterampilan_id) === String(form.keterampilan_id) &&
        String(p.bank_soal_id) === String(form.bank_soal_id),
    ),
);

function onSkillChange() {
    form.bagian_soal_id = '';
}

const indexUrl = computed(() => route('pustaka-soal.daftar', {
    bank_soal_id: props.soal.bank_soal_id,
    keterampilan: props.soal.keterampilan_id,
}));

function submit() {
    form.put(route('pustaka-soal.perbarui', props.soal.id));
}
</script>

<template>
    <Head title="Ubah Soal" />
    <TataLetakDasbor title="Ubah Soal" subtitle="Perbarui teks soal, opsi jawaban, dan kunci">
            <Link :href="indexUrl" class="inline-block text-secondary text-label-md font-medium hover:underline mb-6">← Kembali ke Pustaka Soal</Link>
        <div class="max-w-5xl mx-auto">
            <div class="bg-surface-white rounded-3xl p-8 shadow-soft border border-outline-variant/30">
                <RingkasanSoal :keterampilan-name="namaKeterampilanDariId(form.keterampilan_id)" />
                <form @submit.prevent="submit" class="space-y-6">
                    <FormKonteksSoal
                        :form="form"
                        :bank-soal="bankSoal"
                        :opsi-keterampilan="opsiKeterampilan"
                        :bagian-soals="bagianSoalsForSelectedSkill"
                        :materi-soal="materiSoalSesuai"
                        :is-material-audio="isMaterialAudio"
                        @skill-change="onSkillChange"
                    />

                    <hr class="border-outline-variant/50" />

                    <KontenSoal
                        :form="form"
                        :option-keys="optionKeys"
                        :is-material-audio="isMaterialAudio"
                        :selected-passage="selectedPassage"
                    />

                    <hr class="border-outline-variant/50" />
                    <AksiFormSoal :form="form" :can-submit="canSubmit" :cancel-href="indexUrl" />
                </form>
            </div>
        </div>
    </TataLetakDasbor>
</template>
