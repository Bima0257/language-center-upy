<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import RichTextEditor from '@/Components/Shared/RichTextEditor.vue';
import UploadProgressBar from '@/Components/Shared/UploadProgressBar.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import UserGuide from '@/Components/Shared/UserGuide.vue';
import FileUpload from '@/Components/Shared/FileUpload.vue';
import OptionsInput from '@/Components/ContentLibrary/OptionsInput.vue';
import AnswerKeyPicker from '@/Components/ContentLibrary/AnswerKeyPicker.vue';
import { useUploadProgress } from '@/Composables/useUploadProgress';
import { IconPlus, IconTrash, IconFileDescription, IconInfoCircle, IconHeadphones, IconBooks } from '@tabler/icons-vue';
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    questionBanks: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
    preselectedBankId: { type: Number, default: null },
    returnFilters: { type: Object, default: () => ({}) },
});

const optionKeys = ['A', 'B', 'C', 'D'];

const materialTypeOptions = [
    { id: 'text', name: 'Teks (Reading)' },
    { id: 'audio', name: 'Audio + Gambar' },
];

const passageMode = ref('none');
const passageType = ref('text');
const questionRefs = ref([]);

const globalSkillId = ref('');
const globalPartId = ref('');
const globalMaterialType = ref('text');
const perQuestionSkill = ref(false);

if (props.skills.length === 1) {
    globalSkillId.value = String(props.skills[0].id);
}

function newQuestion() {
    return {
        skill_id: globalSkillId.value,
        skill_part_id: globalPartId.value,
        material_type: globalMaterialType.value,
        question_text: '',
        option_a: '',
        option_b: '',
        option_c: '',
        option_d: '',
        correct_answer: '',
        audio_file: null,
        image_file: null,
    };
}

const form = useForm({
    question_bank_id: props.preselectedBankId || '',
    new_passage_title: '',
    new_passage_type: 'text',
    new_passage_content_text: '',
    new_passage_audio_file: null,
    new_passage_image_file: null,
    _return_skill_id: props.returnFilters?.skill_id || '',
    _return_part_id: props.returnFilters?.part_id || '',
    _return_status: props.returnFilters?.status || '',
    _return_search: props.returnFilters?.search || '',
    questions: [newQuestion()],
});

const selectedBank = computed(() =>
    props.questionBanks.find(b => String(b.id) === String(form.question_bank_id)) || null,
);

const availableSkills = computed(() => {
    if (!selectedBank.value) return props.skills;
    return props.skills.filter(s => String(s.exam_type_id) === String(selectedBank.value.exam_type_id));
});

const passageTypeOptions = [
    { id: 'text', name: 'Teks' },
    { id: 'audio', name: 'Audio' },
    { id: 'image', name: 'Gambar' },
    { id: 'prompt', name: 'Prompt' },
];

function partsForSkill(skillId) {
    return props.parts.filter(p => String(p.skill_id) === String(skillId));
}

function globalParts() {
    return partsForSkill(globalSkillId.value);
}

watch(globalSkillId, (value) => {
    if (perQuestionSkill.value) return;
    globalPartId.value = '';
    for (const q of form.questions) {
        q.skill_id = value;
        q.skill_part_id = '';
    }
});

watch(globalPartId, (value) => {
    if (perQuestionSkill.value) return;
    for (const q of form.questions) {
        q.skill_part_id = value;
    }
});

watch(globalMaterialType, (type) => {
    if (perQuestionSkill.value) return;
    for (const q of form.questions) {
        q.material_type = type;
    }
});

watch(selectedBank, (bank) => {
    if (!bank) return;
    if (!availableSkills.value.some(s => String(s.id) === String(globalSkillId.value))) {
        globalSkillId.value = '';
    }
    if (perQuestionSkill.value) {
        for (const q of form.questions) {
            if (!props.skills.some(s => String(s.id) === String(q.skill_id) && String(s.exam_type_id) === String(bank.exam_type_id))) {
                q.skill_id = '';
                q.skill_part_id = '';
            }
        }
    }
});

function setPerQuestionMode() {
    perQuestionSkill.value = true;
}

function setGlobalMode() {
    perQuestionSkill.value = false;
    if (globalSkillId.value) {
        for (const q of form.questions) {
            q.skill_id = globalSkillId.value;
            q.skill_part_id = globalPartId.value;
        }
    }
}

function onQuestionSkillChange(q) {
    q.skill_part_id = '';
}

watch(passageMode, (mode) => {
    if (mode === 'new' && globalMaterialType.value === 'audio') {
        passageType.value = 'audio';
        form.new_passage_type = 'audio';
    }
});

async function addQuestion() {
    form.questions.push(newQuestion());
    await nextTick();
    const last = questionRefs.value[form.questions.length - 1];
    last?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function removeQuestion(index) {
    if (form.questions.length > 1) {
        form.questions.splice(index, 1);
    }
}

const canSubmit = computed(() => {
    if (!form.question_bank_id) return false;
    if (form.questions.length === 0) return false;
    if (passageMode.value === 'new' && !form.new_passage_title.trim()) return false;
    if (!perQuestionSkill.value && !globalSkillId.value) return false;
    if (!perQuestionSkill.value && !globalPartId.value) return false;
    if (passageMode.value === 'new' && globalMaterialType.value === 'audio' && !form.new_passage_audio_file) return false;
    return form.questions.every(q => {
        if (!q.skill_id || !q.skill_part_id || !q.correct_answer) return false;
        if (q.material_type === 'audio') {
            return !!(q.audio_file || (passageMode.value === 'new' && form.new_passage_audio_file && form.new_passage_type === 'audio'));
        }
        return q.question_text.trim() &&
            q.option_a.trim() && q.option_b.trim() &&
            q.option_c.trim() && q.option_d.trim();
    });
});

function submit() {
    form.post(route('content-library.store'));
}

const { showUploadProgress, uploadLabel, mediaType } = useUploadProgress(form, {
    audioField: 'new_passage_audio_file',
    imageField: 'new_passage_image_file',
});

const indexUrl = computed(() => {
    const p = {};
    if (form.question_bank_id) p.question_bank_id = form.question_bank_id;
    if (form._return_skill_id) p.skill_id = form._return_skill_id;
    if (form._return_part_id) p.part_id = form._return_part_id;
    if (form._return_status) p.status = form._return_status;
    if (form._return_search) p.search = form._return_search;
    return p;
});
</script>

<template>
    <Head title="Tambah Soal Baru" />
    <DashboardLayout title="Tambah Soal Baru">
        <Link :href="route('content-library.index', indexUrl)" class="inline-block text-secondary text-label-md font-medium hover:underline mb-6">← Kembali ke Content Library</Link>
        <div class="max-w-5xl mx-auto">
            <div class="bg-surface-white rounded-3xl p-8 shadow-soft border border-outline-variant/30">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-headline-md font-bold text-primary">Tambah Soal Baru</h2>
                    <UserGuide
                        title="Panduan Tambah Soal"
                        :steps="[
                            { title: 'Pilih Bank Soal', desc: 'Bank menentukan kategori tes dan filter skill.' },
                            { title: 'Pilih Skill & Part', desc: 'Skill & part sebagai kategori soal, tidak menentukan tipe materi.' },
                            { title: 'Pilih Tipe Materi', desc: 'Teks (Reading) atau Audio + Gambar — dipilih manual.' },
                            { title: 'Isi Materi (opsional)', desc: 'Materi soal bisa teks, audio, gambar, atau tanpa materi.' },
                            { title: 'Isi Soal & Kunci', desc: 'Soal standalone langsung diisi; kunci jawaban di bawah soal.' },
                        ]"
                        :rules="[
                            'Soal baru berstatus Draf dan direview admin.',
                            'Audio + Gambar: materi, soal & pilihan ada di audio — audio wajib.',
                            'Teks: teks soal + 4 pilihan jawaban wajib diisi.',
                        ]"
                    />
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <div v-if="preselectedBankId">
                                <label class="text-label-md font-medium text-primary block mb-1.5">Bank Soal</label>
                                <div class="flex items-center gap-2 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl">
                                    <IconBooks :size="18" class="text-secondary shrink-0" />
                                    <span class="text-body-md font-medium text-primary truncate">{{ selectedBank?.name }}</span>
                                    <span v-if="selectedBank?.exam_type" class="inline-block bg-pastel-blue/50 text-primary px-2.5 py-0.5 rounded-full text-label-md shrink-0">
                                        {{ selectedBank.exam_type.name }}
                                    </span>
                                </div>
                            </div>
                            <div v-else>
                                <DropDown
                                    v-model="form.question_bank_id"
                                    :options="questionBanks"
                                    label="Bank Soal *"
                                    placeholder="Pilih Bank Soal"
                                    :option-label="(b) => b.name + (b.exam_type ? ' (' + b.exam_type.name + ')' : '')"
                                    option-value="id"
                                />
                            </div>
                            <p v-if="form.errors.question_bank_id" class="text-error-red text-xs mt-1">{{ form.errors.question_bank_id }}</p>
                        </div>
                    </div>
                    <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                        <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                        Soal baru akan direview oleh admin sebelum bisa dipakai.
                    </p>

                    <!-- PENGATURAN SKILL, PART & TIPE MATERI BATCH -->
                    <div v-if="!perQuestionSkill" class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5 space-y-3">
                        <div class="flex items-start justify-between gap-4">
                            <div class="grid grid-cols-3 gap-4 flex-1">
                                <div>
                                    <DropDown
                                        v-model="globalSkillId"
                                        :options="availableSkills"
                                        label="Skill untuk semua soal *"
                                        placeholder="Pilih skill"
                                        option-label="name"
                                        option-value="id"
                                    />
                                </div>
                                <div>
                                    <DropDown
                                        v-model="globalPartId"
                                        :options="globalParts()"
                                        label="Part *"
                                        placeholder="Pilih part"
                                        option-label="name"
                                        option-value="id"
                                        :disabled="!globalSkillId"
                                    />
                                </div>
                                <div>
                                    <DropDown
                                        v-model="globalMaterialType"
                                        :options="materialTypeOptions"
                                        label="Tipe Materi *"
                                        placeholder="Pilih tipe materi"
                                        option-label="name"
                                        option-value="id"
                                    />
                                </div>
                            </div>
                            <button type="button" @click="setPerQuestionMode"
                                    class="shrink-0 text-secondary text-label-md font-medium hover:underline">Atur skill per soal</button>
                        </div>
                        <p class="text-label-md text-text-muted">Skill, part, dan tipe materi ini otomatis diterapkan ke semua soal di bawah.</p>
                    </div>
                    <div v-else class="flex justify-end">
                        <button type="button" @click="setGlobalMode"
                                class="text-secondary text-label-md font-medium hover:underline">Gunakan satu skill untuk semua soal</button>
                    </div>

                    <hr class="border-outline-variant/50" />

                    <!-- MODE PASSAGE -->
                    <div>
                        <p class="text-label-md font-medium text-primary mb-2">Materi Soal <span class="text-text-muted">(opsional — bisa tanpa materi soal)</span></p>
                        <div class="flex gap-3">
                            <label class="flex items-center gap-2 px-4 py-2.5 rounded-full border cursor-pointer transition-all"
                                   :class="passageMode === 'none' ? 'bg-surface-container-low border-secondary' : 'border-outline-variant hover:border-secondary'">
                                <input type="radio" value="none" v-model="passageMode" class="accent-secondary" />
                                <span class="text-label-md text-text-body">Tanpa Materi Soal</span>
                            </label>
                            <label class="flex items-center gap-2 px-4 py-2.5 rounded-full border cursor-pointer transition-all"
                                   :class="passageMode === 'new' ? 'bg-surface-container-low border-secondary' : 'border-outline-variant hover:border-secondary'">
                                <input type="radio" value="new" v-model="passageMode" class="accent-secondary" />
                                <span class="text-label-md text-text-body">Buat Materi Soal Baru</span>
                            </label>
                        </div>
                    </div>

                    <!-- FORM PASSAGE INLINE -->
                    <div v-if="passageMode === 'new'" class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5 space-y-4">
                        <div class="flex items-center gap-2">
                            <IconFileDescription :size="18" class="text-secondary" />
                            <p class="text-label-md font-semibold text-primary">Materi Soal Baru</p>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="text-label-md font-medium text-primary block mb-1.5">Judul Materi Soal <span class="text-error-red">*</span></label>
                                <input type="text" v-model="form.new_passage_title" required placeholder="Judul bacaan"
                                       class="w-full px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary" />
                                <p v-if="form.errors.new_passage_title" class="text-error-red text-xs mt-1">{{ form.errors.new_passage_title }}</p></div>
                            <div v-if="globalMaterialType !== 'audio'">
                                <DropDown
                                    v-model="passageType"
                                    :options="passageTypeOptions"
                                    label="Tipe Materi Soal *"
                                    option-label="name"
                                    option-value="id"
                                    @change="form.new_passage_type = $event"
                                />
                            </div>
                        </div>

                        <template v-if="globalMaterialType === 'audio'">
                            <FileUpload
                                v-model="form.new_passage_audio_file"
                                label="File Audio (mp3/wav/m4a, max 50MB)"
                                placeholder="Klik untuk upload audio"
                                accept=".mp3,.wav,.ogg,.m4a"
                                media-type="audio"
                                :required="true"
                                :error="form.errors.new_passage_audio_file"
                            />
                            <FileUpload
                                v-model="form.new_passage_image_file"
                                label="Gambar Pendukung (opsional)"
                                placeholder="Klik untuk upload gambar"
                                accept=".jpg,.jpeg,.png,.webp"
                                media-type="image"
                                :error="form.errors.new_passage_image_file"
                            />
                        </template>

                        <template v-else>
                            <div v-if="passageType === 'text' || passageType === 'prompt'">
                                <label class="text-label-md font-medium text-primary block mb-1.5">Konten Teks</label>
                                <RichTextEditor v-model="form.new_passage_content_text" placeholder="Tulis isi teks bacaan di sini..." />
                            </div>

                            <div v-else-if="passageType === 'audio'">
                                <FileUpload
                                    v-model="form.new_passage_audio_file"
                                    label="File Audio (mp3/wav/m4a, max 50MB)"
                                    placeholder="Klik untuk upload audio"
                                    accept=".mp3,.wav,.ogg,.m4a"
                                    media-type="audio"
                                    :error="form.errors.new_passage_audio_file"
                                />
                            </div>

                            <div v-else-if="passageType === 'image'">
                                <FileUpload
                                    v-model="form.new_passage_image_file"
                                    label="File Gambar (jpg/png/webp, max 20MB, otomatis dikompres)"
                                    placeholder="Klik untuk upload gambar"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    media-type="image"
                                    :error="form.errors.new_passage_image_file"
                                />
                            </div>
                        </template>
                    </div>

                    <!-- DAFTAR SOAL -->
                    <div class="flex items-center justify-between">
                        <p class="text-title-lg font-semibold text-primary">Soal</p>
                        <button type="button" @click="addQuestion"
                                class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline">
                            <IconPlus :size="18" /> Tambah Soal
                        </button>
                    </div>

                    <div v-for="(q, qi) in form.questions" :key="qi"
                         :ref="el => questionRefs[qi] = el"
                         class="border border-outline-variant/50 rounded-2xl p-5 space-y-4 relative">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <p class="text-label-md font-semibold text-primary">Soal {{ qi + 1 }}</p>
                                <span v-if="q.material_type === 'audio'" class="inline-flex items-center gap-1 bg-pastel-purple/30 text-primary px-2.5 py-0.5 rounded-full text-label-md font-medium">
                                    <IconHeadphones :size="14" /> Audio
                                </span>
                            </div>
                            <button v-if="form.questions.length > 1" type="button" @click="removeQuestion(qi)"
                                    class="p-1.5 text-text-muted hover:text-error-red transition-colors" title="Hapus soal">
                                <IconTrash :size="16" />
                            </button>
                        </div>

                        <!-- SKILL, PART & TIPE MATERI (hanya mode per-soal) -->
                        <div v-if="perQuestionSkill" class="grid grid-cols-3 gap-4">
                            <div>
                                <DropDown
                                    v-model="q.skill_id"
                                    :options="availableSkills"
                                    label="Skill *"
                                    placeholder="Pilih Skill"
                                    option-label="name"
                                    option-value="id"
                                    @change="onQuestionSkillChange(q)"
                                />
                            </div>
                            <div>
                                <DropDown
                                    v-model="q.skill_part_id"
                                    :options="partsForSkill(q.skill_id)"
                                    label="Part *"
                                    placeholder="Pilih part"
                                    option-label="name"
                                    option-value="id"
                                    :disabled="!q.skill_id"
                                />
                            </div>
                            <div>
                                <DropDown
                                    v-model="q.material_type"
                                    :options="materialTypeOptions"
                                    label="Tipe Materi *"
                                    placeholder="Pilih tipe materi"
                                    option-label="name"
                                    option-value="id"
                                />
                            </div>
                        </div>

                        <!-- MODE AUDIO + GAMBAR: tanpa teks soal/opsi -->
                        <template v-if="q.material_type === 'audio'">
                            <div class="bg-pastel-purple/10 border border-pastel-purple/40 rounded-2xl p-4 space-y-4">
                                <p class="flex items-center gap-1.5 text-label-md text-text-muted">
                                    <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                                    Soal, materi, dan pilihan jawaban berada di audio. Peserta hanya memilih A/B/C/D.
                                </p>
                                <div v-if="!passageMode || passageMode === 'none'" class="grid grid-cols-2 gap-4">
                                    <FileUpload
                                        v-model="q.audio_file"
                                        label="File Audio (max 50MB)"
                                        placeholder="Klik untuk upload audio"
                                        accept=".mp3,.wav,.ogg,.m4a"
                                        media-type="audio"
                                        :required="true"
                                    />
                                    <FileUpload
                                        v-model="q.image_file"
                                        label="Gambar (opsional)"
                                        placeholder="Klik untuk upload gambar"
                                        accept=".jpg,.jpeg,.png,.webp"
                                        media-type="image"
                                    />
                                </div>
                                <div v-else class="flex items-center gap-2 text-label-md text-text-muted">
                                    <IconHeadphones :size="16" class="text-secondary shrink-0" />
                                    Audio diambil dari passage di atas — sub-soal tidak perlu upload audio sendiri.
                                </div>
                                <AnswerKeyPicker v-model="q.correct_answer" :option-keys="optionKeys" label="Pilihan Jawaban" />
                            </div>
                        </template>

                        <!-- MODE READING: teks soal + opsi -->
                        <template v-else>
                            <div><label class="text-label-md font-medium text-primary block mb-1.5">Teks Soal <span class="text-error-red">*</span></label>
                                <BaseTextarea v-model="q.question_text" rows="2" required /></div>

                            <OptionsInput :form="q" :option-keys="optionKeys" />
                            <div>
                                <DropDown
                                    v-model="q.correct_answer"
                                    :options="optionKeys.map(k => ({ id: k, name: k }))"
                                    label="Kunci Jawaban *"
                                    placeholder="Pilih jawaban benar"
                                    option-label="name"
                                    option-value="id"
                                />
                            </div>
                        </template>
                    </div>

                    <hr class="border-outline-variant/50" />
                    <div class="flex gap-4">
                        <BaseButton type="submit" :disabled="form.processing || !canSubmit" size="xl" class="flex-1">
                            {{ form.processing ? 'Menyimpan...' : `Simpan ${form.questions.length} Soal` }}
                        </BaseButton>
                        <BaseButton :href="route('content-library.index', indexUrl)" variant="secondary" size="lg">Batal</BaseButton>
                    </div>
                </form>
            </div>
        </div>
        <UploadProgressBar :show="showUploadProgress" :label="uploadLabel" :media-type="mediaType" />
    </DashboardLayout>
</template>
