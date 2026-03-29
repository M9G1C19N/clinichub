import { usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

export function usePermissions() {
    const page = usePage()

    const user = computed(() => page.props.auth.user)
    const role = computed(() => user.value?.role)
    const permissions = computed(() => user.value?.permissions ?? [])

    const can = (permission) => permissions.value.includes(permission)
    const hasRole = (r) => role.value === r
    const hasAnyRole = (...roles) => roles.includes(role.value)

    return { user, role, can, hasRole, hasAnyRole }
}
