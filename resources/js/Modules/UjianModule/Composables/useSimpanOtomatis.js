import { ref } from 'vue';
import axios from 'axios';

export function useSimpanOtomatis({ sesiUjianId, debounceMs = 500 } = {}) {
    const lastSaved = ref(null);
    const isSaving = ref(false);
    const pending = new Map();
    const inFlight = new Set();
    let debounceTimer = null;

    function kirim(soalId, answerText) {
        const request = axios.post(`/ujian/sesi/${sesiUjianId}/jawaban`, {
            soal_id: soalId,
            teks_jawaban: answerText,
        }).then(() => {
            lastSaved.value = new Date().toLocaleTimeString('id-ID');
        }).catch(() => {});

        inFlight.add(request);
        request.finally(() => inFlight.delete(request));
    }

    function save(soalId, answerText = null) {
        pending.set(soalId, answerText);

        if (debounceTimer) clearTimeout(debounceTimer);

        debounceTimer = setTimeout(() => {
            debounceTimer = null;
            flush();
        }, debounceMs);
    }

    async function flush() {
        if (debounceTimer) {
            clearTimeout(debounceTimer);
            debounceTimer = null;
        }

        while (pending.size > 0 || inFlight.size > 0) {
            if (pending.size > 0) {
                const entries = [...pending.entries()];
                pending.clear();
                isSaving.value = true;

                for (const [soalId, answerText] of entries) {
                    kirim(soalId, answerText);
                }
            }

            if (inFlight.size > 0) {
                await Promise.all([...inFlight]);
            }
        }

        isSaving.value = false;
    }

    function saveImmediate(soalId, answerText = null) {
        save(soalId, answerText);

        return flush();
    }

    return {
        lastSaved,
        isSaving,
        save,
        saveImmediate,
        flush,
    };
}
