<script setup>
import AuthBrandPanel from './AuthBrandPanel.vue';
</script>

<template>
    <div class="relative min-h-dvh overflow-x-clip bg-surface">
        <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -right-24 -top-32 h-[420px] w-[420px] rounded-full bg-pastel-blue/50 blur-3xl"></div>
            <div class="absolute -bottom-40 -left-24 h-[380px] w-[380px] rounded-full bg-pastel-purple/50 blur-3xl"></div>
            <div class="absolute -top-48 left-1/3 h-[560px] w-[560px] rounded-full border border-outline-variant/30"></div>
        </div>

        <main class="auth-shell relative mx-auto flex min-h-dvh w-full max-w-[1440px] items-center px-4 py-5 sm:px-8 lg:h-dvh lg:px-app-margin lg:py-6">
            <div class="grid w-full grid-cols-1 items-center gap-10 lg:h-full lg:grid-cols-2 lg:items-stretch lg:gap-card-gap xl:gap-16">
                <AuthBrandPanel />

                <section class="flex justify-center">
                    <div class="auth-card w-full max-w-[480px] rounded-2xl border border-outline-variant/60 bg-surface-white p-6 shadow-standard sm:p-7 lg:my-auto">
                        <slot />
                    </div>
                </section>
            </div>
        </main>
    </div>
</template>

<style scoped>
@media (max-height: 650px) and (min-width: 1024px) {
    .auth-shell {
        padding-top: 1rem;
        padding-bottom: 1rem;
    }

    .auth-card {
        padding: 1.25rem;
    }

    .auth-shell :deep(.auth-form) {
        gap: 0.625rem;
    }

    .auth-shell :deep(.auth-divider) {
        margin-top: 0.75rem;
        margin-bottom: 0.75rem;
    }

    .auth-shell :deep(.auth-footer) {
        margin-top: 0.25rem;
    }
}
</style>
