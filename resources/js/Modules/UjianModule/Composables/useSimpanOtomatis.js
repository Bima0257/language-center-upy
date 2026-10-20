import { ref } from 'vue';
import axios from 'axios';

export function useSimpanOtomatis({ sesiUjianId, debounceMs = 500 } = {}) {
    const lastSaved = ref(null);
    const isSaving = ref(false);
    let pendingTimer = null;

    function save(soalId, answerText = null) {
        if (pendingTimer) clearTimeout(pendingTimer);

        pendingTimer = setTimeout(() => {
            isSaving.value = true;
            axios.post(`/ujian/sesi/${sesiUjianId}/simpan-jawaban`, {
                soal_id: soalId,
                teks_jawaban: answerText,
            }).then(() => {
                lastSaved.value = new Date().toLocaleTimeString('id-ID');
                isSaving.value = false;
            }).catch(() => {
                isSaving.value = false;
            });
        }, debounceMs);
    }

    function saveImmediate(soalId, answerText = null) {
        if (pendingTimer) clearTimeout(pendingTimer);
        isSaving.value = true;
        axios.post(`/ujian/sesi/${sesiUjianId}/simpan-jawaban`, {
            soal_id: soalId,
            teks_jawaban: answerText,
        }).then(() => {
            lastSaved.value = new Date().toLocaleTimeString('id-ID');
            isSaving.value = false;
        }).catch(() => {
            isSaving.value = false;
        });
    }

    return {
        lastSaved,
        isSaving,
        save,
        saveImmediate,
    };
}
