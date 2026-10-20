<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import JenisTesSummaryTable from './BagianSoal/Components/JenisTesSummaryTable.vue';
import BankSoalSummaryTable from './BagianSoal/Components/BankSoalSummaryTable.vue';
import BagianSoalGroupTable from './BagianSoal/Components/BagianSoalGroupTable.vue';
import BagianSoalFormModal from './BagianSoal/Components/BagianSoalFormModal.vue';
import { IconPlus, IconArrowLeft } from '@tabler/icons-vue';
import { computed, ref, watch } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const props = defineProps({
    bagianSoal: { type: Array, default: () => [] },
    bankSoal: { type: Array, default: () => [] },
    jenisTes: { type: Array, default: () => [] },
    bankSoalTerpilihId: { type: [Number, String], default: null },
    jenisTesTerpilihId: { type: [Number, String], default: null },
    opsiKeterampilan: { type: Array, default: () => [] },
});

const confirm = useConfirm();
const toast = useToast();

const form = useForm({
    bank_soal_id: '',
    keterampilan_id: '',
    nama: '',
    petunjuk: '',
    aktif: true,
});

const editForm = useForm({
    bank_soal_id: '',
    keterampilan_id: '',
    nama: '',
    petunjuk: '',
    aktif: true,
});

const showModal = ref(false);
const creating = ref(false);
const bagianSoalYangDiedit = ref(null);

const modalForm = computed(() => creating.value ? form : editForm);

const bankFilter = ref(props.bankSoalTerpilihId ? String(props.bankSoalTerpilihId) : '');
const jenisTesFilter = ref(props.jenisTesTerpilihId ? String(props.jenisTesTerpilihId) : '');
const keterampilanAktif = ref('');

watch(() => props.bankSoalTerpilihId, (bankId) => {
    bankFilter.value = bankId ? String(bankId) : '';
}, { immediate: true });

watch(() => props.jenisTesTerpilihId, (jenisTesId) => {
    jenisTesFilter.value = jenisTesId ? String(jenisTesId) : '';
}, { immediate: true });

watch(() => props.opsiKeterampilan, (opsiKeterampilan) => {
    const masihTersedia = opsiKeterampilan.some((keterampilan) => String(keterampilan.value) === String(keterampilanAktif.value));

    if (!masihTersedia) {
        keterampilanAktif.value = opsiKeterampilan[0]?.value ?? '';
    }
}, { immediate: true });

watch(bankFilter, () => {
    keterampilanAktif.value = props.opsiKeterampilan[0]?.value ?? '';
});

const bankSoalTerpilih = computed(() => props.bankSoal.find((bank) => String(bank.id) === String(bankFilter.value)) || null);
const jenisTesTerpilih = computed(() => props.jenisTes.find((jenisTes) => String(jenisTes.id) === String(jenisTesFilter.value)) || null);

const ringkasanBankSoal = computed(() => {
    const groups = new Map(props.bankSoal.map((bank) => [String(bank.id), {
        id: bank.id,
        nama: bank.nama,
        jenis_tes: bank.jenis_tes,
        aktif: !!bank.aktif,
        jumlahBagian: 0,
    }]));

    props.bagianSoal.forEach((bagian) => {
        const group = groups.get(String(bagian.bank_soal_id));
        if (!group) return;

        group.jumlahBagian += 1;
    });

    return Array.from(groups.values()).sort((left, right) => left.nama.localeCompare(right.nama, 'id'));
});

const ringkasanJenisTes = computed(() => {
    const groups = new Map(props.jenisTes.map((jenisTes) => [String(jenisTes.id), {
        id: jenisTes.id,
        nama: jenisTes.nama,
        jumlahBankSoal: 0,
        jumlahBagian: 0,
    }]));
    const jumlahBagianPerBank = new Map();

    props.bagianSoal.forEach((bagian) => {
        const bankId = String(bagian.bank_soal_id);
        jumlahBagianPerBank.set(bankId, (jumlahBagianPerBank.get(bankId) ?? 0) + 1);
    });

    props.bankSoal.forEach((bank) => {
        const group = groups.get(String(bank.jenis_tes_id));
        if (!group) return;

        group.jumlahBankSoal += 1;
        group.jumlahBagian += jumlahBagianPerBank.get(String(bank.id)) ?? 0;
    });

    return Array.from(groups.values()).sort((left, right) => left.nama.localeCompare(right.nama, 'id'));
});

function jenisTesUrl(jenisTesId) {
    return `${route('admin.data-induk.bagian-soal.daftar')}?jenis_tes_id=${encodeURIComponent(jenisTesId)}`;
}

function detailUrl(bankId) {
    const params = new URLSearchParams({ bank_id: String(bankId) });
    if (jenisTesFilter.value) params.set('jenis_tes_id', jenisTesFilter.value);

    return `${route('admin.data-induk.bagian-soal.daftar')}?${params.toString()}`;
}

const backUrl = computed(() => jenisTesFilter.value ? jenisTesUrl(jenisTesFilter.value) : route('admin.data-induk.bagian-soal.daftar'));

// ===== Group per keterampilan =====
const tabsKeterampilan = computed(() => props.opsiKeterampilan.map((keterampilan) => ({
    value: keterampilan.value,
    label: keterampilan.label,
})));

const kelompokKeterampilan = computed(() => props.opsiKeterampilan.map((keterampilan) => ({
    ...keterampilan,
    bagianSoal: props.bagianSoal
         .filter((p) => !bankFilter.value || String(p.bank_soal_id) === String(bankFilter.value))
        .filter((p) => String(p.keterampilan_id) === String(keterampilan.value))
        .sort((a, b) => (a.urutan ?? 0) - (b.urutan ?? 0)),
})).filter((group) => String(group.value) === String(keterampilanAktif.value)));

// ===== Drag & drop reorder (per grup keterampilan) =====
const reordering = ref(false);
const originalOrders = ref({});

watch(kelompokKeterampilan, (groups) => {
    originalOrders.value = Object.fromEntries(
        groups.map((g) => [g.value, g.bagianSoal.map((p) => p.id).join(',')]),
    );
}, { immediate: true });

function onReorder(keterampilanId, evt) {
    if (reordering.value) return;

    const prev = (originalOrders.value[keterampilanId] || '').split(',').filter(Boolean);
    if (prev.length === 0 || typeof evt.oldIndex !== 'number' || typeof evt.newIndex !== 'number') return;

    // Hitung urutan baru secara deterministik dari posisi drop (tidak bergantung timing mutasi list)
    const ids = [...prev];
    const [moved] = ids.splice(evt.oldIndex, 1);
    ids.splice(evt.newIndex, 0, moved);

    if (ids.join(',') === prev.join(',')) return;

    reordering.value = true;
     router.post(route('admin.data-induk.bagian-soal.urutkan'), { bagian_soal: ids }, {
        preserveScroll: true,
        onError: () => {
            toast.error('Gagal menyimpan urutan.');
            router.reload({ only: ['bagianSoal'] });
        },
        onFinish: () => { reordering.value = false; },
    });
}

// ===== Modal =====
function openCreate() {
    creating.value = true;
    bagianSoalYangDiedit.value = null;
    form.clearErrors();
    form.reset();
     form.bank_soal_id = bankFilter.value || '';
    form.keterampilan_id = keterampilanAktif.value;
    showModal.value = true;
}

function submit() {
    form.post(route('admin.data-induk.bagian-soal.simpan'), {
        preserveScroll: true,
        onSuccess: () => {
            closeModal();
            form.reset();
        },
    });
}

function startEdit(bagianSoal) {
    creating.value = false;
    bagianSoalYangDiedit.value = bagianSoal;
    editForm.clearErrors();
    editForm.reset();
     editForm.bank_soal_id = String(bagianSoal.bank_soal_id);
    editForm.keterampilan_id = keterampilanAktif.value;
     editForm.nama = bagianSoal.nama;
    editForm.petunjuk = bagianSoal.petunjuk || '';
    editForm.aktif = !!bagianSoal.aktif;
    showModal.value = true;
}

function saveEdit() {
    editForm.put(route('admin.data-induk.bagian-soal.perbarui', bagianSoalYangDiedit.value.id), {
        preserveScroll: true,
        onSuccess: closeModal,
    });
}

function closeModal() {
    showModal.value = false;
    bagianSoalYangDiedit.value = null;
    creating.value = false;
    editForm.reset();
}

async function destroy(bagianSoal) {
     if (!await confirm.confirm(`Hapus bagianSoal "${bagianSoal.nama}"?`)) return;
    router.delete(route('admin.data-induk.bagian-soal.hapus', bagianSoal.id), { preserveScroll: true });
}
</script>

<template>
    <Head :title="bankSoalTerpilih ? `Bagian Soal - ${bankSoalTerpilih.nama}` : jenisTesTerpilih ? `Bank Soal - ${jenisTesTerpilih.nama}` : 'Data Induk - Bagian Soal'" />
    <TataLetakDasbor
        :title="bankSoalTerpilih ? `Bagian Soal - ${bankSoalTerpilih.nama}` : jenisTesTerpilih ? `Bank Soal - ${jenisTesTerpilih.nama}` : 'Data Induk Bagian Soal'"
        :subtitle="bankSoalTerpilih ? 'Kelola bagian soal per keterampilan' : jenisTesTerpilih ? 'Pilih bank soal untuk melihat bagian soal' : 'Pilih jenis tes untuk melihat bank soal'"
    >
        <template v-if="!jenisTesFilter">
            <JenisTesSummaryTable
                :ringkasan-jenis-tes="ringkasanJenisTes"
                :detail-url="jenisTesUrl"
            />
        </template>

        <template v-else-if="!bankFilter">
            <BankSoalSummaryTable
                :ringkasan-bank-soal="ringkasanBankSoal"
                :detail-url="detailUrl"
                :back-url="backUrl"
            />
        </template>

        <template v-else>
            <div v-if="bankSoalTerpilih" class="relative">
                <BaseTabsChrome
                    v-if="tabsKeterampilan.length"
                    v-model="keterampilanAktif"
                    :items="tabsKeterampilan"
                    aria-label="Filter keterampilan bagian soal"
                    class="relative z-10 -mb-px px-5"
                />

                <BaseCard :padding="false" class="overflow-hidden">
                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-outline-variant/30 px-5 py-3.5">
                        <h2 class="text-title-lg font-semibold text-primary">Daftar Bagian Soal</h2>
                        <div class="flex items-center gap-2">
                            <BaseButton variant="secondary" size="sm" :href="backUrl">
                                <IconArrowLeft :size="16" /> Kembali
                            </BaseButton>
                            <BaseButton size="sm" @click="openCreate">
                                <IconPlus :size="16" /> Tambah Bagian Soal
                            </BaseButton>
                        </div>
                    </div>
                    <BagianSoalGroupTable
                        v-for="group in kelompokKeterampilan"
                        :key="group.value"
                        :group="group"
                        :reordering="reordering"
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

        <BagianSoalFormModal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            :bank-soal="bankSoal"
            :opsi-keterampilan="opsiKeterampilan"
            :bank-filter="bankFilter"
            lock-keterampilan
            @close="closeModal"
            @submit="creating ? submit() : saveEdit()"
        />
    </TataLetakDasbor>
</template>
