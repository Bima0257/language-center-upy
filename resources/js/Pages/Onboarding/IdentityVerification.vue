<script setup>
import { ref, onUnmounted, nextTick } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { IconCheck, IconRefresh, IconCamera } from '@tabler/icons-vue';
import OnboardingLayout from '@/Components/Onboarding/OnboardingLayout.vue';

const props = defineProps({
    hasUploaded: { type: Boolean, default: false },
    profile: { type: Object, default: null },
});

const page = usePage();
const flash = page.props.flash;

const form = useForm({
    nim: props.profile?.nim || '',
    faculty: props.profile?.faculty || '',
    department: props.profile?.department || '',
    batch_year: props.profile?.batch_year || '',
    identity_photo: null,
    photo: null,
});

const showForm = ref(false);

const identityPreview = ref(null);
const selfiePreview = ref(null);

const videoRef = ref(null);
const canvasRef = ref(null);
const stream = ref(null);
const cameraActive = ref(false);

async function startCamera() {
    try {
        stream.value = await navigator.mediaDevices.getUserMedia({
            video: { facingMode: 'user', width: { ideal: 1280 }, height: { ideal: 720 } },
            audio: false,
        });
        cameraActive.value = true;
        await nextTick();
        if (videoRef.value) {
            videoRef.value.srcObject = stream.value;
        }
    } catch {
        selfiePreview.value = null;
        form.photo = null;
        const el = document.getElementById('selfieFallbackInput');
        if (el) el.click();
    }
}

function capturePhoto() {
    const video = videoRef.value;
    const canvas = canvasRef.value;
    if (!video || !canvas) return;

    canvas.width = video.videoWidth;
    canvas.height = video.videoHeight;
    const ctx = canvas.getContext('2d');
    ctx.drawImage(video, 0, 0);

    canvas.toBlob((blob) => {
        const file = new File([blob], 'selfie.jpg', { type: 'image/jpeg' });
        form.photo = file;

        const reader = new FileReader();
        reader.onload = (ev) => {
            selfiePreview.value = ev.target.result;
        };
        reader.readAsDataURL(file);

        stopCamera();
    }, 'image/jpeg', 0.9);
}

function stopCamera() {
    if (stream.value) {
        stream.value.getTracks().forEach(track => track.stop());
    }
    stream.value = null;
    cameraActive.value = false;
}

function openSelfie() {
    selfiePreview.value = null;
    form.photo = null;
    startCamera();
}

onUnmounted(() => stopCamera());

function onFileSelect(e, field) {
    const file = e.target.files[0];
    if (!file) return;
    form[field] = file;
    const reader = new FileReader();
    reader.onload = (ev) => {
        if (field === 'identity_photo') identityPreview.value = ev.target.result;
        else selfiePreview.value = ev.target.result;
    };
    reader.readAsDataURL(file);
}

function submit() {
    form.post(route('onboarding.upload-identity'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Verifikasi Identitas" />
    <OnboardingLayout :current-step="2">
        <div class="max-w-xl mx-auto px-4 py-10">

            <!-- SUCCESS FLASH -->
            <div v-if="flash.success"
                 class="mb-6 bg-green-50 border border-green-200 rounded-2xl p-5 flex items-start gap-4">
                <IconCheck class="text-green-500 shrink-0 mt-0.5" :size="22" stroke="1.5" />
                <div>
                    <p class="font-semibold text-green-800 text-title-lg">{{ flash.success }}</p>
                </div>
            </div>

            <!-- STATE: sudah upload, menunggu verifikasi -->
            <template v-if="hasUploaded && !showForm">
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-8 text-center">
                    <IconCheck class="mx-auto text-green-500 mb-4" :size="48" stroke="1.5" />
                    <h1 class="text-headline-md font-bold text-primary mb-2">Identitas Terkirim</h1>
                    <p class="text-text-body text-body-md mb-6">
                        Foto identitas Anda sudah diunggah dan menunggu verifikasi oleh admin.
                        Proses verifikasi biasanya memakan waktu 1x24 jam.
                    </p>
                    <button @click="showForm = true"
                            class="inline-flex items-center gap-2 border border-outline-variant text-primary px-6 py-3 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all">
                        <IconRefresh :size="18" stroke="1.5" />
                        Upload Ulang
                    </button>
                </div>
            </template>

            <!-- STATE: form upload -->
            <template v-if="!hasUploaded || showForm">
                <h1 class="text-headline-md font-bold text-primary mb-2">Verifikasi Identitas</h1>
                <p class="text-text-body text-body-md mb-8">Unggah foto identitas dan swafoto untuk verifikasi akun Anda.</p>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 space-y-5">
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">NIM</label>
                            <input type="text" v-model="form.nim" placeholder="Masukkan NIM"
                                   class="w-full border border-outline-variant rounded-xl px-4 py-3 text-body-md text-primary placeholder:text-text-muted focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" />
                            <p v-if="form.errors.nim" class="text-error-red text-xs mt-1">{{ form.errors.nim }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Fakultas</label>
                            <input type="text" v-model="form.faculty" placeholder="Masukkan nama fakultas"
                                   class="w-full border border-outline-variant rounded-xl px-4 py-3 text-body-md text-primary placeholder:text-text-muted focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" />
                            <p v-if="form.errors.faculty" class="text-error-red text-xs mt-1">{{ form.errors.faculty }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Program Studi</label>
                            <input type="text" v-model="form.department" placeholder="Masukkan program studi"
                                   class="w-full border border-outline-variant rounded-xl px-4 py-3 text-body-md text-primary placeholder:text-text-muted focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" />
                            <p v-if="form.errors.department" class="text-error-red text-xs mt-1">{{ form.errors.department }}</p>
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-1.5">Tahun Angkatan</label>
                            <input type="number" v-model="form.batch_year" placeholder="Contoh: 2025"
                                   min="2000" max="2100"
                                   class="w-full border border-outline-variant rounded-xl px-4 py-3 text-body-md text-primary placeholder:text-text-muted focus:ring-2 focus:ring-primary/10 focus:border-primary outline-none transition-all" />
                            <p v-if="form.errors.batch_year" class="text-error-red text-xs mt-1">{{ form.errors.batch_year }}</p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                        <label class="text-label-md font-medium text-primary block mb-4">Foto KTM/Kartu Identitas Mahasiswa</label>
                        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-8 text-center hover:border-primary transition-colors cursor-pointer"
                             @click="$refs.identityInput.click()">
                            <img v-if="identityPreview" :src="identityPreview" class="max-h-40 mx-auto rounded-lg mb-2" />
                            <p v-else class="text-text-muted text-body-md">Klik untuk foto KTM/Kartu Identitas Mahasiswa pakai kamera</p>
                            <p class="text-text-muted text-label-md mt-1">Maks 5MB, format JPG/PNG</p>
                            <input ref="identityInput" type="file" accept="image/*" capture="environment"
                                   class="hidden" @change="(e) => onFileSelect(e, 'identity_photo')" />
                        </div>
                        <p v-if="form.errors.identity_photo" class="text-error-red text-xs mt-1">{{ form.errors.identity_photo }}</p>
                    </div>

                    <div class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30">
                        <label class="text-label-md font-medium text-primary block mb-4">Swafoto (Selfie)</label>
                        <div class="border-2 border-dashed border-outline-variant rounded-2xl p-8 text-center">
                            <img v-if="selfiePreview && !cameraActive" :src="selfiePreview" class="max-h-40 mx-auto rounded-lg mb-4" />
                            <video v-if="cameraActive" ref="videoRef" autoplay playsinline
                                   class="max-h-60 mx-auto rounded-lg mb-4 bg-black" />
                            <canvas ref="canvasRef" class="hidden" />

                            <template v-if="!cameraActive && !selfiePreview">
                                <button type="button" @click="openSelfie"
                                        class="inline-flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                                    <IconCamera :size="20" stroke="1.5" />
                                    Buka Kamera
                                </button>
                                <p class="text-text-muted text-label-md mt-2">Klik tombol untuk langsung buka kamera depan</p>
                            </template>

                            <template v-if="cameraActive">
                                <div class="flex items-center justify-center gap-4">
                                    <button type="button" @click="capturePhoto"
                                            class="inline-flex items-center gap-2 bg-green-600 text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-green-700 transition-all active:scale-95">
                                        <IconCamera :size="20" stroke="1.5" />
                                        Jepret
                                    </button>
                                    <button type="button" @click="stopCamera"
                                            class="inline-flex items-center gap-2 border border-outline-variant text-text-body px-6 py-3 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all">
                                        Batal
                                    </button>
                                </div>
                            </template>

                            <template v-if="selfiePreview && !cameraActive">
                                <button type="button" @click="openSelfie"
                                        class="inline-flex items-center gap-2 bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95">
                                    <IconCamera :size="20" stroke="1.5" />
                                    Ambil Ulang
                                </button>
                            </template>

                            <input id="selfieFallbackInput" type="file" accept="image/*" capture="user"
                                   class="hidden" @change="(e) => onFileSelect(e, 'photo')" />
                        </div>
                        <p v-if="form.errors.photo" class="text-error-red text-xs mt-1">{{ form.errors.photo }}</p>
                    </div>

                    <button type="submit" :disabled="form.processing || !form.nim || !form.faculty || !form.department || !form.batch_year || (!form.identity_photo && !form.photo)"
                            class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full hover:bg-primary transition-all active:scale-95 shadow-lg shadow-primary-container/10 disabled:opacity-50">
                        {{ form.processing ? 'Mengunggah...' : 'Kirim Verifikasi' }}
                    </button>
                </form>

                <div class="mt-8 bg-pastel-blue/30 rounded-2xl p-5 text-sm text-primary">
                    <p class="font-semibold mb-1">Informasi:</p>
                    <p>Setelah mengirim, admin akan memverifikasi identitas Anda secara manual. Proses biasanya memakan waktu 1x24 jam. Anda akan mendapat notifikasi setelah diverifikasi.</p>
                </div>
            </template>

        </div>
    </OnboardingLayout>
</template>
