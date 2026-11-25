<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { VueDatePicker } from '@vuepic/vue-datepicker';
import { id as localeId } from 'date-fns/locale';
import { IconCalendarEvent } from '@tabler/icons-vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    min: { type: String, default: '' },
    max: { type: String, default: '' },
    placeholder: { type: String, default: 'Pilih tanggal' },
    error: { type: [String, Boolean], default: null },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    inputId: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const root = ref(null);
const teleportTarget = ref(true);
const isDark = ref(false);

function parseDate(value) {
    if (!value || typeof value !== 'string') return null;

    const match = value.trim().replace(' ', 'T').match(/^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}):(\d{2}))?/);

    if (!match) return null;

    const [, year, month, day, hour = '0', minute = '0'] = match;
    const date = new Date(
        Number(year),
        Number(month) - 1,
        Number(day),
        Number(hour),
        Number(minute),
    );

    return Number.isNaN(date.getTime()) ? null : date;
}

function formatDate(value) {
    if (!(value instanceof Date) || Number.isNaN(value.getTime())) return '';

    const pad = number => String(number).padStart(2, '0');

    return `${value.getFullYear()}-${pad(value.getMonth() + 1)}-${pad(value.getDate())}`;
}

function syncTheme() {
    isDark.value = document.documentElement.classList.contains('dark');
}

const internalValue = computed({
    get: () => parseDate(props.modelValue),
    set: value => emit('update:modelValue', formatDate(value)),
});

const minDate = computed(() => parseDate(props.min));
const maxDate = computed(() => parseDate(props.max));

onMounted(() => {
    syncTheme();
    document.addEventListener('dashboard-theme-changed', syncTheme);

    teleportTarget.value = root.value?.closest('dialog') ?? true;
});

onUnmounted(() => {
    document.removeEventListener('dashboard-theme-changed', syncTheme);
});
</script>

<template>
    <div
        ref="root"
        class="base-datepicker"
        :class="{ 'base-datepicker--error': error }"
    >
        <VueDatePicker
            v-model="internalValue"
            :locale="localeId"
            :formats="{ input: 'dd/MM/yyyy' }"
            :time-config="{ enableTimePicker: false }"
            auto-apply
            :min-date="minDate"
            :max-date="maxDate"
            :placeholder="placeholder"
            :disabled="disabled"
            :dark="isDark"
            :teleport="teleportTarget"
            :config="{ allowPreventDefault: true }"
            :floating="{ strategy: 'fixed' }"
            :input-attrs="{ id: inputId ?? undefined, required, clearable: false }"
            :ui="{ input: 'base-datepicker-input', menu: 'base-datepicker-menu' }"
        >
            <template #input-icon>
                <IconCalendarEvent class="base-datepicker-icon" :size="18" />
            </template>
        </VueDatePicker>
        <p v-if="error && typeof error === 'string'" class="mt-1 text-xs text-error-red">
            {{ error }}
        </p>
    </div>
</template>
