<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { computed, watch } from 'vue';

const props = defineProps({
    examTypes: { type: Array, default: () => [] },
    questionBanks: { type: Array, default: () => [] },
});

const form = useForm({
    exam_type_id: '',
    question_bank_id: '',
    title: '',
    description: '',
    mode: 'tryout',
    duration_minutes: 160,
});

const filteredBanks = computed(() => {
    if (!form.exam_type_id) return [];
    return props.questionBanks.filter(
        (b) => String(b.exam_type_id) === String(form.exam_type_id) && b.is_active,
    );
});

watch(() => form.exam_type_id, () => {
    form.question_bank_id = '';
});

function submit() {
    form.post(route('admin.exams.store'));
}
</script>

<template>
    <Head title="Buat Soal Ujian" />
    <DashboardLayout title="Buat Soal Ujian">
        <div class="max-w-2xl mx-auto">
            <BaseCard padding="p-8">
                <form @submit.prevent="submit" class="space-y-6">
                    <div>
                        <DropDown
                            v-model="form.exam_type_id"
                            :options="examTypes"
                            label="Jenis Tes"
                            placeholder="Pilih jenis tes"
                            option-label="name"
                            option-value="id"
                        />
                        <p v-if="form.errors.exam_type_id" class="text-error-red text-xs mt-1">{{ form.errors.exam_type_id }}</p>
                    </div>

                    <div v-if="form.exam_type_id">
                        <DropDown
                            v-model="form.question_bank_id"
                            :options="filteredBanks"
                            label="Bank Soal *"
                            placeholder="Pilih bank soal"
                            option-label="name"
                            option-value="id"
                            empty-message="Belum ada bank soal aktif untuk jenis tes ini."
                        />
                        <p v-if="form.errors.question_bank_id" class="text-error-red text-xs mt-1">{{ form.errors.question_bank_id }}</p>
                    </div>

                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Judul Soal Ujian</label>
                        <input type="text" v-model="form.title" required
                               placeholder="Tes TOEFL 2026"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
                        <p v-if="form.errors.title" class="text-error-red text-xs mt-1">{{ form.errors.title }}</p>
                    </div>
                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Deskripsi</label>
                        <BaseTextarea v-model="form.description" rows="3"
                                      placeholder="Deskripsi ujian..." />
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <DropDown
                                v-model="form.mode"
                                :options="[
                                    { id: 'tryout', name: 'Try Out' },
                                    { id: 'official', name: 'Ujian Resmi' },
                                ]"
                                label="Tipe"
                                option-label="name"
                                option-value="id"
                            />
                        </div>
                        <div>
                            <label class="text-label-md font-medium text-primary block mb-2">Durasi (menit)</label>
                            <input type="number" v-model="form.duration_minutes" required min="1"
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-text-body text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
                        </div>
                    </div>
                    <BaseButton type="submit" :disabled="form.processing || !form.question_bank_id" size="xl" class="w-full">
                        {{ form.processing ? 'Menyimpan...' : 'Buat Soal Ujian' }}
                    </BaseButton>
                </form>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
