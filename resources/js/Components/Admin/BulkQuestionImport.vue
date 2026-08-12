<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';

const emit = defineEmits(['saved']);

const importMode = ref('json');

const jsonForm = useForm({ questions: '' });
const preview = ref([]);

function parsePreview() {
    try {
        preview.value = JSON.parse(jsonForm.questions);
    } catch {
        preview.value = [];
    }
}

function submitJson() {
    jsonForm.post(route('admin.exams.questions.bulk'), {
        preserveScroll: true,
        onSuccess: () => { jsonForm.questions = ''; preview.value = []; emit('saved'); },
    });
}

const fileForm = useForm({ file: null });
const fileInput = ref(null);

function onFileSelect(e) {
    fileForm.file = e.target.files[0];
}

function submitFile() {
    fileForm.post(route('admin.exams.questions.import'), {
        preserveScroll: true,
        onSuccess: () => { fileForm.file = null; emit('saved'); },
    });
}
</script>

<template>
    <div class="space-y-4">
        <div class="flex gap-2 mb-2">
            <button @click="importMode = 'json'"
                    class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                    :class="importMode === 'json' ? 'bg-primary-container text-white' : 'bg-surface-container-low text-text-body hover:bg-surface-container'">
                Import JSON
            </button>
            <button @click="importMode = 'file'"
                    class="px-4 py-2 rounded-full text-label-md font-medium transition-all"
                    :class="importMode === 'file' ? 'bg-primary-container text-white' : 'bg-surface-container-low text-text-body hover:bg-surface-container'">
                Upload File (CSV/XLSX)
            </button>
        </div>

        <div v-if="importMode === 'json'" class="space-y-3">
            <BaseTextarea v-model="jsonForm.questions" rows="6"
                      placeholder='[{"question_bank_id":1,"skill_id":1,"question_text":"...","option_a":"...","option_b":"...","option_c":"...","option_d":"...","correct_answer":"A"}]'
                      @input="parsePreview"
                      class="font-mono text-sm" />
            <p class="text-text-muted text-label-md">Tipe soal otomatis: multiple_choice</p>
            <p v-if="preview.length" class="text-text-muted text-label-md">{{ preview.length }} soal akan diimpor.</p>
            <BaseButton @click="submitJson" :disabled="jsonForm.processing || !jsonForm.questions">
                {{ jsonForm.processing ? 'Mengimpor...' : 'Import JSON' }}
            </BaseButton>
        </div>

        <div v-else class="space-y-3">
            <div class="border-2 border-dashed border-outline-variant rounded-2xl p-6 text-center hover:border-primary transition-colors cursor-pointer"
                 @click="$refs.fileInput.click()">
                <p v-if="!fileForm.file" class="text-text-muted text-text-body text-body-md">Klik untuk upload file CSV atau Excel</p>
                <p v-else class="text-primary text-text-body text-body-md font-medium">{{ fileForm.file.name }}</p>
                <p class="text-text-muted text-label-md mt-1">Format: .xlsx, .xls, .csv — Maks 10MB</p>
                <input ref="fileInput" type="file" accept=".xlsx,.xls,.csv" class="hidden" @change="onFileSelect" />
            </div>
            <div class="bg-surface-container-low rounded-2xl p-4 text-label-md text-text-body">
                <p class="font-medium text-primary mb-1">Format file:</p>
                <code class="text-xs">
question_bank_id,skill_id,question_text,option_a,option_b,option_c,option_d,correct_answer<br />
1,1,"What is...","London","Paris","Berlin","Madrid","B"
                </code>
            </div>
            <button @click="submitFile" :disabled="fileForm.processing || !fileForm.file"
                    class="bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all active:scale-95 disabled:opacity-50">
                {{ fileForm.processing ? 'Mengunggah...' : 'Upload & Import' }}
            </button>
        </div>
    </div>
</template>
