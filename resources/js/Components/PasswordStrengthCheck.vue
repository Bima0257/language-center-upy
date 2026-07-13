<script setup>
import { IconCheck, IconX } from '@tabler/icons-vue';

defineProps({
    password: {
        type: String,
        default: '',
    },
});

const rules = [
    {
        key: 'length',
        label: 'Minimal 8 karakter',
        test: (pwd) => pwd.length >= 8,
    },
    {
        key: 'mixedCase',
        label: 'Huruf besar & huruf kecil',
        test: (pwd) => /[a-z]/.test(pwd) && /[A-Z]/.test(pwd),
    },
    {
        key: 'number',
        label: 'Minimal 1 angka',
        test: (pwd) => /\d/.test(pwd),
    },
    {
        key: 'symbol',
        label: 'Minimal 1 simbol',
        test: (pwd) => /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?`~]/.test(pwd),
    },
];
</script>

<template>
    <div v-if="password.length > 0" class="mt-2 space-y-1.5">
        <div
            v-for="rule in rules"
            :key="rule.key"
            class="flex items-center gap-2 text-xs"
        >
            <IconCheck
                v-if="rule.test(password)"
                class="text-green-500 shrink-0"
                :size="16"
            />
            <IconX
                v-else
                class="text-zinc-300 shrink-0"
                :size="16"
            />
            <span
                :class="rule.test(password) ? 'text-green-600' : 'text-zinc-400'"
            >
                {{ rule.label }}
            </span>
        </div>
    </div>
</template>
