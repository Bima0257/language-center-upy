<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { computed } from 'vue';
import FormKonteksSoal from './Components/FormKonteksSoal.vue';
import KontenSoal from './Components/KontenSoal.vue';
import RingkasanSoal from './Components/RingkasanSoal.vue';

const props = defineProps({
    soal: { type: Object, required: true },
    bankSoal: { type: Array, default: () => [] },
    opsiSection: { type: Array, default: () => [] },
    partSoal: { type: Array, default: () => [] },
    materiSoal: { type: Array, default: () => [] },
    kebijakanMateriSoal: { type: Array, default: () => [] },
});

const optionKeys = ['A', 'B', 'C', 'D'];

const form = useForm({
    bank_soal_id: props.soal.bank_soal_id || '',
    section_id: props.soal.section_id || '',
    part_soal_id: props.soal.part_soal_id || '',
    materi_soal_id: props.soal.materi_soal_id || null,
    teks_soal: props.soal.teks_soal || '',
    pilihan_a: props.soal.pilihan_a || '',
    pilihan_b: props.soal.pilihan_b || '',
    pilihan_c: props.soal.pilihan_c || '',
    pilihan_d: props.soal.pilihan_d || '',
    kunci_jawaban: props.soal.kunci_jawaban || '',
});

function namaSectionDariId(sectionId) {
    return props.opsiSection.find(s => String(s.value) === String(sectionId))?.label || '';
}

function materialForSection(bankId, sectionId) {
    const bank = props.bankSoal.find((item) => String(item.id) === String(bankId));

    return props.kebijakanMateriSoal.find(p =>
        String(p.program_tes_id) === String(bank?.program_tes_id) &&
        String(p.section_id) === String(sectionId),
    )?.materi || null;
}

const isMaterialAudio = computed(() => materialForSection(form.bank_soal_id, form.section_id) === 'audio');

const selectedPolicy = computed(() => {
    const bank = props.bankSoal.find((item) => String(item.id) === String(form.bank_soal_id));

    return props.kebijakanMateriSoal.find((policy) =>
        String(policy.program_tes_id) === String(bank?.program_tes_id) &&
        String(policy.section_id) === String(form.section_id),
    ) || null;
});

const selectedPassage = computed(() =>
    props.materiSoal.find((p) => String(p.id) === String(form.materi_soal_id)) || null,
);

const canSubmit = computed(() => {
    if (!selectedPolicy.value) return false;
    if (isMaterialAudio.value && !selectedPassage.value?.url_audio) return false;
    return true;
});

const partSoalsForSelectedSection = computed(() =>
    props.partSoal.filter((p) =>
        String(p.section_id) === String(form.section_id) &&
        String(p.bank_soal_id) === String(form.bank_soal_id),
    ),
);

function onSectionChange() {
    form.part_soal_id = '';
}

const indexUrl = computed(() => route('pustaka-soal.daftar', {
    bank_soal_id: props.soal.bank_soal_id,
    section: props.soal.section_id,
}));

function submit() {
    form.put(route('pustaka-soal.perbarui', props.soal.id));
}
</script>

<template>
    <Head title="Ubah Soal" />
    <TataLetakDasbor
        title="Ubah Soal"
        subtitle="Perbarui teks soal, opsi jawaban, dan kunci"
        :back-href="indexUrl"
        back-label="Kembali ke Pustaka Soal"
    >
        <div class="max-w-5xl mx-auto">
            <div class="bg-surface-white rounded-2xl p-8 shadow-soft border border-outline-variant/30">
                <RingkasanSoal :section-name="namaSectionDariId(form.section_id)" />
                <form @submit.prevent="submit" class="space-y-6">
                    <FormKonteksSoal
                        :form="form"
                        :bank-soal="bankSoal"
                        :opsi-section="opsiSection"
                        :part-soals="partSoalsForSelectedSection"
                        :materi-soal="materiSoal"
                        @section-change="onSectionChange"
                    />

                    <hr class="border-outline-variant/50" />

                    <KontenSoal
                        :form="form"
                        :option-keys="optionKeys"
                        :is-material-audio="isMaterialAudio"
                        :selected-passage="selectedPassage"
                    />

                    <hr class="border-outline-variant/50" />
                    <BaseFormActions
                        :processing="form.processing"
                        :disabled="!canSubmit"
                        :cancel-href="indexUrl"
                    />
                </form>
            </div>
        </div>
    </TataLetakDasbor>
</template>
