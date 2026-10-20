<script setup>
import { Link } from "@inertiajs/vue3";
import { IconCheck, IconX } from "@tabler/icons-vue";

defineProps({
    jumlahMenungguTinjauan: { type: Number, default: 0 },
    soalMenunggu: { type: Array, default: () => [] },
    allQuestionsHref: { type: String, required: true },
});

const emit = defineEmits(["review"]);

function soalTextPreview(question) {
    return (question.teks_soal || "").substring(0, 120);
}
</script>

<template>
    <BaseCard padding="p-5">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-3">
                <h2 class="text-title-lg font-semibold text-primary">
                    Persetujuan Soal
                </h2>
                <BaseBadge
                    v-if="jumlahMenungguTinjauan > 0"
                    variant="danger"
                    >{{ jumlahMenungguTinjauan }}</BaseBadge
                >
            </div>
            <Link
                :href="allQuestionsHref"
                class="text-secondary text-label-md font-medium hover:underline"
                >Lihat Semua →</Link
            >
        </div>
        <div v-if="soalMenunggu.length === 0" class="py-6 text-center">
            <IconCheck
                class="mx-auto text-success mb-2"
                :size="32"
                stroke="1.5"
            />
            <p class="text-text-muted text-body-md">
                Tidak ada soal menunggu persetujuan.
            </p>
        </div>
        <div v-else class="divide-y divide-outline-variant/20">
            <div
                v-for="question in soalMenunggu"
                :key="question.id"
                class="py-3 flex items-start justify-between gap-4"
            >
                <div class="min-w-0">
                    <p
                        class="text-text-body text-body-md text-primary font-medium mb-1"
                    >
                        {{ soalTextPreview(question)
                        }}{{
                            (question.teks_soal || "").length > 120 ? "…" : ""
                        }}
                    </p>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span
                            v-if="question.keterampilan"
                            class="inline-block bg-info-container text-on-info-container px-2.5 py-0.5 rounded-full text-label-md font-medium"
                            >{{ question.keterampilan.nama }}</span
                        >
                        <span
                            v-if="question.bank_soal"
                            class="inline-block bg-warning-container text-on-warning-container px-2.5 py-0.5 rounded-full text-label-md font-medium"
                            >{{ question.bank_soal.nama }}</span
                        >
                        <span
                            v-if="question.pembuat"
                            class="text-label-md text-text-muted"
                            >oleh {{ question.pembuat.name }}</span
                        >
                    </div>
                </div>
                <div class="flex gap-2 shrink-0">
                    <button
                        @click="emit('review', question.id, 'approved')"
                        class="p-2 text-success hover:text-on-success-container transition-colors"
                        title="Setujui"
                    >
                        <IconCheck :size="18" />
                    </button>
                    <button
                        @click="emit('review', question.id, 'rejected')"
                        class="p-2 text-error-red hover:text-on-error-container transition-colors"
                        title="Tolak"
                    >
                        <IconX :size="18" />
                    </button>
                </div>
            </div>
        </div>
    </BaseCard>
</template>
