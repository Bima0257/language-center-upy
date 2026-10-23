<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import PrecheckHeader from '@/Pages/Ujian/PemeriksaanAwal/components/PrecheckHeader.vue';
import DeviceCheckSection from '@/Pages/Ujian/PemeriksaanAwal/components/DeviceCheckSection.vue';
import CameraCheckSection from '@/Pages/Ujian/PemeriksaanAwal/components/CameraCheckSection.vue';
import SelfieCaptureSection from '@/Pages/Ujian/PemeriksaanAwal/components/SelfieCaptureSection.vue';
import ConnectionCheckSection from '@/Pages/Ujian/PemeriksaanAwal/components/ConnectionCheckSection.vue';
import { ref, computed, onUnmounted, nextTick } from 'vue';

const props = defineProps({
    slotUjian: { type: Object, required: true },
});

const selfieSectionRef = ref(null);
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
    const start = (props.slotUjian.waktu_mulai || '00:00').substring(0, 5);
    const [h, m] = start.split(':').map(Number);
    const tolerance = props.slotUjian.toleransi_keterlambatan_menit ?? 15;
    const totalMin = h * 60 + m + tolerance;
    const dh = Math.floor(totalMin / 60) % 24;
    const dm = totalMin % 60;
    return String(dh).padStart(2, '0') + ':' + String(dm).padStart(2, '0');
});

const form = useForm({
    jenis_perangkat: '',
    agen_pengguna_perangkat: '',
    swafoto: null,
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
                selfieSectionRef.value?.attachStream(stream);
            });
        })
        .catch(() => {
            cameraOk.value = false;
            cameraChecking.value = false;
            cameraError.value = 'Kamera tidak terdeteksi. Izinkan akses kamera di browser, lalu coba lagi.';
        });
}

async function capturePhoto() {
    const blob = await selfieSectionRef.value?.captureFrame();
    if (!blob) return;

    photoBlob.value = blob;
    photoPreview.value = URL.createObjectURL(blob);
    form.swafoto = new File([blob], 'swafoto.jpg', { type: 'image/jpeg' });
}

function retakePhoto() {
    if (photoPreview.value) {
        URL.revokeObjectURL(photoPreview.value);
    }
    photoPreview.value = null;
    photoBlob.value = null;
    form.swafoto = null;
}

function stopTracks() {
    if (cameraStream.value) {
        cameraStream.value.getTracks().forEach(t => t.stop());
        cameraStream.value = null;
    }
}

function startExam() {
    form.jenis_perangkat = deviceType.value;
    form.agen_pengguna_perangkat = navigator.userAgent || '';
    form.post(route('ujian.mulai', props.slotUjian.id));
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
    <TataLetakDasbor title="Persiapan Ujian" subtitle="Periksa perangkat dan identitas sebelum mulai">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8" class="space-y-6">
                <PrecheckHeader :slot-ujian="slotUjian" :late-deadline="lateDeadline" />

                <div class="space-y-4">
                    <DeviceCheckSection :device-type="deviceType" :device-ok="deviceOk" />
                    <CameraCheckSection
                        :camera-ok="cameraOk"
                        :camera-checking="cameraChecking"
                        :camera-error="cameraError"
                        :photo-preview="photoPreview"
                        @check="checkCamera"
                    />
                    <SelfieCaptureSection
                        ref="selfieSectionRef"
                        :camera-ok="cameraOk"
                        :photo-preview="photoPreview"
                        @capture="capturePhoto"
                        @retake="retakePhoto"
                    />
                    <ConnectionCheckSection :connection-ok="connectionOk" />
                </div>

                <BaseButton @click="startExam" :disabled="!allChecked || form.processing" size="xl" class="w-full">
                    {{ form.processing ? 'Memulai...' : 'Mulai Ujian' }}
                </BaseButton>
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
