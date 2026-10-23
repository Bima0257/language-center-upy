<script setup>
import AudioPlayer from '@/Components/Ujian/AudioPlayer.vue';

defineProps({
    materiSoal: { type: Object, default: null },
    bagianSoalLabel: { type: String, default: '' },
    imageSrc: { type: String, default: null },
    audioSrc: { type: String, default: null },
    imageLoading: { type: Object, required: true },
});
</script>

<template>
    <section
        class="w-3/5 border-r border-outline-variant bg-surface-bright flex flex-col overflow-hidden"
    >
        <div class="flex-1 min-h-0 overflow-y-auto scroll-hide p-8">
            <div class="max-w-2xl mx-auto">
                <div class="mb-8">
                    <span
                        class="bg-pastel-purple text-primary px-3 py-1 rounded-full text-[12px] font-bold uppercase tracking-widest mb-4 inline-block"
                        >{{ bagianSoalLabel || 'Audio' }}</span
                    >
                    <h1
                        v-if="materiSoal"
                        class="font-serif text-2xl leading-tight text-text-heading font-bold mb-6"
                    >
                        {{ materiSoal.judul }}
                    </h1>
                    <h1
                        v-else
                        class="font-serif text-2xl leading-tight text-text-heading font-bold mb-6"
                    >
                        {{ bagianSoalLabel || 'Audio' }}
                    </h1>
                </div>
                <p class="text-label-md text-text-muted mb-4">
                    Putar audio sebelum menjawab soal
                </p>
                <div
                    v-if="imageSrc"
                    class="rounded-2xl border border-outline-variant/40 bg-surface-white p-3 shadow-standard"
                >
                    <div class="relative w-full min-h-[300px]">
                        <BaseMediaLoader
                            :loading="imageLoading.loading.value"
                            media-type="image"
                            skeleton-class="h-full min-h-[300px] rounded-lg"
                        >
                            <img
                                :src="imageSrc"
                                class="absolute inset-0 w-full h-full object-cover rounded-lg"
                                @load="imageLoading.onLoad()"
                                @error="imageLoading.onError()"
                            />
                        </BaseMediaLoader>
                        <div class="absolute inset-0 bg-scrim/5 rounded-lg"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="shrink-0 border-t border-outline-variant bg-surface-white">
            <AudioPlayer v-if="audioSrc" :src="audioSrc" bar />
            <div v-else class="px-6 py-4 text-center">
                <p class="text-text-muted text-body-md">
                    Belum ada audio untuk soal ini.
                </p>
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
</style>
