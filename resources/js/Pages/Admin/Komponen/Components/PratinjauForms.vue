<script setup>
import DropDown from "@/Components/Shared/DropDown.vue";
import FileUpload from "@/Components/Shared/FileUpload.vue";
import PasswordStrengthCheck from "@/Components/Shared/PasswordStrengthCheck.vue";
import PreviewCard from "@/Pages/Admin/Komponen/components/KartuPratinjau.vue";
import GrupPratinjau from "@/Pages/Admin/Komponen/components/GrupPratinjau.vue";

defineProps({
    group: { type: Object, required: true },
    componentMeta: { type: Object, required: true },
    isVisible: { type: Function, required: true },
    inputValue: { type: String, required: true },
    textareaValue: { type: String, required: true },
    radioValue: { type: String, required: true },
    dropdownValue: { type: String, required: true },
    fileValue: { type: Object, default: null },
    passwordValue: { type: String, required: true },
    dropdownOptions: { type: Array, default: () => [] },
    radioOptions: { type: Array, default: () => [] },
});

const emit = defineEmits([
    "update:input-value",
    "update:textarea-value",
    "update:radio-value",
    "update:dropdown-value",
    "update:file-value",
    "update:password-value",
]);
</script>

<template>
    <GrupPratinjau :group="group">
        <PreviewCard
            v-if="isVisible('BaseFieldLabel')"
            name="BaseFieldLabel"
            scope="Global"
            :description="componentMeta.BaseFieldLabel.description"
            :path="componentMeta.BaseFieldLabel.path"
        >
            <div>
                <BaseFieldLabel for="label-preview">Label melalui slotUjian</BaseFieldLabel>
                <p id="label-preview" class="text-body-md text-text-body">
                    Label menjaga jarak dan tipografi field tetap konsisten.
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseInput')"
            name="BaseInput"
            scope="Global"
            :description="componentMeta.BaseInput.description"
            :path="componentMeta.BaseInput.path"
        >
            <div class="space-y-3">
                <BaseInput
                    :model-value="inputValue"
                    placeholder="Ketik sesuatu..."
                    @update:model-value="emit('update:input-value', $event)"
                />
                <p class="text-label-md text-text-muted">
                    Value:
                    <span class="font-semibold text-primary">{{ inputValue || "kosong" }}</span>
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseInputError')"
            name="BaseInputError"
            scope="Global"
            :description="componentMeta.BaseInputError.description"
            :path="componentMeta.BaseInputError.path"
        >
            <div>
                <BaseInput error placeholder="Field dengan error" />
                <BaseInputError message="Field ini wajib diisi." />
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseTextarea')"
            name="BaseTextarea"
            scope="Global"
            :description="componentMeta.BaseTextarea.description"
            :path="componentMeta.BaseTextarea.path"
        >
            <BaseTextarea
                :model-value="textareaValue"
                rows="4"
                class="resize-none"
                @update:model-value="emit('update:textarea-value', $event)"
            />
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('BaseRadio')"
            name="BaseRadio"
            scope="Global"
            :description="componentMeta.BaseRadio.description"
            :path="componentMeta.BaseRadio.path"
        >
            <div class="space-y-4">
                <BaseRadio
                    :model-value="radioValue"
                    name="preview-keterampilan"
                    :options="radioOptions"
                    inline
                    @update:model-value="emit('update:radio-value', $event)"
                />
                <p class="text-label-md text-text-muted">
                    Pilihan: <span class="font-semibold text-primary">{{ radioValue }}</span>
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('DropDown')"
            name="DropDown"
            scope="Reusable"
            :description="componentMeta.DropDown.description"
            :path="componentMeta.DropDown.path"
        >
            <div class="space-y-3">
                <DropDown
                    :model-value="dropdownValue"
                    label="Pilih keterampilan"
                    :options="dropdownOptions"
                    option-label="name"
                    option-value="id"
                    clearable
                    @update:model-value="emit('update:dropdown-value', $event)"
                />
                <p class="text-label-md text-text-muted">
                    Value:
                    <span class="font-semibold text-primary">{{ dropdownValue || "kosong" }}</span>
                </p>
            </div>
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('FileUpload')"
            name="FileUpload"
            scope="Reusable"
            :description="componentMeta.FileUpload.description"
            :path="componentMeta.FileUpload.path"
        >
            <FileUpload
                :model-value="fileValue"
                label="Upload cover"
                variant="dropzone"
                accept="image/*"
                media-type="image"
                preview-url="/assets/image/default-materiSoal.png"
                hint="PNG atau JPG, maksimal 2 MB"
                @update:model-value="emit('update:file-value', $event)"
            />
        </PreviewCard>

        <PreviewCard
            v-if="isVisible('PasswordStrengthCheck')"
            name="PasswordStrengthCheck"
            scope="Reusable"
            :description="componentMeta.PasswordStrengthCheck.description"
            :path="componentMeta.PasswordStrengthCheck.path"
        >
            <div>
                <BaseInput
                    :model-value="passwordValue"
                    type="password"
                    @update:model-value="emit('update:password-value', $event)"
                />
                <PasswordStrengthCheck :password="passwordValue" />
            </div>
        </PreviewCard>
    </GrupPratinjau>
</template>
