<script setup>
import { ref } from "vue";
import { IconChevronDown } from "@tabler/icons-vue";

const props = defineProps({
    title: { type: String, required: true },
    defaultOpen: { type: Boolean, default: false },
});

const open = ref(props.defaultOpen);
</script>

<template>
    <div
        class="bg-surface-white rounded-2xl shadow-soft border border-outline-variant/30 overflow-hidden"
    >
        <button
            type="button"
            class="w-full flex items-center justify-between gap-3 px-6 py-4 text-left transition-colors hover:bg-surface-container-low"
            @click="open = !open"
        >
            <span class="text-title-lg font-semibold text-primary">{{
                title
            }}</span>
            <span
                class="text-text-muted transition-transform shrink-0"
                :class="open ? 'rotate-180' : ''"
            >
                <IconChevronDown :size="18" />
            </span>
        </button>
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="opacity-0 -translate-y-1"
            enter-to-class="opacity-100 translate-y-0"
            leave-active-class="transition duration-100 ease-in"
            leave-from-class="opacity-100 translate-y-0"
            leave-to-class="opacity-0 -translate-y-1"
        >
            <div v-show="open" class="px-6 pb-5">
                <slot />
            </div>
        </Transition>
    </div>
</template>
