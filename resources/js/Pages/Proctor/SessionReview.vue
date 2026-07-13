<script setup>
import { Head, useForm, router } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import ViolationTimeline from '@/Components/Proctor/ViolationTimeline.vue';

const props = defineProps({
    session: { type: Object, required: true },
});

const reviewForm = useForm({
    review_status: '',
    review_note: '',
});

function submitReview() {
    reviewForm.post(route('proctor.session.review', props.session.id));
}

const terminateForm = useForm({
    reason: 'Dihentikan oleh proctor.',
});

function terminateSession() {
    if (!confirm('Yakin ingin menghentikan sesi ujian ini?')) return;
    terminateForm.post(route('proctor.session.terminate', props.session.id));
}

function refresh() {
    router.reload({ preserveScroll: true, only: ['session'] });
}

const statusColors = {
    pending: 'bg-amber-100 text-amber-700',
    in_progress: 'bg-blue-100 text-blue-700',
    submitted: 'bg-green-100 text-green-700',
    terminated: 'bg-error-red/10 text-error-red',
    reviewed: 'bg-purple-100 text-purple-700',
};

const reviewStatusColors = {
    sah: 'bg-green-100 text-green-700',
    ujian_ulang: 'bg-amber-100 text-amber-700',
    dibatalkan: 'bg-error-red/10 text-error-red',
};
</script>

<template>
    <Head title="Review Sesi" />
    <DashboardLayout title="Review Sesi">
        <div class="flex justify-end mb-4">
            <button @click="refresh"
                    class="bg-surface-white border border-outline-variant text-primary px-5 py-2 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all active:scale-95">
                Refresh → Segarkan
            </button>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                    <h2 class="text-title-lg font-semibold text-primary mb-4">Informasi Peserta</h2>
                    <div class="grid grid-cols-2 gap-4 text-body-md">
                        <div>
                            <p class="text-text-muted text-label-md">Nama</p>
                            <p class="text-primary font-medium">{{ session.user?.name }}</p>
                        </div>
                        <div>
                            <p class="text-text-muted text-label-md">NIM</p>
                            <p class="text-primary">{{ session.user?.nim || '-' }}</p>
                        </div>
                        <div>
                            <p class="text-text-muted text-label-md">Email</p>
                            <p class="text-primary">{{ session.user?.email }}</p>
                        </div>
                        <div>
                            <p class="text-text-muted text-label-md">Ujian</p>
                            <p class="text-primary">{{ session.schedule?.exam?.title }}</p>
                        </div>
                        <div>
                            <p class="text-text-muted text-label-md">Status</p>
                            <span class="inline-block px-3 py-1 rounded-full text-label-md font-medium"
                                  :class="statusColors[session.status] || 'bg-gray-100 text-gray-700'">
                                {{ session.status?.replace('_', ' ') }}
                            </span>
                        </div>
                        <div>
                            <p class="text-text-muted text-label-md">Pelanggaran</p>
                            <p class="text-title-lg font-bold" :class="session.violation_strikes >= 3 ? 'text-error-red' : 'text-amber-600'">
                                {{ session.violation_strikes }}/3
                            </p>
                        </div>
                        <div>
                            <p class="text-text-muted text-label-md">Mulai</p>
                            <p class="text-primary">{{ session.started_at ? new Date(session.started_at).toLocaleString('id-ID') : '-' }}</p>
                        </div>
                        <div>
                            <p class="text-text-muted text-label-md">Selesai</p>
                            <p class="text-primary">{{ session.submitted_at ? new Date(session.submitted_at).toLocaleString('id-ID') : '-' }}</p>
                        </div>
                    </div>
                </div>

                <div v-if="session.score_total !== null" class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                    <h2 class="text-title-lg font-semibold text-primary mb-4">Skor</h2>
                    <div class="grid grid-cols-4 gap-4 text-center">
                        <div class="bg-surface-container-low rounded-2xl p-4">
                            <p class="text-text-muted text-label-md">Membaca</p>
                            <p class="text-headline-md font-bold text-primary">{{ session.score_reading ?? '-' }}</p>
                        </div>
                        <div class="bg-surface-container-low rounded-2xl p-4">
                            <p class="text-text-muted text-label-md">Mendengar</p>
                            <p class="text-headline-md font-bold text-primary">{{ session.score_listening ?? '-' }}</p>
                        </div>
                        <div class="bg-surface-container-low rounded-2xl p-4">
                            <p class="text-text-muted text-label-md">Berbicara</p>
                            <p class="text-headline-md font-bold text-primary">{{ session.score_speaking ?? '-' }}</p>
                        </div>
                        <div class="bg-surface-container-low rounded-2xl p-4">
                            <p class="text-text-muted text-label-md">Menulis</p>
                            <p class="text-headline-md font-bold text-primary">{{ session.score_writing ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="mt-4 text-center">
                        <p class="text-text-muted text-label-md">Total</p>
                        <p class="text-headline-lg font-bold text-primary">{{ session.score_total }}/120</p>
                    </div>
                </div>

                <ViolationTimeline :violations="session.violation_logs" />

                <div v-if="session.status === 'in_progress' || session.status === 'terminated'"
                     class="bg-white rounded-2xl p-6 shadow-soft border border-error-red/30">
                    <h2 class="text-title-lg font-semibold text-error-red mb-4">Hentikan Sesi</h2>
                    <form @submit.prevent="terminateSession" class="space-y-4">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Alasan</label>
                            <textarea v-model="terminateForm.reason" rows="2" required
                                      class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-error-red"
                                      placeholder="Alasan penghentian..."></textarea>
                        </div>
                        <button type="submit" :disabled="terminateForm.processing"
                                class="w-full bg-error-red text-white py-3.5 rounded-full text-label-md font-medium hover:bg-red-700 transition-all active:scale-95 disabled:opacity-50">
                            {{ terminateForm.processing ? 'Memproses...' : 'Hentikan Sesi' }}
                        </button>
                    </form>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 h-fit sticky top-24">
                <h2 class="text-title-lg font-semibold text-primary mb-4">Keputusan Review</h2>
                <form @submit.prevent="submitReview" class="space-y-4">
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Status</label>
                        <select v-model="reviewForm.review_status" required
                                class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary">
                            <option value="" disabled>Pilih keputusan</option>
                            <option value="sah">✅ Sah</option>
                            <option value="ujian_ulang">🔄 Ujian Ulang</option>
                            <option value="dibatalkan">❌ Dibatalkan</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Catatan</label>
                        <textarea v-model="reviewForm.review_note" rows="5"
                                  class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"
                                  placeholder="Alasan dan detail keputusan..."></textarea>
                    </div>
                    <button type="submit" :disabled="reviewForm.processing"
                            class="w-full bg-primary-container text-white py-3.5 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                        {{ reviewForm.processing ? 'Menyimpan...' : 'Simpan Keputusan' }}
                    </button>
                </form>

                <div v-if="session.review_status" class="mt-6 pt-6 border-t border-outline-variant/30">
                    <h3 class="text-label-md font-medium text-primary mb-2">Hasil Review</h3>
                    <span class="inline-block px-3 py-1 rounded-full text-label-md font-medium"
                          :class="reviewStatusColors[session.review_status] || 'bg-gray-100 text-gray-700'">
                        {{ session.review_status }}
                    </span>
                    <p v-if="session.review_note" class="text-text-body text-body-md mt-2">{{ session.review_note }}</p>
                    <p v-if="session.reviewed_by" class="text-text-muted text-label-md mt-2">
                        Oleh: {{ session.reviewer?.name }} — {{ session.reviewed_at ? new Date(session.reviewed_at).toLocaleString('id-ID') : '' }}
                    </p>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
