<script setup>
import { ref } from 'vue';

const props = defineProps({
    src: { type: String, required: true },
});

const isPlaying = ref(false);
const audioRef = ref(null);

function togglePlay() {
    if (!audioRef.value) return;
    if (isPlaying.value) {
        audioRef.value.pause();
    } else {
        audioRef.value.play();
    }
    isPlaying.value = !isPlaying.value;
}
</script>

<template>
    <div class="bg-white rounded-2xl p-6 border border-outline-variant/30">
        <audio ref="audioRef" :src="src" @ended="isPlaying = false" class="hidden"></audio>
        <div class="flex items-center gap-4">
            <button @click="togglePlay"
                    class="w-12 h-12 bg-primary-container text-white rounded-full flex items-center justify-center hover:bg-primary transition-all active:scale-95">
                <span v-if="!isPlaying" class="text-lg">▶</span>
                <span v-else class="text-lg">⏸</span>
            </button>
            <div class="flex-1">
                <div class="h-2 bg-surface-container-highest rounded-full overflow-hidden">
                    <div class="h-full bg-secondary rounded-full transition-all" :style="{ width: '0%' }"></div>
                </div>
            </div>
        </div>
    </div>
</template>
