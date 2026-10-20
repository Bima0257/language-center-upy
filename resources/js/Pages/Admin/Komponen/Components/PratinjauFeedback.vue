<script setup>
import { IconX } from "@tabler/icons-vue";
import UploadProgressBar from "@/Components/Shared/UploadProgressBar.vue";
import UserGuide from "@/Components/Shared/UserGuide.vue";
import PreviewCard from "@/Pages/Admin/Komponen/Components/KartuPratinjau.vue";
import GrupPratinjau from "@/Pages/Admin/Komponen/Components/GrupPratinjau.vue";

defineProps({
    group: { type: Object, required: true },
    componentMeta: { type: Object, required: true },
    isVisible: { type: Function, required: true },
    modalOpen: { type: Boolean, required: true },
    progressVisible: { type: Boolean, required: true },
    userGuideSteps: { type: Array, default: () => [] },
    showUploadProgress: { type: Function, required: true },
    showConfirmDialog: { type: Function, required: true },
    showToast: { type: Function, required: true },
});

const emit = defineEmits(["update:modal-open"]);
</script>

<template>
    <GrupPratinjau :group="group">
        <PreviewCard
            v-if="isVisible('BaseModal')"
            name="BaseModal"
            scope="Global"
            :description="componentMeta.BaseModal.description"
            :path="componentMeta.BaseModal.path"
        >
            <BaseButton size="sm" @click="emit('update:modal-open', true)">Buka modal</BaseButton>
            <BaseModal
                :show="modalOpen"
                max-width="md"
                @close="emit('update:modal-open', false)"
            >
                <div class="p-6">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 class="text-title-lg font-semibold text-primary">Contoh modal</h3>
                            <p class="mt-1 text-body-md text-text-muted">
                                Modal menjaga fokus konteks dan scroll halaman.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="text-text-muted transition-colors hover:text-primary"
                            aria-label="Tutup modal"
                            @click="emit('update:modal-open', false)"
                        >
                            <IconX :size="20" />
                        </button>
                    </div>
                    <div class="mt-6 flex justify-end">
                        <BaseButton
                            size="sm"
                            variant="secondary"
                            @click="emit('update:modal-open', false)"
                        >
                            Tutup
                        </BaseButton>
                    </div>
                </div>
            </BaseModal>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('UserGuide')"
            name="UserGuide"
            scope="Reusable"
            :description="componentMeta.UserGuide.description"
            :path="componentMeta.UserGuide.path"
        >
            <UserGuide
                title="Panduan komponen"
                :steps="userGuideSteps"
                :rules="['Gunakan slotUjian content untuk tambahan informasi.']"
                button-label="Buka panduan"
            />
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('UploadProgressBar')"
            name="UploadProgressBar"
            scope="Reusable"
            :description="componentMeta.UploadProgressBar.description"
            :path="componentMeta.UploadProgressBar.path"
        >
            <div class="space-y-3">
                <BaseButton size="sm" @click="showUploadProgress">
                    Pratinjau progres unggahan
                </BaseButton>
                <UploadProgressBar
                    :show="progressVisible"
                    label="Menyiapkan contoh file..."
                    media-type="both"
                />
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('ConfirmDialog')"
            name="ConfirmDialog"
            scope="Global"
            :description="componentMeta.ConfirmDialog.description"
            :path="componentMeta.ConfirmDialog.path"
        >
            <div class="space-y-3">
                <BaseButton size="sm" variant="danger" @click="showConfirmDialog">
                    Pratinjau konfirmasi
                </BaseButton>
                <p class="text-label-md text-text-muted">
                    Dialog global sudah dipasang oleh TataLetakDasbor.
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('ToastProvider')"
            name="ToastProvider"
            scope="Global"
            :description="componentMeta.ToastProvider.description"
            :path="componentMeta.ToastProvider.path"
        >
            <div class="flex flex-wrap gap-2">
                <BaseButton size="sm" @click="showToast('success')">Success toast</BaseButton>
                <BaseButton size="sm" variant="danger" @click="showToast('error')">
                    Error toast
                </BaseButton>
                <BaseButton size="sm" variant="secondary" @click="showToast('info')">
                    Info toast
                </BaseButton>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('ScrollToTop')"
            name="ScrollToTop"
            scope="Global"
            :description="componentMeta.ScrollToTop.description"
            :path="componentMeta.ScrollToTop.path"
        >
            <div class="rounded-2xl bg-primary-container p-5 text-on-primary-container">
                <p class="text-title-lg font-semibold">Aktif pada layout dashboard</p>
                <p class="mt-1 text-body-md text-on-primary-container/70">
                    Scroll halaman ini ke bawah untuk melihat tombol kembali ke atas.
                </p>
            </div>
        </PreviewCard>
    </GrupPratinjau>
</template>
