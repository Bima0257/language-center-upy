<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconArrowLeft, IconPlus, IconTrash, IconEdit, IconX, IconClock } from '@tabler/icons-vue';
import { computed, ref } from 'vue';
import { useConfirm } from '@/Composables/useConfirm';
import { useToast } from '@/Composables/useToast';

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    schedule: { type: Object, required: true },
    slots: { type: Array, default: () => [] },
});

const showModal = ref(false);
const creating = ref(false);
const editingSlot = ref(null);

const form = useForm({
    date: '',
    start_time: '08:00',
    end_time: '10:00',
    late_tolerance_minutes: 15,
    max_participants: 30,
});

const editForm = useForm({
    date: '',
    start_time: '',
    end_time: '',
    late_tolerance_minutes: 15,
    max_participants: 30,
    is_active: true,
});

const modalForm = computed(() => (creating.value ? form : editForm));

function openCreate() {
    creating.value = true;
    editingSlot.value = null;
    form.clearErrors();
    form.reset();
    form.date = props.schedule.start_date;
    showModal.value = true;
}

function openEdit(slot) {
    creating.value = false;
    editingSlot.value = slot;
    editForm.clearErrors();
    editForm.reset();
    editForm.date = slot.date;
    editForm.start_time = slot.start_time?.substring(0, 5);
    editForm.end_time = slot.end_time?.substring(0, 5);
    editForm.late_tolerance_minutes = slot.late_tolerance_minutes ?? 15;
    editForm.max_participants = slot.max_participants ?? 30;
    editForm.is_active = !!slot.is_active;
    showModal.value = true;
}

function submit() {
    const target = creating.value ? form : editForm;
    const onSuccess = () => {
        showModal.value = false;
        toast.success(creating.value ? 'Sesi berhasil ditambahkan.' : 'Sesi diperbarui.');
        target.reset();
    };

    if (creating.value) {
        target.post(route('admin.schedules.slots.store', props.schedule.id), { preserveScroll: true, onSuccess });
    } else {
        target.put(route('admin.schedules.slots.update', editingSlot.value.id), { preserveScroll: true, onSuccess });
    }
}

function closeModal() {
    showModal.value = false;
    editingSlot.value = null;
    creating.value = false;
    form.reset();
    editForm.reset();
}

async function destroy(slot) {
    if (slot.sessions_count > 0) return
    if (!await confirm.confirm(`Hapus sesi ${slot.date} ${slot.start_time?.substring(0, 5)}-${slot.end_time?.substring(0, 5)}?`)) return
    router.delete(route('admin.schedules.slots.destroy', slot.id), { preserveScroll: true })
}

const slotsByDate = computed(() => {
    const map = new Map();
    for (const slot of props.slots) {
        const key = slot.date;
        if (!map.has(key)) map.set(key, []);
        map.get(key).push(slot);
    }
    return Array.from(map.entries()).map(([date, items]) => ({ date, items }));
});

function formatDate(value) {
    return value ? new Date(value).toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) : '-'
}
</script>

<template>
    <Head :title="schedule.title" />
    <DashboardLayout :title="schedule.title">
        <div class="space-y-6">
            <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-text-body text-body-md">{{ schedule.exam?.title }}</p>
                        <p class="text-text-muted text-label-md">
                            Periode: {{ formatDate(schedule.start_date) }} s/d {{ formatDate(schedule.end_date) }}
                        </p>
                    </div>
                    <Link :href="route('admin.schedules.all')"
                          class="flex items-center gap-2 text-secondary text-label-md font-medium hover:underline">
                        <IconArrowLeft :size="16" /> Penjadwalan
                    </Link>
                </div>
            </div>

            <BaseCard>
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-title-lg font-semibold text-primary">Sesi Ujian per Hari</h2>
                    <BaseButton size="sm" @click="openCreate">
                        <IconPlus :size="16" /> Tambah Sesi
                    </BaseButton>
                </div>

                <p v-if="slots.length === 0" class="text-text-muted text-body-md text-center py-10">
                    Belum ada sesi. Tambahkan sesi per hari dalam periode ini.
                </p>

                <div v-else class="space-y-5">
                    <div v-for="group in slotsByDate" :key="group.date" class="border border-outline-variant/50 rounded-2xl overflow-hidden">
                        <div class="px-4 py-3 bg-surface-container-low flex items-center gap-2">
                            <IconClock :size="16" class="text-secondary" />
                            <p class="text-label-md font-semibold text-primary">{{ formatDate(group.date) }}</p>
                        </div>
                        <div class="divide-y divide-outline-variant/30">
                            <div v-for="slot in group.items" :key="slot.id"
                                 class="px-4 py-3 flex items-center justify-between gap-3 hover:bg-surface-container-low/50 transition-colors">
                                <div>
                                    <p class="text-body-md font-semibold text-primary">
                                        {{ slot.start_time?.substring(0, 5) }} – {{ slot.end_time?.substring(0, 5) }} WIB
                                    </p>
                                    <p class="text-label-md text-text-muted">
                                        {{ slot.sessions_count || 0 }}/{{ slot.max_participants }} peserta —
                                        toleransi {{ slot.late_tolerance_minutes ?? 15 }} menit —
                                        <span :class="slot.is_active ? 'text-green-600' : 'text-error-red'">
                                            {{ slot.is_active ? 'Aktif' : 'Nonaktif' }}
                                        </span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 shrink-0">
                                    <button @click="openEdit(slot)" class="p-2 text-text-muted hover:text-secondary transition-colors">
                                        <IconEdit :size="18" />
                                    </button>
                                    <button @click="destroy(slot)"
                                            :disabled="slot.sessions_count > 0"
                                            :title="slot.sessions_count > 0 ? 'Tidak bisa dihapus — sudah diikuti peserta' : 'Hapus sesi'"
                                            class="p-2 transition-colors"
                                            :class="slot.sessions_count > 0
                                                ? 'text-outline-variant cursor-not-allowed'
                                                : 'text-text-muted hover:text-error-red'">
                                        <IconTrash :size="18" />
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </div>

        <!-- Modal Tambah / Edit Sesi -->
        <BaseModal :show="showModal" @close="closeModal" max-width="2xl">
            <div class="p-8">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">{{ creating ? 'Tambah Sesi' : 'Edit Sesi' }}</h2>
                    <button @click="closeModal" class="p-2 text-text-muted hover:text-primary transition-colors">
                        <IconX :size="20" />
                    </button>
                </div>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Tanggal Sesi</label>
                        <input type="date" v-model="modalForm.date" required
                               :min="schedule.start_date" :max="schedule.end_date"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="modalForm.errors.date" class="text-error-red text-xs mt-1">{{ modalForm.errors.date }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Jam Mulai</label>
                            <input type="time" v-model="modalForm.start_time" required
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                            <p v-if="modalForm.errors.start_time" class="text-error-red text-xs mt-1">{{ modalForm.errors.start_time }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Jam Selesai</label>
                            <input type="time" v-model="modalForm.end_time" required
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                            <p v-if="modalForm.errors.end_time" class="text-error-red text-xs mt-1">{{ modalForm.errors.end_time }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Toleransi (menit)</label>
                            <input type="number" v-model="modalForm.late_tolerance_minutes" min="0"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Kuota Peserta</label>
                            <input type="number" v-model="modalForm.max_participants" min="1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                    </div>
                    <label v-if="!creating" class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" v-model="modalForm.is_active" class="w-4 h-4 rounded border-outline-variant text-primary-container focus:ring-secondary" />
                        <span class="text-label-md text-text-body">Aktif</span>
                    </label>
                    <div class="flex gap-4 pt-2">
                        <BaseButton type="submit" :disabled="modalForm.processing" size="xl" class="flex-1">
                            {{ modalForm.processing ? 'Menyimpan...' : (creating ? 'Tambah Sesi' : 'Simpan Perubahan') }}
                        </BaseButton>
                        <BaseButton type="button" variant="secondary" size="lg" @click="closeModal">
                            Batal
                        </BaseButton>
                    </div>
                </form>
            </div>
        </BaseModal>
    </DashboardLayout>
</template>
