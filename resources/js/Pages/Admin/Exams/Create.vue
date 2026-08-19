<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import DropDown from '@/Components/Shared/DropDown.vue';
import { IconInfoCircle, IconBooks, IconListDetails } from '@tabler/icons-vue';
import { computed } from 'vue';

const props = defineProps({
    examTypes: { type: Array, default: () => [] },
    skillOptions: { type: Array, default: () => [] },
    parts: { type: Array, default: () => [] },
    banksBySkill: { type: Array, default: () => [] },
});

const form = useForm({
    exam_type_id: '',
    title: '',
    description: '',
    mode: 'tryout',
    duration_minutes: 160,
});

const selectedTypeBanks = computed(() => {
    const typeId = String(form.exam_type_id);
    if (!typeId) return [];

    return props.banksBySkill
        .map((group) => ({
            skill: group.skill,
            label: group.label,
            banks: (group.banks || []).filter((b) => String(b.exam_type_id) === typeId),
        }))
        .filter((group) => group.banks.length > 0);
});

function partsFor(group, bank) {
    return props.parts.filter((p) =>
        String(p.question_bank_id) === String(bank.id) &&
        String(p.skill) === String(group.skill),
    );
}

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

                    <div v-if="form.exam_type_id" class="bg-pastel-blue/10 border border-pastel-blue/40 rounded-2xl p-5">
                        <p class="flex items-center gap-1.5 text-label-md font-semibold text-primary mb-3">
                            <IconInfoCircle :size="16" class="text-secondary shrink-0" />
                            Section otomatis akan dibuat per skill dan per bank soal:
                        </p>
                        <p v-if="selectedTypeBanks.length === 0" class="text-label-md text-text-muted">
                            Belum ada bank soal dengan soal approved untuk jenis tes ini.
                        </p>
                        <div v-else class="space-y-3">
                            <div v-for="group in selectedTypeBanks" :key="group.skill"
                                 class="bg-surface-white rounded-xl px-4 py-3 border border-outline-variant/40">
                                <p class="flex items-center gap-2 text-body-md font-semibold text-primary mb-2">
                                    <IconBooks :size="18" class="text-secondary shrink-0" />
                                    {{ group.label }}
                                </p>
                                <div class="space-y-1.5">
                                    <div v-for="bank in group.banks" :key="bank.id"
                                         class="flex items-center gap-2 text-label-md text-text-body">
                                        <IconListDetails :size="16" class="text-text-muted shrink-0" />
                                        <span class="font-medium text-primary">{{ bank.name }}</span>
                                        <span class="text-text-muted">— {{ partsFor(group, bank).length || 0 }} part</span>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                    <BaseButton type="submit" :disabled="form.processing" size="xl" class="w-full">
                        {{ form.processing ? 'Menyimpan...' : 'Buat Soal Ujian' }}
                    </BaseButton>
                </form>
            </BaseCard>
        </div>
    </DashboardLayout>
</template>
