<script setup>
import { useForm } from '@inertiajs/vue3';
import { nextTick, ref } from 'vue';

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: '',
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    nextTick(() => passwordInput.value.focus());
};

const deleteUser = () => {
    form.delete(route('profile.destroy'), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.clearErrors();
    form.reset();
};
</script>

<template>
    <section class="space-y-6">
        <header>
            <h2 class="text-lg font-medium text-text-heading">
                Delete Account
            </h2>

            <p class="mt-1 text-sm text-text-body">
                Once your account is deleted, all of its resources and data will
                be permanently deleted. Before deleting your account, please
                download any data or information that you wish to retain.
            </p>
        </header>

        <BaseButton variant="danger" @click="confirmUserDeletion">Delete Account</BaseButton>

        <BaseModal
            :show="confirmingUserDeletion"
            max-width="md"
            fit-content
            aria-labelledby="delete-account-modal-title"
            aria-describedby="delete-account-modal-description"
            @close="closeModal"
        >
            <template #header>
                <BaseModalHeader>
                    <div class="px-6 py-4">
                        <h2 id="delete-account-modal-title" class="text-lg font-medium text-text-heading">
                            Are you sure you want to delete your account?
                        </h2>

                        <p id="delete-account-modal-description" class="mt-1 text-sm text-text-body">
                            Once your account is deleted, all of its resources and data
                            will be permanently deleted. Please enter your password to
                            confirm you would like to permanently delete your account.
                        </p>
                    </div>
                </BaseModalHeader>
            </template>

            <template #default>
                <div class="px-6 pt-6">
                    <BaseFieldLabel
                        for="password"
                        value="Password"
                        class="sr-only"
                    />

                    <BaseInput
                        id="password"
                        ref="passwordInput"
                        v-model="form.password"
                        type="password"
                        class="mt-1 block w-3/4"
                        placeholder="Password"
                        @keyup.enter="deleteUser"
                    />

                    <BaseInputError :message="form.errors.password" class="mt-2" />
                </div>
            </template>

            <template #footer>
                <BaseModalFooter>
                    <div class="px-6 py-3 flex justify-end">
                        <BaseButton variant="secondary" @click="closeModal">
                            Cancel
                        </BaseButton>

                        <BaseButton
                            variant="danger"
                            class="ms-3"
                            :disabled="form.processing"
                            @click="deleteUser"
                        >
                            Delete Account
                        </BaseButton>
                    </div>
                </BaseModalFooter>
            </template>
        </BaseModal>
    </section>
</template>
