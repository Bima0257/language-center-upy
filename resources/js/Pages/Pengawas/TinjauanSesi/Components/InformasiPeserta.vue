<script setup>
const props = defineProps({
    sesiUjian: { type: Object, required: true },
});

const statusColors = {
    pending: 'bg-warning-container text-on-warning-container',
    in_progress: 'bg-info-container text-on-info-container',
    submitted: 'bg-success-container text-on-success-container',
    terminated: 'bg-error-container text-on-error-container',
};

function formatDateTime(value) {
    return value ? new Date(value).toLocaleString('id-ID') : '-';
}
</script>

<template>
    <div class="bg-surface-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
        <h2 class="text-title-lg font-semibold text-text-heading mb-4">Informasi Peserta</h2>
        <div class="grid grid-cols-2 gap-4 text-text-body text-body-md">
            <div>
                <p class="text-text-muted text-label-md">Nama</p>
                <p class="text-text-heading font-medium">{{ props.sesiUjian.pengguna?.name }}</p>
            </div>
            <div>
                <p class="text-text-muted text-label-md">NIM</p>
                <p class="text-text-heading">{{ props.sesiUjian.pengguna?.nim || '-' }}</p>
            </div>
            <div>
                <p class="text-text-muted text-label-md">Email</p>
                <p class="text-text-heading">{{ props.sesiUjian.pengguna?.email }}</p>
            </div>
            <div>
                <p class="text-text-muted text-label-md">Ujian</p>
                <p class="text-text-heading">{{ props.sesiUjian.slot_ujian?.periode_ujian?.ujian?.judul }}</p>
            </div>
            <div>
                <p class="text-text-muted text-label-md">Status</p>
                <span class="inline-block px-3 py-1 rounded-full text-label-md font-medium"
                      :class="statusColors[props.sesiUjian.status] || 'bg-neutral-container text-on-neutral-container'">
                    {{ props.sesiUjian.status?.replace('_', ' ') }}
                </span>
            </div>
            <div>
                <p class="text-text-muted text-label-md">Pelanggaran</p>
                <p class="text-title-lg font-bold" :class="props.sesiUjian.jumlah_pelanggaran >= 3 ? 'text-error-red' : 'text-warning'">
                    {{ props.sesiUjian.jumlah_pelanggaran }}/3
                </p>
            </div>
            <div>
                <p class="text-text-muted text-label-md">Mulai</p>
                <p class="text-text-heading">{{ formatDateTime(props.sesiUjian.dimulai_pada) }}</p>
            </div>
            <div>
                <p class="text-text-muted text-label-md">Selesai</p>
                <p class="text-text-heading">{{ formatDateTime(props.sesiUjian.diserahkan_pada) }}</p>
            </div>
        </div>

        <!-- FOTO SELFIE + INFO PERANGKAT -->
        <div v-if="props.sesiUjian.lokasi_swafoto || props.sesiUjian.jenis_perangkat" class="mt-4 pt-4 border-t border-outline-variant/30">
            <div class="grid grid-cols-2 gap-4">
                <div v-if="props.sesiUjian.jenis_perangkat">
                    <p class="text-text-muted text-label-md">Perangkat</p>
                    <p class="text-text-heading font-medium capitalize">{{ props.sesiUjian.jenis_perangkat }}</p>
                </div>
                <div v-if="props.sesiUjian.swafoto_diambil_pada">
                    <p class="text-text-muted text-label-md">Foto Diambil</p>
                    <p class="text-text-heading">{{ formatDateTime(props.sesiUjian.swafoto_diambil_pada) }}</p>
                </div>
            </div>
            <div v-if="props.sesiUjian.lokasi_swafoto" class="mt-3">
                <p class="text-text-muted text-label-md mb-2">Foto Selfie</p>
                <img :src="'/media/' + props.sesiUjian.lokasi_swafoto"
                     class="w-32 h-32 object-cover rounded-2xl border border-outline-variant/30"
                     alt="Selfie peserta ujian" />
            </div>
        </div>
    </div>
</template>
