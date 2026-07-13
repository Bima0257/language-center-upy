<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconCamera, IconMicrophone, IconWifi, IconCheck, IconX } from '@tabler/icons-vue';
import { ref, computed } from 'vue';

const props = defineProps({
    schedule: { type: Object, required: true },
});

const cameraOk = ref(false);
const micOk = ref(false);
const connectionOk = ref(true);

const allChecked = computed(() => cameraOk.value && micOk.value && connectionOk.value);

const form = useForm({});

function startExam() {
    form.post(route('exam.start', props.schedule.id));
}

function checkCamera() {
    navigator.mediaDevices?.getUserMedia({ video: true })
        .then(() => { cameraOk.value = true; })
        .catch(() => { cameraOk.value = false; });
}

function checkMic() {
    navigator.mediaDevices?.getUserMedia({ audio: true })
        .then(() => { micOk.value = true; })
        .catch(() => { micOk.value = false; });
}
</script>

<template>
    <Head title="Persiapan Ujian" />
    <DashboardLayout title="Persiapan Ujian">
        <div class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl p-8 shadow-soft border border-outline-variant/30 space-y-6">
                <div>
                    <h2 class="text-headline-md font-bold text-primary mb-2">{{ schedule.exam?.title }}</h2>
                    <p class="text-text-body text-body-md">{{ schedule.title }}</p>
                </div>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
                        <div class="flex items-center gap-3">
                            <IconCamera class="text-text-muted" :size="22" stroke="1.5" />
                            <div>
                                <p class="text-label-md font-medium text-primary">Kamera</p>
                                <p class="text-text-muted text-xs">{{ cameraOk ? 'Terdeteksi' : 'Belum dicek' }}</p>
                            </div>
                        </div>
                        <button v-if="!cameraOk" @click="checkCamera"
                                class="bg-primary-container text-white px-4 py-2 rounded-full text-label-md hover:bg-primary transition-all active:scale-95">
                            Cek Kamera
                        </button>
                        <IconCheck v-else class="text-green-500" :size="22" />
                    </div>
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
                        <div class="flex items-center gap-3">
                            <IconMicrophone class="text-text-muted" :size="22" stroke="1.5" />
                            <div>
                                <p class="text-label-md font-medium text-primary">Mikrofon</p>
                                <p class="text-text-muted text-xs">{{ micOk ? 'Terdeteksi' : 'Belum dicek' }}</p>
                            </div>
                        </div>
                        <button v-if="!micOk" @click="checkMic"
                                class="bg-primary-container text-white px-4 py-2 rounded-full text-label-md hover:bg-primary transition-all active:scale-95">
                            Cek Mikrofon
                        </button>
                        <IconCheck v-else class="text-green-500" :size="22" />
                    </div>
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
                        <div class="flex items-center gap-3">
                            <IconWifi class="text-text-muted" :size="22" stroke="1.5" />
                            <div>
                                <p class="text-label-md font-medium text-primary">Koneksi Internet</p>
                                <p class="text-text-muted text-xs">{{ connectionOk ? 'Stabil' : 'Bermasalah' }}</p>
                            </div>
                        </div>
                        <IconCheck v-if="connectionOk" class="text-green-500" :size="22" />
                        <IconX v-else class="text-error-red" :size="22" />
                    </div>
                </div>
                <button @click="startExam" :disabled="!allChecked || form.processing"
                        class="w-full bg-primary-container text-white font-semibold text-title-lg py-3.5 rounded-full hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                    {{ form.processing ? 'Memulai...' : 'Mulai Ujian' }}
                </button>
            </div>
        </div>
    </DashboardLayout>
</template>
