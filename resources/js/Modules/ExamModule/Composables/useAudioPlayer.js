import { ref } from 'vue';

export function useAudioPlayer() {
    const isPlaying = ref(false);
    const currentTime = ref(0);
    const duration = ref(0);
    let audioElement = null;

    function load(src) {
        if (audioElement) {
            audioElement.pause();
            audioElement = null;
        }
        audioElement = new Audio(src);
        audioElement.addEventListener('timeupdate', () => {
            currentTime.value = audioElement.currentTime;
        });
        audioElement.addEventListener('loadedmetadata', () => {
            duration.value = audioElement.duration;
        });
        audioElement.addEventListener('ended', () => {
            isPlaying.value = false;
        });
    }

    function play() {
        if (!audioElement) return;
        audioElement.play();
        isPlaying.value = true;
    }

    function pause() {
        if (!audioElement) return;
        audioElement.pause();
        isPlaying.value = false;
    }

    function toggle() {
        if (isPlaying.value) pause();
        else play();
    }

    function setTime(time) {
        if (audioElement) {
            audioElement.currentTime = time;
            currentTime.value = time;
        }
    }

    function destroy() {
        if (audioElement) {
            audioElement.pause();
            audioElement = null;
        }
    }

    return {
        isPlaying,
        currentTime,
        duration,
        load,
        play,
        pause,
        toggle,
        setTime,
        destroy,
    };
}
