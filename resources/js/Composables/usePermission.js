import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

export function usePermission() {
    const page = usePage();

    const userRoles = computed(
        () => page.props.auth?.roles ?? [],
    );
    const permissions = computed(() => page.props.auth?.permissions ?? []);

    function can(permission) {
        return userRoles.value.includes('superadmin')
            || permissions.value.includes(permission);
    }

    function canAny(...permissionNames) {
        return permissionNames.some(permission => can(permission));
    }

    return { can, canAny };
}
