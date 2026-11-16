<script setup>
import { IconBooks, IconTrendingUp, IconUsers } from '@tabler/icons-vue';
import AuthVisual from './AuthVisual.vue';

const stats = [
    { icon: IconUsers, value: '10.000+', label: 'Peserta Aktif' },
    { icon: IconBooks, value: '5.000+', label: 'Soal Latihan' },
    { icon: IconTrendingUp, value: '85+', label: 'Rata-rata Skor' },
];
</script>

<template>
    <div class="hidden min-w-0 flex-col lg:flex lg:h-full">
        <div class="flex items-center gap-3">
            <img
                src="/assets/image/logo-dark.png"
                alt="UPY Language Test"
                class="h-10 w-auto object-contain"
            />
            <span class="text-headline-md font-bold tracking-tight text-text-heading">
                UPY Language Test
            </span>
        </div>

        <div class="flex flex-col lg:my-auto lg:items-center lg:text-center">
            <h1
                class="auth-headline mt-8 max-w-lg text-[28px] font-bold leading-[1.15] tracking-[-0.02em] text-text-heading sm:text-4xl lg:mt-0 xl:text-[40px]"
            >
                Tingkatkan Kemampuan<br />Bahasa Anda
            </h1>

            <p class="mt-3 max-w-md text-body-md text-text-body">
                Platform ujian bahasa yang membantu Anda mengukur kemampuan dan meraih skor terbaik.
            </p>

            <AuthVisual class="auth-visual mt-8 hidden lg:block" />

            <dl class="auth-stats mt-8 hidden items-center gap-5 lg:flex">
                <template v-for="(stat, index) in stats" :key="stat.label">
                    <div v-if="index" class="h-9 w-px bg-outline-variant/60" aria-hidden="true"></div>
                    <div class="flex items-center gap-3">
                        <span
                            class="flex h-9 w-9 items-center justify-center rounded-xl bg-surface-container-low text-secondary"
                        >
                            <component :is="stat.icon" :size="18" stroke="1.75" />
                        </span>
                        <div class="min-w-0">
                            <dt class="text-title-lg font-bold text-text-heading">{{ stat.value }}</dt>
                            <dd class="text-label-md text-text-muted">{{ stat.label }}</dd>
                        </div>
                    </div>
                </template>
            </dl>
        </div>
    </div>
</template>

<style scoped>
@media (max-height: 650px) and (min-width: 1024px) {
    .auth-visual,
    .auth-stats {
        display: none;
    }

    .auth-headline {
        margin-top: 1.25rem;
    }
}
</style>
