<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import {
    IconAlertTriangle,
    IconCheck,
    IconClipboardCheck,
    IconFileDescription,
    IconUsers,
    IconEyeCheck,
    IconAlertTriangle as IconFlag,
    IconBooks,
    IconX,
} from "@tabler/icons-vue";
import DashboardLayout from "@/Components/Dashboard/DashboardLayout.vue";
import HeroBanner from "@/Components/Dashboard/HeroBanner.vue";
import StatsGrid from "@/Components/Dashboard/StatsGrid.vue";
import AssignmentsList from "@/Components/Dashboard/AssignmentsList.vue";
import Leaderboard from "@/Components/Dashboard/Leaderboard.vue";
import BarChart from "@/Components/Charts/BarChart.vue";
import DoughnutChart from "@/Components/Charts/DoughnutChart.vue";
import { useConfirm } from "@/Composables/useConfirm";
import { useToast } from "@/Composables/useToast";
import { skillLabel } from "@/constants/skills";

const confirm = useConfirm();
const toast = useToast();

const props = defineProps({
    recentSessions: { type: Array, default: () => [] },
    availableExamsCount: { type: Number, default: 0 },
    totalUsers: { type: Number, default: 0 },
    totalExams: { type: Number, default: 0 },
    activeSessionsCount: { type: Number, default: 0 },
    flaggedSessionsCount: { type: Number, default: 0 },
    pendingReviewCount: { type: Number, default: 0 },
    pendingQuestions: { type: Array, default: () => [] },
    totalQuestions: { type: Number, default: 0 },
    totalPassages: { type: Number, default: 0 },
    questionsBySkill: { type: Array, default: () => [] },
    questionsByStatus: { type: Array, default: () => [] },
    questionsByBank: { type: Array, default: () => [] },
});

const roles = usePage().props.auth?.roles || [];
const isStudent = roles.includes("student");
const isInstructor = roles.includes("instructor");
const isProctor =
    roles.includes("proctor") ||
    roles.includes("admin") ||
    roles.includes("superadmin");
const isAdmin = roles.includes("admin") || roles.includes("superadmin");

const statusLabels = {
    draft: "Draf",
    submitted: "Diajukan",
    approved: "Disetujui",
    rejected: "Ditolak",
    archived: "Arsip",
};

const skillChartLabels = computed(() => props.questionsBySkill.map((i) => skillLabel(i.label)));
const skillChartData = computed(() => props.questionsBySkill.map((i) => i.count));

const statusChartLabels = computed(() =>
    props.questionsByStatus.map((i) => statusLabels[i.status] || i.status),
);
const statusChartData = computed(() => props.questionsByStatus.map((i) => i.count));

const bankChartLabels = computed(() => props.questionsByBank.map((i) => i.label));
const bankChartData = computed(() => props.questionsByBank.map((i) => i.count));

function questionTextPreview(q) {
    return (q.question_text || "").substring(0, 120);
}

async function reviewQuestion(id, status) {
    const note =
        status === "rejected"
            ? await confirm.prompt("Catatan penolakan (opsional):")
            : null;
    router.patch(
        route("content-library.review", id),
        { status, review_note: note },
        {
            preserveScroll: true,
            onSuccess: () => {
                toast.success(
                    status === "approved"
                        ? "Soal disetujui."
                        : "Soal ditolak.",
                );
                router.reload({
                    only: ["pendingQuestions", "pendingReviewCount"],
                    preserveState: true,
                    preserveScroll: true,
                });
            },
        },
    );
}
</script>

<template>
    <Head title="Dasbor" />
    <DashboardLayout title="Dashboard">
        <!-- VERIFICATION BANNER (all roles) -->
        <div
            v-if="isStudent && !$page.props.auth.user.is_verified"
            class="mb-6 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-900 rounded-2xl p-5 flex items-start gap-4"
        >
            <IconAlertTriangle
                class="text-amber-500 dark:text-amber-400 shrink-0 mt-0.5"
                :size="22"
                stroke="1.5"
            />
            <div class="flex-1">
                <p class="font-semibold text-amber-800 dark:text-amber-200 text-title-lg">
                    Identitas Belum Diverifikasi
                </p>
                <p class="text-amber-700 dark:text-amber-300 text-body-md mt-1">
                    Akun Anda masih menunggu verifikasi admin. Upload foto
                    identitas untuk melanjutkan.
                </p>
                <Link
                    :href="route('onboarding.verify-identity')"
                    class="inline-block mt-3 bg-amber-600 dark:bg-amber-500 text-white px-5 py-2 rounded-full text-label-md font-medium hover:bg-amber-700 dark:hover:bg-amber-600 transition-colors active:scale-95"
                >
                    Verifikasi Sekarang
                </Link>
            </div>
        </div>

        <!-- === STUDENT DASHBOARD === -->
        <template v-if="isStudent">
            <Link
                :href="route('exam.available')"
                class="mb-6 flex items-center justify-between bg-primary-container text-white rounded-2xl p-6 hover:opacity-90 transition-all active:scale-[0.99]"
            >
                <div>
                    <p class="text-title-lg font-semibold">Tryout Tersedia</p>
                    <p class="text-white/80 text-body-md">
                        {{ availableExamsCount }} ujian tersedia — Cek kemampuan
                        TOEFL Anda
                    </p>
                </div>
                <IconClipboardCheck :size="32" stroke="1.5" />
            </Link>

            <HeroBanner />
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <StatsGrid />
                    <AssignmentsList />
                </div>
                <div class="space-y-8">
                    <Leaderboard />
                </div>
            </div>

            <BaseCard
                v-if="recentSessions?.length"
                class="mt-6"
            >
                <h2 class="text-title-lg font-semibold text-primary mb-4">
                    Tryout Terbaru
                </h2>
                <div class="space-y-3">
                    <div
                        v-for="s in recentSessions"
                        :key="s.id"
                        class="flex items-center justify-between p-3 bg-surface-container-low rounded-xl"
                    >
                        <div>
                            <p class="font-medium text-primary">
                                {{ s.schedule?.exam?.title }}
                            </p>
                            <p class="text-label-md text-text-muted">
                                {{
                                    new Date(s.created_at).toLocaleDateString(
                                        "id-ID",
                                    )
                                }}
                            </p>
                        </div>
                        <p
                            class="text-title-lg font-bold"
                            :class="
                                (s.score_total ?? 0) >= 80
                                    ? 'text-green-600 dark:text-green-400'
                                    : 'text-amber-600 dark:text-amber-400'
                            "
                        >
                            {{ s.score_total ?? "-" }}/120
                        </p>
                    </div>
                </div>
            </BaseCard>
        </template>

        <!-- === INSTRUCTOR DASHBOARD === -->
        <template v-else-if="isInstructor">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <BaseCard class="text-center">
                    <IconClipboardCheck
                        class="mx-auto text-secondary mb-2"
                        :size="32"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ totalQuestions }}
                    </p>
                    <p class="text-text-muted text-label-md">Total Soal</p>
                </BaseCard>
                <BaseCard class="text-center">
                    <IconBooks
                        class="mx-auto text-secondary mb-2"
                        :size="32"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ totalPassages }}
                    </p>
                    <p class="text-text-muted text-label-md">Total Materi Soal</p>
                </BaseCard>
                <BaseCard class="flex flex-col items-center justify-center gap-3">
                    <BaseButton
                        :href="route('content-library.index')"
                        class="w-full"
                    >
                        Kelola Bank Soal →
                    </BaseButton>
                    <BaseButton
                        :href="route('content-library.passages.index')"
                        variant="secondary"
                        class="w-full"
                    >
                        Kelola Materi Soal →
                    </BaseButton>
                </BaseCard>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <BaseCard>
                    <BarChart
                        :labels="skillChartLabels"
                        :data="skillChartData"
                        title="Jumlah Soal per Skill"
                    />
                </BaseCard>
                <BaseCard>
                    <DoughnutChart
                        :labels="statusChartLabels"
                        :data="statusChartData"
                        title="Status Review Soal"
                    />
                </BaseCard>
            </div>
            <BaseCard class="mb-6">
                <BarChart
                    :labels="bankChartLabels"
                    :data="bankChartData"
                    title="Jumlah Soal per Bank Soal"
                />
            </BaseCard>
        </template>

        <!-- === ADMIN DASHBOARD === -->
        <template v-else-if="isAdmin">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <BaseCard padding="p-5" class="text-center">
                    <IconUsers
                        class="mx-auto text-secondary mb-1"
                        :size="28"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ totalUsers }}
                    </p>
                    <p class="text-text-muted text-label-md">User Terdaftar</p>
                </BaseCard>
                <BaseCard padding="p-5" class="text-center">
                    <IconFileDescription
                        class="mx-auto text-secondary mb-1"
                        :size="28"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ totalExams }}
                    </p>
                    <p class="text-text-muted text-label-md">Total Ujian</p>
                </BaseCard>
                <BaseCard padding="p-5" class="text-center">
                    <IconEyeCheck
                        class="mx-auto text-secondary mb-1"
                        :size="28"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ activeSessionsCount }}
                    </p>
                    <p class="text-text-muted text-label-md">Sesi Aktif</p>
                </BaseCard>
                <div
                    class="bg-surface-white rounded-2xl p-5 shadow-soft border border-error-red/20 text-center"
                >
                    <IconFlag
                        class="mx-auto text-error-red mb-1"
                        :size="28"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-error-red">
                        {{ flaggedSessionsCount }}
                    </p>
                    <p class="text-text-muted text-label-md">Perlu Review</p>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <Link
                    :href="route('admin.verify-users')"
                    class="bg-surface-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 flex items-center justify-between hover:border-secondary transition-all"
                >
                    <div>
                        <p class="text-title-lg font-semibold text-primary">
                            Verifikasi Pengguna
                        </p>
                        <p class="text-text-muted text-body-md">
                            Setujui atau tolak identitas peserta
                        </p>
                    </div>
                    <IconUsers
                        :size="28"
                        stroke="1.5"
                        class="text-text-muted"
                    />
                </Link>
                <Link
                    :href="route('admin.reports.integrity')"
                    class="bg-surface-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 flex items-center justify-between hover:border-secondary transition-all"
                >
                    <div>
                        <p class="text-title-lg font-semibold text-primary">
                            Laporan Integritas
                        </p>
                        <p class="text-text-muted text-body-md">
                            Tinjau log pelanggaran ujian
                        </p>
                    </div>
                    <IconFileDescription
                        :size="28"
                        stroke="1.5"
                        class="text-text-muted"
                    />
                </Link>
            </div>
            <BaseCard padding="p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-title-lg font-semibold text-primary">
                        Akses Cepat
                    </h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <BaseButton
                        :href="route('admin.exams.index')"
                        size="xs"
                        >Kelola Exam</BaseButton
                    >
                    <Link
                        :href="route('proctor.dashboard')"
                        class="bg-secondary text-white px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-secondary-container transition-all"
                        >Dashboard Pengawas</Link
                    >
                    <Link
                        :href="route('admin.verify-users')"
                        class="border border-outline-variant text-primary px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all"
                        >Verifikasi</Link
                    >
                    <Link
                        :href="route('admin.reports.integrity')"
                        class="border border-outline-variant text-primary px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all"
                        >Laporan</Link
                    >
                </div>
            </BaseCard>
            <BaseCard padding="p-5">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-title-lg font-semibold text-primary">
                            Persetujuan Soal
                        </h2>
                        <BaseBadge
                            v-if="pendingReviewCount > 0"
                            variant="danger"
                            >{{ pendingReviewCount }}</BaseBadge
                        >
                    </div>
                    <Link
                        :href="route('content-library.index', { status: 'draft' })"
                        class="text-secondary text-label-md font-medium hover:underline"
                        >Lihat Semua →</Link
                    >
                </div>
                <div v-if="pendingQuestions.length === 0" class="py-6 text-center">
                    <IconCheck
                        class="mx-auto text-green-500 dark:text-green-400 mb-2"
                        :size="32"
                        stroke="1.5"
                    />
                    <p class="text-text-muted text-body-md">
                        Tidak ada soal menunggu persetujuan.
                    </p>
                </div>
                <div v-else class="divide-y divide-outline-variant/20">
                    <div
                        v-for="q in pendingQuestions"
                        :key="q.id"
                        class="py-3 flex items-start justify-between gap-4"
                    >
                        <div class="min-w-0">
                            <p class="text-text-body text-body-md text-primary font-medium mb-1">
                                {{ questionTextPreview(q)
                                }}{{ (q.question_text || "").length > 120 ? "…" : "" }}
                            </p>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    v-if="q.skill"
                                    class="inline-block bg-pastel-blue/50 text-blue-700 dark:text-blue-300 px-2.5 py-0.5 rounded-full text-label-md font-medium"
                                    >{{ q.skill.name }}</span
                                >
                                <span
                                    v-if="q.question_bank"
                                    class="inline-block bg-pastel-peach/50 text-amber-800 dark:text-amber-300 px-2.5 py-0.5 rounded-full text-label-md font-medium"
                                    >{{ q.question_bank.name }}</span
                                >
                                <span
                                    v-if="q.creator"
                                    class="text-label-md text-text-muted"
                                    >oleh {{ q.creator.name }}</span
                                >
                            </div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            <button
                                @click="reviewQuestion(q.id, 'approved')"
                                class="p-2 text-green-600 dark:text-green-400 hover:text-green-800 dark:hover:text-green-400 transition-colors"
                                title="Setujui"
                            >
                                <IconCheck :size="18" />
                            </button>
                            <button
                                @click="reviewQuestion(q.id, 'rejected')"
                                class="p-2 text-error-red hover:text-red-700 dark:hover:text-red-400 transition-colors"
                                title="Tolak"
                            >
                                <IconX :size="18" />
                            </button>
                        </div>
                    </div>
                </div>
            </BaseCard>
        </template>

        <!-- === PROCTOR (standalone) DASHBOARD === -->
        <template v-else-if="isProctor">
            <div class="text-center py-10">
                <IconEyeCheck
                    class="mx-auto text-secondary mb-4"
                    :size="48"
                    stroke="1.5"
                />
                <h2 class="text-headline-md font-bold text-primary mb-2">
                    Dashboard Pengawas
                </h2>
                <p class="text-text-body text-body-md mb-6">
                    Pantau sesi ujian langsung dan tinjau pelanggaran
                </p>
                <BaseButton
                    :href="route('proctor.dashboard')"
                    size="xl"
                    class="px-8"
                >
                    Buka Dashboard Pengawas →
                </BaseButton>
            </div>
        </template>
    </DashboardLayout>
</template>
