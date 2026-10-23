<script setup>
import AudioPlayer from '@/Components/Ujian/AudioPlayer.vue';
import { useMediaLoad } from '@/Composables/useMediaLoad';
import { IconCheck } from '@tabler/icons-vue';

const props = defineProps({
    currentQuestion: { type: Object, default: null },
    questionIndex: { type: Number, default: 0 },
    questionCount: { type: Number, default: 0 },
    materiSoal: { type: Object, default: null },
    bagianSoalLabel: { type: String, default: '' },
    imageSrc: { type: String, default: null },
    audioSrc: { type: String, default: null },
    showKey: { type: Boolean, default: false },
});

const optionKeys = ['A', 'B', 'C', 'D'];
const imageLoading = useMediaLoad(() => props.imageSrc);

function isCorrect(key) {
    return props.currentQuestion?.kunci_jawaban === key;
}
</script>

<template>
    <section class="w-1/2 border-r border-outline-variant overflow-y-auto scroll-hide p-8 bg-surface-bright">
        <div class="max-w-xl mx-auto">
            <div class="mb-8">
                <span class="bg-pastel-purple text-primary px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-widest mb-4 inline-block">{{ bagianSoalLabel || 'Audio' }}</span>
                <h1 v-if="materiSoal" class="font-serif text-2xl leading-tight text-text-heading font-bold mb-6">{{ materiSoal.judul }}</h1>
                <h1 v-else class="font-serif text-2xl leading-tight text-text-heading font-bold mb-6">{{ bagianSoalLabel || 'Audio' }}</h1>
            </div>
            <p class="text-label-md text-text-muted mb-4">Putar audio sebelum menjawab soal</p>
            <div class="bg-surface-container-low rounded-2xl border border-outline-variant overflow-hidden flex flex-col">
                <div v-if="imageSrc" class="relative w-full min-h-[300px]">
                    <BaseMediaLoader
                        :loading="imageLoading.loading.value"
                        media-type="image"
                        skeleton-class="h-full min-h-[300px] rounded-none border-0"
                    >
                        <img
                            :src="imageSrc"
                            class="absolute inset-0 w-full h-full object-cover"
                            @load="imageLoading.onLoad()"
                            @error="imageLoading.onError()"
                        />
                    </BaseMediaLoader>
                    <div class="absolute inset-0 bg-scrim/5"></div>
                </div>
                <AudioPlayer v-if="audioSrc" :src="audioSrc" strip />
                <div v-else class="bg-surface-white p-6 text-center border-t border-outline-variant">
                    <p class="text-text-muted text-body-md">Belum ada audio untuk soal ini.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="w-1/2 flex flex-col bg-surface-white">
        <div class="flex-1 overflow-y-auto p-8 scroll-hide">
            <div class="max-w-lg mx-auto">
                <div class="flex items-center gap-2 mb-8">
                    <div class="w-8 h-8 rounded-lg bg-primary-container text-on-primary-container flex items-center justify-center font-bold">{{ questionIndex + 1 }}</div>
                    <span class="text-text-muted font-label-md text-label-md uppercase tracking-wider">QUESTION {{ questionIndex + 1 }} OF {{ questionCount }}</span>
                </div>
                <div class="space-y-4" v-if="currentQuestion">
                    <div v-for="key in optionKeys" :key="key"
                         class="option-card w-full flex items-center gap-4 p-5 border-2 rounded-2xl transition-all duration-200 group"
                         :class="showKey && isCorrect(key) ? 'option-correct' : 'border-surface-container-high'">
                        <div class="w-8 h-8 rounded-full border-2 flex-shrink-0 flex items-center justify-center font-bold transition-colors"
                             :class="showKey && isCorrect(key)
                                 ? 'bg-success border-success text-on-success'
                                 : 'border-outline-variant text-text-muted group-hover:border-secondary group-hover:text-primary'">
                            {{ key }}
                        </div>
                        <span v-if="showKey && isCorrect(key)" class="inline-flex items-center gap-1 text-success font-bold">
                            <IconCheck :size="16" /> Kunci
                        </span>
                        <span v-else class="text-label-md text-text-muted">Pilihan jawaban di dalam audio</span>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>

<style scoped>
.scroll-hide::-webkit-scrollbar {
    width: 6px;
}
.scroll-hide::-webkit-scrollbar-track {
    background: var(--color-scrollbar-track);
}
.scroll-hide::-webkit-scrollbar-thumb {
    background: var(--color-scrollbar-thumb);
    border-radius: 10px;
}
.option-card:hover {
    border-color: var(--color-secondary);
    background-color: var(--color-surface-container-low);
}
.option-correct {
    border-color: var(--color-success) !important;
    background-color: var(--color-success-container) !important;
}
</style>
