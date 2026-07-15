<script setup>
import { Head, Link, usePage } from "@inertiajs/vue3";
import {
    IconAlertTriangle,
    IconCheck,
    IconClipboardCheck,
    IconFileDescription,
    IconUsers,
    IconEyeCheck,
    IconAlertTriangle as IconFlag,
} from "@tabler/icons-vue";
import DashboardLayout from "@/Components/Dashboard/DashboardLayout.vue";
import HeroBanner from "@/Components/Dashboard/HeroBanner.vue";
import StatsGrid from "@/Components/Dashboard/StatsGrid.vue";
import AssignmentsList from "@/Components/Dashboard/AssignmentsList.vue";
import Leaderboard from "@/Components/Dashboard/Leaderboard.vue";

defineProps({
    recentSessions: { type: Array, default: () => [] },
    availableExamsCount: { type: Number, default: 0 },
    totalUsers: { type: Number, default: 0 },
    totalExams: { type: Number, default: 0 },
    activeSessionsCount: { type: Number, default: 0 },
    flaggedSessionsCount: { type: Number, default: 0 },
    totalQuestions: { type: Number, default: 0 },
});

const roles = usePage().props.auth?.roles || [];
const isStudent = roles.includes("student");
const isInstructor = roles.includes("instructor");
const isProctor =
    roles.includes("proctor") ||
    roles.includes("admin") ||
    roles.includes("superadmin");
const isAdmin = roles.includes("admin") || roles.includes("superadmin");
</script>

<template>
    <Head title="Dasbor" />
    <DashboardLayout title="Dashboard">
        <!-- VERIFICATION BANNER (all roles) -->
        <div
            v-if="isStudent && !$page.props.auth.user.is_verified"
            class="mb-6 bg-amber-50 border border-amber-200 rounded-2xl p-5 flex items-start gap-4"
        >
            <IconAlertTriangle
                class="text-amber-500 shrink-0 mt-0.5"
                :size="22"
                stroke="1.5"
            />
            <div class="flex-1">
                <p class="font-semibold text-amber-800 text-title-lg">
                    Identitas Belum Diverifikasi
                </p>
                <p class="text-amber-700 text-body-md mt-1">
                    Akun Anda masih menunggu verifikasi admin. Upload foto
                    identitas untuk melanjutkan.
                </p>
                <Link
                    :href="route('onboarding.verify-identity')"
                    class="inline-block mt-3 bg-amber-600 text-white px-5 py-2 rounded-full text-label-md font-medium hover:bg-amber-700 transition-colors active:scale-95"
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

            <div
                v-if="recentSessions?.length"
                class="mt-6 bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30"
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
                                    ? 'text-green-600'
                                    : 'text-amber-600'
                            "
                        >
                            {{ s.score_total ?? "-" }}/120
                        </p>
                    </div>
                </div>
            </div>
        </template>

        <!-- === INSTRUCTOR DASHBOARD === -->
        <template v-else-if="isInstructor">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div
                    class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 text-center"
                >
                    <IconFileDescription
                        class="mx-auto text-secondary mb-2"
                        :size="32"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ totalExams }}
                    </p>
                    <p class="text-text-muted text-label-md">Total Ujian</p>
                </div>
                <div
                    class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 text-center"
                >
                    <IconClipboardCheck
                        class="mx-auto text-secondary mb-2"
                        :size="32"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ totalQuestions }}
                    </p>
                    <p class="text-text-muted text-label-md">Total Soal</p>
                </div>
                <div
                    class="bg-white rounded-2xl p-6 shadow-soft border border-outline-variant/30 flex flex-col items-center justify-center gap-3"
                >
                    <Link
                        :href="route('admin.exams.index')"
                        class="w-full text-center bg-primary-container text-white px-6 py-3 rounded-full text-label-md font-medium hover:bg-primary transition-all"
                    >
                        Kelola Exam →
                    </Link>
                    <Link
                        :href="route('content-library.index')"
                        class="w-full text-center border border-outline-variant text-primary px-6 py-3 rounded-full text-label-md font-medium hover:bg-surface-container-low transition-all"
                    >
                        Content Library →
                    </Link>
                </div>
            </div>
            <HeroBanner />
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <div class="lg:col-span-2 space-y-8">
                    <StatsGrid />
                </div>
                <div class="space-y-8">
                    <Leaderboard />
                </div>
            </div>
        </template>

        <!-- === ADMIN DASHBOARD === -->
        <template v-else-if="isAdmin && !isProctor">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div
                    class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 text-center"
                >
                    <IconUsers
                        class="mx-auto text-secondary mb-1"
                        :size="28"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ totalUsers }}
                    </p>
                    <p class="text-text-muted text-label-md">User Terdaftar</p>
                </div>
                <div
                    class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 text-center"
                >
                    <IconFileDescription
                        class="mx-auto text-secondary mb-1"
                        :size="28"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ totalExams }}
                    </p>
                    <p class="text-text-muted text-label-md">Total Ujian</p>
                </div>
                <div
                    class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 text-center"
                >
                    <IconEyeCheck
                        class="mx-auto text-secondary mb-1"
                        :size="28"
                        stroke="1.5"
                    />
                    <p class="text-headline-md font-bold text-primary">
                        {{ activeSessionsCount }}
                    </p>
                    <p class="text-text-muted text-label-md">Sesi Aktif</p>
                </div>
                <div
                    class="bg-white rounded-2xl p-5 shadow-soft border border-error-red/20 text-center"
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
                    class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 flex items-center justify-between hover:border-secondary transition-all"
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
                    class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30 flex items-center justify-between hover:border-secondary transition-all"
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
            <div
                class="bg-white rounded-2xl p-5 shadow-soft border border-outline-variant/30"
            >
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-title-lg font-semibold text-primary">
                        Akses Cepat
                    </h2>
                </div>
                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('admin.exams.index')"
                        class="bg-primary-container text-white px-5 py-2.5 rounded-full text-label-md font-medium hover:bg-primary transition-all"
                        >Kelola Exam</Link
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
            </div>
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
                <Link
                    :href="route('proctor.dashboard')"
                    class="inline-block bg-primary-container text-white px-8 py-3.5 rounded-full text-title-lg font-semibold hover:bg-primary transition-all active:scale-95"
                >
                    Buka Dashboard Pengawas →
                </Link>
            </div>
        </template>
    </DashboardLayout>
</template>
