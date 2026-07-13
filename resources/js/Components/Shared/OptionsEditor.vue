<script setup>
import { ref, watch, computed } from 'vue';
import { IconPlus, IconX, IconArrowUp, IconArrowDown } from '@tabler/icons-vue';

const props = defineProps({
    modelValue: { type: [String, Array], default: '' },
    type: { type: String, default: 'multiple_choice' },
});

const emit = defineEmits(['update:modelValue']);

const options = ref([]);

const needsOptions = computed(() => {
    return ['multiple_choice', 'multi_select', 'order', 'matching', 'error_id'].includes(props.type);
});

const isReadonly = computed(() => props.type === 'true_false');

function getDefaultOptions() {
    switch (props.type) {
        case 'true_false':
            return [
                { key: 'A', text: 'True' },
                { key: 'B', text: 'False' },
                { key: 'C', text: 'Not Given' },
            ];
        case 'error_id':
            return [
                { key: 'A', text: '' },
                { key: 'B', text: '' },
                { key: 'C', text: '' },
                { key: 'D', text: '' },
            ];
        case 'order':
            return [
                { key: '1', text: '' },
                { key: '2', text: '' },
                { key: '3', text: '' },
                { key: '4', text: '' },
            ];
        case 'matching':
            return [
                { left: '', right: '' },
                { left: '', right: '' },
                { left: '', right: '' },
                { left: '', right: '' },
            ];
        default:
            return [
                { key: 'A', text: '' },
                { key: 'B', text: '' },
                { key: 'C', text: '' },
                { key: 'D', text: '' },
            ];
    }
}

function parseOptions(val) {
    if (Array.isArray(val) && val.length) return val;
    if (typeof val === 'string' && val) {
        try {
            const parsed = JSON.parse(val);
            if (Array.isArray(parsed)) return parsed;
        } catch {}
    }
    return null;
}

function initOptions() {
    const parsed = parseOptions(props.modelValue);
    if (parsed && props.type !== 'true_false') {
        options.value = parsed;
        return;
    }
    options.value = getDefaultOptions();
}

function generateKeys() {
    if (['multiple_choice', 'multi_select', 'error_id'].includes(props.type)) {
        options.value.forEach((opt, i) => { opt.key = String.fromCharCode(65 + i); });
    }
}

function addOption() {
    if (props.type === 'matching') {
        options.value.push({ left: '', right: '' });
    } else if (props.type === 'order') {
        const nextKey = String(options.value.length + 1);
        options.value.push({ key: nextKey, text: '' });
    } else {
        const nextKey = String.fromCharCode(65 + options.value.length);
        options.value.push({ key: nextKey, text: '' });
    }
    emitChange();
}

function removeOption(index) {
    if (options.value.length <= 2) return;
    options.value.splice(index, 1);
    if (['multiple_choice', 'multi_select'].includes(props.type)) generateKeys();
    else if (props.type === 'order') {
        options.value.forEach((o, i) => { o.key = String(i + 1); });
    }
    emitChange();
}

function moveUp(index) {
    if (index === 0 || props.type !== 'order') return;
    [options.value[index - 1], options.value[index]] = [options.value[index], options.value[index - 1]];
    options.value.forEach((o, i) => { o.key = String(i + 1); });
    emitChange();
}

function moveDown(index) {
    if (index >= options.value.length - 1 || props.type !== 'order') return;
    [options.value[index], options.value[index + 1]] = [options.value[index + 1], options.value[index]];
    options.value.forEach((o, i) => { o.key = String(i + 1); });
    emitChange();
}

function hasTextFields() {
    if (props.type === 'true_false') return false;
    return options.value.some(o => {
        if (props.type === 'matching') return o.left?.trim() || o.right?.trim();
        return o.text?.trim();
    });
}

function emitChange() {
    if (!needsOptions.value || isReadonly.value) return;
    if (!hasTextFields()) {
        emit('update:modelValue', '');
        return;
    }
    let output;
    if (props.type === 'matching') {
        output = options.value.map(o => ({ left: o.left, right: o.right }));
    } else if (props.type === 'order') {
        output = options.value.map(o => ({ key: o.key, text: o.text }));
    } else {
        output = options.value.map(o => ({ key: o.key, text: o.text }));
    }
    emit('update:modelValue', JSON.stringify(output));
}

initOptions();

watch(() => props.type, () => { initOptions(); emitChange(); });
watch(() => props.modelValue, (val) => {
    const parsed = parseOptions(val);
    if (parsed) options.value = parsed;
    else if (!val) initOptions();
});
</script>

<template>
    <div v-if="needsOptions">
        <!-- MATCHING: left-right pairs -->
        <template v-if="type === 'matching'">
            <label class="text-label-md font-medium text-primary block mb-1.5">Pasangan (Kiri → Kanan)</label>
            <div v-for="(opt, i) in options" :key="i"
                 class="flex items-center gap-2 mb-2">
                <input type="text" v-model="opt.left" @input="emitChange"
                       placeholder="Kiri" class="flex-1 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                <span class="text-text-muted">→</span>
                <input type="text" v-model="opt.right" @input="emitChange"
                       placeholder="Kanan" class="flex-1 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                <button v-if="options.length > 2" @click="removeOption(i)"
                        class="p-2 text-text-muted hover:text-error-red transition-colors shrink-0">
                    <IconX :size="18" />
                </button>
            </div>
            <button @click="addOption"
                    class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline mt-1">
                <IconPlus :size="16" /> Tambah Pasangan
            </button>
        </template>

        <!-- ORDER: sortable items -->
        <template v-else-if="type === 'order'">
            <label class="text-label-md font-medium text-primary block mb-1.5">Item (atur urutan)</label>
            <div v-for="(opt, i) in options" :key="i"
                 class="flex items-center gap-2 mb-2">
                <div class="flex flex-col gap-0.5">
                    <button @click="moveUp(i)" :disabled="i === 0"
                            class="p-0.5 text-text-muted hover:text-primary disabled:opacity-30">
                        <IconArrowUp :size="14" />
                    </button>
                    <button @click="moveDown(i)" :disabled="i >= options.length - 1"
                            class="p-0.5 text-text-muted hover:text-primary disabled:opacity-30">
                        <IconArrowDown :size="14" />
                    </button>
                </div>
                <span class="w-6 text-center text-label-md font-bold text-primary shrink-0">{{ i + 1 }}</span>
                <input type="text" v-model="opt.text" @input="emitChange"
                       :placeholder="'Item ' + (i + 1)"
                       class="flex-1 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
                <button v-if="options.length > 2" @click="removeOption(i)"
                        class="p-2 text-text-muted hover:text-error-red transition-colors shrink-0">
                    <IconX :size="18" />
                </button>
            </div>
            <button @click="addOption"
                    class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline mt-1">
                <IconPlus :size="16" /> Tambah Item
            </button>
        </template>

        <!-- ERROR ID: 4 sentence parts -->
        <template v-else-if="type === 'error_id'">
            <label class="text-label-md font-medium text-primary block mb-1.5">Bagian Kalimat (A/B/C/D)</label>
            <p class="text-text-muted text-label-md mb-2">Tulis setiap bagian kalimat di kolom masing-masing. Satu bagian mengandung error.</p>
            <div v-for="(opt, i) in options" :key="i"
                 class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-primary-container text-white font-bold text-label-md shrink-0">{{ opt.key }}</span>
                <input type="text" v-model="opt.text" @input="emitChange"
                       :placeholder="'Bagian ' + opt.key"
                       class="flex-1 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary" />
            </div>
        </template>

        <!-- MULTIPLE CHOICE / MULTI SELECT: A/B/C/D -->
        <template v-else>
            <label class="text-label-md font-medium text-primary block mb-1.5">
                {{ type === 'multi_select' ? 'Pilihan (pilih lebih dari satu)' : 'Pilihan Jawaban' }}
            </label>
            <div v-for="(opt, i) in options" :key="i"
                 class="flex items-center gap-2 mb-2">
                <span class="inline-flex items-center justify-center w-10 h-10 rounded-xl bg-primary-container text-white font-bold text-label-md shrink-0">
                    {{ opt.key }}
                </span>
                <input type="text" v-model="opt.text" @input="emitChange"
                       :placeholder="'Pilihan ' + opt.key"
                       class="flex-1 px-4 py-3 bg-surface-container-lowest border border-outline-variant rounded-2xl text-body-md focus:outline-none focus:border-secondary placeholder:text-text-muted" />
                <button v-if="options.length > 2" @click="removeOption(i)"
                        class="p-2 text-text-muted hover:text-error-red transition-colors shrink-0">
                    <IconX :size="18" />
                </button>
            </div>
            <button @click="addOption"
                    class="flex items-center gap-1.5 text-secondary text-label-md font-medium hover:underline mt-1">
                <IconPlus :size="16" /> Tambah Opsi
            </button>
        </template>
    </div>

    <!-- TRUE/FALSE: fixed display only -->
    <div v-else-if="isReadonly" class="bg-surface-container-low rounded-2xl p-4">
        <label class="text-label-md font-medium text-primary block mb-1.5">Pilihan Jawaban</label>
        <div class="flex flex-wrap gap-3">
            <span class="inline-flex items-center px-4 py-2 bg-surface-white rounded-xl border border-outline-variant text-label-md font-medium">True</span>
            <span class="inline-flex items-center px-4 py-2 bg-surface-white rounded-xl border border-outline-variant text-label-md font-medium">False</span>
            <span class="inline-flex items-center px-4 py-2 bg-surface-white rounded-xl border border-outline-variant text-label-md font-medium">Not Given</span>
        </div>
    </div>
</template>
