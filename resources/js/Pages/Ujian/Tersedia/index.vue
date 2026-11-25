<script setup>
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import TataLetakDasbor from '@/Layouts/TataLetakDasbor.vue';
import { IconClipboardCheck, IconClock, IconPlayerPlay } from '@tabler/icons-vue';

defineProps({
    ujian: { type: Array, default: () => [] },
});

const memproses = ref(null);

function mulaiTryout(dataUjian) {
    if (memproses.value !== null) return;

    memproses.value = dataUjian.id;
    router.post(route('ujian.tryout.mulai', dataUjian.id), {}, {
        onFinish: () => {
            memproses.value = null;
        },
    });
}
</script>

<template>
    <Head title="Tryout Tersedia" />
    <TataLetakDasbor title="Tryout Tersedia" subtitle="Pilih tryout yang ingin Anda kerjakan — bisa diulang kapan saja">
        <div v-if="ujian.length === 0" class="rounded-2xl border border-outline-variant/30 bg-surface-white p-10 text-center shadow-soft">
            <h2 class="mb-2 text-title-lg font-semibold text-text-heading">Belum Ada Tryout</h2>
            <p class="text-body-md text-text-body">Saat ini belum ada ujian tryout yang dapat diikuti.</p>
        </div>

        <div v-else class="space-y-4">
            <BaseCard
                v-for="dataUjian in ujian"
                :key="dataUjian.id"
                class="flex flex-col justify-between gap-4 md:flex-row md:items-center"
            >
                <div class="min-w-0">
                    <div class="mb-1 flex flex-wrap items-center gap-2">
                        <h3 class="text-title-lg font-semibold text-text-heading">{{ dataUjian.judul }}</h3>
                        <span class="rounded-full bg-pastel-blue px-3 py-1 text-label-md font-medium text-text-heading">
                            {{ dataUjian.program_tes?.nama }}
                        </span>
                    </div>
                    <div v-if="dataUjian.deskripsi" class="prose prose-sm max-w-none text-body-md text-text-body" v-html="dataUjian.deskripsi" />
                    <p class="mt-2 flex items-center gap-1.5 text-label-md text-text-muted">
                        <IconClock :size="14" />
                        Durasi {{ dataUjian.durasi_menit }} menit
                    </p>
                </div>

                <BaseButton
                    type="button"
                    :disabled="memproses !== null"
                    class="shrink-0"
                    @click="mulaiTryout(dataUjian)"
                >
                    <IconPlayerPlay v-if="memproses !== dataUjian.id" :size="18" />
                    <IconClipboardCheck v-else :size="18" />
                    {{ memproses === dataUjian.id ? 'Memulai...' : 'Mulai Tryout' }}
                </BaseButton>
            </BaseCard>
        </div>
    </TataLetakDasbor>
</template>
