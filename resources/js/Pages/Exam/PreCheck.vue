<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconCamera, IconWifi, IconCheck, IconX, IconDeviceDesktop, IconRefresh } from '@tabler/icons-vue';
import { ref, computed, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    slot: { type: Object, required: true },
});

const videoRef = ref(null);
const canvasRef = ref(null);
const photoPreview = ref(null);
const photoBlob = ref(null);
const cameraStream = ref(null);
const cameraChecking = ref(false);
const cameraError = ref('');

const deviceType = ref(null);
const deviceOk = ref(false);
const cameraOk = ref(false);
const connectionOk = ref(true);

const allChecked = computed(() => deviceOk.value && cameraOk.value && photoBlob.value !== null && connectionOk.value);

const lateDeadline = computed(() => {
    const start = (props.slot.start_time || '00:00').substring(0, 5);
    const [h, m] = start.split(':').map(Number);
    const tolerance = props.slot.late_tolerance_minutes ?? 15;
    const totalMin = h * 60 + m + tolerance;
    const dh = Math.floor(totalMin / 60) % 24;
    const dm = totalMin % 60;
    return String(dh).padStart(2, '0') + ':' + String(dm).padStart(2, '0');
});

const form = useForm({
    device_type: '',
    device_user_agent: '',
    selfie: null,
});

function detectDevice() {
    const ua = navigator.userAgent || '';

    if (/(Android(?!.*Mobile)|Tablet|PlayBook|Silk|Kindle)/i.test(ua)) {
        deviceType.value = 'tablet';
        deviceOk.value = false;
        return;
    }
    if (/(Android|iPhone|iPod|Mobile|Opera Mini|Opera Mobi|IEMobile)/i.test(ua)) {
        deviceType.value = 'mobile';
        deviceOk.value = false;
        return;
    }

    deviceType.value = 'desktop';
    deviceOk.value = true;
}

function checkCamera() {
    cameraChecking.value = true;
    cameraError.value = '';
    navigator.mediaDevices?.getUserMedia({ video: true })
        .then((stream) => {
            cameraStream.value = stream;
            cameraOk.value = true;
            cameraChecking.value = false;
            nextTick(() => {
                if (videoRef.value) {
                    videoRef.value.srcObject = stream;
                    videoRef.value.play().catch(() => {});
                }
            });
        })
        .catch(() => {
            cameraOk.value = false;
            cameraChecking.value = false;
            cameraError.value = 'Kamera tidak terdeteksi. Izinkan akses kamera di browser, lalu coba lagi.';
        });
}

function capturePhoto() {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    if (!video || !canvas) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    canvas.getContext('2d').drawImage(video, 0, 0);

    canvas.toBlob((blob) => {
        if (blob) {
            photoBlob.value = blob;
            photoPreview.value = URL.createObjectURL(blob);
            form.selfie = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
        }
    }, 'image/jpeg', 0.9);
}

function retakePhoto() {
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }
    photoPreview.value = null;
    photoBlob.value = null;
    form.selfie = null;
}

function stopTracks() {
    if (cameraStream.value) {
        cameraStream.value.getTracks().forEach(t => t.stop());
        cameraStream.value = null;
    }
}

function startExam() {
    form.device_type = deviceType.value;
    form.device_user_agent = navigator.userAgent || '';
    form.post(route('exam.start', props.slot.id));
}

onUnmounted(() => {
    stopTracks();
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }
});

detectDevice();
</script>

<template>
    <Head title="Persiapan Ujian" />
    <DashboardLayout title="Persiapan Ujian">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8" class="space-y-6">
                <div>
                    <h2 class="text-headline-md font-bold text-primary mb-2">{{ slot.schedule?.exam?.title }}</h2>
                    <p class="text-text-body text-body-md">{{ slot.schedule?.title }} — Sesi {{ slot.start_time?.substring(0, 5) }}–{{ slot.end_time?.substring(0, 5) }} WIB</p>
                    <p class="text-amber-600 dark:text-amber-400 text-xs mt-1">
                        Mulai ujian harus dilakukan sebelum pukul {{ lateDeadline }} WIB (toleransi {{ slot.late_tolerance_minutes ?? 15 }} menit).
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- PERANGKAT -->
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
                        <div class="flex items-center gap-3">
                            <IconDeviceDesktop class="text-text-muted" :size="22" stroke="1.5" />
                            <div>
                                <p class="text-label-md font-medium text-primary">Perangkat</p>
                                <p class="text-text-muted text-xs">
                                    <template v-if="deviceType === 'desktop'">Laptop / Komputer — Terdeteksi</template>
                                    <template v-else-if="deviceType === 'mobile'">HP — Tidak diperbolehkan</template>
                                    <template v-else-if="deviceType === 'tablet'">Tablet — Tidak diperbolehkan</template>
                                    <template v-else>Memeriksa...</template>
                                </p>
                            </div>
                        </div>
                        <IconCheck v-if="deviceOk" class="text-green-500 dark:text-green-400" :size="22" />
                        <IconX v-else-if="deviceType" class="text-error-red" :size="22" />
                    </div>
                    <p v-if="deviceType && !deviceOk" class="text-error-red text-xs -mt-2 px-4">
                        Ujian hanya dapat diakses dari laptop atau komputer.
                    </p>

                    <!-- KAMERA -->
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
                        <div class="flex items-center gap-3">
                            <IconCamera class="text-text-muted" :size="22" stroke="1.5" />
                            <div>
                                <p class="text-label-md font-medium text-primary">Kamera</p>
                                <p class="text-text-muted text-xs">
                                    <template v-if="cameraOk && !photoPreview">Terdeteksi — Siap ambil foto</template>
                                    <template v-else-if="cameraOk && photoPreview">Foto berhasil diambil</template>
                                    <template v-else>Belum dicek</template>
                                </p>
                            </div>
                        </div>
                        <BaseButton v-if="!cameraOk" size="sm" :disabled="cameraChecking" @click="checkCamera">
                            {{ cameraChecking ? 'Mengecek...' : 'Cek Kamera' }}
                        </BaseButton>
                        <IconCheck v-else class="text-green-500 dark:text-green-400" :size="22" />
                    </div>
                    <p v-if="!cameraOk && !cameraChecking" class="text-text-muted text-xs -mt-2 px-4">
                        Klik "Cek Kamera" lalu izinkan akses kamera pada browser Anda.
                    </p>
                    <p v-if="cameraError" class="text-error-red text-xs -mt-2 px-4">
                        {{ cameraError }}
                    </p>

                    <!-- VIDEO PREVIEW + SELFIE -->
                    <div v-if="cameraOk" class="space-y-3">
                        <p v-if="!photoPreview" class="text-text-muted text-xs px-1">
                            Pastikan wajah Anda terlihat jelas dan menghadap ke kamera, lalu klik "Ambil Foto" untuk selfie live.
                        </p>
                        <div class="bg-surface-container-low rounded-2xl overflow-hidden">
                            <video v-show="!photoPreview" ref="videoRef" autoplay playsinline muted class="w-full rounded-2xl" />
                            <img v-if="photoPreview" :src="photoPreview" class="w-full rounded-2xl" />
                        </div>
                        <div class="flex gap-3">
                            <BaseButton v-if="!photoPreview" size="md" class="flex-1" @click="capturePhoto">
                                <IconCamera :size="18" /> Ambil Foto
                            </BaseButton>
                            <BaseButton v-if="photoPreview" variant="secondary" size="md" class="flex-1" @click="retakePhoto">
                                <IconRefresh :size="18" /> Ambil Ulang
                            </BaseButton>
                        </div>
                        <p v-if="photoPreview" class="text-text-muted text-xs text-center">
                            Foto live berhasil diambil — akan dilampirkan ke sesi ujian sebagai verifikasi identitas.
                        </p>
                    </div>

                    <canvas ref="canvasRef" class="hidden" />

                    <!-- KONEKSI -->
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
                        <div class="flex items-center gap-3">
                            <IconWifi class="text-text-muted" :size="22" stroke="1.5" />
                            <div>
                                <p class="text-label-md font-medium text-primary">Koneksi Internet</p>
                                <p class="text-text-muted text-xs">{{ connectionOk ? 'Stabil' : 'Bermasalah' }}</p>
                            </div>
                        </div>
                        <IconCheck v-if="connectionOk" class="text-green-500 dark:text-green-400" :size="22" />
                        <IconX v-else class="text-error-red" :size="22" />
                    </div>
                </div>

                <BaseButton @click="startExam" :disabled="!allChecked || form.processing" size="xl" class="w-full">
                    {{ form.processing ? 'Memulai...' : 'Mulai Ujian' }}
                </BaseButton>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
