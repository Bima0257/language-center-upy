<script setup>
import { Link } from "@inertiajs/vue3";
import { IconAlertTriangle } from "@tabler/icons-vue";

defineProps({
    href: { type: String, required: true },
});
</script>

<template>
    <div
        class="mb-6 bg-warning-container border border-warning rounded-2xl p-5 flex items-start gap-4"
    >
        <IconAlertTriangle
            class="text-warning shrink-0 mt-0.5"
            :size="22"
            stroke="1.5"
        />
        <div class="flex-1">
            <p class="font-semibold text-on-warning-container text-title-lg">
                Identitas Belum Diverifikasi
            </p>
            <p class="text-on-warning-container text-body-md mt-1">
                Akun Anda masih menunggu verifikasi admin. Upload foto
                identitas untuk melanjutkan.
            </p>
            <Link
                :href="href"
                class="inline-block mt-3 bg-warning text-on-warning px-5 py-2 rounded-full text-label-md font-medium hover:bg-warning-container hover:text-on-warning-container transition-colors active:scale-95"
            >
                Verifikasi Sekarang
            </Link>
        </div>
    </div>
</template>
