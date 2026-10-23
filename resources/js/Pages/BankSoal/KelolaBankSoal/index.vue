<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import FormBankSoal from './Components/FormBankSoal.vue';
import TabelBankSoal from './Components/TabelBankSoal.vue';
import TabelKategoriBankSoal from './Components/TabelKategoriBankSoal.vue';

const props = defineProps({
    bankSoal: { type: Array, default: () => [] },
    jenisTes: { type: Array, default: () => [] },
    kategori: { type: Object, default: null },
});

const confirm = useConfirm();

const form = useForm({
    jenis_tes_id: '',
    nama: '',
    deskripsi: '',
    aktif: true,
});

const editForm = useForm({
    jenis_tes_id: '',
    nama: '',
    deskripsi: '',
    aktif: true,
});

const showModal = ref(false);
const creating = ref(false);
const editingBank = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

function stripHtml(html) {
    const div = document.createElement('div');
    div.innerHTML = html || '';
    return div.textContent || '';
}

const semuaKolom = [
    { key: 'nama', label: 'Nama', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'jenis_tes', label: 'Kategori', render: (val) => val?.nama || '-' },
    { key: 'deskripsi', label: 'Deskripsi', render: (val) => { const text = stripHtml(val); return text ? (text.length > 60 ? text.substring(0, 60) + '…' : text) : '-'; } },
    { key: 'soal_count', label: 'Soal', render: (val) => val ?? 0 },
    { key: 'aktif', label: 'Status', badge: true, render: (val) => val ? 'Aktif' : 'Nonaktif' },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

const kolom = computed(() => props.kategori
    ? semuaKolom.filter((column) => column.key !== 'jenis_tes')
    : semuaKolom);

const kategoriKolom = [
    { key: 'nama', label: 'Kategori', sortable: true, className: 'font-medium text-text-heading' },
    { key: 'deskripsi', label: 'Deskripsi', render: (val) => val ? (val.length > 80 ? `${val.substring(0, 80)}…` : val) : '-' },
    { key: 'jumlahBankSoal', label: 'Bank Soal', sortable: true, render: (val) => val ?? 0 },
    { key: 'id', label: 'Aksi', slot: 'actions' },
];

const kategoriBankSoal = computed(() => {
    const groups = new Map();

    props.jenisTes.forEach((jenisTes) => {
        groups.set(jenisTes.id, {
            id: jenisTes.id,
            nama: jenisTes.nama,
            deskripsi: stripHtml(jenisTes.deskripsi),
            aktif: !!jenisTes.aktif,
            jumlahBankSoal: 0,
            jumlahSoal: 0,
        });
    });

    props.bankSoal.forEach((bank) => {
        const jenisTes = bank.jenis_tes || {
            id: null,
            nama: 'Tanpa Kategori',
            deskripsi: '',
            aktif: false,
        };
        const key = jenisTes.id ?? 'tanpa-kategori';
        const existing = groups.get(key);

        if (existing) {
            existing.jumlahBankSoal += 1;
            existing.jumlahSoal += Number(bank.soal_count ?? 0);
            return;
        }

        groups.set(key, {
            id: jenisTes.id,
            nama: jenisTes.nama,
            deskripsi: stripHtml(jenisTes.deskripsi),
            aktif: !!jenisTes.aktif,
            jumlahBankSoal: 1,
            jumlahSoal: Number(bank.soal_count ?? 0),
        });
    });

    return Array.from(groups.values()).sort((left, right) => left.nama.localeCompare(right.nama, 'id'));
});

function openCreate() {
    creating.value = true;
    editingBank.value = null;
    form.clearErrors();
    form.reset();
    if (props.kategori?.id) {
        form.jenis_tes_id = String(props.kategori.id);
    } else if (props.jenisTes.length === 1) {
        form.jenis_tes_id = String(props.jenisTes[0].id);
    }
    showModal.value = true;
}

function submit() {
    form.post(route('pustaka-soal.bank-soal.simpan'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

function startEdit(bank) {
    creating.value = false;
    editingBank.value = bank;
    editForm.clearErrors();
    editForm.reset();
    editForm.jenis_tes_id = String(bank.jenis_tes_id);
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
    editingBank.value = null;
    creating.value = false;
    editForm.reset();
}

async function destroy(bank) {
    if (!await confirm.confirm(`Hapus bank soal "${bank.nama}"? Semua soal di dalamnya akan terhapus.`)) return;
    router.delete(route('pustaka-soal.bank-soal.hapus', bank.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="kategori ? `Bank Soal - ${kategori.nama}` : 'Kategori Bank Soal'" />
    <TataLetakDasbor
        :title="kategori ? `Bank Soal - ${kategori.nama}` : 'Kategori Bank Soal'"
        :subtitle="kategori ? 'Daftar bank soal dalam kategori ini' : 'Pilih kategori untuk melihat daftar bank soal'"
    >
        <template v-if="!kategori">
            <TabelKategoriBankSoal :data="kategoriBankSoal" :columns="kategoriKolom" @create="openCreate" />
        </template>

        <template v-else>
            <TabelBankSoal
                :data="bankSoal"
                :columns="kolom"
                :back-url="route('pustaka-soal.bank-soal.daftar')"
                @create="openCreate"
                @edit="startEdit"
                @delete="destroy"
            />
        </template>

        <FormBankSoal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            :jenis-tes="jenisTes"
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
    </TataLetakDasbor>
</template>
