<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { IconCamera, IconMicrophone, IconWifi, IconCheck, IconX } from '@tabler/icons-vue';
import { ref, computed } from 'vue';

const props = defineProps({
    slot: { type: Object, required: true },
});

const cameraOk = ref(false);
const micOk = ref(false);
const connectionOk = ref(true);

const allChecked = computed(() => cameraOk.value && micOk.value && connectionOk.value);

const form = useForm({});

function startExam() {
    form.post(route('exam.start', props.slot.id));
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
            <BaseCard padding="p-8" class="space-y-6">
                <div>
                    <h2 class="text-headline-md font-bold text-primary mb-2">{{ slot.schedule?.exam?.title }}</h2>
                    <p class="text-text-body text-body-md">{{ slot.schedule?.title }} — Sesi {{ slot.start_time?.substring(0, 5) }}–{{ slot.end_time?.substring(0, 5) }} WIB</p>
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
                        <BaseButton v-if="!cameraOk" size="sm" @click="checkCamera">
                            Cek Kamera
                        </BaseButton>
                        <IconCheck v-else class="text-green-500 dark:text-green-400" :size="22" />
                    </div>
                    <div class="flex items-center justify-between p-4 bg-surface-container-low rounded-2xl">
                        <div class="flex items-center gap-3">
                            <IconMicrophone class="text-text-muted" :size="22" stroke="1.5" />
                            <div>
                                <p class="text-label-md font-medium text-primary">Mikrofon</p>
                                <p class="text-text-muted text-xs">{{ micOk ? 'Terdeteksi' : 'Belum dicek' }}</p>
                            </div>
                        </div>
                        <BaseButton v-if="!micOk" size="sm" @click="checkMic">
                            Cek Mikrofon
                        </BaseButton>
                        <IconCheck v-else class="text-green-500 dark:text-green-400" :size="22" />
                    </div>
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
