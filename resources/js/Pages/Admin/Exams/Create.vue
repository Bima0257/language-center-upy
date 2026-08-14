<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconInfoCircle, IconBooks, IconListDetails } from '@tabler/icons-vue';
import { computed } from 'vue';

const props = defineProps({
    examTypes: { type: Array, default: () => [] },
    skills: { type: Array, default: () => [] },
});

const form = useForm({
    exam_type_id: '',
    title: '',
    description: '',
    mode: 'tryout',
    duration_minutes: 160,
});

const typeSkills = computed(() =>
    props.skills.filter(s => String(s.exam_type_id) === String(form.exam_type_id)),
);

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

                    <div v-if="typeSkills.length" class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5">
                        <p class="flex items-center gap-1.5 text-label-md font-semibold text-primary mb-3">
                            <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                            Section otomatis akan dibuat dari skill:
                        </p>
                        <div class="space-y-2">
                            <div v-for="s in typeSkills" :key="s.id"
                                 class="flex items-center gap-3 bg-surface-white rounded-xl px-4 py-3 border border-outline-variant/40">
                                <IconBooks :size="18" class="text-secondary shrink-0" />
                                <div class="flex-1">
                                    <p class="text-body-md font-semibold text-primary">{{ s.name }}</p>
                                    <p class="text-label-md text-text-muted">
                                        {{ s.skill_parts?.length || 0 }} part:
                                        {{ (s.skill_parts || []).map(p => p.name).join(', ') || '-' }}
                                    </p>
                                </div>
                                <IconListDetails :size="18" class="text-text-muted shrink-0" />
                            </div>
                        </div>
                    </div>
                    <div v-else-if="form.exam_type_id" class="bg-surface-container-low rounded-2xl p-5 text-center">
                        <p class="text-text-muted text-body-md">Belum ada skill aktif untuk jenis tes ini.</p>
                    </div>

                    <div>
                        <label class="text-label-md font-medium text-primary block mb-2">Judul Soal Ujian</label>
                        <input type="text" v-model="form.title" required
                               placeholder="Tes TOEFL 2026"
                               class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
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
                                   class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary focus:shadow-[0_0_0_2px_rgba(86,71,200,0.1)]" />
                        </div>
                    </div>
                    <BaseButton type="submit" :disabled="form.processing" size="xl" class="w-full">
                        {{ form.processing ? 'Menyimpan...' : 'Buat Soal Ujian' }}
                    </BaseButton>
                </form>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
