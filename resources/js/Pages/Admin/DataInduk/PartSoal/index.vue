<script setup>
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import ProgramTesSummaryTable from './Components/ProgramTesSummaryTable.vue';
import BankSoalSummaryTable from './Components/BankSoalSummaryTable.vue';
import PartSoalGroupTable from './Components/PartSoalGroupTable.vue';
import PartSoalFormModal from './Components/PartSoalFormModal.vue';
import { computed, ref, watch } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    partSoal: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
    programTes: { type: Array, default: () => [] },
    bankSoalTerpilihId: { type: [Number, String], default: null },
    programTesTerpilihId: { type: [Number, String], default: null },
    opsiSection: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();
const page = usePage();

const form = useForm({
    bank_soal_id: '',
    section_id: '',
    nama: '',
    petunjuk: '',
    aktif: true,
});

const editForm = useForm({
    bank_soal_id: '',
    section_id: '',
    nama: '',
    petunjuk: '',
    aktif: true,
});

const showModal = ref(false);
const creating = ref(false);
const partSoalYangDiedit = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);
const canManagePartSoal = computed(() =>
    [
        'admin.data-induk.part-soal.simpan',
        'admin.data-induk.part-soal.urutkan',
        'admin.data-induk.part-soal.perbarui',
        'admin.data-induk.part-soal.hapus',
    ].some(permission => (page.props.auth?.permissions || []).includes(permission)),
);

const bankFilter = ref(props.bankSoalTerpilihId ? String(props.bankSoalTerpilihId) : '');
const programTesFilter = ref(props.programTesTerpilihId ? String(props.programTesTerpilihId) : '');
const sectionAktif = ref('');

watch(() => props.bankSoalTerpilihId, (bankId) => {
    bankFilter.value = bankId ? String(bankId) : '';
}, { immediate: true });

watch(() => props.programTesTerpilihId, (programTesId) => {
    programTesFilter.value = programTesId ? String(programTesId) : '';
}, { immediate: true });

watch(() => props.opsiSection, (opsiSection) => {
    const masihTersedia = opsiSection.some((section) => String(section.value) === String(sectionAktif.value));

    if (!masihTersedia) {
        sectionAktif.value = opsiSection[0]?.value ?? '';
    }
}, { immediate: true });

watch(bankFilter, () => {
    sectionAktif.value = props.opsiSection[0]?.value ?? '';
});

const bankSoalTerpilih = computed(() => props.bankSoal.find((bank) => String(bank.id) === String(bankFilter.value)) || null);
const programTesTerpilih = computed(() => props.programTes.find((programTes) => String(programTes.id) === String(programTesFilter.value)) || null);

const ringkasanBankSoal = computed(() => {
    const groups = new Map(props.bankSoal.map((bank) => [String(bank.id), {
        id: bank.id,
        nama: bank.nama,
        program_tes: bank.program_tes,
        aktif: !!bank.aktif,
        jumlahPart: 0,
    }]));

    props.partSoal.forEach((part) => {
        const group = groups.get(String(part.bank_soal_id));
        if (!group) return;

        group.jumlahPart += 1;
    });

    return Array.from(groups.values()).sort((left, right) => left.nama.localeCompare(right.nama, 'id'));
});

const ringkasanProgramTes = computed(() => {
    const groups = new Map(props.programTes.map((programTes) => [String(programTes.id), {
        id: programTes.id,
        kode: programTes.kode,
        nama: programTes.nama,
        jumlahBankSoal: 0,
        jumlahPart: 0,
    }]));
    const jumlahPartPerBank = new Map();

    props.partSoal.forEach((part) => {
        const bankId = String(part.bank_soal_id);
        jumlahPartPerBank.set(bankId, (jumlahPartPerBank.get(bankId) ?? 0) + 1);
    });

    props.bankSoal.forEach((bank) => {
        const group = groups.get(String(bank.program_tes_id));
        if (!group) return;

        group.jumlahBankSoal += 1;
        group.jumlahPart += jumlahPartPerBank.get(String(bank.id)) ?? 0;
    });

    return Array.from(groups.values()).sort((left, right) => left.nama.localeCompare(right.nama, 'id'));
});

function programTesUrl(programTesId) {
    return `${route('admin.data-induk.part-soal.daftar')}?program_tes_id=${encodeURIComponent(programTesId)}`;
}

function detailUrl(bankId) {
    const params = new URLSearchParams({ bank_id: String(bankId) });
    if (programTesFilter.value) params.set('program_tes_id', programTesFilter.value);

    return `${route('admin.data-induk.part-soal.daftar')}?${params.toString()}`;
}

const backUrl = computed(() => programTesFilter.value ? programTesUrl(programTesFilter.value) : route('admin.data-induk.part-soal.daftar'));

// ===== Group per section =====
const tabsSection = computed(() => props.opsiSection.map((section) => ({
    value: section.value,
    label: section.label,
})));

const kelompokSection = computed(() => props.opsiSection.map((section) => ({
    ...section,
    partSoal: props.partSoal
         .filter((p) => !bankFilter.value || String(p.bank_soal_id) === String(bankFilter.value))
        .filter((p) => String(p.section_id) === String(section.value))
        .sort((a, b) => (a.urutan ?? 0) - (b.urutan ?? 0)),
})).filter((group) => String(group.value) === String(sectionAktif.value)));

// ===== Drag & drop reorder (per grup section) =====
const reordering = ref(false);
const originalOrders = ref({});

watch(kelompokSection, (groups) => {
    originalOrders.value = Object.fromEntries(
        groups.map((g) => [g.value, g.partSoal.map((p) => p.id).join(',')]),
    );
}, { immediate: true });

function onReorder(sectionId, evt) {
    if (!canManagePartSoal.value || reordering.value) return;

    const adaPartTerkunci = props.partSoal.some(
        (part) => String(part.section_id) === String(sectionId) && part.dipakai_ujian_aktif,
    );

    if (adaPartTerkunci) return;

    const prev = (originalOrders.value[sectionId] || '').split(',').filter(Boolean);
    if (prev.length === 0 || typeof evt.oldIndex !== 'number' || typeof evt.newIndex !== 'number') return;

    // Hitung urutan baru secara deterministik dari posisi drop (tidak bergantung timing mutasi list)
    const ids = [...prev];
    const [moved] = ids.splice(evt.oldIndex, 1);
    ids.splice(evt.newIndex, 0, moved);

    if (ids.join(',') === prev.join(',')) return;

    reordering.value = true;
     router.post(route('admin.data-induk.part-soal.urutkan'), { part_soal: ids }, {
        preserveScroll: true,
        onError: () => {
            toast.error('Gagal menyimpan urutan.');
            router.reload({ only: ['partSoal'] });
        },
        onFinish: () => { reordering.value = false; },
    });
}

// ===== Modal =====
function openCreate() {
    if (!canManagePartSoal.value) return;

    creating.value = true;
    partSoalYangDiedit.value = null;
    form.clearErrors();
    form.reset();
     form.bank_soal_id = bankFilter.value || '';
    form.section_id = sectionAktif.value;
    showModal.value = true;
}

function submit() {
    form.post(route('admin.data-induk.part-soal.simpan'), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function startEdit(partSoal) {
    if (!canManagePartSoal.value || partSoal.dipakai_ujian_aktif) return;

    creating.value = false;
    partSoalYangDiedit.value = partSoal;
    editForm.clearErrors();
    editForm.reset();
     editForm.bank_soal_id = String(partSoal.bank_soal_id);
    editForm.section_id = sectionAktif.value;
     editForm.nama = partSoal.nama;
    editForm.petunjuk = partSoal.petunjuk || '';
    editForm.aktif = !!partSoal.aktif;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.data-induk.part-soal.perbarui', partSoalYangDiedit.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
}

async function destroy(partSoal) {
    if (!canManagePartSoal.value || partSoal.dipakai_ujian_aktif) return;

    if (!await confirm.confirm(`Hapus partSoal "${partSoal.nama}"?`)) return;
    router.delete(route('admin.data-induk.part-soal.hapus', partSoal.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="bankSoalTerpilih ? `Part Soal - ${bankSoalTerpilih.nama}` : programTesTerpilih ? `Bank Soal - ${programTesTerpilih.nama}` : 'Data Induk - Part Soal'" />
    <TataLetakDasbor
        :title="bankSoalTerpilih ? `Part Soal - ${bankSoalTerpilih.nama}` : programTesTerpilih ? `Bank Soal - ${programTesTerpilih.nama}` : 'Data Induk Part Soal'"
        :subtitle="bankSoalTerpilih ? 'Kelola part soal per section' : programTesTerpilih ? 'Pilih bank soal untuk melihat part soal' : 'Pilih program tes untuk melihat bank soal'"
        :back-href="programTesFilter ? backUrl : null"
    >
        <template v-if="!programTesFilter">
            <ProgramTesSummaryTable
                :ringkasan-program-tes="ringkasanProgramTes"
                :detail-url="programTesUrl"
            />
        </template>

        <template v-else-if="!bankFilter">
            <BankSoalSummaryTable
                :ringkasan-bank-soal="ringkasanBankSoal"
                :detail-url="detailUrl"
            />
        </template>

        <template v-else>
            <div v-if="bankSoalTerpilih" class="relative">
                <BaseTabsChrome
                    v-if="tabsSection.length"
                    v-model="sectionAktif"
                    :items="tabsSection"
                    aria-label="Filter section part soal"
                    class="relative z-10 -mb-px px-5"
                />

                <BaseCard :padding="false" class="border-t-0! overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3.5">
                        <h2 class="text-title-lg font-semibold text-text-heading">Daftar Part Soal</h2>
                        <div class="flex items-center gap-2">
                             <BaseTambahData v-if="canManagePartSoal" @click="openCreate" />
                        </div>
                    </div>
                    <PartSoalGroupTable
                        v-for="group in kelompokSection"
                        :key="group.value"
                         :group="group"
                         :reordering="reordering"
                         :can-manage="canManagePartSoal"
                         attached
                        @reorder="onReorder"
                        @edit="startEdit"
                        @delete="destroy"
                    />
                </BaseCard>
            </div>
            <BaseCard v-else class="text-center py-12">
                <p class="text-text-muted text-body-md">Bank soal tidak ditemukan.</p>
            </BaseCard>
        </template>

        <PartSoalFormModal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            :bank-soal="bankSoal"
            :opsi-section="opsiSection"
            :bank-filter="bankFilter"
            lock-section
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
    </TataLetakDasbor>
</template>
