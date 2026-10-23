<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import PeriodeUjianInfo from '@/Pages/Admin/PeriodeUjian/components/PeriodeUjianInfo.vue';
import DaftarSlotUjian from '@/Pages/Admin/PeriodeUjian/components/DaftarSlotUjian.vue';
import FormSlotUjianModal from '@/Pages/Admin/PeriodeUjian/components/FormSlotUjianModal.vue';

const confirm = useConfirm();

const props = defineProps({
    periodeUjian: { type: Object, required: true },
    slotUjian: { type: Array, default: () => [] },
});

const showModal = ref(false);
const creating = ref(false);
const editingSlot = ref(null);

const form = useForm({
    tanggal: '',
    waktu_mulai: '08:00',
    waktu_selesai: '10:00',
    toleransi_keterlambatan_menit: 15,
    kapasitas_peserta: 30,
});

const editForm = useForm({
    tanggal: '',
    waktu_mulai: '',
    waktu_selesai: '',
    toleransi_keterlambatan_menit: 15,
    kapasitas_peserta: 30,
    aktif: true,
});

const modalForm = computed(() => (creating.value ? form : editForm));

// Native input[type=date] butuh format yyyy-mm-dd — Inertia men-serialize Carbon sebagai ISO datetime penuh
const slotDateMin = computed(() => props.periodeUjian.tanggal_mulai?.substring(0, 10) || '');
const slotDateMax = computed(() => props.periodeUjian.tanggal_selesai?.substring(0, 10) || '');

function openCreate() {
    creating.value = true;
    editingSlot.value = null;
    form.clearErrors();
    form.reset();
    form.tanggal = slotDateMin.value;
    showModal.value = true;
}

function openEdit(slotUjian) {
    creating.value = false;
    editingSlot.value = slotUjian;
    editForm.clearErrors();
    editForm.reset();
    editForm.tanggal = slotUjian.tanggal?.substring(0, 10) || '';
    editForm.waktu_mulai = slotUjian.waktu_mulai?.substring(0, 5);
    editForm.waktu_selesai = slotUjian.waktu_selesai?.substring(0, 5);
    editForm.toleransi_keterlambatan_menit = slotUjian.toleransi_keterlambatan_menit ?? 15;
    editForm.kapasitas_peserta = slotUjian.kapasitas_peserta ?? 30;
    editForm.aktif = !!slotUjian.aktif;
    showModal.value = true;
}

function submit() {
    const target = creating.value ? form : editForm;
    const onSuccess = () => {
        showModal.value = false;
        target.reset();
    };

    if (creating.value) {
        target.post(route('admin.periode-ujian.slot.simpan', props.periodeUjian.id), { preserveScroll: true, onSuccess });
    } else {
        target.put(route('admin.periode-ujian.slot.perbarui', editingSlot.value.id), { preserveScroll: true, onSuccess });
    }
}

function closeModal() {
    showModal.value = false;
    editingSlot.value = null;
    creating.value = false;
    form.reset();
    editForm.reset();
}

async function destroy(slotUjian) {
    if (slotUjian.sesi_ujian_count > 0) return
    if (!await confirm.confirm(`Hapus sesi ${slotUjian.tanggal} ${slotUjian.waktu_mulai?.substring(0, 5)}-${slotUjian.waktu_selesai?.substring(0, 5)}?`)) return
    router.delete(route('admin.periode-ujian.slot.hapus', slotUjian.id), { preserveScroll: true })
}

const slotsByDate = computed(() => {
    const map = new Map();
    for (const slotUjian of props.slotUjian) {
        const key = slotUjian.tanggal;
        if (!map.has(key)) map.set(key, []);
        map.get(key).push(slotUjian);
    }
    return Array.from(map.entries()).map(([tanggal, items]) => ({ tanggal, items }));
});

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-'
}
</script>

<template>
    <Head :title="periodeUjian.judul" />
    <TataLetakDasbor :title="periodeUjian.judul" :subtitle="formatDate(periodeUjian.tanggal_mulai) + ' – ' + formatDate(periodeUjian.tanggal_selesai)">
        <div class="space-y-6">
            <PeriodeUjianInfo :periode-ujian="periodeUjian" :format-date="formatDate" />

            <DaftarSlotUjian
                :slot-groups="slotsByDate"
                :format-date="formatDate"
                @create="openCreate"
                @edit="openEdit"
                @delete="destroy"
            />
        </div>

        <FormSlotUjianModal
            :show="showModal"
            :creating="creating"
            :form="modalForm"
            :periode-ujian="periodeUjian"
            :slot-date-min="slotDateMin"
            :slot-date-max="slotDateMax"
            :format-date="formatDate"
            @close="closeModal"
            @submit="submit"
        />
    </TataLetakDasbor>
</template>
