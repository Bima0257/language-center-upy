import { ref } from 'vue'

const state = ref({
    visible: false,
    message: '',
    type: 'confirm',
    resolve: null,
    inputValue: '',
    inputPlaceholder: '',
})

export function useConfirm() {
    function confirm(message) {
        return new Promise((resolve) => {
            state.value = {
                visible: true,
                message,
                type: 'confirm',
                resolve,
                inputValue: '',
                inputPlaceholder: '',
            }
        })
    }

    function prompt(message, placeholder = '') {
        return new Promise((resolve) => {
            state.value = {
                visible: true,
                message,
                type: 'prompt',
                resolve,
                inputValue: '',
                inputPlaceholder: placeholder,
            }
        })
    }

    function getState() {
        return state
    }

    function onOk() {
        if (state.value.type === 'prompt') {
            state.value.resolve?.(state.value.inputValue)
        } else {
            state.value.resolve?.(true)
        }
        dismiss()
    }

    function onCancel() {
        state.value.resolve?.(state.value.type === 'prompt' ? null : false)
        dismiss()
    }

    function dismiss() {
        state.value = {
            visible: false,
            message: '',
            type: 'confirm',
            resolve: null,
            inputValue: '',
            inputPlaceholder: '',
        }
    }

    return { confirm, prompt, getState, onOk, onCancel, dismiss }
}
