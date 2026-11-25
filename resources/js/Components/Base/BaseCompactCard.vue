<script setup>
import { computed, useSlots } from 'vue';
import { Link } from '@inertiajs/vue3';
import { IconChevronRight } from '@tabler/icons-vue';
import BaseBadge from '@/Components/Base/BaseBadge.vue';
import BaseSkeleton from '@/Components/Base/BaseSkeleton.vue';

const props = defineProps({
    href: { type: String, default: null },
    icon: { type: [Object, Function], default: null },
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    statusLabel: { type: String, default: '' },
    statusVariant: { type: String, default: 'neutral' },
    statusIcon: { type: [Object, Function], default: null },
    metaItems: { type: Array, default: () => [] },
    description: { type: String, default: '' },
    descriptionTone: { type: String, default: 'muted' },
    progress: { type: Number, default: null },
    progressLabel: { type: String, default: '' },
    chevron: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    disabled: { type: Boolean, default: false },
    elevated: { type: Boolean, default: true },
});

const slots = useSlots();

const contentTag = computed(() => (props.href ? Link : 'div'));

const showChevron = computed(() => !slots.actions && (props.chevron || Boolean(props.href)));

const hasMeta = computed(() => props.metaItems.length > 0 || Boolean(slots.meta));

const hasDescription = computed(() => props.description !== '' || Boolean(slots.description));

const progressPercent = computed(() => Math.min(100, Math.max(0, Number(props.progress) || 0)));

const hasValidProgress = computed(() => props.progress !== null && Number.isFinite(Number(props.progress)));

const descriptionClass = computed(() => ({
    muted: 'text-text-muted',
    success: 'text-success',
    warning: 'text-warning',
    danger: 'text-error-red',
    info: 'text-info',
}[props.descriptionTone] || 'text-text-muted'));
</script>

<template>
    <div
        class="group flex flex-col gap-3 rounded-2xl border border-surface-container-high bg-surface-white p-4 transition-all sm:flex-row sm:items-center sm:gap-6"
        :class="{
            'shadow-soft': props.elevated && !props.loading,
            'opacity-60 pointer-events-none': props.disabled,
            'hover:[border-color:color-mix(in_srgb,var(--color-secondary)_50%,transparent)]': props.href && !props.disabled,
        }"
    >
        <template v-if="props.loading">
            <div class="flex min-w-0 flex-1 items-start gap-3">
                <BaseSkeleton circle width="2.75rem" height="2.75rem" />
                <div class="min-w-0 flex-1 space-y-2">
                    <BaseSkeleton width="45%" height="0.875rem" />
                    <BaseSkeleton width="65%" height="0.75rem" />
                    <BaseSkeleton width="35%" height="0.75rem" />
                </div>
            </div>
        </template>

        <template v-else>
            <component
                :is="contentTag"
                :href="props.href || undefined"
                class="flex min-w-0 flex-1 items-start gap-3 rounded-xl focus:outline-none"
                :class="props.href ? 'focus-visible:ring-2 focus-visible:ring-secondary/40 focus-visible:ring-offset-2' : ''"
            >
                <div
                    v-if="props.icon || slots.icon"
                    class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-surface-container-low text-secondary"
                >
                    <slot name="icon">
                        <component :is="props.icon" :size="20" stroke="1.8" />
                    </slot>
                </div>

                <div class="min-w-0 flex-1">
                    <div class="flex min-w-0 flex-wrap items-center gap-2">
                        <slot name="title">
                            <p class="min-w-0 break-words text-body-md font-semibold text-text-heading">{{ props.title }}</p>
                        </slot>
                        <div v-if="props.statusLabel || slots.status" class="shrink-0">
                            <slot name="status">
                                <BaseBadge v-if="props.statusLabel" :variant="props.statusVariant">
                                    <component :is="props.statusIcon" v-if="props.statusIcon" :size="14" />
                                    {{ props.statusLabel }}
                                </BaseBadge>
                            </slot>
                        </div>
                    </div>

                    <slot name="subtitle">
                        <p v-if="props.subtitle" class="mt-0.5 break-words text-label-md leading-snug text-text-muted">{{ props.subtitle }}</p>
                    </slot>

                    <slot name="meta">
                        <div v-if="hasMeta" class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-label-md text-text-muted">
                            <span
                                v-for="(item, index) in props.metaItems"
                                :key="`${item.label}-${index}`"
                                class="inline-flex items-center gap-1 whitespace-nowrap"
                            >
                                <component :is="item.icon" v-if="item.icon" :size="14" class="shrink-0" />
                                <span>{{ item.label }}</span>
                            </span>
                        </div>
                    </slot>

                    <slot name="description">
                        <p
                            v-if="hasDescription"
                            class="mt-1 whitespace-pre-line break-words text-label-md"
                            :class="descriptionClass"
                        >
                            {{ props.description }}
                        </p>
                    </slot>

                    <slot name="progress">
                        <div v-if="hasValidProgress" class="mt-2">
                            <div class="mb-1 flex items-center justify-between gap-2 text-label-md text-text-muted">
                                <span class="truncate">{{ props.progressLabel }}</span>
                                <span>{{ progressPercent }}%</span>
                            </div>
                            <div
                                class="h-1.5 overflow-hidden rounded-full bg-track-neutral"
                                role="progressbar"
                                :aria-valuenow="progressPercent"
                                aria-valuemin="0"
                                aria-valuemax="100"
                            >
                                <div class="h-full rounded-full bg-secondary" :style="{ width: `${progressPercent}%` }" />
                            </div>
                        </div>
                    </slot>
                </div>

                <IconChevronRight v-if="showChevron" :size="16" class="shrink-0 self-center text-text-muted" />
            </component>

            <div v-if="slots.actions" class="flex shrink-0 items-center gap-2 sm:self-center">
                <slot name="actions" />
            </div>
        </template>
    </div>
</template>
