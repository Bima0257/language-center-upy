<script setup>
defineProps({
    sesiTerbaru: { type: Array, default: () => [] },
});
</script>

<template>
    <BaseCard v-if="sesiTerbaru?.length" class="mt-6">
        <h2 class="text-title-lg font-semibold text-text-heading mb-4">
            Tryout Terbaru
        </h2>
        <div class="space-y-3">
            <div
                v-for="sesi in sesiTerbaru"
                :key="sesi.id"
                class="flex items-center justify-between p-3 bg-surface-container-low rounded-xl"
            >
                <div>
                    <p class="font-medium text-text-heading">
                        {{ sesi.slot_ujian?.periode_ujian?.ujian?.judul }}
                    </p>
                    <p class="text-label-md text-text-muted">
                        {{
                            new Date(sesi.created_at).toLocaleDateString(
                                "id-ID",
                            )
                        }}
                    </p>
                </div>
                <p
                    class="text-title-lg font-bold"
                    :class="
                        (sesi.skor_total ?? 0) >= 80
                            ? 'text-success'
                            : 'text-warning'
                    "
                >
                    {{ sesi.skor_total ?? "-" }}/120
                </p>
            </div>
        </div>
    </BaseCard>
</template>
