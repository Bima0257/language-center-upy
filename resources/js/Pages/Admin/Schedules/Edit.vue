<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';

const props = defineProps({
    exam: { type: Object, required: true },
    schedule: { type: Object, required: true },
});

function toDatetimeLocal(value) {
    if (!value) return '';
    const d = new Date(value);
    const pad = (n) => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

const form = useForm({
    title: props.schedule.title,
    scheduled_start: toDatetimeLocal(props.schedule.scheduled_start),
    scheduled_end: toDatetimeLocal(props.schedule.scheduled_end),
    late_tolerance_minutes: props.schedule.late_tolerance_minutes,
    max_participants: props.schedule.max_participants,
    is_active: props.schedule.is_active,
});

function submit() {
    form.put(route('admin.schedules.update', [props.exam.id, props.schedule.id]));
}
</script>

<template>
    <Head title="Edit Jadwal" />
    <DashboardLayout title="Edit Jadwal">
        <div class="max-w-2xl mx-auto">
            <div class="bg-surface-white rounded-2xl p-8 shadow-soft border border-outline-variant/30">
                <p class="text-text-body text-body-md mb-6">Ujian: <strong>{{ exam.title }}</strong></p>
                <form @submit.prevent="submit" class="space-y-5">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Nama Sesi</label>
                        <input type="text" v-model="form.title" required
                               placeholder="Sesi 1 — 10 Juli 2026"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        <p v-if="form.errors.title" class="text-error-red text-xs mt-1">{{ form.errors.title }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Mulai</label>
                            <input type="datetime-local" v-model="form.scheduled_start" required
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                            <p v-if="form.errors.scheduled_start" class="text-error-red text-xs mt-1">{{ form.errors.scheduled_start }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Selesai</label>
                            <input type="datetime-local" v-model="form.scheduled_end" required
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                            <p v-if="form.errors.scheduled_end" class="text-error-red text-xs mt-1">{{ form.errors.scheduled_end }}</p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Toleransi (menit)</label>
                            <input type="number" v-model="form.late_tolerance_minutes" min="0"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Kuota Peserta</label>
                            <input type="number" v-model="form.max_participants" min="1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="is_active" v-model="form.is_active"
                               class="rounded border-outline-variant text-primary focus:ring-secondary" />
                        <label for="is_active" class="text-text-body text-body-md">Aktif</label>
                    </div>
                    <button type="submit" :disabled="form.processing"
                            class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                        {{ form.processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                    </button>
                </form>
            </div>
        </div>
    </DashboardLayout>
</template>
