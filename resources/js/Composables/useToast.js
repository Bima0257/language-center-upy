import { toast } from 'vue-sonner'

export function useToast() {
    return {
        success(msg) {
            toast.success(msg)
        },
        error(msg) {
            toast.error(msg)
        },
        warning(msg) {
            toast.warning(msg)
        },
        info(msg) {
            toast(msg)
        },
    }
}
