<script setup>
import { ref } from 'vue';
import { useMediaLoad } from '@/Composables/useMediaLoad';
import { IconVolume, IconPlayerPlayFilled, IconPlayerPauseFilled } from '@tabler/icons-vue';

const props = defineProps({
    src: { type: String, required: true },
    title: { type: String, default: '' },
    strip: { type: Boolean, default: false },
});

const { loading, onLoad, onError } = useMediaLoad(() => props.src);
const isPlaying = ref(false);
const audioRef = ref(null);
const barRef = ref(null);
const currentTime = ref(0);
const duration = ref(0);
const progress = ref(0);

function togglePlay() {
    if (!audioRef.value) return;
    if (audioRef.value.paused) {
        audioRef.value.play();
    } else {
        audioRef.value.pause();
    }
}

function onTimeUpdate() {
    currentTime.value = audioRef.value?.currentTime || 0;
    progress.value = duration.value > 0 ? (currentTime.value / duration.value) * 100 : 0;
}

function onLoadedMetadata() {
    duration.value = audioRef.value?.duration || 0;
    onLoad();
}

function onSeek(e) {
    const bar = barRef.value;
    if (!bar || !audioRef.value || !duration.value) return;
    const rect = bar.getBoundingClientRect();
    const ratio = Math.min(Math.max((e.clientX - rect.left) / rect.width, 0), 1);
    audioRef.value.currentTime = ratio * duration.value;
    onTimeUpdate();
}

function formatTime(seconds) {
    if (!seconds || isNaN(seconds)) return '0:00';
    const m = Math.floor(seconds / 60);
    const s = Math.floor(seconds % 60);
    return `${m}:${s.toString().padStart(2, '0')}`;
}
</script>

<template>
    <div class="bg-surface-white flex flex-col gap-4"
         :class="strip ? 'p-6 border-t border-surface-variant' : 'p-6 rounded-2xl border border-outline-variant/30'">
        <audio ref="audioRef" :src="src" preload="metadata" class="hidden"
               @timeupdate="onTimeUpdate"
               @loadedmetadata="onLoadedMetadata"
               @error="onError"
               @play="isPlaying = true"
               @pause="isPlaying = false"
               @ended="isPlaying = false"></audio>

        <BaseMediaLoader
            v-if="loading"
            media-type="audio"
            skeleton-class="h-12"
        />

        <template v-else>
        <div class="flex justify-between items-center text-text-heading font-label-md text-label-md">
            <span>{{ formatTime(currentTime) }}</span>
            <span class="text-text-muted">{{ formatTime(duration) }}</span>
        </div>

        <div ref="barRef" @click="onSeek"
             class="relative w-full h-[6px] bg-track-neutral rounded-full cursor-pointer group">
            <div class="absolute top-0 left-0 h-full bg-secondary rounded-full"
                 :style="{ width: progress + '%' }"></div>
            <div class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 w-3 h-3 bg-white border-2 border-secondary rounded-full opacity-0 group-hover:opacity-100 transition-opacity"
                 :style="{ left: progress + '%' }"></div>
        </div>

        <div class="flex justify-between items-center mt-2">
            <div class="flex items-center gap-4">
                <button @click="togglePlay"
                        class="w-10 h-10 rounded-full bg-pastel-blue flex items-center justify-center text-secondary hover:bg-secondary hover:text-white transition-colors shrink-0">
                    <IconPlayerPlayFilled v-if="!isPlaying" :size="18" />
                    <IconPlayerPauseFilled v-else :size="18" />
                </button>
                <span v-if="title" class="font-title-lg text-title-lg text-text-heading">{{ title }}</span>
            </div>
            <button class="text-text-muted hover:text-text-heading transition-colors" title="Volume">
                <IconVolume :size="20" />
            </button>
        </div>
        </template>
    </div>
</template>
