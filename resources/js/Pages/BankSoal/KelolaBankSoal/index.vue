<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { computed, h, ref } from 'vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import { useConfirm } from '@/Composables/useConfirm';
import { formatProgramTes } from '@/constants/programTes';
import DetailBankSoalModal from './Components/DetailBankSoalModal.vue';
import FormBankSoal from './Components/FormBankSoal.vue';
import TabelBankSoal from './Components/TabelBankSoal.vue';
import TabelProgramTesBankSoal from './Components/TabelProgramTesBankSoal.vue';

const props = defineProps({
    bankSoal: { type: Array, default: () => [] },
    programTes: { type: Array, default: () => [] },
    programTesTerpilih: { type: Object, default: null },
});

const confirm = useConfirm();

const form = useForm({
    program_tes_id: '',
    nama: '',
    deskripsi: '',
    aktif: true,
});

const editForm = useForm({
    program_tes_id: '',
    nama: '',
    deskripsi: '',
    aktif: true,
});

const showModal = ref(false);
const showDetailModal = ref(false);
const creating = ref(false);
const editingBank = ref(null);
const detailBank = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

const semuaKolom = [
    { accessorKey: 'nama', header: 'Nama', meta: { cellClass: 'font-medium text-text-heading' } },
    {
        id: 'program_tes',
        header: 'Program Tes',
        accessorFn: row => formatProgramTes(row.program_tes),
    },
    { accessorKey: 'soal_count', header: 'Soal', cell: info => info.getValue() ?? 0 },
    {
        accessorKey: 'aktif',
        header: 'Status',
        cell: info => h(
            BaseBadge,
            { variant: info.getValue() ? 'success' : 'neutral' },
            () => (info.getValue() ? 'Aktif' : 'Nonaktif'),
        ),
    },
];

const kolom = computed(() => props.programTesTerpilih
    ? semuaKolom.filter((column) => column.id !== 'program_tes')
    : semuaKolom);

const programTesKolom = [
    {
        id: 'program_tes',
        header: 'Program Tes',
        meta: { cellClass: 'font-medium text-text-heading' },
        accessorFn: row => formatProgramTes(row),
    },
    { accessorKey: 'jumlahBankSoal', header: 'Bank Soal', cell: info => info.getValue() ?? 0 },
];

const ringkasanProgramTes = computed(() => {
    const groups = new Map();

    props.programTes.forEach((programTes) => {
        groups.set(programTes.id, {
            id: programTes.id,
            kode: programTes.kode,
            nama: programTes.nama,
            deskripsi: stripHtml(programTes.deskripsi),
            aktif: !!programTes.aktif,
            jumlahBankSoal: 0,
            jumlahSoal: 0,
        });
    });

    props.bankSoal.forEach((bank) => {
        const programTes = bank.program_tes || {
            id: null,
            kode: null,
            nama: 'Tanpa Program Tes',
            deskripsi: '',
            aktif: false,
        };
        const key = programTes.id ?? 'tanpa-program-tes';
        const existing = groups.get(key);

        if (existing) {
            existing.jumlahBankSoal += 1;
            existing.jumlahSoal += Number(bank.soal_count ?? 0);
            return;
        }

        groups.set(key, {
            id: programTes.id,
            kode: programTes.kode,
            nama: programTes.nama,
            deskripsi: stripHtml(programTes.deskripsi),
            aktif: !!programTes.aktif,
            jumlahBankSoal: 1,
            jumlahSoal: Number(bank.soal_count ?? 0),
        });
    });

    return Array.from(groups.values()).sort((left, right) => left.nama.localeCompare(right.nama, 'id'));
});

function openDetail(bank) {
    detailBank.value = bank;
    showDetailModal.value = true;
}

function closeDetail() {
    showDetailModal.value = false;
}

function openCreate() {
    creating.value = true;
    editingBank.value = null;
    form.clearErrors();
    form.reset();
    if (props.programTesTerpilih?.id) {
        form.program_tes_id = String(props.programTesTerpilih.id);
    } else if (props.programTes.length === 1) {
        form.program_tes_id = String(props.programTes[0].id);
    }
    showModal.value = true;
}

function submit() {
    form.post(route('pustaka-soal.bank-soal.simpan'), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function startEdit(bank) {
    creating.value = false;
    editingBank.value = bank;
    editForm.clearErrors();
    editForm.reset();
    editForm.program_tes_id = String(bank.program_tes_id);
    editForm.nama = bank.nama;
    editForm.deskripsi = bank.deskripsi || '';
    editForm.aktif = !!bank.aktif;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('pustaka-soal.bank-soal.perbarui', editingBank.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
}

async function destroy(bank) {
    if (!await confirm.confirm(`Hapus bank soal "${bank.nama}"? Semua soal di dalamnya akan terhapus.`)) return;
    router.delete(route('pustaka-soal.bank-soal.hapus', bank.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="programTesTerpilih ? `Bank Soal - ${programTesTerpilih.nama}` : 'Program Tes Bank Soal'" />
    <TataLetakDasbor
        :title="programTesTerpilih ? `Bank Soal - ${programTesTerpilih.nama}` : 'Program Tes Bank Soal'"
        :subtitle="programTesTerpilih ? 'Daftar bank soal dalam program tes ini' : 'Pilih program tes untuk melihat daftar bank soal'"
        :back-href="programTesTerpilih ? route('pustaka-soal.bank-soal.daftar') : null"
    >
        <template v-if="!programTesTerpilih">
            <TabelProgramTesBankSoal
                table-key="admin-program-tes-bank-soal"
                :data="ringkasanProgramTes"
                :columns="programTesKolom"
                @create="openCreate"
            />
        </template>

        <template v-else>
            <TabelBankSoal
                table-key="admin-bank-soal"
                :data="bankSoal"
                :columns="kolom"
                @create="openCreate"
                @detail="openDetail"
                @edit="startEdit"
                @delete="destroy"
            />
        </template>

        <DetailBankSoalModal
            :show="showDetailModal"
            :bank-soal="detailBank"
            @close="closeDetail"
        />

        <FormBankSoal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            :program-tes="programTes"
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
    </TataLetakDasbor>
</template>
