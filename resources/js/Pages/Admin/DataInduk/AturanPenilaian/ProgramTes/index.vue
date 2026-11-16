<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import BaseButton from '@/Components/Base/BaseButton.vue';
import BaseCard from '@/Components/Base/BaseCard.vue';
import { useConfirm } from '@/Composables/useConfirm';
import AturanPenilaianTable from '../Components/AturanPenilaianTable.vue';
import AturanPenilaianFormModal from '../Components/AturanPenilaianFormModal.vue';

const props = defineProps({
    programTes: { type: Object, required: true },
    daftarSkema: { type: Array, default: () => [] },
    skemaTerpilih: { type: Object, default: null },
    aturanPenilaian: { type: Array, default: () => [] },
    interpretasiSkor: { type: Array, default: () => [] },
    total: { type: Object, default: () => ({ minimum: 0, maksimum: 0 }) },
    readOnly: { type: Boolean, default: true },
});

const confirm = useConfirm();

const showModal = ref(false);
const editingRule = ref(null);
const selectedSkema = ref(props.skemaTerpilih?.id ?? '');
const form = useForm({
    tabel_konversi: {},
    skor_mentah_maksimum: 50,
    skor_skala_minimum: 100,
    skor_skala_maksimum: 200,
});

const statusLabels = {
    draft: 'Draft',
    aktif: 'Aktif',
    arsip: 'Arsip',
};

const statusClasses = {
    draft: 'bg-warning-container text-on-warning-container',
    aktif: 'bg-success-container text-on-success-container',
    arsip: 'bg-surface-container text-text-muted',
};

const statusSkema = computed(() => props.skemaTerpilih?.status ?? null);

function pilihSkema() {
    router.get(
        route('admin.data-induk.aturan-penilaian.program-tes.daftar', props.programTes.id),
        { skema: selectedSkema.value },
        { preserveState: true, preserveScroll: true },
    );
}

function buatVersi() {
    router.post(route('admin.data-induk.aturan-penilaian.program-tes.versi.simpan', props.programTes.id), {}, {
        preserveScroll: true,
    });
}

async function aktifkanSkema() {
    if (!props.skemaTerpilih) return;

    if (!await confirm.confirm('Aktifkan versi skema ini? Versi aktif sebelumnya akan diarsipkan.')) return;

    router.post(route('admin.data-induk.aturan-penilaian.skema.aktifkan', props.skemaTerpilih.id), {}, {
        preserveScroll: true,
    });
}

async function arsipkanSkema() {
    if (!props.skemaTerpilih) return;

    if (!await confirm.confirm('Arsipkan versi skema ini?')) return;

    router.post(route('admin.data-induk.aturan-penilaian.skema.arsipkan', props.skemaTerpilih.id), {}, {
        preserveScroll: true,
    });
}

function bukaInterpretasi() {
    router.get(route('admin.data-induk.interpretasi-skor.program-tes.daftar', props.programTes.id), {
        skema: props.skemaTerpilih?.id,
    });
}

function startEdit(rule) {
    if (props.readOnly) return;

    editingRule.value = rule;
    form.clearErrors();
    form.tabel_konversi = { ...(rule.tabel_konversi || {}) };
    form.skor_mentah_maksimum = rule.skor_mentah_maksimum || 50;
    form.skor_skala_minimum = rule.skor_skala_minimum ?? 100;
    form.skor_skala_maksimum = rule.skor_skala_maksimum || 200;
    showModal.value = true;
}

function closeModal() {
    showModal.value = false;
    editingRule.value = null;
    form.reset();
    form.clearErrors();
}

function saveRule() {
    if (!editingRule.value || !props.skemaTerpilih) return;

    form.put(route(
        'admin.data-induk.aturan-penilaian.skema.section.perbarui',
        [props.skemaTerpilih.id, editingRule.value.section_id],
    ), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}
</script>

<template>
    <Head :title="`Aturan Penilaian - ${props.programTes.nama}`" />
    <TataLetakDasbor
        :title="`Aturan Penilaian - ${props.programTes.nama}`"
        :subtitle="`Kelola skema penilaian pada ${props.programTes.nama}`"
        :back-href="route('admin.data-induk.aturan-penilaian.daftar')"
    >
        <BaseCard class="mb-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div>
                        <label class="mb-1.5 block text-label-md font-medium text-text-heading">Versi Skema</label>
                        <select
                            v-model="selectedSkema"
                            class="min-w-56 rounded-2xl border border-outline-variant bg-surface-container-lowest px-4 py-2.5 text-body-md text-text-body focus:border-secondary focus:outline-none"
                            @change="pilihSkema"
                        >
                            <option value="" disabled>Pilih versi</option>
                            <option v-for="skema in props.daftarSkema" :key="skema.id" :value="skema.id">
                                {{ skema.nama }} — {{ statusLabels[skema.status] || skema.status }}
                            </option>
                        </select>
                    </div>

                    <span
                        v-if="statusSkema"
                        class="inline-flex w-fit items-center rounded-full px-3 py-1 text-label-md font-medium"
                        :class="statusClasses[statusSkema] || statusClasses.arsip"
                    >
                        {{ statusLabels[statusSkema] || statusSkema }}
                    </span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <BaseButton variant="secondary" size="sm" @click="buatVersi">
                        Buat Versi Baru
                    </BaseButton>
                    <BaseButton
                        v-if="statusSkema === 'draft'"
                        variant="primary"
                        size="sm"
                        @click="aktifkanSkema"
                    >
                        Aktifkan
                    </BaseButton>
                    <BaseButton
                        v-if="statusSkema === 'draft'"
                        variant="secondary"
                        size="sm"
                        @click="arsipkanSkema"
                    >
                        Arsipkan
                    </BaseButton>
                    <BaseButton
                        v-if="props.skemaTerpilih"
                        variant="secondary"
                        size="sm"
                        @click="bukaInterpretasi"
                    >
                        Interpretasi Skor
                    </BaseButton>
                </div>
            </div>

            <div v-if="props.skemaTerpilih" class="mt-4 flex flex-wrap gap-4 text-label-md text-text-muted">
                <span>Total skala: {{ props.total.minimum }}–{{ props.total.maksimum }}</span>
                <span>Interpretasi: {{ props.interpretasiSkor.length }} rentang</span>
            </div>

            <p v-if="props.readOnly && props.skemaTerpilih" class="mt-3 text-label-md text-text-muted">
                Versi ini tidak dapat diubah. Buat versi baru untuk mengubah aturan penilaian.
            </p>

            <p v-if="!props.skemaTerpilih" class="mt-3 text-label-md text-text-muted">
                Belum ada skema penilaian. Buat versi baru untuk mulai mengonfigurasi.
            </p>
        </BaseCard>

        <AturanPenilaianTable
            :aturan-penilaian="props.aturanPenilaian"
            :read-only="props.readOnly"
            @edit="startEdit"
        />

        <AturanPenilaianFormModal
            :show="showModal"
            :form="form"
            :rule="editingRule"
            @close="closeModal"
            @submit="saveRule"
        />
    </TataLetakDasbor>
</template>
