<script setup>
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { IconClipboardCheck } from "@tabler/icons-vue";
import TataLetakDasbor from "@/Layouts/TataLetakDasbor.vue";
import HeroBanner from "@/Pages/Dasbor/Components/HeroBanner.vue";
import StatsGrid from "@/Pages/Dasbor/Components/StatsGrid.vue";
import AssignmentsList from "@/Pages/Dasbor/Components/AssignmentsList.vue";
import Leaderboard from "@/Pages/Dasbor/Components/Leaderboard.vue";
import VerificationBanner from "@/Pages/Dasbor/Components/VerificationBanner.vue";
import RecentExams from "@/Pages/Dasbor/Components/RecentExams.vue";
import AdminStatCards from "@/Pages/Dasbor/Components/AdminStatCards.vue";
import AdminQuickLinks from "@/Pages/Dasbor/Components/AdminQuickLinks.vue";
import PendingQuestionReviews from "@/Pages/Dasbor/Components/PendingQuestionReviews.vue";
import { useConfirm } from "@/Composables/useConfirm";

const confirm = useConfirm();

defineProps({
    sesiTerbaru: { type: Array, default: () => [] },
    jumlahUjianTersedia: { type: Number, default: 0 },
    jumlahPengguna: { type: Number, default: 0 },
    jumlahUjian: { type: Number, default: 0 },
    jumlahSesiAktif: { type: Number, default: 0 },
    jumlahSesiDitandai: { type: Number, default: 0 },
    jumlahMenungguTinjauan: { type: Number, default: 0 },
    soalMenunggu: { type: Array, default: () => [] },
});

const roles = usePage().props.auth?.roles || [];
const isMahasiswa = roles.includes("student");
const isAdmin = roles.includes("admin") || roles.includes("superadmin");

async function reviewQuestion(id, status) {
    const note =
        status === "rejected"
            ? await confirm.prompt("Catatan penolakan (opsional):")
            : null;
    router.patch(
        route("pustaka-soal.tinjau", id),
        { status, catatan_tinjauan: note },
        {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({
                    only: ["soalMenunggu", "jumlahMenungguTinjauan"],
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
    <TataLetakDasbor title="Dasbor" hide-header>
        <VerificationBanner
            v-if="isMahasiswa && !$page.props.auth.user.terverifikasi"
            :href="route('onboarding.verify-identity')"
        />

        <!-- === STUDENT DASHBOARD === -->
        <template v-if="isMahasiswa">
            <Link
                :href="route('ujian.tersedia')"
                class="mb-6 flex items-center justify-between bg-primary-container text-on-primary-container rounded-2xl p-6 hover:opacity-90 transition-all active:scale-[0.99]"
            >
                <div>
                    <p class="text-title-lg font-semibold">Tryout Tersedia</p>
                    <p class="text-on-primary-container/80 text-body-md">
                        {{ jumlahUjianTersedia }} ujian tersedia — Cek kemampuan
                        Tes Bahasa Anda
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

            <RecentExams :sesi-terbaru="sesiTerbaru" />
        </template>

        <!-- === ADMIN DASHBOARD === -->
        <template v-else-if="isAdmin">
            <AdminStatCards
                :jumlah-pengguna="jumlahPengguna"
                :jumlah-ujian="jumlahUjian"
                :jumlah-sesi-aktif="jumlahSesiAktif"
                :jumlah-sesi-ditandai="jumlahSesiDitandai"
            />
            <AdminQuickLinks
                :manage-exam-href="route('admin.ujian.daftar')"
                :proctor-dashboard-href="route('pengawas.dasbor')"
                :verification-href="route('admin.verifikasi-pengguna')"
                :session-report-href="route('admin.laporan.sesi')"
            />
            <PendingQuestionReviews
                :jumlah-menunggu-tinjauan="jumlahMenungguTinjauan"
                :soal-menunggu="soalMenunggu"
                :all-questions-href="route('pustaka-soal.daftar', { status: 'draft' })"
                @review="reviewQuestion"
            />
        </template>

    </TataLetakDasbor>
</template>
