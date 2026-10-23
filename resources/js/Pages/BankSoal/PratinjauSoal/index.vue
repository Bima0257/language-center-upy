<script setup>
import { Head } from '@inertiajs/vue3';
import ReadingPreviewPane from '@/Pages/BankSoal/PratinjauSoal/components/ReadingPreviewPane.vue';
import ListeningPreviewPane from '@/Pages/BankSoal/PratinjauSoal/components/ListeningPreviewPane.vue';
import { IconArrowLeft, IconArrowRight, IconEye } from '@tabler/icons-vue';
import { computed, ref } from 'vue';

const props = defineProps({
    soal: { type: Array, default: () => [] },
    indeks: { type: Number, default: 0 },
});

const showKey = ref(false);
const currentIndex = ref(props.indeks);

const current = computed(() => props.soal[currentIndex.value] || null);
const isMaterialAudio = computed(() => current.value?.materi === 'audio');
const materiSoal = computed(() => current.value?.materi_soal || null);
const audioSrc = computed(() => {
    const path = materiSoal.value?.url_audio;
    return path ? '/media/' + path : null;
});
const imageSrc = computed(() => {
    const path = materiSoal.value?.url_gambar;
    return path ? '/media/' + path : null;
});

const bagianSoalLabel = computed(() => current.value?.bagian_soal?.nama || current.value?.keterampilan?.nama || '');

function goTo(dir) {
    const next = currentIndex.value + dir;
    if (next >= 0 && next < props.soal.length) {
        currentIndex.value = next;
    }
}

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = route('pustaka-soal.daftar');
    }
}
</script>

<template>
    <Head title="Pratinjau Soal" />
    <div class="h-screen flex flex-col bg-surface">
        <!-- HEADER (TopAppBar) -->
        <header class="shrink-0 flex justify-between items-center px-6 h-14 bg-primary-container border-b border-on-primary-container/10 z-10">
            <div class="flex items-center gap-6">
                <div class="flex flex-col">
                    <span class="font-body-md text-body-md text-on-primary-container font-bold">{{ bagianSoalLabel ? bagianSoalLabel + ' — ' : '' }}Soal {{ currentIndex + 1 }} dari {{ soal.length }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="showKey = !showKey"
                        class="flex items-center gap-2 px-4 py-1.5 bg-secondary-container hover:bg-secondary-container/80 text-on-secondary-container rounded-full font-label-md text-label-md font-bold transition-all active:scale-95 duration-150 shadow-lg">
                    <IconEye :size="14" /> {{ showKey ? 'Sembunyikan Kunci' : 'Tampilkan Kunci' }}
                </button>
                    <button @click="goBack"
                        class="flex items-center gap-2 px-4 py-1.5 bg-surface-white text-primary-container rounded-full font-label-md text-label-md font-bold transition-all active:scale-95 duration-150">
                        <IconArrowLeft :size="14" /> Kembali
                    </button>
            </div>
        </header>

            <!-- MAIN CONTENT -->
            <main class="flex-1 flex overflow-hidden">
                <ReadingPreviewPane
                    v-if="!isMaterialAudio"
                    :current-question="current"
                    :question-index="currentIndex"
                    :question-count="soal.length"
                    :materi-soal="materiSoal"
                    :bagian-soal-label="bagianSoalLabel"
                    :image-src="imageSrc"
                    :show-key="showKey"
                />
                <ListeningPreviewPane
                    v-else
                    :current-question="current"
                    :question-index="currentIndex"
                    :question-count="soal.length"
                    :materi-soal="materiSoal"
                    :bagian-soal-label="bagianSoalLabel"
                    :image-src="imageSrc"
                    :audio-src="audioSrc"
                    :show-key="showKey"
                />
            </main>

            <!-- FOOTER CONTROLS -->
            <footer class="h-14 border-t border-outline-variant px-6 flex justify-between items-center bg-surface-container-low shrink-0">
                <BaseButton @click="goTo(-1)" :disabled="currentIndex === 0" class="px-6">
                    <IconArrowLeft :size="16" class="transition-transform group-hover:-translate-x-1" /> Sebelumnya
                </BaseButton>
                <div v-if="soal.length > 1" class="flex gap-2">
                    <div v-for="(q, i) in soal" :key="q.id"
                         class="w-2 h-2 rounded-full transition-all"
                         :class="i === currentIndex ? 'bg-secondary' : 'bg-outline-variant'"></div>
                </div>
                <BaseButton v-if="soal.length > 1" @click="goTo(1)" :disabled="currentIndex >= soal.length - 1"
                        class="px-6">
                    Selanjutnya <IconArrowRight :size="16" class="transition-transform group-hover:translate-x-1" />
                </BaseButton>
                <BaseButton v-else @click="goBack" class="px-6">
                    <IconArrowLeft :size="16" /> Kembali
                </BaseButton>
            </footer>
    </div>
</template>
