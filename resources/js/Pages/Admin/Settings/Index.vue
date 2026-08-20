<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Components/Dashboard/DashboardLayout.vue';
import { ref } from 'vue';

const props = defineProps({
    settings: { type: Object, default: () => ({}) },
    groups: { type: Array, default: () => [] },
});

const groupLabels = {
    general: 'Umum',
    exam: 'Ujian',
    scoring: 'Penilaian',
    certificate: 'Sertifikat',
};

const groupDescriptions = {
    general: 'Pengaturan umum aplikasi',
    exam: 'Pengaturan default untuk ujian',
    scoring: 'Pengaturan sistem penilaian',
    certificate: 'Pengaturan sertifikat',
};

function buildForms() {
    const forms = {};
    for (const group of props.groups) {
        const items = props.settings[group] || [];
        forms[group] = useForm({
            values: items.map(s => ({ key: s.key, value: s.value })),
        });
    }
    return forms;
}

const forms = buildForms();
const activeGroup = ref(props.groups[0] || 'general');

function saveGroup(group) {
    forms[group].patch(route('admin.settings.update'), {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Pengaturan" />
    <DashboardLayout title="Pengaturan">
        <div class="flex flex-col md:flex-row gap-6">
            <div class="w-full md:w-56 shrink-0">
                <nav class="space-y-1">
                    <button
                        v-for="group in groups"
                        :key="group"
                        @click="activeGroup = group"
                        class="w-full text-left px-4 py-3 rounded-2xl text-label-md font-medium transition-colors"
                        :class="activeGroup === group ? 'bg-primary-container text-white' : 'text-text-body hover:bg-surface-container-low'"
                    >
                        {{ groupLabels[group] || group }}
                    </button>
                </nav>
            </div>

            <div class="flex-1">
                <BaseCard v-for="group in groups" :key="group" v-show="activeGroup === group">
                    <h2 class="text-title-lg font-bold text-primary mb-1">{{ groupLabels[group] || group }}</h2>
                    <p class="text-text-muted text-body-md mb-6">{{ groupDescriptions[group] || '' }}</p>

                    <form @submit.prevent="saveGroup(group)" class="space-y-4">
                        <div v-for="item in (settings[group] || [])" :key="item.key">
                            <label class="text-label-md font-medium text-primary block mb-1.5">
                                {{ item.key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase()) }}
                            </label>
                            <input
                                type="text"
                                v-model="item.value"
                                class="w-full px-4 py-3.5 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary"
                            />
                        </div>
                        <div class="flex justify-end pt-2">
                            <BaseButton type="submit" :disabled="forms[group].processing" size="lg">
                                {{ forms[group].processing ? 'Menyimpan...' : 'Simpan Pengaturan' }}
                            </BaseButton>
                        </div>
                    </form>
                </BaseCard>
            </div>
        </div>
    </DashboardLayout>
</template>
