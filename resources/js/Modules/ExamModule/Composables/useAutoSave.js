import { ref } from 'vue';
import axios from 'axios';

export function useAutoSave({ sessionId, debounceMs = 500 } = {}) {
    const lastSaved = ref(null);
    const isSaving = ref(false);
    let pendingTimer = null;

    function save(questionId, answerText = null, answerJson = null) {
        if (pendingTimer) clearTimeout(pendingTimer);

        pendingTimer = setTimeout(() => {
            isSaving.value = true;
            axios.post(`/exam/session/${sessionId}/answer`, {
                question_id: questionId,
                answer_text: answerText,
                answer_json: answerJson,
            }).then(() => {
                lastSaved.value = new Date().toLocaleTimeString('id-ID');
                isSaving.value = false;
            }).catch(() => {
                isSaving.value = false;
            });
        }, debounceMs);
    }

    function saveImmediate(questionId, answerText = null, answerJson = null) {
        if (pendingTimer) clearTimeout(pendingTimer);
        isSaving.value = true;
        axios.post(`/exam/session/${sessionId}/answer`, {
            question_id: questionId,
            answer_text: answerText,
            answer_json: answerJson,
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
