<script setup>
import { Head, Link } from '@inertiajs/vue3';
import RichTextViewer from '@/Components/Shared/RichTextViewer.vue';
import AudioPlayer from '@/Components/Exam/AudioPlayer.vue';
import { IconArrowLeft, IconArrowRight, IconCheck, IconEye } from '@tabler/icons-vue';
import { computed, ref } from 'vue';

const props = defineProps({
    questions: { type: Array, default: () => [] },
    index: { type: Number, default: 0 },
});

const optionKeys = ['A', 'B', 'C', 'D'];
const showKey = ref(false);
const currentIndex = ref(props.index);

const current = computed(() => props.questions[currentIndex.value] || null);
const isListening = computed(() => current.value?.skill?.code === 'listening');
const passage = computed(() => current.value?.passage || null);
const audioSrc = computed(() => {
    const path = current.value?.audio_url || passage.value?.audio_url;
    return path ? '/storage/' + path : null;
});
const imageSrc = computed(() => {
    const path = current.value?.image_url || passage.value?.image_url;
    return path ? '/storage/' + path : null;
});

const skillLabel = computed(() => {
    if (!current.value?.skill) return 'Soal';
    return current.value.skill.code === 'listening' ? 'Listening' : 'Reading Passage';
});

const partLabel = computed(() => current.value?.skillPart?.name || current.value?.skill?.name || '');

function optionText(key) {
    return current.value ? (current.value['option_' + key.toLowerCase()] || '') : '';
}

function isCorrect(key) {
    return current.value?.correct_answer === key;
}

function goTo(dir) {
    const next = currentIndex.value + dir;
    if (next >= 0 && next < props.questions.length) {
        currentIndex.value = next;
    }
}

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = route('content-library.index');
    }
}
</script>

<template>
    <Head title="Preview Soal" />
    <div class="h-screen flex flex-col bg-surface">
        <!-- HEADER (TopAppBar) -->
        <header class="shrink-0 flex justify-between items-center px-6 h-14 bg-primary-container border-b border-white/10 z-10">
            <div class="flex items-center gap-6">
                <div class="flex flex-col">
                    <span class="font-body-md text-body-md text-white font-bold">{{ partLabel ? partLabel + ' — ' : '' }}Soal {{ currentIndex + 1 }} dari {{ questions.length }}</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button @click="showKey = !showKey"
                        class="flex items-center gap-2 px-4 py-1.5 bg-secondary-container hover:bg-secondary-container/80 text-white rounded-full font-label-md text-label-md font-bold transition-all active:scale-95 duration-150 shadow-lg">
                    <IconEye :size="14" /> {{ showKey ? 'Sembunyikan Kunci' : 'Tampilkan Kunci' }}
                </button>
                    <button @click="goBack"
                            class="flex items-center gap-2 px-4 py-1.5 bg-white text-primary-container rounded-full font-label-md text-label-md font-bold transition-all active:scale-95 duration-150">
                        <IconArrowLeft :size="14" /> Kembali
                    </button>
            </div>
        </header>

            <!-- MAIN CONTENT -->
            <main class="flex-1 flex overflow-hidden">
                <!-- READING: SPLIT PANE -->
                <template v-if="!isListening">
                    <!-- LEFT PANE: PASSAGE -->
                    <section class="w-1/2 border-r border-outline-variant overflow-y-auto scroll-hide p-8 bg-surface-bright">
                        <div class="max-w-xl mx-auto">
                            <div class="mb-8">
                                <span class="bg-pastel-blue text-primary px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-widest mb-4 inline-block">{{ partLabel || 'Materi' }}</span>
                                <h1 v-if="passage" class="font-serif text-2xl leading-tight text-primary font-bold mb-6">{{ passage.title }}</h1>
                                <h1 v-else class="font-serif text-2xl leading-tight text-primary font-bold mb-6">Soal Standalone</h1>
                            </div>
                            <div v-if="passage?.content_text" class="font-serif text-[18px] leading-[1.8] text-on-background space-y-6 [&_.rich-text-content]:leading-[1.8] [&_.rich-text-content]:text-on-background [&_.rich-text-content_p]:mb-6">
                                <RichTextViewer :content="passage.content_text" />
                            </div>
                            <div v-if="passage?.image_url" class="my-6 rounded-xl border border-outline-variant/40 bg-surface-white p-3 shadow-standard">
                                <img :src="'/storage/' + passage.image_url" class="w-full h-auto rounded-lg" />
                            </div>
                        </div>
                    </section>
                    <!-- RIGHT PANE: QUESTION -->
                    <section class="w-1/2 flex flex-col bg-surface-white">
                        <div class="flex-1 overflow-y-auto p-8 scroll-hide">
                            <div class="max-w-lg mx-auto">
                                <div class="flex items-center gap-2 mb-8">
                                    <div class="w-8 h-8 rounded-lg bg-primary-container text-white flex items-center justify-center font-bold">{{ currentIndex + 1 }}</div>
                                    <span class="text-text-muted font-label-md text-label-md uppercase tracking-wider">QUESTION {{ currentIndex + 1 }} OF {{ questions.length }}</span>
                                </div>
                                <h2 class="font-headline-md text-headline-md text-text-heading mb-10 leading-snug">{{ current?.question_text }}</h2>
                                <div class="space-y-4" v-if="current">
                                    <div v-for="key in optionKeys" :key="key"
                                         class="option-card w-full flex items-start gap-4 p-5 border-2 rounded-2xl text-left transition-all duration-200 group"
                                         :class="showKey && isCorrect(key) ? 'option-correct' : 'border-surface-container-high'">
                                        <div class="w-8 h-8 rounded-full border-2 flex-shrink-0 flex items-center justify-center font-bold transition-colors"
                                             :class="showKey && isCorrect(key)
                                                 ? 'bg-green-500 border-green-500 text-white'
                                                 : 'border-outline-variant text-text-muted group-hover:border-secondary group-hover:text-primary'">
                                            {{ key }}
                                        </div>
                                        <span class="font-body-md text-title-lg text-on-surface-variant leading-tight pt-1">
                                            {{ optionText(key) }}
                                            <span v-if="showKey && isCorrect(key)" class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-bold ml-2">
                                                <IconCheck :size="16" /> Kunci
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>

                <!-- LISTENING: SPLIT PANE -->
                <template v-else>
                    <!-- LEFT PANE: MEDIA -->
                    <section class="w-1/2 border-r border-outline-variant overflow-y-auto scroll-hide p-8 bg-surface-bright">
                        <div class="max-w-xl mx-auto">
                            <div class="mb-8">
                                <span class="bg-pastel-purple text-primary px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-widest mb-4 inline-block">{{ partLabel || 'Listening' }}</span>
                                <h1 v-if="passage" class="font-serif text-2xl leading-tight text-primary font-bold mb-6">{{ passage.title }}</h1>
                                <h1 v-else class="font-serif text-2xl leading-tight text-primary font-bold mb-6">{{ partLabel || 'Listening' }}</h1>
                            </div>
                            <p class="text-label-md text-text-muted mb-4">Putar audio sebelum menjawab soal</p>
                            <div class="bg-surface-container-low rounded-2xl border border-surface-variant overflow-hidden flex flex-col">
                                <div v-if="imageSrc" class="relative w-full min-h-[300px]">
                                    <img :src="imageSrc" class="absolute inset-0 w-full h-full object-cover" />
                                    <div class="absolute inset-0 bg-black/5"></div>
                                </div>
                                <AudioPlayer v-if="audioSrc" :src="audioSrc" strip />
                                <div v-else class="bg-surface-white p-6 text-center border-t border-surface-variant">
                                    <p class="text-text-muted text-body-md">Belum ada audio untuk soal ini.</p>
                                </div>
                            </div>
                        </div>
                    </section>
                    <!-- RIGHT PANE: QUESTION -->
                    <section class="w-1/2 flex flex-col bg-surface-white">
                        <div class="flex-1 overflow-y-auto p-8 scroll-hide">
                            <div class="max-w-lg mx-auto">
                                <div class="flex items-center gap-2 mb-8">
                                    <div class="w-8 h-8 rounded-lg bg-primary-container text-white flex items-center justify-center font-bold">{{ currentIndex + 1 }}</div>
                                    <span class="text-text-muted font-label-md text-label-md uppercase tracking-wider">QUESTION {{ currentIndex + 1 }} OF {{ questions.length }}</span>
                                </div>
                                <div class="space-y-4" v-if="current">
                                    <div v-for="key in optionKeys" :key="key"
                                         class="option-card w-full flex items-center gap-4 p-5 border-2 rounded-2xl transition-all duration-200 group"
                                         :class="showKey && isCorrect(key) ? 'option-correct' : 'border-surface-container-high'">
                                        <div class="w-8 h-8 rounded-full border-2 flex-shrink-0 flex items-center justify-center font-bold transition-colors"
                                             :class="showKey && isCorrect(key)
                                                 ? 'bg-green-500 border-green-500 text-white'
                                                 : 'border-outline-variant text-text-muted group-hover:border-secondary group-hover:text-primary'">
                                            {{ key }}
                                        </div>
                                        <span v-if="showKey && isCorrect(key)" class="inline-flex items-center gap-1 text-green-600 dark:text-green-400 font-bold">
                                            <IconCheck :size="16" /> Kunci
                                        </span>
                                        <span v-else class="text-label-md text-text-muted">Pilihan jawaban di dalam audio</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                </template>
            </main>

            <!-- FOOTER CONTROLS -->
            <footer class="h-14 border-t border-outline-variant px-6 flex justify-between items-center bg-surface-container-low shrink-0">
                <button @click="goTo(-1)" :disabled="currentIndex === 0"
                        class="flex items-center gap-2 px-4 py-2 text-text-body font-bold hover:text-primary transition-colors group disabled:opacity-30 disabled:cursor-not-allowed">
                    <IconArrowLeft :size="16" class="transition-transform group-hover:-translate-x-1" /> Sebelumnya
                </button>
                <div v-if="questions.length > 1" class="flex gap-2">
                    <div v-for="(q, i) in questions" :key="q.id"
                         class="w-2 h-2 rounded-full transition-all"
                         :class="i === currentIndex ? 'bg-secondary' : 'bg-outline-variant'"></div>
                </div>
                <BaseButton v-if="questions.length > 1" @click="goTo(1)" :disabled="currentIndex >= questions.length - 1"
                        class="px-6">
                    Selanjutnya <IconArrowRight :size="16" class="transition-transform group-hover:translate-x-1" />
                </BaseButton>
                <BaseButton v-else @click="goBack" class="px-6">
                    <IconArrowLeft :size="16" /> Kembali
                </BaseButton>
            </footer>
    </div>
</template>

<style scoped>
.scroll-hide::-webkit-scrollbar {
    width: 6px;
}
.scroll-hide::-webkit-scrollbar-track {
    background: #f1f1f1;
}
.scroll-hide::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 10px;
}
.option-card:hover {
    border-color: #5647c8;
    background-color: #f3f3f6;
}
.option-correct {
    border-color: #22c55e !important;
    background-color: #f0fdf4 !important;
}
</style>
